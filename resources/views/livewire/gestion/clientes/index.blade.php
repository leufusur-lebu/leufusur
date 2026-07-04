<?php

use App\Enums\TipoCliente;
use App\Models\Cliente;
use Illuminate\Database\QueryException;
use Livewire\WithPagination;

use function Livewire\Volt\{computed, layout, state, uses};

uses([WithPagination::class]);
layout('layouts.gestion');

state([
    'search' => '',
    'tipo' => '',
    'sortBy' => 'nombre',
    'sortDirection' => 'asc',
]);

$updatedSearch = fn () => $this->resetPage();
$updatedTipo = fn () => $this->resetPage();

$ordenarPor = function (string $columna) {
    if ($this->sortBy === $columna) {
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        $this->sortBy = $columna;
        $this->sortDirection = 'asc';
    }
};

$eliminar = function (Cliente $cliente) {
    try {
        $cliente->delete();
        session()->flash('status', 'Cliente eliminado.');
    } catch (QueryException) {
        session()->flash('error', 'No se puede eliminar "'.$cliente->nombre.'": tiene cotizaciones asociadas.');
    }
};

$clientes = computed(function () {
    return Cliente::query()
        ->when($this->search, function ($query) {
            $query->where(function ($query) {
                $query->where('nombre', 'like', "%{$this->search}%")
                    ->orWhere('nombre_empresa', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('telefono', 'like', "%{$this->search}%")
                    ->orWhere('rut_run', 'like', "%{$this->search}%");
            });
        })
        ->when($this->tipo, fn ($query) => $query->where('tipo', $this->tipo))
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
                        <h1 class="text-lg font-semibold text-gray-900">Clientes</h1>

                        <a href="{{ route('gestion.clientes.create') }}" wire:navigate
                            class="inline-flex items-center justify-center rounded-md bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-500">
                            + Nuevo cliente
                        </a>
                    </div>

                    {{-- Mensajes flash --}}
                    @if (session('status'))
                        <div class="mt-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mt-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ session('error') }}
                        </div>
                    @endif

                    {{-- Filtros --}}
                    <div class="mt-6 flex flex-col gap-4 sm:flex-row">
                        <div class="flex-1">
                            <input type="search" wire:model.live.debounce.300ms="search"
                                placeholder="Buscar por nombre, email, teléfono o RUT..."
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                        </div>

                        <div class="sm:w-56">
                            <select wire:model.live="tipo"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                                <option value="">Todos los tipos</option>
                                @foreach (TipoCliente::cases() as $opcion)
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
                                    <th class="py-3 pr-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Tipo
                                    </th>
                                    <th class="px-3 py-3 text-left">
                                        <button wire:click="ordenarPor('nombre')"
                                            class="text-xs font-medium uppercase tracking-wide text-gray-500 hover:text-gray-700">
                                            Nombre
                                            @if ($sortBy === 'nombre')
                                                {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                            @endif
                                        </button>
                                    </th>
                                    <th class="px-3 py-3 text-left">
                                        <button wire:click="ordenarPor('email')"
                                            class="text-xs font-medium uppercase tracking-wide text-gray-500 hover:text-gray-700">
                                            Email
                                            @if ($sortBy === 'email')
                                                {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                            @endif
                                        </button>
                                    </th>
                                    <th class="px-3 py-3 text-left">
                                        <button wire:click="ordenarPor('telefono')"
                                            class="text-xs font-medium uppercase tracking-wide text-gray-500 hover:text-gray-700">
                                            Teléfono
                                            @if ($sortBy === 'telefono')
                                                {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                            @endif
                                        </button>
                                    </th>
                                    <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        RUT
                                    </th>
                                    <th class="py-3 pl-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($this->clientes as $cliente)
                                    <tr wire:key="cliente-{{ $cliente->id }}">
                                        <td class="py-3 pr-3">
                                            <span
                                                @class([
                                                    'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
                                                    'bg-cyan-50 text-cyan-700' => $cliente->tipo === TipoCliente::Empresa,
                                                    'bg-slate-100 text-slate-700' => $cliente->tipo === TipoCliente::Persona,
                                                ])>
                                                @if ($cliente->tipo === TipoCliente::Empresa)
                                                    🏢
                                                @else
                                                    👤
                                                @endif
                                                {{ $cliente->tipo->label() }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-sm text-gray-900">
                                            {{ $cliente->nombre }}
                                            @if ($cliente->nombre_empresa)
                                                <span class="block text-xs text-gray-500">{{ $cliente->nombre_empresa }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-sm text-gray-500">{{ $cliente->email }}</td>
                                        <td class="px-3 py-3 text-sm text-gray-500">{{ $cliente->telefono }}</td>
                                        <td class="px-3 py-3 text-sm text-gray-500">{{ $cliente->rut_run }}</td>
                                        <td class="py-3 pl-3 text-right text-sm">
                                            <a href="{{ route('gestion.clientes.edit', $cliente) }}" wire:navigate
                                                class="font-medium text-teal-600 hover:text-teal-500">Editar</a>
                                            <button wire:click="eliminar({{ $cliente->id }})"
                                                wire:confirm="¿Eliminar a {{ $cliente->nombre }}? Esta acción no se puede deshacer."
                                                class="ml-4 font-medium text-red-600 hover:text-red-500">
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-sm text-gray-500">
                                            @if ($search || $tipo)
                                                No se encontraron clientes con esos filtros.
                                            @else
                                                Aún no hay clientes registrados.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $this->clientes->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
