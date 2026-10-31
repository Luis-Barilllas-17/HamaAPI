@props(['compact' => false])

<a class="brand" href="/proyectos" aria-label="Ir a Proyectos Hama">
    <span class="brand-logo-shell" aria-hidden="true">
        <img class="brand-logo" src="{{ asset('images/hama-dragon-logo.png') }}" alt="">
    </span>
    <span>
        <strong>HAMA CREATION</strong>
        @unless($compact)
            <small>Estudio creativo</small>
        @endunless
    </span>
</a>
