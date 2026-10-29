<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hama;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HamaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'updated_since' => ['sometimes', 'nullable', 'date'],
        ]);

        $hamas = Hama::query()
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('nombre', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%");
                });
            })
            ->when($filters['updated_since'] ?? null, function ($query, string $updatedSince): void {
                $query->where('updated_at', '>', $updatedSince);
            })
            ->latest('updated_at')
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $hamas,
            'meta' => [
                'resource' => 'hamas',
                'api_version' => 'v1',
                'count' => $hamas->count(),
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate($this->rules(), $this->messages());
        $datos = $this->synchronizePatternCount($datos);
        $hama = Hama::create($datos);

        return response()->json([
            'success' => true,
            'message' => 'Hama creado correctamente',
            'data' => $hama,
        ], 201);
    }

    public function show(Hama $hama): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $hama,
        ]);
    }

    public function update(Request $request, Hama $hama): JsonResponse
    {
        $datos = $request->validate($this->rules(partial: true), $this->messages());
        $datos = $this->synchronizePatternCount($datos);
        $hama->update($datos);

        return response()->json([
            'success' => true,
            'message' => 'Hama actualizado correctamente',
            'data' => $hama->fresh(),
        ]);
    }

    public function destroy(Hama $hama): JsonResponse
    {
        $hama->delete();

        return response()->json([
            'success' => true,
            'message' => 'Hama eliminado correctamente',
        ]);
    }

    private function rules(bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return [
            'nombre' => [...$required, 'string', 'max:255'],
            'descripcion' => ['sometimes', 'nullable', 'string'],
            'ancho' => [...$required, 'integer', 'min:1', 'max:50'],
            'alto' => [...$required, 'integer', 'min:1', 'max:50'],
            'cantidad_cuentas' => [...$required, 'integer', 'min:1'],
            'color_principal' => [...$required, 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'patron' => ['sometimes', 'nullable', 'array', 'max:2500'],
            'patron.*.posicion' => ['required', 'integer', 'min:0', 'max:2499', 'distinct'],
            'patron.*.color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    private function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'min' => 'El campo :attribute debe ser como mínimo :min.',
            'max' => 'El campo :attribute no puede ser mayor que :max.',
            'color_principal.regex' => 'El color principal debe tener formato hexadecimal, por ejemplo #8B5CF6.',
            'patron.max' => 'El patrón no puede contener más de 2500 cuentas.',
            'patron.*.posicion.distinct' => 'Cada posición del patrón debe ser única.',
            'patron.*.color.regex' => 'Cada cuenta debe tener un color hexadecimal válido.',
        ];
    }

    private function synchronizePatternCount(array $datos): array
    {
        if (array_key_exists('patron', $datos) && is_array($datos['patron'])) {
            $datos['cantidad_cuentas'] = count($datos['patron']);
        }

        return $datos;
    }
}
