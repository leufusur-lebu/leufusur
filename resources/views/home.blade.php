@extends('layouts.app')

@section('content')

    {{-- ===================== Inicio / Hero ===================== --}}
    <section id="inicio" class="relative overflow-hidden bg-slate-900 pt-32 pb-28 sm:pt-40 sm:pb-36">
        {{-- Fondos decorativos --}}
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="absolute -top-24 -left-24 h-72 w-72 rounded-full bg-teal-500/20 blur-3xl"></div>
            <div class="absolute top-1/4 -right-24 h-80 w-80 rounded-full bg-cyan-500/20 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-6xl px-4 text-center sm:px-6">
            <span
                class="inline-flex items-center gap-2 rounded-full border border-teal-400/30 bg-teal-400/10 px-4 py-1.5 text-sm font-medium text-teal-300">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                Proveedor del Estado &middot; Mercado Público
            </span>

            <h1 class="mx-auto mt-6 max-w-3xl text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                Tecnología, seguridad y conectividad para tu organización
            </h1>

            <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-slate-300">
                En Leufu Sur SpA instalamos cámaras de seguridad, desarrollamos sitios web, implementamos redes de datos
                y proveemos productos tecnológicos para empresas, instituciones y hogares.
            </p>

            <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
                <a href="#servicios"
                    class="w-full rounded-xl bg-gradient-to-r from-teal-500 to-cyan-500 px-7 py-3.5 text-sm font-semibold text-white shadow-lg shadow-teal-500/20 transition hover:from-teal-400 hover:to-cyan-400 sm:w-auto">
                    Ver servicios
                </a>
                <a href="#contacto"
                    class="w-full rounded-xl border border-white/20 bg-white/5 px-7 py-3.5 text-sm font-semibold text-white transition hover:bg-white/10 sm:w-auto">
                    Contáctanos
                </a>
            </div>
        </div>

        {{-- Onda decorativa (río / sur) hacia la siguiente sección --}}
        <div class="absolute inset-x-0 bottom-0 leading-[0]" aria-hidden="true">
            <svg viewBox="0 0 1440 120" preserveAspectRatio="none" fill="#ffffff" class="h-[60px] w-full sm:h-[100px]">
                <path d="M0,64 C240,120 480,8 720,40 C960,72 1200,120 1440,72 L1440,120 L0,120 Z" />
            </svg>
        </div>
    </section>

    {{-- ===================== Nosotros ===================== --}}
    <section id="nosotros" class="bg-white py-20 sm:py-28">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-wider text-teal-600">Nosotros</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Quiénes somos</h2>
                <p class="mt-5 text-lg leading-relaxed text-slate-600">
                    Leufu Sur SpA es una empresa chilena de servicios informáticos con base en el sur de Chile.
                    Acompañamos a empresas, instituciones públicas y personas con soluciones de seguridad electrónica,
                    desarrollo web, redes y tecnología.
                </p>
            </div>

            {{-- Misión / Visión --}}
            <div class="mt-14 grid gap-6 md:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-8">
                    <h3 class="text-lg font-semibold text-slate-900">Misión</h3>
                    <p class="mt-3 leading-relaxed text-slate-600">
                        Entregar soluciones tecnológicas confiables y a la medida, que aporten seguridad, eficiencia y
                        crecimiento a nuestros clientes.
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-8">
                    <h3 class="text-lg font-semibold text-slate-900">Visión</h3>
                    <p class="mt-3 leading-relaxed text-slate-600">
                        Ser un referente en el sur de Chile en servicios informáticos y seguridad electrónica,
                        reconocidos por nuestra cercanía, calidad y cumplimiento.
                    </p>
                </div>
            </div>

            {{-- Valores --}}
            <div class="mt-6 grid gap-6 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 p-6">
                    <h4 class="font-semibold text-slate-900">Cercanía</h4>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Atención directa y soporte real, hablando claro y sin tecnicismos.
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-200 p-6">
                    <h4 class="font-semibold text-slate-900">Calidad</h4>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Equipos y prácticas profesionales en cada instalación y proyecto.
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-200 p-6">
                    <h4 class="font-semibold text-slate-900">Cumplimiento</h4>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Respetamos plazos y compromisos, también como proveedores del Estado.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== Servicios ===================== --}}
    <section id="servicios" class="bg-slate-50 py-20 sm:py-28">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-wider text-teal-600">Servicios</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Lo que hacemos</h2>
                <p class="mt-5 text-lg leading-relaxed text-slate-600">
                    Soluciones integrales de tecnología y seguridad, desde la instalación hasta el soporte.
                </p>
            </div>

            <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                {{-- Cámaras de seguridad --}}
                <div class="group rounded-2xl border border-slate-200 bg-white p-7 transition hover:-translate-y-1 hover:border-teal-200 hover:shadow-lg hover:shadow-teal-500/5">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-teal-600 to-cyan-500 text-white">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                    </div>
                    <h3 class="mt-5 text-lg font-semibold text-slate-900">Cámaras de seguridad</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Instalación y mantención de sistemas de videovigilancia (CCTV), control de acceso y alarmas para
                        hogares, empresas e instituciones.
                    </p>
                </div>

                {{-- Desarrollo web --}}
                <div class="group rounded-2xl border border-slate-200 bg-white p-7 transition hover:-translate-y-1 hover:border-teal-200 hover:shadow-lg hover:shadow-teal-500/5">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-teal-600 to-cyan-500 text-white">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" />
                        </svg>
                    </div>
                    <h3 class="mt-5 text-lg font-semibold text-slate-900">Desarrollo web</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Diseño y desarrollo de sitios y aplicaciones web a medida: modernos, rápidos, seguros y fáciles
                        de administrar.
                    </p>
                </div>

                {{-- Redes de datos --}}
                <div class="group rounded-2xl border border-slate-200 bg-white p-7 transition hover:-translate-y-1 hover:border-teal-200 hover:shadow-lg hover:shadow-teal-500/5">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-teal-600 to-cyan-500 text-white">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z" />
                        </svg>
                    </div>
                    <h3 class="mt-5 text-lg font-semibold text-slate-900">Redes de datos</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Diseño e implementación de redes cableadas e inalámbricas, cableado estructurado y conectividad
                        estable para tu operación.
                    </p>
                </div>

                {{-- Productos tecnológicos --}}
                <div class="group rounded-2xl border border-slate-200 bg-white p-7 transition hover:-translate-y-1 hover:border-teal-200 hover:shadow-lg hover:shadow-teal-500/5">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-teal-600 to-cyan-500 text-white">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z" />
                        </svg>
                    </div>
                    <h3 class="mt-5 text-lg font-semibold text-slate-900">Productos tecnológicos</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Venta de equipos, insumos y soluciones tecnológicas para tu hogar, empresa o institución, con
                        asesoría incluida.
                    </p>
                </div>

                {{-- Mercado Público --}}
                <div class="group rounded-2xl border border-slate-200 bg-white p-7 transition hover:-translate-y-1 hover:border-teal-200 hover:shadow-lg hover:shadow-teal-500/5">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-teal-600 to-cyan-500 text-white">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" />
                        </svg>
                    </div>
                    <h3 class="mt-5 text-lg font-semibold text-slate-900">Proveedor del Estado</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Atendemos licitaciones y órdenes de compra del sector público a través de Mercado Público
                        (ChileCompra).
                    </p>
                </div>

                {{-- Soporte y mantención --}}
                <div class="group rounded-2xl border border-slate-200 bg-white p-7 transition hover:-translate-y-1 hover:border-teal-200 hover:shadow-lg hover:shadow-teal-500/5">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-teal-600 to-cyan-500 text-white">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26" />
                        </svg>
                    </div>
                    <h3 class="mt-5 text-lg font-semibold text-slate-900">Soporte y mantención TI</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Soporte técnico, mantención preventiva y asistencia para tus equipos, sistemas e
                        infraestructura.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== Contacto ===================== --}}
    <section id="contacto" class="bg-white py-20 sm:py-28">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="overflow-hidden rounded-3xl bg-slate-900 px-6 py-14 sm:px-12">
                <div class="grid items-center gap-12 lg:grid-cols-2">
                    {{-- Texto + acciones --}}
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wider text-teal-400">Contacto</p>
                        <h2 class="mt-2 text-3xl font-bold tracking-tight text-white sm:text-4xl">
                            Conversemos sobre tu proyecto
                        </h2>
                        <p class="mt-5 text-lg leading-relaxed text-slate-300">
                            Cuéntanos qué necesitas y te enviaremos una propuesta sin compromiso. Atendemos a clientes
                            particulares, empresas e instituciones públicas.
                        </p>
                        <div class="mt-8 flex flex-col gap-4 sm:flex-row">
                            <a href="mailto:contacto@leufusur.cl"
                                class="rounded-xl bg-gradient-to-r from-teal-500 to-cyan-500 px-7 py-3.5 text-center text-sm font-semibold text-white shadow-lg shadow-teal-500/20 transition hover:from-teal-400 hover:to-cyan-400">
                                Escríbenos
                            </a>
                            <a href="tel:+56912345678"
                                class="rounded-xl border border-white/20 bg-white/5 px-7 py-3.5 text-center text-sm font-semibold text-white transition hover:bg-white/10">
                                Llamar
                            </a>
                        </div>
                    </div>

                    {{-- Datos de contacto --}}
                    <div class="grid gap-4 sm:grid-cols-2">
                        {{-- Correo --}}
                        <a href="mailto:contacto@leufusur.cl"
                            class="rounded-2xl border border-white/10 bg-white/5 p-5 transition hover:bg-white/10">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6 text-teal-400">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                            <p class="mt-3 text-xs font-medium uppercase tracking-wide text-slate-400">Correo</p>
                            <p class="mt-1 text-sm font-medium text-white">leufusurspa@icloud.com</p>
                        </a>

                        {{-- Teléfono --}}
                        <a href="tel:+56912345678"
                            class="rounded-2xl border border-white/10 bg-white/5 p-5 transition hover:bg-white/10">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6 text-teal-400">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                            </svg>
                            <p class="mt-3 text-xs font-medium uppercase tracking-wide text-slate-400">Teléfono</p>
                            <p class="mt-1 text-sm font-medium text-white">+56 9 6247 1305</p>
                        </a>

                        {{-- Ubicación --}}
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6 text-teal-400">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                            </svg>
                            <p class="mt-3 text-xs font-medium uppercase tracking-wide text-slate-400">Ubicación</p>
                            <p class="mt-1 text-sm font-medium text-white">Lebu - Biobío - Chile</p>
                        </div>

                        {{-- Mercado Público --}}
                        <a href="https://www.mercadopublico.cl" target="_blank" rel="noopener"
                            class="rounded-2xl border border-white/10 bg-white/5 p-5 transition hover:bg-white/10">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6 text-teal-400">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" />
                            </svg>
                            <p class="mt-3 text-xs font-medium uppercase tracking-wide text-slate-400">Compra pública</p>
                            <p class="mt-1 text-sm font-medium text-white">Mercado Público</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
