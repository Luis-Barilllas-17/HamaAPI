<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ExternalConnectionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless(
            app()->environment(['local', 'testing'])
                && in_array($request->ip(), ['127.0.0.1', '::1'], true),
            403,
            'La comprobación entre proyectos solo está disponible localmente.'
        );

        $data = $request->validate([
            'base_url' => ['required', 'url:http,https', 'max:2048'],
            'token' => ['required', 'string', 'max:8192'],
        ]);

        $baseUrl = $this->baseUrl($data['base_url']);
        $this->ensureLocalPartner($baseUrl);
        $token = preg_replace('/^Bearer\s+/i', '', trim($data['token'])) ?? trim($data['token']);

        try {
            [$response, $contract, $endpoint] = $this->probe($baseUrl, $token);
        } catch (ConnectionException) {
            return response()->json([
                'success' => false,
                'message' => "No fue posible contactar {$baseUrl}. Inicia el otro proyecto y revisa su puerto.",
            ], 502);
        }

        if ($response->unauthorized() || $response->forbidden()) {
            return response()->json([
                'success' => false,
                'message' => 'El otro proyecto respondió, pero rechazó el token. Debes usar un token generado por ese mismo proyecto.',
                'remote_status' => $response->status(),
            ], 422);
        }

        if (! $response->successful()) {
            $message = $response->notFound()
                ? 'El proyecto respondió, pero no expone una ruta de conexión compatible.'
                : "El otro proyecto respondió con HTTP {$response->status()}.";

            return response()->json([
                'success' => false,
                'message' => $message,
                'remote_status' => $response->status(),
            ], 422);
        }

        $payload = $response->json();

        if (! is_array($payload) || ! $this->isConnected($contract, $payload)) {
            return response()->json([
                'success' => false,
                'message' => "La ruta {$endpoint} respondió, pero no confirmó una conexión autenticada.",
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => data_get($payload, 'message', 'La conexión entre proyectos fue confirmada.'),
            'connection' => [
                'status' => 'connected',
                'provider' => $contract === 'hama'
                    ? data_get($payload, 'connection.provider', 'Proyecto Hama')
                    : data_get($payload, 'service', 'Proyecto externo'),
                'consumer' => config('app.name', 'ProyectoAPI'),
                'base_url' => $baseUrl,
                'endpoint' => $endpoint,
                'contract' => $contract,
            ],
            'sharing' => $contract === 'hama'
                ? data_get($payload, 'capabilities', [])
                : data_get($payload, 'sharing', []),
        ]);
    }

    /** @return array{Response, string, string} */
    private function probe(string $baseUrl, string $token): array
    {
        $hamaEndpoint = $baseUrl.'/api/v1/connections/handshake';
        $response = Http::acceptJson()
            ->asJson()
            ->withToken($token)
            ->connectTimeout(3)
            ->timeout(8)
            ->post($hamaEndpoint, [
                'project_name' => config('app.name', 'ProyectoAPI'),
            ]);

        if (! $response->notFound()) {
            return [$response, 'hama', $hamaEndpoint];
        }

        $partnerEndpoint = $baseUrl.'/api/v1/partner/connection';
        $response = Http::acceptJson()
            ->withToken($token)
            ->connectTimeout(3)
            ->timeout(8)
            ->get($partnerEndpoint);

        return [$response, 'partner', $partnerEndpoint];
    }

    /** @param array<string, mixed> $payload */
    private function isConnected(string $contract, array $payload): bool
    {
        return $contract === 'hama'
            ? data_get($payload, 'success') === true
                && data_get($payload, 'connection.status') === 'connected'
            : data_get($payload, 'status') === 'connected';
    }

    private function baseUrl(string $url): string
    {
        $url = rtrim($url, '/');

        return preg_replace(
            '#/api/v1/(?:connections/handshake|partner/connection)$#i',
            '',
            $url
        ) ?? $url;
    }

    private function ensureLocalPartner(string $baseUrl): void
    {
        $host = strtolower((string) parse_url($baseUrl, PHP_URL_HOST));
        $scheme = strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME));

        abort_unless(
            in_array($scheme, ['http', 'https'], true)
                && ($host === 'localhost'
                    || $host === '127.0.0.1'
                    || $host === '::1'
                    || str_ends_with($host, '.test')),
            422,
            'Por seguridad, la URL debe pertenecer a un proyecto local de Laragon.'
        );
    }
}
