# Script d'initialisation du module comptable

# À exécuter après déploiement

## 1. Exécuter les migrations

php artisan migrate --force

## 2. Peupler le plan comptable IMF

php artisan db:seed --class=ChartOfAccountsSeeder

## 3. Vérifier l'installation

php artisan tinker

> > > \App\Models\Compte::count()
> > > \App\Models\Compte::classes()->get()
> > > \App\Models\Compte::where('code', '571')->first()->getHierarchyPath()

## 4. Test rapide du service comptable

> > > $service = app(\App\Services\AccountingService::class);
> > > // Le service est prêt à être utilisé dans les composants métier

## Notes de déploiement

- Migration production-safe: N'écrase pas les données existantes
- Seeder utilise updateOrCreate: Peut être relancé sans risque
- AccountingService est injecté via container Laravel
