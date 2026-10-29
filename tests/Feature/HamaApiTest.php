<?php

namespace Tests\Feature;

use App\Models\Hama;
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
            ->assertJsonPath('data.nombre', 'Estrella cósmica');

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
            ->assertJsonPath('shared_data.saved_hamas.records', 1)
            ->assertJsonPath('shared_data.hama_creation.board', '50x50');
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
            ->assertJsonPath('capabilities.1.allowed', false)
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
            ->assertJsonPath('capabilities.1.allowed', true);
    }

    public function test_connection_center_detects_a_partner_api_without_browser_cors(): void
    {
        Http::fake([
            'http://127.0.0.1:8001/api/v1/connections/handshake' => Http::response([], 404),
            'http://127.0.0.1:8001/api/v1/partner/connection' => Http::response([
                'status' => 'connected',
                'service' => 'MeshiVerse',
                'message' => 'Conexión autenticada correctamente.',
                'sharing' => [
                    'dish_of_the_day' => ['label' => 'Platillo del día'],
                ],
            ]),
        ]);

        $this->postJson('/conexiones/probar-externa', [
            'base_url' => 'http://127.0.0.1:8001',
            'token' => 'token-generado-por-meshiverse',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('connection.status', 'connected')
            ->assertJsonPath('connection.provider', 'MeshiVerse')
            ->assertJsonPath('connection.contract', 'partner');

        Http::assertSentCount(2);
    }

    public function test_external_connection_explains_when_partner_rejects_its_token(): void
    {
        Http::fake([
            'http://127.0.0.1:8001/api/v1/connections/handshake' => Http::response([], 404),
            'http://127.0.0.1:8001/api/v1/partner/connection' => Http::response([
                'message' => 'Unauthenticated.',
            ], 401),
        ]);

        $this->postJson('/conexiones/probar-externa', [
            'base_url' => 'http://127.0.0.1:8001',
            'token' => 'token-equivocado',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('remote_status', 401)
            ->assertJsonPath(
                'message',
                'El otro proyecto respondió, pero rechazó el token. Debes usar un token generado por ese mismo proyecto.'
            );
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
