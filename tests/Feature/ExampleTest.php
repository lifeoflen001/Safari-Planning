<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_redirects_the_root_to_the_planner(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('planner'));
    }

    public function test_the_application_redirects_guests_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_to_login_from_the_planner(): void
    {
        $response = $this->get('/planner');

        $response->assertRedirect(route('login'));
    }

    public function test_auth_login_is_the_canonical_guest_entry_point(): void
    {
        $this->get('/auth/login')->assertOk();
        $this->get('/login')->assertRedirect('/auth/login');
    }
}
