<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Perfil seguro de Hama Creation">
    <title>Mi perfil | Hama Creation</title>
    <x-hama-favicon />
    <link rel="stylesheet" href="{{ asset('css/hama.css') }}?v=11">
</head>
<body>
    <div class="app-layout">
        <x-hama-sidebar active="profile" />

        <div class="content-area">
            <main class="main-content profile-page">
                <header class="page-header creative-header">
                    <div><span class="eyebrow">CUENTA Y SEGURIDAD</span><h1>Mi perfil</h1><p>Tu identidad dentro del estudio y el estado de protección de esta sesión.</p></div>
                </header>

                <section class="profile-hero">
                    <div class="profile-large-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</div>
                    <div><span class="section-kicker">{{ auth()->user()->isAdmin() ? 'ADMINISTRADOR' : 'CREADOR HAMA' }}</span><h2>{{ auth()->user()->name }}</h2><p>{{ auth()->user()->email }}</p></div>
                    <span class="session-badge"><i aria-hidden="true"></i> Sesión activa</span>
                </section>

                <section class="profile-grid">
                    <article class="profile-card"><span class="profile-card-icon mint"><x-hama-icon name="profile" /></span><h3>Datos de la cuenta</h3><dl><div><dt>Nombre</dt><dd>{{ auth()->user()->name }}</dd></div><div><dt>Correo</dt><dd>{{ auth()->user()->email }}</dd></div><div><dt>Tipo de acceso</dt><dd>{{ auth()->user()->isAdmin() ? 'Administrador' : 'Usuario' }}</dd></div><div><dt>Miembro desde</dt><dd>{{ auth()->user()->created_at?->translatedFormat('d M Y') }}</dd></div></dl></article>
                    <article class="profile-card"><span class="profile-card-icon magenta"><x-hama-icon name="settings" /></span><h3>Protección activa</h3><ul class="security-list"><li><i></i>Autenticación por sesión</li><li><i></i>Protección CSRF</li><li><i></i>API protegida por Passport</li><li><i></i>Límite de intentos de acceso</li></ul><a class="secondary-button" href="/configuracion">Abrir configuración</a></article>
                </section>

                <form class="profile-logout-card" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <div><span class="section-kicker">FINALIZAR SESIÓN</span><h3>¿Terminaste por hoy?</h3><p>Cierra la sesión para proteger el estudio en equipos compartidos.</p></div>
                    <button class="danger-button" type="submit"><x-hama-icon name="logout" /> Cerrar sesión</button>
                </form>
            </main>
        </div>
    </div>
</body>
</html>
