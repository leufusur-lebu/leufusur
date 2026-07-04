<?php

use App\Enums\TipoCliente;
use App\Models\Cliente;
use App\Rules\RutValido;
use Illuminate\Validation\Rule;

use function Livewire\Volt\{layout, mount, state};

layout('layouts.gestion');

state([
    'cliente' => null,
    'tipo' => TipoCliente::Persona->value,
    'nombre' => '',
    'nombre_empresa' => '',
    'email' => '',
    'telefono' => '',
    'rut_run' => '',
    'direccion' => '',
]);

mount(function (?Cliente $cliente = null) {
    if ($cliente?->exists) {
        $this->cliente = $cliente;
        $this->tipo = $cliente->tipo->value;
        $this->nombre = $cliente->nombre;
        $this->nombre_empresa = $cliente->nombre_empresa ?? '';
        $this->email = $cliente->email;
        $this->telefono = $cliente->telefono;
        $this->rut_run = $cliente->rut_run;
        $this->direccion = $cliente->direccion;
    }
});

$guardar = function () {
    $datos = $this->validate([
        'tipo' => ['required', Rule::enum(TipoCliente::class)],
        'nombre' => ['required', 'string', 'max:255'],
        'nombre_empresa' => ['nullable', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255'],
        'telefono' => ['required', 'string', 'max:50'],
        'rut_run' => [
            'required',
            new RutValido,
            Rule::unique('clientes', 'rut_run')->ignore($this->cliente?->id),
        ],
        'direccion' => ['required', 'string', 'max:255'],
    ]);

    if ($datos['tipo'] === TipoCliente::Empresa->value) {
        $datos['nombre_empresa'] = null;
    }

    if ($this->cliente) {
        $this->cliente->update($datos);
    } else {
        Cliente::create($datos);
    }

    session()->flash('status', $this->cliente ? 'Cliente actualizado.' : 'Cliente creado.');

    $this->redirectRoute('gestion.clientes.index', navigate: true);
};

?>

<div>
    <div class="py-12">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h1 class="text-lg font-semibold text-gray-900">
                        {{ $cliente ? 'Editar cliente' : 'Nuevo cliente' }}
                    </h1>

                    <form wire:submit="guardar" class="mt-6 space-y-6">
                        {{-- Tipo --}}
                        <div>
                            <x-input-label for="tipo" value="Tipo de cliente" />
                            <select id="tipo" wire:model.live="tipo"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                                @foreach (TipoCliente::cases() as $opcion)
                                    <option value="{{ $opcion->value }}">{{ $opcion->label() }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('tipo')" class="mt-2" />
                        </div>

                        {{-- Nombre --}}
                        <div>
                            <x-input-label for="nombre"
                                :value="$tipo === TipoCliente::Empresa->value ? 'Razón social' : 'Nombre completo'" />
                            <x-text-input id="nombre" wire:model="nombre" type="text" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                        </div>

                        {{-- Empresa que representa (solo personas naturales) --}}
                        @if ($tipo === TipoCliente::Persona->value)
                            <div>
                                <x-input-label for="nombre_empresa" value="Empresa que representa (opcional)" />
                                <x-text-input id="nombre_empresa" wire:model="nombre_empresa" type="text"
                                    class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('nombre_empresa')" class="mt-2" />
                            </div>
                        @endif

                        {{-- Email y teléfono --}}
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <x-input-label for="email" value="Correo electrónico" />
                                <x-text-input id="email" wire:model="email" type="email" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="telefono" value="Teléfono" />
                                <x-text-input id="telefono" wire:model="telefono" type="text"
                                    placeholder="+56 9 1234 5678" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('telefono')" class="mt-2" />
                            </div>
                        </div>

                        {{-- RUT --}}
                        <div>
                            <x-input-label for="rut_run" value="RUT" />
                            <x-text-input id="rut_run" wire:model="rut_run" type="text" placeholder="12.345.678-9"
                                class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('rut_run')" class="mt-2" />
                        </div>

                        {{-- Dirección --}}
                        <div>
                            <x-input-label for="direccion" value="Dirección" />
                            <x-text-input id="direccion" wire:model="direccion" type="text" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('direccion')" class="mt-2" />
                        </div>

                        {{-- Acciones --}}
                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('gestion.clientes.index') }}" wire:navigate
                                class="text-sm text-gray-600 hover:text-gray-900">
                                Cancelar
                            </a>
                            <x-primary-button>
                                {{ $cliente ? 'Guardar cambios' : 'Crear cliente' }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
