<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Acceso a Hama Creation">
    <title>Iniciar sesión | Hama Creation</title>
    <x-hama-favicon />
    <link rel="stylesheet" href="{{ asset('css/hama.css') }}?v=12">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-story" aria-label="Bienvenida a Hama Creation">
            <x-hama-brand />
            <div class="auth-story-copy">
                <h1>Tu mundo creativo, bajo control.</h1>
            </div>
            <div class="pukei-palette" aria-label="Paleta Pukei-Pukei">
                <i title="Verde Pukei"></i><i title="Turquesa"></i><i title="Crema"></i><i title="Magenta"></i><i title="Amarillo"></i>
            </div>
            <img class="auth-creature" src="{{ asset('images/hama-dragon-logo.png') }}" alt="Dragón Pukei-Pukei en pixel art">
        </section>

        <section class="auth-card" aria-labelledby="login-title">
            <div class="auth-card-heading">
                <span class="auth-lock" aria-hidden="true">◆</span>
                <span class="section-kicker">BIENVENIDO DE VUELTA</span>
                <h2 id="login-title">Iniciar sesión</h2>
                <p>Usa tus credenciales para acceder al estudio.</p>
            </div>

            <form method="POST" action="{{ route('login.store') }}" class="auth-form" novalidate>
                @csrf
                <label class="auth-field" for="email">
                    <span>Correo electrónico</span>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="tu@correo.com" autocomplete="email" required autofocus>
                    @error('email')<small>{{ $message }}</small>@enderror
                </label>

                <label class="auth-field" for="password">
                    <span>Contraseña</span>
                    <input id="password" name="password" type="password" placeholder="Tu contraseña" autocomplete="current-password" required>
                    @error('password')<small>{{ $message }}</small>@enderror
                </label>

                <label class="remember-field">
                    <input name="remember" type="checkbox" value="1">
                    <span>Recordarme en este equipo</span>
                </label>

                <button class="primary-button auth-submit" type="submit">Entrar al estudio <span aria-hidden="true">→</span></button>
            </form>

            <p class="auth-switch">¿Todavía no tienes acceso? <a href="{{ route('register') }}">Crear una cuenta</a></p>
        </section>
    </main>
</body>
</html>
