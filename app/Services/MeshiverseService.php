<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class MeshiverseService
{
    /** @return array<string, mixed> */
    public function snapshot(): array
    {
        $baseUrl = rtrim((string) config('services.meshiverse.base_url'), '/');

        try {
            $statusResponse = Http::acceptJson()
                ->connectTimeout(4)
                ->timeout(10)
                ->get($baseUrl.'/api/v1/status');

            if (! $statusResponse->successful()) {
                return $this->offline($baseUrl, "MeshiVerse respondió con HTTP {$statusResponse->status()}.");
            }

            $dishResponse = Http::acceptJson()
                ->connectTimeout(4)
                ->timeout(12)
                ->get($baseUrl.'/api/v1/dish-of-the-day');
            $status = $statusResponse->json();

            return [
                'connected' => true,
                'service' => data_get($status, 'service', 'MeshiVerse'),
                'version' => data_get($status, 'version', 'v1'),
                'base_url' => $baseUrl,
                'dish_available' => $dishResponse->successful(),
                'dish' => $dishResponse->successful() ? data_get($dishResponse->json(), 'data') : null,
                'meta' => $dishResponse->successful() ? data_get($dishResponse->json(), 'meta', []) : [],
                'message' => $dishResponse->successful()
                    ? 'ProyectoAPI está recibiendo datos públicos de MeshiVerse.'
                    : "La API está activa, pero el platillo respondió con HTTP {$dishResponse->status()}.",
                'checked_at' => now()->toIso8601String(),
            ];
        } catch (ConnectionException) {
            return $this->offline($baseUrl, 'No fue posible contactar la API de MeshiVerse.');
        }
    }

    /** @return array<string, mixed> */
    private function offline(string $baseUrl, string $message): array
    {
        return [
            'connected' => false,
            'service' => 'MeshiVerse',
            'version' => null,
            'base_url' => $baseUrl,
            'dish_available' => false,
            'dish' => null,
            'meta' => [],
            'message' => $message,
            'checked_at' => now()->toIso8601String(),
        ];
    }
}
