<?php

use App\Enums\EstadoCotizacion;
use App\Models\Cotizacion;
use Livewire\WithPagination;

use function Livewire\Volt\{computed, layout, state, uses};

uses([WithPagination::class]);
layout('layouts.gestion');

state([
    'search' => '',
    'estado' => '',
    'sortBy' => 'created_at',
    'sortDirection' => 'desc',
]);

$updatedSearch = fn () => $this->resetPage();
$updatedEstado = fn () => $this->resetPage();

$ordenarPor = function (string $columna) {
    if ($this->sortBy === $columna) {
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        $this->sortBy = $columna;
        $this->sortDirection = 'asc';
    }
};

$cotizaciones = computed(function () {
    return Cotizacion::query()
        ->with('cliente')
        ->when($this->search, function ($query) {
            $query->where(function ($query) {
                $query->where('numero_cotizacion', 'like', "%{$this->search}%")
                    ->orWhere('descripcion', 'like', "%{$this->search}%")
                    ->orWhereHas('cliente', function ($query) {
                        $query->where('nombre', 'like', "%{$this->search}%")
                            ->orWhere('nombre_empresa', 'like', "%{$this->search}%");
                    });
            });
        })
        ->when($this->estado, fn ($query) => $query->where('estado', $this->estado))
        ->orderBy($this->sortBy, $this->sortDirection)
        ->paginate(10);
});

?>

<div>
    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    {{-- Encabezado --}}
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <h1 class="text-lg font-semibold text-gray-900">Cotizaciones</h1>

                        <a href="{{ route('gestion.cotizaciones.create') }}" wire:navigate
                            class="inline-flex items-center justify-center rounded-md bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-500">
                            + Nueva cotización
                        </a>
                    </div>

                    {{-- Mensajes flash --}}
                    @if (session('status'))
                        <div class="mt-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{-- Filtros --}}
                    <div class="mt-6 flex flex-col gap-4 sm:flex-row">
                        <div class="flex-1">
                            <input type="search" wire:model.live.debounce.300ms="search"
                                placeholder="Buscar por número, descripción o cliente..."
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                        </div>

                        <div class="sm:w-56">
                            <select wire:model.live="estado"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                                <option value="">Todos los estados</option>
                                @foreach (EstadoCotizacion::cases() as $opcion)
                                    <option value="{{ $opcion->value }}">{{ $opcion->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Tabla --}}
                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="py-3 pr-3 text-left">
                                        <button wire:click="ordenarPor('numero_cotizacion')"
                                            class="text-xs font-medium uppercase tracking-wide text-gray-500 hover:text-gray-700">
                                            Número
                                            @if ($sortBy === 'numero_cotizacion')
                                                {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                            @endif
                                        </button>
                                    </th>
                                    <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Cliente
                                    </th>
                                    <th class="px-3 py-3 text-left">
                                        <button wire:click="ordenarPor('fecha_emision')"
                                            class="text-xs font-medium uppercase tracking-wide text-gray-500 hover:text-gray-700">
                                            Emisión
                                            @if ($sortBy === 'fecha_emision')
                                                {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                            @endif
                                        </button>
                                    </th>
                                    <th class="px-3 py-3 text-left">
                                        <button wire:click="ordenarPor('total_calculado')"
                                            class="text-xs font-medium uppercase tracking-wide text-gray-500 hover:text-gray-700">
                                            Total
                                            @if ($sortBy === 'total_calculado')
                                                {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                            @endif
                                        </button>
                                    </th>
                                    <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Estado
                                    </th>
                                    <th class="py-3 pl-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($this->cotizaciones as $cotizacion)
                                    <tr wire:key="cotizacion-{{ $cotizacion->id }}">
                                        <td class="py-3 pr-3 text-sm font-medium text-gray-900">
                                            {{ $cotizacion->numero_cotizacion }}
                                        </td>
                                        <td class="px-3 py-3 text-sm text-gray-500">
                                            {{ $cotizacion->cliente->nombre }}
                                        </td>
                                        <td class="px-3 py-3 text-sm text-gray-500">
                                            {{ $cotizacion->fecha_emision->format('d-m-Y') }}
                                        </td>
                                        <td class="px-3 py-3 text-sm text-gray-500">
                                            ${{ number_format($cotizacion->total_calculado, 0, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-3">
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
                                        <td class="py-3 pl-3 text-right text-sm">
                                            <a href="{{ route('gestion.cotizaciones.show', $cotizacion) }}" wire:navigate
                                                class="font-medium text-teal-600 hover:text-teal-500">Ver</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-sm text-gray-500">
                                            @if ($search || $estado)
                                                No se encontraron cotizaciones con esos filtros.
                                            @else
                                                Aún no hay cotizaciones registradas.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $this->cotizaciones->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
