<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HamaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_hamas_can_be_created_listed_updated_and_deleted(): void
    {
        $createResponse = $this->postJson('/api/hamas', [
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

        $this->getJson('/api/hamas')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson("/api/hamas/{$hamaId}")
            ->assertOk()
            ->assertJsonPath('data.cantidad_cuentas', 4)
            ->assertJsonCount(4, 'data.patron');

        $this->patchJson("/api/hamas/{$hamaId}", [
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

        $this->deleteJson("/api/hamas/{$hamaId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('hamas', ['id' => $hamaId]);
    }

    public function test_hama_fields_are_validated(): void
    {
        $this->postJson('/api/hamas', [
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
}
