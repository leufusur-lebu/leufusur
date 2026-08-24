<?php

use App\Models\Cliente;
use App\Models\Proyecto;
use Illuminate\Support\Facades\Session;

use function Livewire\Volt\{computed, layout, mount, state};

layout('layouts.gestion');

state([
    'proyecto' => null,
    'cliente_id' => '',
    'nombre' => '',
    'descripcion' => '',
    'fecha_inicio' => fn () => today()->toDateString(),
]);

mount(function (?Proyecto $proyecto = null) {
    if ($proyecto?->exists) {
        $this->proyecto = $proyecto;
        $this->cliente_id = $proyecto->cliente_id;
        $this->nombre = $proyecto->nombre;
        $this->descripcion = (string) $proyecto->descripcion;
        $this->fecha_inicio = $proyecto->fecha_inicio->toDateString();
    }
});

$clientes = computed(fn () => Cliente::orderBy('nombre')->get(['id', 'nombre', 'nombre_empresa']));

$guardar = function () {
    $datos = $this->validate([
        'cliente_id' => ['required', 'exists:clientes,id'],
        'nombre' => ['required', 'string', 'max:255'],
        'descripcion' => ['nullable', 'string', 'max:2000'],
        'fecha_inicio' => ['required', 'date'],
    ]);

    if ($this->proyecto) {
        $this->proyecto->update($datos);
        Session::flash('status', 'Proyecto actualizado.');
        $this->redirectRoute('gestion.proyectos.show', $this->proyecto, navigate: true);

        return;
    }

    $proyecto = Proyecto::create($datos);
    Session::flash('status', 'Proyecto creado.');
    $this->redirectRoute('gestion.proyectos.show', $proyecto, navigate: true);
};

?>

<div>
    <div class="py-12">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h1 class="text-lg font-semibold text-gray-900">
                    {{ $proyecto ? 'Editar proyecto' : 'Nuevo proyecto' }}
                </h1>

                <form wire:submit="guardar" class="mt-6 space-y-6">
                    <div>
                        <x-input-label for="cliente_id" value="Cliente" />
                        <select id="cliente_id" wire:model="cliente_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                            <option value="">Selecciona un cliente…</option>
                            @foreach ($this->clientes as $cliente)
                                <option value="{{ $cliente->id }}">
                                    {{ $cliente->nombre }}@if ($cliente->nombre_empresa) — {{ $cliente->nombre_empresa }}@endif
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('cliente_id')" class="mt-2" />
                        <p class="mt-1 text-xs text-gray-500">
                            ¿No está en la lista?
                            <a href="{{ route('gestion.clientes.create') }}" wire:navigate class="font-medium text-teal-600 hover:text-teal-500">Crea el cliente primero</a>.
                        </p>
                    </div>

                    <div>
                        <x-input-label for="nombre" value="Nombre del proyecto" />
                        <x-text-input id="nombre" wire:model="nombre" type="text" class="mt-1 block w-full"
                            placeholder="Ej: Instalación de red — Oficina Lebu" />
                        <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="fecha_inicio" value="Fecha de inicio" />
                        <x-date-picker id="fecha_inicio" model="fecha_inicio" class="mt-1" />
                        <x-input-error :messages="$errors->get('fecha_inicio')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="descripcion" value="Descripción (opcional)" />
                        <textarea id="descripcion" wire:model="descripcion" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                            placeholder="Detalle de la necesidad o el alcance del trabajo…"></textarea>
                        <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('gestion.proyectos.index') }}" wire:navigate
                            class="text-sm text-gray-600 hover:text-gray-900">Cancelar</a>
                        <x-primary-button>
                            {{ $proyecto ? 'Guardar cambios' : 'Crear proyecto' }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
