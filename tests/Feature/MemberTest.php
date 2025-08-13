<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MemberTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_member_can_be_registered()
    {
        $response = $this->post('/members', [
            'name' => 'Jean',
            'postnom' => 'Makuta',
            'email' => 'jean@example.com',
            'phone' => '0991234567',
        ]);

        $response->assertStatus(302); // redirection après création
        $this->assertDatabaseHas('users', [
            'email' => 'jean@example.com',
        ]);
    }
}
