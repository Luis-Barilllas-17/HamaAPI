<?php

namespace Tests\Feature;

use App\Models\Hama;
use App\Models\InventoryColor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\TestCase;

class HamaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_hamas_can_be_created_listed_updated_and_deleted(): void
    {
        $this->authenticateClient();

        $createResponse = $this->postJson('/api/v1/hamas', [
            'nombre' => 'Estrella cósmica',
            'descripcion' => 'Patrón de estrella con cuentas amarillas',
            'ancho' => 50,
            'alto' => 50,
            'cantidad_cuentas' => 4,
            'color_principal' => '#F59E0B',
            'patron' => [
                ['posicion' => 1124, 'color' => '#F59E0B'],
                ['posicion' => 1125, 'color' => '#F59E0B'],
                ['posicion' => 1174, 'color' => '#F59E0B'],
                ['posicion' => 1175, 'color' => '#F59E0B'],
            ],
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nombre', 'Estrella cósmica')
            ->assertJsonPath('data.es_publico', true);

        $hamaId = $createResponse->json('data.id');

        $this->getJson('/api/v1/hamas')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.api_version', 'v1')
            ->assertJsonPath('meta.count', 1);

        $this->getJson("/api/v1/hamas/{$hamaId}")
            ->assertOk()
            ->assertJsonPath('data.cantidad_cuentas', 4)
            ->assertJsonCount(4, 'data.patron');

        $this->patchJson("/api/v1/hamas/{$hamaId}", [
            'nombre' => 'Estrella galáctica',
            'cantidad_cuentas' => 5,
            'patron' => [
                ['posicion' => 1124, 'color' => '#8B5CF6'],
                ['posicion' => 1125, 'color' => '#8B5CF6'],
                ['posicion' => 1174, 'color' => '#8B5CF6'],
                ['posicion' => 1175, 'color' => '#8B5CF6'],
                ['posicion' => 1225, 'color' => '#FFFFFF'],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nombre', 'Estrella galáctica')
            ->assertJsonPath('data.cantidad_cuentas', 5)
            ->assertJsonCount(5, 'data.patron');

        $this->deleteJson("/api/v1/hamas/{$hamaId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('hamas', ['id' => $hamaId]);
    }

    public function test_hama_fields_are_validated(): void
    {
        $this->authenticateClient();

        $this->postJson('/api/v1/hamas', [
            'nombre' => '',
            'ancho' => 51,
            'alto' => 0,
            'cantidad_cuentas' => 0,
            'color_principal' => 'morado',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nombre',
                'ancho',
                'alto',
                'cantidad_cuentas',
                'color_principal',
            ]);
    }

    public function test_hama_routes_reject_requests_without_a_bearer_token(): void
    {
        $this->getJson('/api/v1/hamas')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');

        $this->getJson('/api/v1/colors')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_external_clients_only_receive_public_hamas(): void
    {
        $public = Hama::create([
            'nombre' => 'Hama público',
            'descripcion' => 'Se puede compartir',
            'ancho' => 50,
            'alto' => 50,
            'cantidad_cuentas' => 1,
            'color_principal' => '#2B8A70',
            'es_publico' => true,
            'patron' => [['posicion' => 0, 'color' => '#2B8A70']],
        ]);
        $private = Hama::create([
            'nombre' => 'Hama privado',
            'descripcion' => 'No debe salir de la aplicación',
            'ancho' => 50,
            'alto' => 50,
            'cantidad_cuentas' => 1,
            'color_principal' => '#C238BA',
            'es_publico' => false,
            'patron' => [['posicion' => 1, 'color' => '#C238BA']],
        ]);

        $this->authenticateClient(['hamas.read']);

        $this->getJson('/api/v1/hamas')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $public->id)
            ->assertJsonPath('data.0.es_publico', true);

        $this->getJson("/api/v1/hamas/{$private->id}")->assertNotFound();
        $this->getJson('/api/v1/colors')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.color', '#2B8A70');
    }

    public function test_external_projects_share_public_hamas_colors_and_can_create_a_public_hama(): void
    {
        $this->authenticateClient(['hamas.read', 'hamas.write']);

        $created = $this->postJson('/api/v1/hamas', [
            'nombre' => 'Flor enviada por otra aplicación',
            'descripcion' => 'Creada mediante el contrato compartido',
            'ancho' => 50,
            'alto' => 50,
            'cantidad_cuentas' => 2,
            'color_principal' => '#D887AF',
            'es_publico' => false,
            'patron' => [
                ['posicion' => 1200, 'color' => '#D887AF'],
                ['posicion' => 1201, 'color' => '#E5C94F'],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.es_publico', true);

        $this->getJson('/api/v1/hamas')
            ->assertOk()
            ->assertJsonPath('meta.visibility', 'public_only')
            ->assertJsonPath('data.0.id', $created->json('data.id'));

        $this->getJson('/api/v1/colors')
            ->assertOk()
            ->assertJsonPath('meta.visibility', 'public_hamas_only')
            ->assertJsonFragment(['color' => '#D887AF'])
            ->assertJsonFragment(['color' => '#E5C94F']);
    }

    public function test_users_see_their_private_hamas_and_admins_see_everything(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_USER]);
        $other = User::factory()->create(['role' => User::ROLE_USER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $private = Hama::create([
            'user_id' => $owner->id,
            'nombre' => 'Privado del propietario',
            'descripcion' => null,
            'ancho' => 50,
            'alto' => 50,
            'cantidad_cuentas' => 1,
            'color_principal' => '#C238BA',
            'es_publico' => false,
            'patron' => [['posicion' => 1, 'color' => '#C238BA']],
        ]);
        $otherHama = Hama::create([
            'user_id' => $other->id,
            'nombre' => 'Proyecto de otra persona',
            'descripcion' => null,
            'ancho' => 50,
            'alto' => 50,
            'cantidad_cuentas' => 1,
            'color_principal' => '#2B8A70',
            'es_publico' => true,
            'patron' => [['posicion' => 2, 'color' => '#2B8A70']],
        ]);

        Passport::actingAs($owner, ['hamas.read']);
        $this->getJson('/api/v1/hamas')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $private->id);
        $this->getJson("/api/v1/hamas/{$otherHama->id}")->assertNotFound();

        Passport::actingAs($admin, ['hamas.read']);
        $this->getJson('/api/v1/hamas')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_scopes_limit_what_an_api_client_can_do(): void
    {
        $this->authenticateClient(['hamas.read']);

        $hama = Hama::create([
            'nombre' => 'Solo lectura',
            'descripcion' => null,
            'ancho' => 50,
            'alto' => 50,
            'cantidad_cuentas' => 1,
            'color_principal' => '#8B5CF6',
            'patron' => [['posicion' => 0, 'color' => '#8B5CF6']],
        ]);

        $this->getJson('/api/v1/hamas')->assertOk();
        $this->postJson('/api/v1/hamas', [])->assertForbidden();
        $this->deleteJson("/api/v1/hamas/{$hama->id}")->assertForbidden();
    }

    public function test_colors_endpoint_shares_the_colors_used_by_hamas(): void
    {
        $this->authenticateClient(['hamas.read']);

        Hama::create([
            'nombre' => 'Corazón de prueba',
            'descripcion' => 'Diseño con dos colores',
            'ancho' => 50,
            'alto' => 50,
            'cantidad_cuentas' => 3,
            'color_principal' => '#EF4444',
            'patron' => [
                ['posicion' => 0, 'color' => '#ef4444'],
                ['posicion' => 1, 'color' => '#EF4444'],
                ['posicion' => 2, 'color' => '#8B5CF6'],
            ],
        ]);

        $this->getJson('/api/v1/colors')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.resource', 'hama_colors')
            ->assertJsonPath('meta.count', 2)
            ->assertJsonPath('meta.total_cuentas', 3)
            ->assertJsonPath('data.0.color', '#EF4444')
            ->assertJsonPath('data.0.cantidad_cuentas', 2)
            ->assertJsonPath('data.0.cantidad_hamas', 1)
            ->assertJsonPath('data.1.color', '#8B5CF6');
    }

    public function test_a_user_can_manage_their_personal_color_inventory(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['hamas.read', 'hamas.write', 'hamas.delete']);

        $createResponse = $this->postJson('/api/v1/inventory-colors', [
            'nombre' => 'Verde bosque',
            'hex_color' => '#48b978',
            'cantidad' => 850,
        ])
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Verde bosque')
            ->assertJsonPath('data.hex_color', '#48B978')
            ->assertJsonPath('data.cantidad', 850);

        $colorId = $createResponse->json('data.id');

        $this->getJson('/api/v1/inventory-colors')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total_cuentas', 850);

        $this->patchJson("/api/v1/inventory-colors/{$colorId}", [
            'nombre' => 'Verde Pukei',
            'cantidad' => 1200,
        ])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Verde Pukei')
            ->assertJsonPath('data.cantidad', 1200);

        $this->deleteJson("/api/v1/inventory-colors/{$colorId}")
            ->assertOk();

        $this->assertDatabaseMissing('inventory_colors', ['id' => $colorId]);
    }

    public function test_color_inventory_is_private_to_each_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $color = InventoryColor::create([
            'user_id' => $owner->id,
            'nombre' => 'Turquesa',
            'hex_color' => '#258D8D',
            'cantidad' => 400,
        ]);

        Passport::actingAs($other, ['hamas.read', 'hamas.write', 'hamas.delete']);
        $this->getJson('/api/v1/inventory-colors')
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->patchJson("/api/v1/inventory-colors/{$color->id}", ['cantidad' => 999])
            ->assertNotFound();

    }

    public function test_external_clients_cannot_read_a_personal_color_inventory(): void
    {
        $this->authenticateClient(['hamas.read']);

        $this->getJson('/api/v1/inventory-colors')
            ->assertForbidden()
            ->assertJsonPath('message', 'El inventario personal requiere un token de usuario.');
    }

    public function test_legacy_endpoint_remains_available(): void
    {
        $this->authenticateClient(['hamas.read']);

        $this->getJson('/api/hamas')
            ->assertOk()
            ->assertJsonPath('meta.api_version', 'v1');
    }

    public function test_index_filters_help_other_apps_synchronize_hamas(): void
    {
        $this->authenticateClient(['hamas.read']);

        $oldHama = Hama::create([
            'nombre' => 'Corazón clásico',
            'descripcion' => 'Diseño antiguo',
            'ancho' => 50,
            'alto' => 50,
            'cantidad_cuentas' => 1,
            'color_principal' => '#EF4444',
            'patron' => [['posicion' => 10, 'color' => '#EF4444']],
        ]);
        $oldHama->timestamps = false;
        $oldHama->forceFill([
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ])->save();

        Hama::create([
            'nombre' => 'Estrella reciente',
            'descripcion' => 'Diseño para compartir',
            'ancho' => 50,
            'alto' => 50,
            'cantidad_cuentas' => 1,
            'color_principal' => '#F59E0B',
            'patron' => [['posicion' => 20, 'color' => '#F59E0B']],
        ]);

        $this->getJson('/api/v1/hamas?search=estrella')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Estrella reciente');

        $this->getJson('/api/v1/hamas?updated_since='.urlencode(now()->subDay()->toIso8601String()))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Estrella reciente');
    }

    public function test_connection_handshake_reports_shared_capabilities(): void
    {
        $scopes = ['hamas.read', 'hamas.write'];
        $this->authenticateClient($scopes);

        Hama::create([
            'nombre' => 'Proyecto compartido',
            'descripcion' => 'Visible para otra API',
            'ancho' => 50,
            'alto' => 50,
            'cantidad_cuentas' => 1,
            'color_principal' => '#8B5CF6',
            'patron' => [['posicion' => 0, 'color' => '#8B5CF6']],
        ]);

        $this->withToken($this->fakeJwt($scopes))
            ->postJson('/api/v1/connections/handshake', [
                'project_name' => 'Proyecto Dinosaurio',
            ])
            ->assertOk()
            ->assertJsonPath('connection.status', 'connected')
            ->assertJsonPath('connection.consumer', 'Proyecto Dinosaurio')
            ->assertJsonPath('capabilities.0.allowed', true)
            ->assertJsonPath('capabilities.1.allowed', true)
            ->assertJsonPath('capabilities.2.allowed', true)
            ->assertJsonPath('shared_data.saved_hamas.records', 1)
            ->assertJsonPath('shared_data.saved_hamas.visibility', 'public_only')
            ->assertJsonPath('shared_data.colors.endpoint', '/api/v1/colors')
            ->assertJsonPath('shared_data.colors.source', 'public_hamas')
            ->assertJsonPath('shared_data.hama_creation.board', '50x50')
            ->assertJsonPath('shared_data.hama_creation.visibility', 'public');
    }

    public function test_connection_handshake_detects_a_read_only_token(): void
    {
        $scopes = ['hamas.read'];
        $this->authenticateClient($scopes);

        $this->withToken($this->fakeJwt($scopes))
            ->postJson('/api/v1/connections/handshake', [
                'project_name' => 'Proyecto lector',
            ])
            ->assertOk()
            ->assertJsonPath('capabilities.0.allowed', true)
            ->assertJsonPath('capabilities.1.allowed', true)
            ->assertJsonPath('capabilities.2.allowed', false)
            ->assertJsonPath('shared_data.hama_creation.available', false);
    }

    public function test_connection_handshake_rejects_missing_tokens(): void
    {
        $this->postJson('/api/v1/connections/handshake', [
            'project_name' => 'Proyecto sin token',
        ])->assertUnauthorized();
    }

    public function test_connection_center_generates_a_passport_token_automatically(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $tokenResponse = $this->postJson('/conexiones/token', [
            'project_name' => 'Proyecto Dinosaurio',
            'scopes' => ['hamas.read', 'hamas.write'],
        ])
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure(['access_token', 'expires_in']);

        $this->withToken($tokenResponse->json('access_token'))
            ->postJson('/api/v1/connections/handshake', [
                'project_name' => 'Proyecto Dinosaurio',
            ])
            ->assertOk()
            ->assertJsonPath('connection.status', 'connected')
            ->assertJsonPath('capabilities.0.allowed', true)
            ->assertJsonPath('capabilities.1.allowed', true)
            ->assertJsonPath('capabilities.2.allowed', true);
    }

    public function test_the_studio_generates_a_personal_token_for_private_projects(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $private = Hama::create([
            'user_id' => $user->id,
            'nombre' => 'Diseño privado del estudio',
            'descripcion' => null,
            'ancho' => 50,
            'alto' => 50,
            'cantidad_cuentas' => 1,
            'color_principal' => '#C238BA',
            'es_publico' => false,
            'patron' => [['posicion' => 8, 'color' => '#C238BA']],
        ]);

        $tokenResponse = $this->actingAs($user)
            ->postJson('/session/api-token')
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure(['access_token', 'expires_in']);

        $this->withToken($tokenResponse->json('access_token'))
            ->getJson('/api/v1/hamas')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $private->id)
            ->assertJsonPath('data.0.es_publico', false);
    }

    public function test_meshiverse_section_displays_public_api_data_without_asking_for_a_token(): void
    {
        $this->actingAs(User::factory()->create());

        Http::fake([
            'https://meshiverse.fataldesk.lat/api/v1/status' => Http::response([
                'status' => 'ok',
                'service' => 'MeshiVerse',
                'version' => 'v1',
            ]),
            'https://meshiverse.fataldesk.lat/api/v1/dish-of-the-day' => Http::response([
                'data' => [
                    'title' => 'Senshi al vapor',
                    'subtitle' => 'Platillo de prueba',
                    'description' => 'Descripción servida por la API.',
                    'ingredients' => ['Harina', 'Agua'],
                    'steps' => ['Mezclar', 'Cocinar'],
                    'links' => ['web' => 'https://meshiverse.fataldesk.lat/recetas/senshi-al-vapor'],
                ],
                'meta' => ['selection' => 'dish-of-the-day'],
            ]),
        ]);

        $this->get('/meshiverse')
            ->assertOk()
            ->assertSee('Senshi al vapor')
            ->assertSee('Una integración que se siente natural')
            ->assertDontSee('external-token');

        Http::assertSentCount(2);
    }

    public function test_meshiverse_status_reports_if_data_is_being_exchanged(): void
    {
        $this->actingAs(User::factory()->create());

        Http::fake([
            'https://meshiverse.fataldesk.lat/api/v1/status' => Http::response([
                'status' => 'ok',
                'service' => 'MeshiVerse',
                'version' => 'v1',
            ]),
            'https://meshiverse.fataldesk.lat/api/v1/dish-of-the-day' => Http::response([
                'data' => ['title' => 'Senshi al vapor'],
                'meta' => ['selection' => 'dish-of-the-day'],
            ]),
        ]);

        $this->getJson('/meshiverse/status')
            ->assertOk()
            ->assertJsonPath('connected', true)
            ->assertJsonPath('dish_available', true)
            ->assertJsonPath('service', 'MeshiVerse')
            ->assertJsonPath('dish.title', 'Senshi al vapor');
    }

    public function test_visual_workspace_sections_render_successfully(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->get('/proyectos')
            ->assertOk()
            ->assertSee('Tus creaciones')
            ->assertDontSee('Diseños guardados')
            ->assertSee('/crear-hama', false);

        $this->get('/crear-hama')
            ->assertOk()
            ->assertSee('Crea algo que se sienta tuyo')
            ->assertSee('hama-canvas', false);

        $this->get('/inventario')
            ->assertOk()
            ->assertSee('Inventario de colores')
            ->assertSee('Escanear foto')
            ->assertSee('colors-grid', false);

        $this->get('/configuracion')
            ->assertOk()
            ->assertSee('API y conexión entre proyectos')
            ->assertSee('/configuracion/api', false);

        $this->get('/configuracion/api')
            ->assertOk()
            ->assertSee('Conexiones API')
            ->assertDontSee('Probar conexión');

        $this->get('/conexiones')
            ->assertRedirect('/configuracion/api');
    }

    /** @param array<int, string> $scopes */
    private function authenticateClient(array $scopes = ['hamas.read', 'hamas.write', 'hamas.delete']): void
    {
        $client = app(ClientRepository::class)
            ->createClientCredentialsGrantClient('Cliente de pruebas');

        Passport::actingAsClient($client, $scopes);
    }

    /** @param array<int, string> $scopes */
    private function fakeJwt(array $scopes): string
    {
        $encode = static fn (array $value): string => rtrim(strtr(
            base64_encode((string) json_encode($value)),
            '+/',
            '-_'
        ), '=');

        return $encode(['alg' => 'RS256', 'typ' => 'JWT']).'.'
            .$encode(['scopes' => $scopes, 'exp' => now()->addHour()->timestamp]).'.signature';
    }
}
