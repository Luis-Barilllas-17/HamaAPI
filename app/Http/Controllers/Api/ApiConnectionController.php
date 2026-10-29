<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hama;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ApiConnectionController extends Controller
{
    public function handshake(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_name' => ['required', 'string', 'max:100'],
        ]);

        $claims = $this->tokenClaims($request->bearerToken());
        $scopes = collect($claims['scopes'] ?? [])
            ->filter(fn (mixed $scope): bool => is_string($scope))
            ->values()
            ->all();

        $canRead = $this->hasScope($scopes, 'hamas.read');
        $canWrite = $this->hasScope($scopes, 'hamas.write');

        return response()->json([
            'success' => true,
            'message' => 'Conexión autenticada correctamente con ProyectoAPI.',
            'connection' => [
                'status' => 'connected',
                'provider' => config('app.name', 'ProyectoAPI'),
                'consumer' => $data['project_name'],
                'protocol' => 'OAuth 2.0 Client Credentials',
                'api_version' => 'v1',
                'checked_at' => now()->toIso8601String(),
            ],
            'token' => [
                'valid' => true,
                'scopes' => $scopes,
                'expires_at' => isset($claims['exp'])
                    ? Carbon::createFromTimestamp((int) $claims['exp'])->toIso8601String()
                    : null,
            ],
            'capabilities' => [
                [
                    'key' => 'hamas.list',
                    'label' => 'Consultar proyectos Hama guardados',
                    'allowed' => $canRead,
                    'scope' => 'hamas.read',
                    'method' => 'GET',
                    'endpoint' => '/api/v1/hamas',
                ],
                [
                    'key' => 'hamas.create',
                    'label' => 'Crear un Hama con su patrón 50 × 50',
                    'allowed' => $canWrite,
                    'scope' => 'hamas.write',
                    'method' => 'POST',
                    'endpoint' => '/api/v1/hamas',
                ],
            ],
            'shared_data' => [
                'saved_hamas' => [
                    'available' => $canRead,
                    'records' => $canRead ? Hama::count() : null,
                    'fields' => [
                        'id',
                        'nombre',
                        'descripcion',
                        'ancho',
                        'alto',
                        'cantidad_cuentas',
                        'color_principal',
                        'patron',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'hama_creation' => [
                    'available' => $canWrite,
                    'board' => '50x50',
                    'accepted_fields' => [
                        'nombre',
                        'descripcion',
                        'ancho',
                        'alto',
                        'cantidad_cuentas',
                        'color_principal',
                        'patron',
                    ],
                ],
            ],
        ]);
    }

    /** @param array<int, string> $scopes */
    private function hasScope(array $scopes, string $required): bool
    {
        return in_array('*', $scopes, true) || in_array($required, $scopes, true);
    }

    /** @return array<string, mixed> */
    private function tokenClaims(?string $token): array
    {
        if (! $token) {
            return [];
        }

        $segments = explode('.', $token);

        if (count($segments) !== 3) {
            return [];
        }

        $payload = strtr($segments[1], '-_', '+/');
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
        $decoded = base64_decode($payload, true);

        if ($decoded === false) {
            return [];
        }

        $claims = json_decode($decoded, true);

        return is_array($claims) ? $claims : [];
    }
}
