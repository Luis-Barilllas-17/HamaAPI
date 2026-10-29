<?php

namespace App\Http\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class ConnectionTokenController extends Controller
{
    public function store(
        Request $request,
        ClientRepository $clients,
        AccessTokenController $tokens,
        ResponseInterface $psrResponse,
    ): Response {
        abort_unless(
            app()->environment(['local', 'testing'])
                && in_array($request->ip(), ['127.0.0.1', '::1'], true),
            403,
            'La generación automática de tokens solo está disponible localmente.'
        );

        $data = $request->validate([
            'project_name' => ['required', 'string', 'max:100'],
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => [
                'required',
                'string',
                Rule::in(['hamas.read', 'hamas.write']),
            ],
        ]);

        $client = $clients->createClientCredentialsGrantClient(
            'Conexión automática · '.$data['project_name']
        );

        $oauthRequest = (new ServerRequest('POST', '/oauth/token'))->withParsedBody([
            'grant_type' => 'client_credentials',
            'client_id' => (string) $client->getKey(),
            'client_secret' => $client->plainSecret,
            'scope' => implode(' ', array_unique($data['scopes'])),
        ]);

        return $tokens->issueToken($oauthRequest, $psrResponse);
    }
}
