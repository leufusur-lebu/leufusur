<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Leufu Sur SpA — Servicios informáticos, seguridad y redes')</title>
    <meta name="description"
        content="@yield('description', 'Leufu Sur SpA: instalación de cámaras de seguridad, desarrollo web, redes de datos y venta de productos tecnológicos. Proveedores del Estado a través de Mercado Público.')">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-white font-sans text-slate-700 antialiased">

    {{-- ===================== Navegación ===================== --}}
    <header class="fixed inset-x-0 top-0 z-50 border-b border-slate-200/70 bg-white/90 backdrop-blur">
        <nav class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
            {{-- Logo --}}
            <a href="#inicio" class="flex items-center gap-2.5">
                <span
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-teal-600 to-cyan-500 text-white shadow-sm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" class="h-5 w-5">
                        <path d="M2 7c2.5 0 2.5 2 5 2s2.5-2 5-2 2.5 2 5 2 2.5-2 5-2" />
                        <path d="M2 12c2.5 0 2.5 2 5 2s2.5-2 5-2 2.5 2 5 2 2.5-2 5-2" />
                        <path d="M2 17c2.5 0 2.5 2 5 2s2.5-2 5-2 2.5 2 5 2 2.5-2 5-2" />
                    </svg>
                </span>
                <span class="text-lg font-semibold tracking-tight text-slate-900">
                    Leufu Sur <span class="font-normal text-slate-400">SpA</span>
                </span>
            </a>

            {{-- Enlaces escritorio --}}
            <div class="hidden items-center gap-8 md:flex">
                <a href="#nosotros" class="text-sm font-medium text-slate-600 transition hover:text-teal-600">Nosotros</a>
                <a href="#servicios" class="text-sm font-medium text-slate-600 transition hover:text-teal-600">Servicios</a>
                <a href="#contacto" class="text-sm font-medium text-slate-600 transition hover:text-teal-600">Contacto</a>
                <a href="#contacto"
                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">
                    Cotizar
                </a>
            </div>

            {{-- Menú móvil (sin JavaScript, con <details>) --}}
            <details class="relative md:hidden">
                <summary
                    class="flex h-9 w-9 cursor-pointer list-none items-center justify-center rounded-lg border border-slate-200 text-slate-700 [&::-webkit-details-marker]:hidden">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5">
                        <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                </summary>
                <div class="absolute right-0 mt-2 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white py-2 shadow-lg">
                    <a href="#nosotros" class="block px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50">Nosotros</a>
                    <a href="#servicios" class="block px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50">Servicios</a>
                    <a href="#contacto" class="block px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50">Contacto</a>
                </div>
            </details>
        </nav>
    </header>

    {{-- ===================== Contenido ===================== --}}
    <main>
        @yield('content')
    </main>

    {{-- ===================== Pie de página ===================== --}}
    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
            <div class="grid gap-8 md:grid-cols-3">
                {{-- Marca --}}
                <div>
                    <div class="flex items-center gap-2.5">
                        <span
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-teal-600 to-cyan-500 text-white">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" class="h-5 w-5">
                                <path d="M2 7c2.5 0 2.5 2 5 2s2.5-2 5-2 2.5 2 5 2 2.5-2 5-2" />
                                <path d="M2 12c2.5 0 2.5 2 5 2s2.5-2 5-2 2.5 2 5 2 2.5-2 5-2" />
                                <path d="M2 17c2.5 0 2.5 2 5 2s2.5-2 5-2 2.5 2 5 2 2.5-2 5-2" />
                            </svg>
                        </span>
                        <span class="text-lg font-semibold tracking-tight text-slate-900">Leufu Sur SpA</span>
                    </div>
                    <p class="mt-4 max-w-xs text-sm leading-relaxed text-slate-500">
                        Servicios informáticos, seguridad electrónica y tecnología para empresas, instituciones y hogares
                        en el sur de Chile.
                    </p>
                </div>

                {{-- Enlaces --}}
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Navegación</h3>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li><a href="#inicio" class="text-slate-500 transition hover:text-teal-600">Inicio</a></li>
                        <li><a href="#nosotros" class="text-slate-500 transition hover:text-teal-600">Nosotros</a></li>
                        <li><a href="#servicios" class="text-slate-500 transition hover:text-teal-600">Servicios</a></li>
                        <li><a href="#contacto" class="text-slate-500 transition hover:text-teal-600">Contacto</a></li>
                    </ul>
                </div>

                {{-- Contacto --}}
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Contacto</h3>
                    <ul class="mt-4 space-y-2.5 text-sm text-slate-500">
                        <li><a href="mailto:contacto@leufusur.cl" class="transition hover:text-teal-600">contacto@leufusur.cl</a></li>
                        <li><a href="tel:+56912345678" class="transition hover:text-teal-600">+56 9 1234 5678</a></li>
                        <li>Sur de Chile</li>
                    </ul>
                </div>
            </div>

            <div class="mt-10 flex flex-col items-center justify-between gap-3 border-t border-slate-200 pt-6 sm:flex-row">
                <p class="text-sm text-slate-400">&copy; {{ date('Y') }} Leufu Sur SpA. Todos los derechos reservados.</p>
                <p class="text-sm font-medium text-slate-500">Proveedor del Estado &middot; Mercado Público</p>
            </div>
        </div>
    </footer>

</body>

</html>
