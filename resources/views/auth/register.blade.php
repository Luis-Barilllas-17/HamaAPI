<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Crear acceso seguro a Hama Creation">
    <title>Crear cuenta | Hama Creation</title>
    <x-hama-favicon />
    <link rel="stylesheet" href="{{ asset('css/hama.css') }}?v=10">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-story register-story" aria-label="Crear cuenta en Hama Creation">
            <x-hama-brand />
            <div class="auth-story-copy">
                <span class="eyebrow">NUEVO PERFIL</span>
                <h1>Crea, guarda y comparte tu imaginación.</h1>
                <p>Tu cuenta mantiene las herramientas del estudio detrás de una sesión segura, mientras Passport protege los endpoints.</p>
            </div>
            <div class="pukei-palette" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div>
            <img class="auth-creature" src="{{ asset('images/hama-dragon-logo.png') }}" alt="Dragón Pukei-Pukei en pixel art">
        </section>

        <section class="auth-card" aria-labelledby="register-title">
            <div class="auth-card-heading">
                <span class="auth-lock" aria-hidden="true">＋</span>
                <span class="section-kicker">ACCESO PERSONAL</span>
                <h2 id="register-title">Crear cuenta</h2>
                <p>Configura tus credenciales para entrar al estudio.</p>
            </div>

            <form method="POST" action="{{ route('register.store') }}" class="auth-form" novalidate>
                @csrf
                <label class="auth-field" for="name"><span>Nombre</span><input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Cómo quieres que te llamemos" autocomplete="name" required autofocus>@error('name')<small>{{ $message }}</small>@enderror</label>
                <label class="auth-field" for="email"><span>Correo electrónico</span><input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="tu@correo.com" autocomplete="email" required>@error('email')<small>{{ $message }}</small>@enderror</label>
                <label class="auth-field" for="password"><span>Contraseña</span><input id="password" name="password" type="password" placeholder="Mínimo 8 caracteres" autocomplete="new-password" required>@error('password')<small>{{ $message }}</small>@enderror</label>
                <label class="auth-field" for="password_confirmation"><span>Confirmar contraseña</span><input id="password_confirmation" name="password_confirmation" type="password" placeholder="Repite la contraseña" autocomplete="new-password" required></label>
                <p class="password-hint">Usa mayúsculas, minúsculas y al menos un número.</p>
                <button class="primary-button auth-submit" type="submit">Crear mi acceso <span aria-hidden="true">→</span></button>
            </form>

            <p class="auth-switch">¿Ya tienes una cuenta? <a href="{{ route('login') }}">Iniciar sesión</a></p>
        </section>
    </main>
</body>
</html>
