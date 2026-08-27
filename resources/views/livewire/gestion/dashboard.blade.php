<?php

use App\Enums\EstadoCotizacion;
use App\Enums\TipoMovimiento;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Gasto;
use App\Models\MovimientoBancario;

use function Livewire\Volt\{computed, layout};

layout('layouts.gestion');

$estadisticas = computed(fn () => [
    'cotizacionesActivas' => Cotizacion::whereIn('estado', [EstadoCotizacion::Borrador, EstadoCotizacion::Enviada])->count(),
    'cotizacionesAprobadas' => Cotizacion::where('estado', EstadoCotizacion::Aprobada)->count(),
    'totalClientes' => Cliente::count(),
    'totalGastos' => Gasto::sum('total_calculado'),
    'ingresosCobrados' => (float) MovimientoBancario::where('tipo', TipoMovimiento::Abono)->where('conciliado', true)->sum('monto'),
]);

$ultimasCotizaciones = computed(fn () => Cotizacion::with('cliente')->latest()->limit(5)->get());

?>

<div>
    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            {{-- Botones rápidos --}}
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('gestion.cotizaciones.create') }}" wire:navigate
                    class="rounded-md bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-500">
                    + Nueva cotización
                </a>
                <a href="{{ route('gestion.clientes.create') }}" wire:navigate
                    class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    + Nuevo cliente
                </a>
                <a href="{{ route('gestion.cotizaciones.index') }}" wire:navigate
                    class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    Ver todas las cotizaciones
                </a>
            </div>

            {{-- Ingresos cobrados (destacado) --}}
            <a href="{{ route('gestion.conciliacion.index') }}" wire:navigate
                class="block rounded-lg bg-white p-6 shadow-sm ring-1 ring-inset ring-teal-100 hover:ring-teal-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Ingresos cobrados</p>
                        <p class="mt-2 text-3xl font-semibold text-teal-700">${{ number_format($this->estadisticas['ingresosCobrados'], 0, ',', '.') }}</p>
                        <p class="mt-1 text-xs text-gray-400">Abonos conciliados en el banco</p>
                    </div>
                    <span class="text-sm font-medium text-teal-600">Ver conciliación →</span>
                </div>
            </a>

            {{-- Resumen --}}
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Cotizaciones activas</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $this->estadisticas['cotizacionesActivas'] }}</p>
                    <p class="mt-1 text-xs text-gray-400">Borrador o enviadas</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Cotizaciones aprobadas</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $this->estadisticas['cotizacionesAprobadas'] }}</p>
                    <p class="mt-1 text-xs text-gray-400">Con gastos habilitados</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Clientes registrados</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $this->estadisticas['totalClientes'] }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Total en gastos</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">${{ number_format($this->estadisticas['totalGastos'], 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-gray-400">En todas las cotizaciones</p>
                </div>
            </div>

            {{-- Últimas cotizaciones --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h2 class="text-sm font-semibold text-gray-900">Últimas cotizaciones</h2>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Número</th>
                                <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Cliente</th>
                                <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Estado</th>
                                <th class="py-2 pl-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($this->ultimasCotizaciones as $cotizacion)
                                <tr wire:key="reciente-{{ $cotizacion->id }}">
                                    <td class="py-2 text-sm font-medium text-teal-600">
                                        <a href="{{ route('gestion.cotizaciones.show', $cotizacion) }}" wire:navigate class="hover:text-teal-500">
                                            {{ $cotizacion->numero_cotizacion }}
                                        </a>
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-500">{{ $cotizacion->cliente->nombre }}</td>
                                    <td class="px-3 py-2">
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
                                    </td>
                                    <td class="py-2 pl-3 text-right text-sm text-gray-900">${{ number_format($cotizacion->total_calculado, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-sm text-gray-500">Aún no hay cotizaciones registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
