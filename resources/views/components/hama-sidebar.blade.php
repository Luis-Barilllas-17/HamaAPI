@props(['active' => 'projects'])

@php
    $currentUser = auth()->user();
    $nameParts = preg_split('/\s+/', trim($currentUser?->name ?? 'Usuario')) ?: ['U'];
    $initials = collect($nameParts)
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<header class="topbar">
    <div class="topbar-inner">
        <x-hama-brand />

        <nav class="topbar-nav" aria-label="Navegación principal">
            <section class="nav-group" aria-labelledby="studio-nav-label">
                <span id="studio-nav-label" class="nav-label">Estudio</span>
                <div class="nav-items">
                    <a class="nav-item {{ $active === 'create' ? 'active' : '' }}" href="/crear-hama" @if($active === 'create') aria-current="page" @endif>
                        <span class="nav-icon nav-icon-create"><x-hama-icon name="create" /></span><span>Crear Hama</span>
                    </a>
                    <a class="nav-item {{ $active === 'projects' ? 'active' : '' }}" href="/proyectos" @if($active === 'projects') aria-current="page" @endif>
                        <span class="nav-icon"><x-hama-icon name="projects" /></span><span>Inicio</span>
                    </a>
                    <a class="nav-item {{ $active === 'inventory' ? 'active' : '' }}" href="/inventario" @if($active === 'inventory') aria-current="page" @endif>
                        <span class="nav-icon"><x-hama-icon name="inventory" /></span><span>Colores</span>
                    </a>
                </div>
            </section>

            <section class="nav-group" aria-labelledby="inspiration-nav-label">
                <span id="inspiration-nav-label" class="nav-label">Inspiración</span>
                <div class="nav-items">
                    <a class="nav-item {{ $active === 'meshiverse' ? 'active' : '' }}" href="/meshiverse" @if($active === 'meshiverse') aria-current="page" @endif>
                        <span class="nav-icon nav-icon-meshi"><x-hama-icon name="meshiverse" /></span><span>MeshiVerse</span>
                    </a>
                </div>
            </section>

            <section class="nav-group profile-nav-group" aria-labelledby="profile-nav-label">
                <span id="profile-nav-label" class="nav-label">Perfil</span>
                <details class="profile-menu">
                    <summary class="profile-trigger {{ in_array($active, ['profile', 'settings'], true) ? 'active' : '' }}">
                        <span class="profile-avatar" aria-hidden="true">{{ $initials }}</span>
                        <span class="profile-copy"><strong>{{ $currentUser?->name }}</strong><small>{{ $currentUser?->isAdmin() ? 'Administrador' : 'Usuario' }}</small></span>
                        <x-hama-icon name="chevron" />
                    </summary>
                    <div class="profile-popover">
                        <div class="profile-popover-heading"><strong>{{ $currentUser?->name }}</strong><small>{{ $currentUser?->email }} · {{ $currentUser?->isAdmin() ? 'Administrador' : 'Usuario' }}</small></div>
                        <a class="profile-link {{ $active === 'profile' ? 'active' : '' }}" href="/perfil"><x-hama-icon name="profile" /> Mi perfil</a>
                        <a class="profile-link {{ $active === 'settings' ? 'active' : '' }}" href="/configuracion"><x-hama-icon name="settings" /> Configuración</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="profile-link logout-link" type="submit"><x-hama-icon name="logout" /> Cerrar sesión</button>
                        </form>
                    </div>
                </details>
            </section>
        </nav>
    </div>
</header>
