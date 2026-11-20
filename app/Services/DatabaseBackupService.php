<?php

namespace App\Services;

use PDO;
use RuntimeException;

/**
 * Sauvegarde complète de la base MySQL au format SQL, écrite entièrement en PHP
 * (aucun appel à mysqldump : fonctionne aussi en hébergement mutualisé).
 *
 *  - Photo cohérente : une transaction « consistent snapshot » fige la lecture au moment
 *    du clic, sans bloquer les opérations en cours (moteur InnoDB).
 *  - Au fil de l'eau : connexion dédiée en lecture non tamponnée, les lignes sont écrites
 *    une par une ; la mémoire reste faible même pour une grosse table.
 *  - Fuseau UTC pendant l'export (et dans le fichier) : les colonnes TIMESTAMP sont
 *    restaurées à l'identique, quel que soit le fuseau du serveur d'import.
 *  - Le fichier produit se réimporte tel quel : mysql nom_base < sauvegarde.sql
 */
class DatabaseBackupService
{
    /** Tables temporaires : structure seulement (leur contenu est inutile à une restauration). */
    public const STRUCTURE_ONLY = ['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches'];

    /** Nombre de lignes par instruction INSERT. */
    private const ROWS_PER_INSERT = 200;

    /**
     * Écrit la sauvegarde morceau par morceau via $write(string $sql).
     *
     * @return array{tables: int, rows: int} statistiques de l'export
     */
    public function dump(callable $write): array
    {
        $pdo = $this->connect();
        $database = config('database.connections.mysql.database');

        // Photo figée de toutes les tables au même instant
        $pdo->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $pdo->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY');

        $write($this->header($database));

        $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
        $totalRows = 0;

        foreach ($tables as $table) {
            $create = $pdo->query('SHOW CREATE TABLE ' . $this->ident($table))->fetch(PDO::FETCH_NUM)[1];

            $write("\n-- ------------------------------------------------------------\n");
            $write("-- Table {$this->ident($table)}\n");
            $write('DROP TABLE IF EXISTS ' . $this->ident($table) . ";\n");
            $write($create . ";\n");

            if (in_array($table, self::STRUCTURE_ONLY, true)) {
                $write("-- (contenu non exporté : table temporaire)\n");
                continue;
            }

            $totalRows += $this->dumpRows($pdo, $table, $write);
        }

        $pdo->exec('COMMIT');
        $write($this->footer());

        return ['tables' => count($tables), 'rows' => $totalRows];
    }

    /** Lignes d'une table, regroupées en INSERT de ROWS_PER_INSERT lignes. */
    private function dumpRows(PDO $pdo, string $table, callable $write): int
    {
        $statement = $pdo->query('SELECT * FROM ' . $this->ident($table));
        $columns = null;
        $buffer = [];
        $count = 0;

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $columns ??= implode(', ', array_map([$this, 'ident'], array_keys($row)));
            $buffer[] = '(' . implode(', ', array_map(fn ($value) => $this->value($pdo, $value), $row)) . ')';
            $count++;

            if (count($buffer) >= self::ROWS_PER_INSERT) {
                $write('INSERT INTO ' . $this->ident($table) . " ({$columns}) VALUES\n" . implode(",\n", $buffer) . ";\n");
                $buffer = [];
            }
        }

        if ($buffer !== []) {
            $write('INSERT INTO ' . $this->ident($table) . " ({$columns}) VALUES\n" . implode(",\n", $buffer) . ";\n");
        }

        $statement->closeCursor();

        return $count;
    }

    /** Valeur SQL : NULL, ou chaîne protégée par le pilote (MySQL convertit vers le type de la colonne). */
    private function value(PDO $pdo, $value): string
    {
        return $value === null ? 'NULL' : $pdo->quote((string) $value);
    }

    /** Nom de table ou de colonne entre accents graves. */
    private function ident(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    /** Connexion dédiée : lecture non tamponnée, UTC, utf8mb4. */
    private function connect(): PDO
    {
        $config = config('database.connections.mysql');

        if (($config['driver'] ?? null) !== 'mysql') {
            throw new RuntimeException('La sauvegarde ne fonctionne qu\'avec une base MySQL.');
        }

        $dsn = isset($config['unix_socket']) && $config['unix_socket'] !== ''
            ? "mysql:unix_socket={$config['unix_socket']};dbname={$config['database']};charset=utf8mb4"
            : "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";

        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
        ]);
        $pdo->exec("SET time_zone = '+00:00'");

        return $pdo;
    }

    private function header(string $database): string
    {
        $date = now()->format('d/m/Y H:i:s');
        $app = config('app.name');

        return <<<SQL
            -- Sauvegarde de la base « {$database} » ({$app})
            -- Générée le {$date} depuis l'application
            -- Restauration : mysql -u UTILISATEUR -p NOM_BASE < fichier.sql
            -- ATTENTION : contient toutes les données (clients, soldes, mots de passe chiffrés).

            SET NAMES utf8mb4;
            SET time_zone = '+00:00';
            SET FOREIGN_KEY_CHECKS = 0;
            SET UNIQUE_CHECKS = 0;
            SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
            SET AUTOCOMMIT = 0;
            START TRANSACTION;

            SQL;
    }

    private function footer(): string
    {
        return <<<SQL

            COMMIT;
            SET FOREIGN_KEY_CHECKS = 1;
            SET UNIQUE_CHECKS = 1;
            -- Fin de la sauvegarde

            SQL;
    }
}
