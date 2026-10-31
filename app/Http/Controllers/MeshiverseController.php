<?php

namespace App\Http\Controllers;

use App\Services\MeshiverseService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class MeshiverseController extends Controller
{
    public function index(MeshiverseService $meshiverse): View
    {
        return view('meshiverse.index', [
            'meshiverse' => $meshiverse->snapshot(),
        ]);
    }

    public function status(MeshiverseService $meshiverse): JsonResponse
    {
        return response()->json($meshiverse->snapshot());
    }
}
