<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryColor;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryColorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $colors = $user->inventoryColors()
            ->orderByDesc('cantidad')
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $colors,
            'meta' => [
                'resource' => 'inventory_colors',
                'count' => $colors->count(),
                'total_cuentas' => $colors->sum('cantidad'),
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $data = $request->validate($this->rules($user), $this->messages());
        $data['hex_color'] = strtoupper($data['hex_color']);
        $color = $user->inventoryColors()->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Color agregado al inventario',
            'data' => $color,
        ], 201);
    }

    public function update(Request $request, InventoryColor $inventoryColor): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $this->ensureOwner($user, $inventoryColor);
        $data = $request->validate($this->rules($user, $inventoryColor, partial: true), $this->messages());

        if (array_key_exists('hex_color', $data)) {
            $data['hex_color'] = strtoupper($data['hex_color']);
        }

        $inventoryColor->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Color actualizado correctamente',
            'data' => $inventoryColor->fresh(),
        ]);
    }

    public function destroy(Request $request, InventoryColor $inventoryColor): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $this->ensureOwner($user, $inventoryColor);
        $inventoryColor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Color eliminado del inventario',
        ]);
    }

    private function rules(User $user, ?InventoryColor $inventoryColor = null, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return [
            'nombre' => [...$required, 'string', 'max:80'],
            'hex_color' => [
                ...$required,
                'string',
                'regex:/^#[0-9A-Fa-f]{6}$/',
                Rule::unique('inventory_colors', 'hex_color')
                    ->where('user_id', $user->getKey())
                    ->ignore($inventoryColor?->getKey()),
            ],
            'cantidad' => [...$required, 'integer', 'min:0', 'max:10000000'],
        ];
    }

    private function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'hex_color.regex' => 'El color debe usar formato hexadecimal, por ejemplo #8B78D7.',
            'hex_color.unique' => 'Ese color ya existe en tu inventario. Puedes editar su cantidad.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'La cantidad no puede ser negativa.',
            'cantidad.max' => 'La cantidad indicada es demasiado grande.',
        ];
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user('api');
        abort_unless($user instanceof User, 403, 'El inventario personal requiere un token de usuario.');

        return $user;
    }

    private function ensureOwner(User $user, InventoryColor $inventoryColor): void
    {
        abort_unless((int) $inventoryColor->user_id === (int) $user->getKey(), 404);
    }
}
