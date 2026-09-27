<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_active_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::STAF_KEUANGAN,
        ]);
        $user->assignRole(UserRole::STAF_KEUANGAN->value);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_inactive_users_cannot_authenticate(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
            'role' => UserRole::STAF_KEUANGAN,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_deactivated_user_session_is_invalidated_by_middleware(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::STAF_KEUANGAN,
        ]);

        $this->actingAs($user);

        // Deactivate user
        $user->update(['is_active' => false]);

        // Attempt to access protected route
        $response = $this->get('/dashboard');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_staf_umum_is_redirected_to_aset_module(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::STAF_UMUM,
        ]);
        $user->assignRole(UserRole::STAF_UMUM->value);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('aset.index'));
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
