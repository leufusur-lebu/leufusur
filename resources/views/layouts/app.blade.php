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

                    <div class="mt-5 flex items-center gap-3">
                        <a href="https://www.facebook.com/profile.php?id=61596128236063" target="_blank" rel="noopener" aria-label="Facebook"
                            class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-teal-500 hover:text-teal-600">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                <path d="M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 .955.042 1.468.103a8.68 8.68 0 0 1 1.141.195v3.325a8.623 8.623 0 0 0-.653-.036 26.805 26.805 0 0 0-.733-.009c-.707 0-1.259.096-1.675.309a1.686 1.686 0 0 0-.679.622c-.258.42-.374.995-.374 1.752v1.297h3.919l-.386 2.103-.287 1.564h-3.246v8.245C19.396 23.238 24 18.179 24 12.044c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.628 3.874 10.35 9.101 11.647Z" />
                            </svg>
                        </a>
                        <a href="https://wa.me/56962471305" target="_blank" rel="noopener" aria-label="WhatsApp"
                            class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-teal-500 hover:text-teal-600">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z" />
                            </svg>
                        </a>
                    </div>
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
                        <li><a href="mailto:leufusurspa@icloud.com" class="transition hover:text-teal-600">leufusurspa@icloud.com</a></li>
                        <li><a href="tel:+56962471305" class="transition hover:text-teal-600">+56 9 6247 1305</a></li>
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
