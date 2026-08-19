<?php

use App\Enums\EstadoProyecto;
use App\Models\Proyecto;
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

$eliminar = function (Proyecto $proyecto) {
    $nombre = $proyecto->nombre;
    $proyecto->delete();

    session()->flash('status', 'Proyecto "'.$nombre.'" eliminado.');
};

$proyectos = computed(function () {
    return Proyecto::query()
        ->with('cliente')
        ->when($this->search, function ($query) {
            $query->where(function ($query) {
                $query->where('nombre', 'like', "%{$this->search}%")
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
                        <div>
                            <h1 class="text-lg font-semibold text-gray-900">Proyectos</h1>
                            <p class="mt-1 text-sm text-gray-500">Trabajos que nacen de una necesidad directa, sin cotización previa.</p>
                        </div>

                        <a href="{{ route('gestion.proyectos.create') }}" wire:navigate
                            class="inline-flex items-center justify-center rounded-md bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-500">
                            + Nuevo proyecto
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
                                placeholder="Buscar por nombre, descripción o cliente..."
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                        </div>

                        <div class="sm:w-56">
                            <select wire:model.live="estado"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                                <option value="">Todos los estados</option>
                                @foreach (EstadoProyecto::cases() as $opcion)
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
                                        <button wire:click="ordenarPor('nombre')"
                                            class="text-xs font-medium uppercase tracking-wide text-gray-500 hover:text-gray-700">
                                            Nombre
                                            @if ($sortBy === 'nombre')
                                                {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                            @endif
                                        </button>
                                    </th>
                                    <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Cliente
                                    </th>
                                    <th class="px-3 py-3 text-left">
                                        <button wire:click="ordenarPor('fecha_inicio')"
                                            class="text-xs font-medium uppercase tracking-wide text-gray-500 hover:text-gray-700">
                                            Inicio
                                            @if ($sortBy === 'fecha_inicio')
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
                                @forelse ($this->proyectos as $proyecto)
                                    <tr wire:key="proyecto-{{ $proyecto->id }}">
                                        <td class="py-3 pr-3 text-sm font-medium text-gray-900">
                                            {{ $proyecto->nombre }}
                                        </td>
                                        <td class="px-3 py-3 text-sm text-gray-500">
                                            {{ $proyecto->cliente->nombre }}
                                        </td>
                                        <td class="px-3 py-3 text-sm text-gray-500">
                                            {{ $proyecto->fecha_inicio->format('d-m-Y') }}
                                        </td>
                                        <td class="px-3 py-3">
                                            <span
                                                @class([
                                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                                    'bg-blue-50 text-blue-700' => $proyecto->estado === EstadoProyecto::Activo,
                                                    'bg-green-50 text-green-700' => $proyecto->estado === EstadoProyecto::Facturado,
                                                    'bg-gray-100 text-gray-700' => $proyecto->estado === EstadoProyecto::Cerrado,
                                                ])>
                                                {{ $proyecto->estado->label() }}
                                            </span>
                                        </td>
                                        <td class="py-3 pl-3 text-right text-sm">
                                            <a href="{{ route('gestion.proyectos.show', $proyecto) }}" wire:navigate
                                                class="font-medium text-teal-600 hover:text-teal-500">Ver</a>
                                            <button wire:click="eliminar({{ $proyecto->id }})"
                                                wire:confirm="¿Eliminar el proyecto '{{ $proyecto->nombre }}'? Se borrarán también sus gastos. Esta acción no se puede deshacer."
                                                class="ml-3 font-medium text-red-600 hover:text-red-500">
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-sm text-gray-500">
                                            @if ($search || $estado)
                                                No se encontraron proyectos con esos filtros.
                                            @else
                                                Aún no hay proyectos registrados.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $this->proyectos->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
