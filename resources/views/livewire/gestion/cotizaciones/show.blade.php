<?php

use App\Enums\EstadoCotizacion;
use App\Models\Cotizacion;
use Illuminate\Support\Facades\Session;

use function Livewire\Volt\{layout, mount, state};

layout('layouts.gestion');

state(['cotizacion' => null]);

mount(function (Cotizacion $cotizacion) {
    $this->cotizacion = $cotizacion->load('cliente', 'lineas');
});

$marcarEnviada = function () {
    try {
        $this->cotizacion->marcarEnviada();
        $this->cotizacion->refresh();
        Session::flash('status', 'Cotización marcada como enviada.');
    } catch (RuntimeException $e) {
        Session::flash('error', $e->getMessage());
    }
};

$marcarAprobada = function () {
    try {
        $this->cotizacion->marcarAprobada();
        $this->cotizacion->refresh();
        Session::flash('status', 'Cotización marcada como aprobada.');
    } catch (RuntimeException $e) {
        Session::flash('error', $e->getMessage());
    }
};

$marcarRechazada = function () {
    try {
        $this->cotizacion->marcarRechazada();
        $this->cotizacion->refresh();
        Session::flash('status', 'Cotización marcada como rechazada.');
    } catch (RuntimeException $e) {
        Session::flash('error', $e->getMessage());
    }
};

$duplicar = function () {
    $nueva = $this->cotizacion->duplicar();

    Session::flash('status', 'Cotización duplicada como '.$nueva->numero_cotizacion.'.');

    $this->redirectRoute('gestion.cotizaciones.edit', $nueva, navigate: true);
};

$eliminar = function () {
    $numero = $this->cotizacion->numero_cotizacion;
    $this->cotizacion->delete();

    Session::flash('status', 'Cotización '.$numero.' eliminada.');

    $this->redirectRoute('gestion.cotizaciones.index', navigate: true);
};

?>

