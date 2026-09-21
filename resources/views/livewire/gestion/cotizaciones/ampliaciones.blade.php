<?php

use App\Models\Ampliacion;
use App\Models\Cotizacion;
use Illuminate\Support\Str;

use function Livewire\Volt\{computed, mount, state};

// Ampliaciones de una cotización aprobada: trabajos adicionales surgidos en ejecución que no
// estaban considerados. Cada ampliación tiene sus propias líneas y suma al total a cobrar.
state([
    'cotizacion' => null,
    'mostrarForm' => false,
    'editandoId' => null,
    'fecha' => fn () => today()->toDateString(),
    'descripcion' => '',
    'lineas' => fn () => [
        ['_key' => (string) Str::uuid(), 'descripcion' => '', 'unidad' => 'UN', 'cantidad' => 1, 'valor_unitario' => 0],
    ],
]);

mount(function (Cotizacion $cotizacion) {
    $this->cotizacion = $cotizacion;
});

$ampliaciones = computed(fn () => $this->cotizacion->ampliaciones()->with('lineas')->get());

$resumen = computed(function () {
    $subtotal = collect($this->lineas)->sum(
        fn ($linea) => (float) ($linea['cantidad'] ?? 0) * (float) ($linea['valor_unitario'] ?? 0)
    );

    return [
        'subtotal' => $subtotal,
        'iva' => $subtotal * Cotizacion::IVA,
        'total' => $subtotal + ($subtotal * Cotizacion::IVA),
    ];
});

$lineaEnBlanco = fn () => ['_key' => (string) Str::uuid(), 'descripcion' => '', 'unidad' => 'UN', 'cantidad' => 1, 'valor_unitario' => 0];

$prepararNueva = function () use ($lineaEnBlanco) {
    $this->editandoId = null;
    $this->fecha = today()->toDateString();
    $this->descripcion = '';
    $this->lineas = [$lineaEnBlanco()];
    $this->mostrarForm = true;
    $this->resetValidation();
};

$editar = function (int $id) {
    $ampliacion = $this->cotizacion->ampliaciones()->with('lineas')->findOrFail($id);

    $this->editandoId = $ampliacion->id;
    $this->fecha = $ampliacion->fecha->toDateString();
    $this->descripcion = $ampliacion->descripcion;
    $this->lineas = $ampliacion->lineas->map(fn ($linea) => [
        '_key' => (string) Str::uuid(),
        'descripcion' => $linea->descripcion,
        'unidad' => $linea->unidad,
        'cantidad' => (float) $linea->cantidad,
        'valor_unitario' => (float) $linea->valor_unitario,
    ])->all();
    $this->mostrarForm = true;
    $this->resetValidation();
};

$cancelar = function () use ($lineaEnBlanco) {
    $this->mostrarForm = false;
    $this->editandoId = null;
    $this->descripcion = '';
    $this->lineas = [$lineaEnBlanco()];
    $this->resetValidation();
};

$agregarLinea = function () use ($lineaEnBlanco) {
    $this->lineas[] = $lineaEnBlanco();
};

$eliminarLinea = function (int $indice) {
    if (count($this->lineas) <= 1) {
        return;
    }

    unset($this->lineas[$indice]);
    $this->lineas = array_values($this->lineas);
};

$guardar = function () {
    $datos = $this->validate([
        'fecha' => ['required', 'date'],
        'descripcion' => ['required', 'string', 'max:255'],
        'lineas' => ['required', 'array', 'min:1'],
        'lineas.*.descripcion' => ['required', 'string', 'max:255'],
        'lineas.*.unidad' => ['required', 'string', 'max:20'],
        'lineas.*.cantidad' => ['required', 'numeric', 'min:0.01'],
        'lineas.*.valor_unitario' => ['required', 'numeric', 'min:0'],
    ]);

    $ampliacion = $this->editandoId
        ? $this->cotizacion->ampliaciones()->findOrFail($this->editandoId)
        : $this->cotizacion->ampliaciones()->make();

    $ampliacion->fill(['fecha' => $datos['fecha'], 'descripcion' => $datos['descripcion']]);
    $ampliacion->save();

    $ampliacion->lineas()->delete();
    foreach (array_values($datos['lineas']) as $indice => $linea) {
        $ampliacion->lineas()->create([
            'descripcion' => $linea['descripcion'],
            'unidad' => $linea['unidad'],
            'cantidad' => $linea['cantidad'],
            'valor_unitario' => $linea['valor_unitario'],
            'subtotal_calculado' => (float) $linea['cantidad'] * (float) $linea['valor_unitario'],
            'orden' => $indice,
        ]);
    }

    $ampliacion->recalcularTotales();

    unset($this->ampliaciones);
    $this->cotizacion->refresh();
    $this->cancelar();

    session()->flash('status', 'Ampliación guardada.');
};

