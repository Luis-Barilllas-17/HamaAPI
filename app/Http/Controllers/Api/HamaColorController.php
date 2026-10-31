<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hama;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HamaColorController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $hamas = Hama::query()
            ->visibleToApiConsumer($request->user('api'))
            ->get(['id', 'nombre', 'color_principal', 'patron']);
        $colors = [];

        foreach ($hamas as $hama) {
            $colorsInHama = [];

            foreach ($hama->patron ?? [] as $bead) {
                $color = $this->normalizeColor($bead['color'] ?? null);

                if ($color === null) {
                    continue;
                }

                $colors[$color] ??= [
                    'color' => $color,
                    'cantidad_cuentas' => 0,
                    'hamas' => [],
                ];
                $colors[$color]['cantidad_cuentas']++;
                $colorsInHama[$color] = true;
            }

            $principal = $this->normalizeColor($hama->color_principal);

            if ($principal !== null) {
                $colors[$principal] ??= [
                    'color' => $principal,
                    'cantidad_cuentas' => 0,
                    'hamas' => [],
                ];
                $colorsInHama[$principal] = true;
            }

            foreach (array_keys($colorsInHama) as $color) {
                $colors[$color]['hamas'][] = [
                    'id' => $hama->id,
                    'nombre' => $hama->nombre,
                ];
            }
        }

        $data = collect($colors)
            ->map(function (array $color): array {
                $color['cantidad_hamas'] = count($color['hamas']);

                return $color;
            })
            ->sortByDesc('cantidad_cuentas')
            ->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'resource' => 'hama_colors',
                'api_version' => 'v1',
                'count' => $data->count(),
                'total_cuentas' => $data->sum('cantidad_cuentas'),
                'visibility' => $request->user('api') ? 'personal' : 'public_hamas_only',
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function normalizeColor(mixed $color): ?string
    {
        if (! is_string($color) || preg_match('/^#[0-9A-Fa-f]{6}$/', $color) !== 1) {
            return null;
        }

        return strtoupper($color);
    }
}
