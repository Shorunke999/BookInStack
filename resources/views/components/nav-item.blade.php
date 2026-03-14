{{--
    Component: nav-item
    Props: $route (named route), $label, $icon
--}}
<a href="{{ route($route) }}"
   class="nav-item {{ request()->routeIs($route) ? 'active' : '' }}">
    @include('components.icon', ['name' => $icon])
    {{ $label }}
</a>