$eliminar = function (int $id) {
    $this->cotizacion->ampliaciones()->findOrFail($id)->delete();

    unset($this->ampliaciones);
    $this->cotizacion->refresh();

    session()->flash('status', 'Ampliación eliminada.');
};

?>

@php
    $totalAmpliaciones = $this->ampliaciones->sum('total_calculado');
@endphp

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-gray-900">Ampliaciones (trabajos adicionales)</h2>
        @if (! $mostrarForm)
            <button wire:click="prepararNueva" class="text-sm font-medium text-teal-600 hover:text-teal-500">
                + Agregar ampliación
            </button>
        @endif
    </div>

    <p class="mt-1 text-xs text-gray-500">
        Trabajos no considerados que surgieron durante la ejecución. Cada ampliación suma al total a cobrar.
    </p>

    {{-- Listado de ampliaciones registradas --}}
    @if ($this->ampliaciones->isNotEmpty())
        <div class="mt-4 space-y-3">
            @foreach ($this->ampliaciones as $ampliacion)
                <div class="rounded-md border border-gray-200 p-4" wire:key="ampliacion-{{ $ampliacion->id }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $ampliacion->descripcion }}</p>
                            <p class="text-xs text-gray-500">{{ $ampliacion->fecha->format('d-m-Y') }}</p>
                        </div>
                        <div class="flex shrink-0 gap-3">
                            <button wire:click="editar({{ $ampliacion->id }})" class="text-sm text-teal-600 hover:text-teal-500">Editar</button>
                            <button wire:click="eliminar({{ $ampliacion->id }})"
                                wire:confirm="¿Eliminar esta ampliación y sus líneas?"
                                class="text-sm text-red-600 hover:text-red-500">Eliminar</button>
                        </div>
                    </div>

                    <table class="mt-3 min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="py-1 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Ítem</th>
                                <th class="px-3 py-1 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Unidad</th>
                                <th class="px-3 py-1 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Cant.</th>
                                <th class="px-3 py-1 text-right text-xs font-medium uppercase tracking-wide text-gray-500">V. unit.</th>
                                <th class="py-1 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($ampliacion->lineas as $linea)
                                <tr wire:key="ampliacion-{{ $ampliacion->id }}-linea-{{ $linea->id }}">
                                    <td class="py-1 text-sm text-gray-900">{{ $linea->descripcion }}</td>
                                    <td class="px-3 py-1 text-right text-sm text-gray-500">{{ $linea->unidad }}</td>
                                    <td class="px-3 py-1 text-right text-sm text-gray-500">{{ rtrim(rtrim($linea->cantidad, '0'), '.') }}</td>
                                    <td class="px-3 py-1 text-right text-sm text-gray-500">${{ number_format($linea->valor_unitario, 0, ',', '.') }}</td>
                                    <td class="py-1 text-right text-sm text-gray-900">${{ number_format($linea->subtotal_calculado, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-2 text-right text-sm text-gray-500">
                        Neto ${{ number_format($ampliacion->subtotal_calculado, 0, ',', '.') }}
                        + IVA ${{ number_format($ampliacion->iva_calculado, 0, ',', '.') }}
                        = <span class="font-semibold text-gray-900">${{ number_format($ampliacion->total_calculado, 0, ',', '.') }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 ml-auto max-w-xs space-y-1 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500">Total ampliaciones</dt>
                <dd class="text-gray-900">${{ number_format($totalAmpliaciones, 0, ',', '.') }}</dd>
            </div>
            <div class="flex justify-between border-t border-gray-200 pt-1 font-semibold">
                <dt class="text-gray-900">Total a cobrar (cotiz. + ampl.)</dt>
                <dd class="text-gray-900">${{ number_format($this->cotizacion->totalConAmpliaciones(), 0, ',', '.') }}</dd>
            </div>
        </div>
    @elseif (! $mostrarForm)
        <p class="mt-4 text-sm text-gray-500">Aún no hay ampliaciones registradas.</p>
    @endif

    {{-- Formulario de alta/edición (inline, no modal) --}}
    @if ($mostrarForm)
        <form wire:submit="guardar" class="mt-5 rounded-md border border-teal-200 bg-teal-50/40 p-4">
            <h3 class="text-sm font-semibold text-gray-900">
                {{ $editandoId ? 'Editar ampliación' : 'Nueva ampliación' }}
            </h3>

            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="ampliacion_descripcion" value="Motivo / descripción" />
                    <x-text-input id="ampliacion_descripcion" wire:model="descripcion" type="text" class="mt-1 block w-full"
                        placeholder="Ej: Refuerzo de cableado no considerado" />
                    <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="ampliacion_fecha" value="Fecha" />
                    <x-date-picker id="ampliacion_fecha" model="fecha" class="mt-1" />
                    <x-input-error :messages="$errors->get('fecha')" class="mt-2" />
                </div>
            </div>

            <div class="mt-4">
                <x-input-label value="Líneas de la ampliación" />
                <div class="mt-2 space-y-3">
                    @foreach ($lineas as $indice => $linea)
                        <div class="rounded-md border border-gray-200 bg-white p-3" wire:key="ampl-linea-{{ $linea['_key'] ?? $indice }}">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
                                <div class="sm:flex-1">
                                    <input type="text" wire:model="lineas.{{ $indice }}.descripcion"
                                        placeholder="Producto o servicio"
                                        class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                    <x-input-error :messages="$errors->get('lineas.'.$indice.'.descripcion')" class="mt-1" />
                                </div>
                                <div class="sm:w-20">
                                    <input type="text" wire:model="lineas.{{ $indice }}.unidad" placeholder="Unidad"
                                        class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                    <x-input-error :messages="$errors->get('lineas.'.$indice.'.unidad')" class="mt-1" />
                                </div>
                                <div class="sm:w-24">
                                    <input type="number" step="0.01" min="0" wire:model.live="lineas.{{ $indice }}.cantidad"
                                        placeholder="Cantidad"
                                        class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                    <x-input-error :messages="$errors->get('lineas.'.$indice.'.cantidad')" class="mt-1" />
                                </div>
                                <div class="sm:w-36">
                                    <input type="number" step="0.01" min="0" wire:model.live="lineas.{{ $indice }}.valor_unitario"
                                        placeholder="Valor unitario"
                                        class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                    <x-input-error :messages="$errors->get('lineas.'.$indice.'.valor_unitario')" class="mt-1" />
                                </div>
                                <div class="flex items-center justify-end sm:w-28 sm:justify-center sm:pt-2">
                                    <span class="text-sm text-gray-500">
                                        ${{ number_format((float) ($linea['cantidad'] ?? 0) * (float) ($linea['valor_unitario'] ?? 0), 0, ',', '.') }}
                                    </span>
                                </div>
                                <div class="flex justify-end sm:pt-1.5">
                                    <button type="button" wire:click="eliminarLinea({{ $indice }})"
                                        class="text-sm text-red-600 hover:text-red-500">Quitar</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <button type="button" wire:click="agregarLinea" class="mt-3 text-sm font-medium text-teal-600 hover:text-teal-500">
                    + Agregar línea
                </button>
                <x-input-error :messages="$errors->get('lineas')" class="mt-2" />
            </div>

            <div class="mt-4 rounded-md bg-white p-4">
                <dl class="ml-auto max-w-xs space-y-1 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Neto</dt>
                        <dd class="text-gray-900">${{ number_format($this->resumen['subtotal'], 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">IVA (19%)</dt>
                        <dd class="text-gray-900">${{ number_format($this->resumen['iva'], 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-gray-200 pt-1 font-semibold">
                        <dt class="text-gray-900">Total ampliación</dt>
                        <dd class="text-gray-900">${{ number_format($this->resumen['total'], 0, ',', '.') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="mt-4 flex items-center justify-end gap-3">
                <button type="button" wire:click="cancelar" class="text-sm text-gray-600 hover:text-gray-900">Cancelar</button>
                <x-primary-button>{{ $editandoId ? 'Guardar cambios' : 'Registrar ampliación' }}</x-primary-button>
            </div>
        </form>
    @endif
</div>
