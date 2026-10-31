<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Inspiración recibida desde la API pública de MeshiVerse">
    <title>MeshiVerse | Hama Creation</title>
    <x-hama-favicon />
    <link rel="stylesheet" href="{{ asset('css/hama.css') }}?v=11">
    <link rel="stylesheet" href="{{ asset('css/meshiverse.css') }}?v=2">
    <link rel="stylesheet" href="{{ asset('css/soft-pages.css') }}?v=1">
</head>
<body>
    @php($dish = $meshiverse['dish'] ?? null)
    <div class="app-layout">
        <x-hama-sidebar active="meshiverse" />

        <div class="content-area">
            <main class="main-content meshiverse-page">
                <header class="meshi-header">
                    <div><span class="eyebrow">INSPIRACIÓN INVITADA</span><h1>MeshiVerse</h1><p>Una pausa creativa servida por otra API. Entra, descubre el platillo del día y deja que sus colores o su historia inspiren tu próximo Hama.</p></div>
                    <span class="meshi-status {{ $meshiverse['connected'] ? 'online' : 'offline' }}"><i aria-hidden="true"></i>{{ $meshiverse['connected'] ? 'Datos en vivo' : 'Sin conexión' }}</span>
                </header>

                @if ($meshiverse['connected'] && $dish)
                    <section class="meshi-source-bar" aria-label="Origen de los datos">
                        <span><b>API EXTERNA</b> {{ $meshiverse['service'] }} · {{ $meshiverse['version'] }}</span>
                        <span>Actualizado {{ \Illuminate\Support\Carbon::parse($meshiverse['checked_at'])->translatedFormat('d M, H:i') }}</span>
                    </section>

                    <article class="dish-hero">
                        <div class="dish-image-wrap">
                            <img src="{{ data_get($dish, 'image_url') }}" alt="{{ data_get($dish, 'title', 'Platillo de MeshiVerse') }}">
                            <span class="dish-origin">{{ data_get($dish, 'source_label', 'MeshiVerse') }}</span>
                        </div>
                        <div class="dish-copy">
                            <span class="dish-kicker">PLATILLO DEL DÍA</span>
                            <h2>{{ data_get($dish, 'title') }}</h2>
                            <p class="dish-subtitle">{{ data_get($dish, 'subtitle') }}</p>
                            <p class="dish-description">{{ data_get($dish, 'description') }}</p>

                            <div class="dish-metrics">
                                <div><span>Tiempo</span><strong>{{ data_get($dish, 'time.total_minutes', '—') }} min</strong></div>
                                <div><span>Dificultad</span><strong>{{ ucfirst(data_get($dish, 'difficulty', '—')) }}</strong></div>
                                <div><span>Porciones</span><strong>{{ data_get($dish, 'servings', '—') }}</strong></div>
                            </div>

                            @if (data_get($dish, 'anime.name'))
                                <div class="anime-chip" style="--anime-from: {{ data_get($dish, 'anime.colors.from', '#8B78D7') }}; --anime-to: {{ data_get($dish, 'anime.colors.to', '#B99FE8') }}">
                                    <span>{{ data_get($dish, 'anime.icon', '✦') }}</span>
                                    <div><small>Inspirado en</small><strong>{{ data_get($dish, 'anime.name') }}</strong></div>
                                </div>
                            @endif

                            <div class="meshi-actions">
                                <a class="primary-button meshi-link" href="{{ data_get($dish, 'links.web', $meshiverse['base_url']) }}" target="_blank" rel="noopener noreferrer">Ver receta ↗</a>
                                <a class="quiet-link" href="/crear-hama">Crear un Hama inspirado</a>
                            </div>
                        </div>
                    </article>

                    <section class="recipe-details">
                        <article>
                            <div class="detail-heading"><span>01</span><div><small>PREPARACIÓN</small><h3>Ingredientes</h3></div></div>
                            <ul class="ingredient-list">@foreach (data_get($dish, 'ingredients', []) as $ingredient)<li><i aria-hidden="true"></i>{{ $ingredient }}</li>@endforeach</ul>
                        </article>
                        <article>
                            <div class="detail-heading"><span>02</span><div><small>PASO A PASO</small><h3>Cómo prepararlo</h3></div></div>
                            <ol class="steps-list">@foreach (data_get($dish, 'steps', []) as $step)<li><span>{{ $loop->iteration }}</span><p>{{ $step }}</p></li>@endforeach</ol>
                        </article>
                    </section>

                    <aside class="api-data-note"><span aria-hidden="true">↔</span><div><strong>Una integración que se siente natural</strong><p>ProyectoAPI consultó automáticamente <code>/api/v1/dish-of-the-day</code>. No se pidió ningún token al visitante.</p></div></aside>
                @elseif ($meshiverse['connected'])
                    <section class="meshi-empty-state"><span aria-hidden="true">M</span><h2>La API está conectada</h2><p>{{ $meshiverse['message'] }}</p><a class="primary-button" href="/meshiverse">Actualizar datos</a></section>
                @else
                    <section class="meshi-empty-state error"><span aria-hidden="true">!</span><h2>MeshiVerse está tomando un descanso</h2><p>{{ $meshiverse['message'] }}</p><a class="primary-button" href="/meshiverse">Intentar nuevamente</a></section>
                @endif
            </main>
        </div>
    </div>

</body>
</html>
