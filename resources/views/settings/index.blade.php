<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Configuración de Hama Creation">
    <title>Configuración | Hama Creation</title>
    <x-hama-favicon />
    <link rel="stylesheet" href="{{ asset('css/hama.css') }}?v=11">
    @if (auth()->user()->isAdmin())
        <script src="{{ asset('js/passport-access.js') }}?v=3" defer></script>
    @endif
</head>
<body>
    <div class="app-layout">
        <x-hama-sidebar active="settings" />

        <div class="content-area">
            <main class="main-content settings-page">
                <header class="page-header creative-header">
                    <div><span class="eyebrow">ESPACIO DE TRABAJO</span><h1>Configuración</h1><p>Preferencias, acceso e información de tu cuenta reunidos en un solo lugar.</p></div>
                </header>

                @if (auth()->user()->isAdmin())
                    <section class="settings-feature-card">
                        <div class="settings-feature-icon" aria-hidden="true">↔</div>
                        <div><span class="section-kicker">LARAVEL PASSPORT</span><h2>API y conexión entre proyectos</h2><p>Genera tokens, comprueba el intercambio con MeshiVerse y revisa exactamente qué datos públicos comparte ProyectoAPI.</p></div>
                        <a class="primary-button" href="/configuracion/api">Abrir conexiones API</a>
                    </section>

                    <section class="settings-grid">
                        <article class="settings-card"><span class="settings-card-icon lavender" aria-hidden="true">◇</span><h3>Acceso de esta sesión</h3><p>Agrega o cambia el Bearer token que utiliza la interfaz para consumir el CRUD protegido.</p><button id="open-passport-token" class="secondary-button" type="button">Administrar token <small id="passport-token-status" class="token-status">Sin token</small></button></article>
                        <article class="settings-card"><span class="settings-card-icon peach" aria-hidden="true">P</span><h3>Colección de Postman</h3><p>La colección incluye token OAuth2, handshake, proyectos públicos, colores y todo el CRUD.</p><code>postman/ProyectoAPI-Hamas.postman_collection.json</code></article>
                        <article class="settings-card"><span class="settings-card-icon mint" aria-hidden="true">M</span><h3>Base de datos</h3><p>Los proyectos, patrones 50×50 y colores se almacenan mediante Eloquent.</p><dl><div><dt>Motor</dt><dd>MySQL</dd></div><div><dt>Base</dt><dd>ProyectoAPI</dd></div></dl></article>
                        <article class="settings-card"><span class="settings-card-icon sky" aria-hidden="true">13</span><h3>Aplicación</h3><p>El administrador controla la integración y los accesos externos.</p><dl><div><dt>Framework</dt><dd>Laravel 13</dd></div><div><dt>API</dt><dd>v1</dd></div></dl></article>
                    </section>
                @else
                    <section class="settings-feature-card account-feature-card">
                        <div class="settings-feature-icon" aria-hidden="true">◎</div>
                        <div><span class="section-kicker">TU CUENTA</span><h2>Preferencias del estudio</h2><p>Tu acceso está protegido y puedes decidir si cada nuevo Hama será público o privado.</p></div>
                        <a class="primary-button" href="/perfil">Ver mi perfil</a>
                    </section>

                    <section class="settings-grid">
                        <article class="settings-card"><span class="settings-card-icon mint" aria-hidden="true">●</span><h3>Proyectos privados</h3><p>Solo aparecerán dentro de tu estudio. Nunca se enviarán a otros proyectos conectados.</p></article>
                        <article class="settings-card"><span class="settings-card-icon peach" aria-hidden="true">◉</span><h3>Proyectos públicos</h3><p>Podrán consultarse mediante la conexión administrada y aparecerán en el listado compartido.</p></article>
                    </section>
                @endif
            </main>
        </div>
    </div>

    @if (auth()->user()->isAdmin())
        <x-passport-dialog />
    @endif
</body>
</html>
