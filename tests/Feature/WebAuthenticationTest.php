<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_from_the_studio(): void
    {
        $this->get('/proyectos')->assertRedirect('/login');
        $this->get('/crear-hama')->assertRedirect('/login');
        $this->get('/configuracion/api')->assertRedirect('/login');
    }

    public function test_login_shows_the_clean_creative_welcome(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Tu mundo creativo, bajo control.')
            ->assertSee('hama-dragon-logo.png', false)
            ->assertDontSee('ESTUDIO PROTEGIDO')
            ->assertDontSee('Entra para diseñar patrones')
            ->assertDontSee('Sesión protegida con Laravel');
    }

    public function test_a_person_can_register_and_enter_the_studio(): void
    {
        $response = $this->post('/registro', [
            'name' => 'Creadora Hama',
            'email' => 'creadora@example.com',
            'password' => 'HamaSegura1',
            'password_confirmation' => 'HamaSegura1',
        ]);

        $response->assertRedirect('/proyectos');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Creadora Hama',
            'email' => 'creadora@example.com',
            'role' => User::ROLE_USER,
        ]);
    }

    public function test_a_registered_person_can_login_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'artista@example.com',
            'password' => 'HamaSegura1',
        ]);

        $this->post('/login', [
            'email' => 'artista@example.com',
            'password' => 'HamaSegura1',
        ])->assertRedirect('/proyectos');

        $this->assertAuthenticatedAs($user);
        $this->get('/proyectos')
            ->assertOk()
            ->assertSee('Estudio')
            ->assertSee('Inspiración')
            ->assertSee('Perfil');

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->create([
            'email' => 'artista@example.com',
            'password' => 'HamaSegura1',
        ]);

        $this->from('/login')->post('/login', [
            'email' => 'artista@example.com',
            'password' => 'incorrecta',
        ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_only_admins_can_open_api_configuration(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($user);

        $this->get('/configuracion')
            ->assertOk()
            ->assertSee('Preferencias del estudio')
            ->assertDontSee('Abrir conexiones API');
        $this->get('/configuracion/api')->assertForbidden();
        $this->postJson('/conexiones/token', [])->assertForbidden();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin);

        $this->get('/configuracion')
            ->assertOk()
            ->assertSee('Abrir conexiones API');
        $this->get('/configuracion/api')->assertOk();
    }

    public function test_the_demo_seeder_creates_admin_and_normal_user_accounts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@proyectoapi.local',
            'role' => User::ROLE_ADMIN,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'usuario@proyectoapi.local',
            'role' => User::ROLE_USER,
        ]);
    }
}
