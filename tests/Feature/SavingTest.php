<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\MembershipCard;
use App\Models\DailyContribution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_membership_card_can_be_created_and_contributions_generated()
    {
        // Crée un utilisateur fictif pour simuler un membre
        $member = User::factory()->create();

        // Prépare les données du carnet
        $payload = [
            'member_id' => $member->id,
            'code' => 'CARD001',
            'currency' => 'CDF',
            'price' => 31000, // ex: 1000 x 31 jours
            'subscription_amount' => 1000,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'is_active' => true,
        ];

        // Appelle la route de création (ex: formulaire vente carnet)
        $response = $this->post('/membres/vendre-carnet', $payload);

        // Vérifie la redirection (si le contrôleur redirige après l'enregistrement)
        $response->assertStatus(302);

        // Vérifie que la carte a été créée dans la base
        $this->assertDatabaseHas('membership_cards', [
            'member_id' => $member->id,
            'contribution_date' => now(),
            'amount' => 10000,
        ]);

        // Vérifie que les contributions ont bien été créées
        $this->assertDatabaseHas('daily_contributions', [
            'amount' => 1000,
            'is_paid' => false, // ou true selon ta logique
        ]);

        // Optionnel : on peut vérifier combien de lignes ont été créées
        $this->assertEquals(31, DailyContribution::count());
    }
}