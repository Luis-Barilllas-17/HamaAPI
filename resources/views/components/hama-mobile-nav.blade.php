@props(['active' => 'projects'])

<nav class="mobile-bottom-nav" aria-label="Navegación móvil">
    <a class="{{ $active === 'create' ? 'active' : '' }}" href="/crear-hama"><span><x-hama-icon name="create" /></span><small>Crear</small></a>
    <a class="{{ $active === 'projects' ? 'active' : '' }}" href="/proyectos"><span><x-hama-icon name="projects" /></span><small>Proyectos</small></a>
    <a class="{{ $active === 'inventory' ? 'active' : '' }}" href="/inventario"><span><x-hama-icon name="inventory" /></span><small>Colores</small></a>
    <a class="{{ $active === 'meshiverse' ? 'active' : '' }}" href="/meshiverse"><span><x-hama-icon name="meshiverse" /></span><small>Meshi</small></a>
    <a class="{{ $active === 'settings' ? 'active' : '' }}" href="/configuracion"><span><x-hama-icon name="settings" /></span><small>Ajustes</small></a>
</nav>
