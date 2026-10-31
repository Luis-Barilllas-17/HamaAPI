<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passport\ClientRepository;
use RuntimeException;

class SessionApiTokenController extends Controller
{
    public function store(Request $request, ClientRepository $clients): JsonResponse
    {
        try {
            $clients->personalAccessClient('users');
        } catch (RuntimeException) {
            $clients->createPersonalAccessGrantClient('Interfaz Hama Creation', 'users');
        }

        $result = $request->user()->createToken('Sesión del estudio Hama', [
            'hamas.read',
            'hamas.write',
            'hamas.delete',
        ]);

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $result->accessToken,
            'expires_in' => $result->expiresIn,
        ]);
    }
}