<div>
    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            @if (session('error'))
                <div class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
            @endif

            {{-- Encabezado y acciones --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-lg font-semibold text-gray-900">{{ $cotizacion->numero_cotizacion }}</h1>
                            <span
                                @class([
                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                    'bg-gray-100 text-gray-700' => $cotizacion->estado === EstadoCotizacion::Borrador,
                                    'bg-blue-50 text-blue-700' => $cotizacion->estado === EstadoCotizacion::Enviada,
                                    'bg-green-50 text-green-700' => $cotizacion->estado === EstadoCotizacion::Aprobada,
                                    'bg-red-50 text-red-700' => $cotizacion->estado === EstadoCotizacion::Rechazada,
                                ])>
                                {{ $cotizacion->estado->label() }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">{{ $cotizacion->descripcion }}</p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @if ($cotizacion->estado === EstadoCotizacion::Borrador)
                            <a href="{{ route('gestion.cotizaciones.edit', $cotizacion) }}" wire:navigate
                                class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                Editar
                            </a>
                            <button wire:click="marcarEnviada" wire:confirm="¿Marcar esta cotización como enviada? Ya no podrás editarla."
                                class="rounded-md bg-teal-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-500">
                                Marcar como enviada
                            </button>
                        @elseif ($cotizacion->estado === EstadoCotizacion::Enviada)
                            <button wire:click="marcarRechazada" wire:confirm="¿Marcar esta cotización como rechazada?"
                                class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-red-700 shadow-sm ring-1 ring-inset ring-red-300 hover:bg-red-50">
                                Marcar como rechazada
                            </button>
                            <button wire:click="marcarAprobada" wire:confirm="¿Marcar esta cotización como aprobada?"
                                class="rounded-md bg-teal-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-500">
                                Marcar como aprobada
                            </button>
                        @endif

                        <button wire:click="duplicar"
                            class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            Duplicar
                        </button>

                        <a href="{{ route('gestion.cotizaciones.pdf', $cotizacion) }}" target="_blank"
                            class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            Exportar PDF
                        </a>

                        <button wire:click="eliminar"
                            wire:confirm="¿Eliminar la cotización {{ $cotizacion->numero_cotizacion }}? Esta acción no se puede deshacer."
                            class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-red-700 shadow-sm ring-1 ring-inset ring-red-300 hover:bg-red-50">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>

            {{-- Cliente --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h2 class="text-sm font-semibold text-gray-900">Cliente</h2>
                <dl class="mt-3 grid gap-x-6 gap-y-2 sm:grid-cols-2 text-sm">
                    <div><dt class="text-gray-500">Nombre</dt><dd class="text-gray-900">{{ $cotizacion->cliente->nombre }}</dd></div>
                    @if ($cotizacion->cliente->nombre_empresa)
                        <div><dt class="text-gray-500">Empresa</dt><dd class="text-gray-900">{{ $cotizacion->cliente->nombre_empresa }}</dd></div>
                    @endif
                    @if ($cotizacion->cliente->giro)
                        <div><dt class="text-gray-500">Giro</dt><dd class="text-gray-900">{{ $cotizacion->cliente->giro }}</dd></div>
                    @endif
                    @if ($cotizacion->cliente->email)
                        <div><dt class="text-gray-500">Email</dt><dd class="text-gray-900">{{ $cotizacion->cliente->email }}</dd></div>
                    @endif
                    @if ($cotizacion->cliente->telefono)
                        <div><dt class="text-gray-500">Teléfono</dt><dd class="text-gray-900">{{ $cotizacion->cliente->telefono }}</dd></div>
                    @endif
                    <div><dt class="text-gray-500">RUT</dt><dd class="text-gray-900">{{ $cotizacion->cliente->rut_run }}</dd></div>
                    @if ($cotizacion->cliente->direccion)
                        <div><dt class="text-gray-500">Dirección</dt><dd class="text-gray-900">{{ $cotizacion->cliente->direccion }}</dd></div>
                    @endif
                </dl>
            </div>

            {{-- Fechas e info adicional --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid gap-x-6 gap-y-2 sm:grid-cols-3 text-sm">
                    <div><dt class="text-gray-500">Fecha de emisión</dt><dd class="text-gray-900">{{ $cotizacion->fecha_emision->format('d-m-Y') }}</dd></div>
                    <div><dt class="text-gray-500">Fecha de vencimiento</dt><dd class="text-gray-900">{{ $cotizacion->fecha_vencimiento->format('d-m-Y') }}</dd></div>
                    @if ($cotizacion->id_mercado_publico)
                        <div><dt class="text-gray-500">ID Mercado Público</dt><dd class="text-gray-900">{{ $cotizacion->id_mercado_publico }}</dd></div>
                    @endif
                </dl>

                @if ($cotizacion->archivo_adjunto)
                    <a href="{{ route('gestion.cotizaciones.adjunto', $cotizacion) }}"
                        class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-teal-600 hover:text-teal-500">
                        📎 Descargar archivo adjunto
                    </a>
                @endif
            </div>

            {{-- Líneas de detalle --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h2 class="text-sm font-semibold text-gray-900">Líneas de detalle</h2>

                <table class="mt-3 min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Descripción</th>
                            <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Unidad</th>
                            <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Cantidad</th>
                            <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Valor unitario</th>
                            <th class="py-2 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($cotizacion->lineas as $linea)
                            <tr wire:key="linea-detalle-{{ $linea->id }}">
                                <td class="py-2 text-sm text-gray-900">
                                    {{ $linea->descripcion }}
                                    @if ($linea->detalle_extendido)
                                        <div class="prose prose-sm mt-1 max-w-none text-xs text-gray-500">{!! $linea->detalle_extendido !!}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right text-sm text-gray-500">{{ $linea->unidad }}</td>
                                <td class="px-3 py-2 text-right text-sm text-gray-500">{{ rtrim(rtrim($linea->cantidad, '0'), '.') }}</td>
                                <td class="px-3 py-2 text-right text-sm text-gray-500">${{ number_format($linea->valor_unitario, 0, ',', '.') }}</td>
                                <td class="py-2 text-right text-sm text-gray-900">${{ number_format($linea->subtotal_calculado, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <dl class="mt-4 ml-auto max-w-xs space-y-1 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Subtotal</dt>
                        <dd class="text-gray-900">${{ number_format($cotizacion->subtotal_calculado, 0, ',', '.') }}</dd>
                    </div>
                    @if ((float) $cotizacion->descuento_porcentaje > 0)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Descuento ({{ rtrim(rtrim($cotizacion->descuento_porcentaje, '0'), '.') }}%)</dt>
                            <dd class="text-gray-900">${{ number_format($cotizacion->base_gravada_calculada, 0, ',', '.') }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-500">IVA (19%)</dt>
                        <dd class="text-gray-900">${{ number_format($cotizacion->iva_calculado, 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-gray-200 pt-1 font-semibold">
                        <dt class="text-gray-900">Total</dt>
                        <dd class="text-gray-900">${{ number_format($cotizacion->total_calculado, 0, ',', '.') }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Historial de estado --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h2 class="text-sm font-semibold text-gray-900">Historial de estado</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    <li class="flex justify-between">
                        <span class="text-gray-500">Creada (borrador)</span>
                        <span class="text-gray-900">{{ $cotizacion->created_at->format('d-m-Y H:i') }}</span>
                    </li>
                    @if ($cotizacion->enviada_en)
                        <li class="flex justify-between">
                            <span class="text-gray-500">Enviada</span>
                            <span class="text-gray-900">{{ $cotizacion->enviada_en->format('d-m-Y H:i') }}</span>
                        </li>
                    @endif
                    @if ($cotizacion->aprobada_en)
                        <li class="flex justify-between">
                            <span class="text-gray-500">Aprobada</span>
                            <span class="text-gray-900">{{ $cotizacion->aprobada_en->format('d-m-Y H:i') }}</span>
                        </li>
                    @endif
                    @if ($cotizacion->rechazada_en)
                        <li class="flex justify-between">
                            <span class="text-gray-500">Rechazada</span>
                            <span class="text-gray-900">{{ $cotizacion->rechazada_en->format('d-m-Y H:i') }}</span>
                        </li>
                    @endif
                </ul>
            </div>

            {{-- Facturación y gastos: solo cuando la cotización está aprobada --}}
            @if ($cotizacion->estado === EstadoCotizacion::Aprobada)
                <livewire:gestion.cotizaciones.factura-venta :cotizacion="$cotizacion" :key="'factura-venta-'.$cotizacion->id" />
                <livewire:gestion.cotizaciones.gastos :cotizacion="$cotizacion" :key="'gastos-'.$cotizacion->id" />
            @endif

            <a href="{{ route('gestion.cotizaciones.index') }}" wire:navigate class="inline-block text-sm text-gray-600 hover:text-gray-900">
                ← Volver a cotizaciones
            </a>
        </div>
    </div>
</div>
