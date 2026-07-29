<?php

use App\Enums\EstadoCotizacion;
use App\Models\Cliente;
use App\Models\Cotizacion;
use Illuminate\Support\Str;

use function Livewire\Volt\{computed, layout, mount, state, usesFileUploads};

layout('layouts.gestion');
usesFileUploads();

state([
    'cotizacion' => null,
    'cliente_id' => null,
    'clienteSearch' => '',
    'descripcion' => '',
    'fecha_emision' => fn () => today()->toDateString(),
    'fecha_vencimiento' => '',
    'descuento_porcentaje' => 0,
    'id_mercado_publico' => '',
    'nuevoAdjunto' => null,
    'lineas' => fn () => [
        ['_key' => (string) Str::uuid(), 'descripcion' => '', 'detalle_extendido' => '', 'mostrar_detalle' => false, 'unidad' => 'UN', 'cantidad' => 1, 'valor_unitario' => 0],
    ],
]);

mount(function (?Cotizacion $cotizacion = null) {
    if (! $cotizacion?->exists) {
        return;
    }

    if (! $cotizacion->estado->esEditable()) {
        $this->redirectRoute('gestion.cotizaciones.show', $cotizacion, navigate: true);

        return;
    }

    $this->cotizacion = $cotizacion;
    $this->cliente_id = $cotizacion->cliente_id;
    $this->clienteSearch = $cotizacion->cliente->nombre;
    $this->descripcion = $cotizacion->descripcion;
    $this->fecha_emision = $cotizacion->fecha_emision->toDateString();
    $this->fecha_vencimiento = $cotizacion->fecha_vencimiento->toDateString();
    $this->descuento_porcentaje = (float) $cotizacion->descuento_porcentaje;
    $this->id_mercado_publico = $cotizacion->id_mercado_publico ?? '';

    $this->lineas = $cotizacion->lineas->map(fn ($linea) => [
        '_key' => (string) Str::uuid(),
        'descripcion' => $linea->descripcion,
        'detalle_extendido' => $linea->detalle_extendido ?? '',
        'mostrar_detalle' => filled($linea->detalle_extendido),
        'unidad' => $linea->unidad,
        'cantidad' => (float) $linea->cantidad,
        'valor_unitario' => (float) $linea->valor_unitario,
    ])->all();
});

$clientesSugeridos = computed(function () {
    if ($this->cliente_id || trim($this->clienteSearch) === '') {
        return collect();
    }

    return Cliente::query()
        ->where(function ($query) {
            $query->where('nombre', 'like', "%{$this->clienteSearch}%")
                ->orWhere('nombre_empresa', 'like', "%{$this->clienteSearch}%");
        })
        ->orderBy('nombre')
        ->limit(8)
        ->get();
});

$seleccionarCliente = function (Cliente $cliente) {
    $this->cliente_id = $cliente->id;
    $this->clienteSearch = $cliente->nombre;
};

$quitarCliente = function () {
    $this->cliente_id = null;
    $this->clienteSearch = '';
};

$agregarLinea = function () {
    $this->lineas[] = ['_key' => (string) Str::uuid(), 'descripcion' => '', 'detalle_extendido' => '', 'mostrar_detalle' => false, 'unidad' => 'UN', 'cantidad' => 1, 'valor_unitario' => 0];
};

$eliminarLinea = function (int $indice) {
    if (count($this->lineas) <= 1) {
        return;
    }

    unset($this->lineas[$indice]);
    $this->lineas = array_values($this->lineas);
};

$resumen = computed(function () {
    $subtotal = collect($this->lineas)->sum(
        fn ($linea) => (float) ($linea['cantidad'] ?? 0) * (float) ($linea['valor_unitario'] ?? 0)
    );

    $baseGravada = $subtotal - ($subtotal * (float) $this->descuento_porcentaje / 100);
    $iva = $baseGravada * Cotizacion::IVA;

    return [
        'subtotal' => $subtotal,
        'baseGravada' => $baseGravada,
        'iva' => $iva,
        'total' => $baseGravada + $iva,
    ];
});

$guardar = function () {
    if ($this->cotizacion && ! $this->cotizacion->fresh()->estado->esEditable()) {
        session()->flash('error', 'Esta cotización ya no se puede editar.');
        $this->redirectRoute('gestion.cotizaciones.show', $this->cotizacion, navigate: true);

        return;
    }

    $datos = $this->validate([
        'cliente_id' => ['required', 'exists:clientes,id'],
        'descripcion' => ['required', 'string', 'max:255'],
        'fecha_emision' => ['required', 'date'],
        'fecha_vencimiento' => ['required', 'date', 'after_or_equal:fecha_emision'],
        'descuento_porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
        'id_mercado_publico' => ['nullable', 'string', 'max:255'],
        'nuevoAdjunto' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:10240'],
        'lineas' => ['required', 'array', 'min:1'],
        'lineas.*.descripcion' => ['required', 'string', 'max:255'],
        'lineas.*.detalle_extendido' => ['nullable', 'string', 'max:20000'],
        'lineas.*.unidad' => ['required', 'string', 'max:20'],
        'lineas.*.cantidad' => ['required', 'numeric', 'min:0.01'],
        'lineas.*.valor_unitario' => ['required', 'numeric', 'min:0'],
    ]);

    if ($this->cotizacion) {
        $this->cotizacion->update([
            'cliente_id' => $datos['cliente_id'],
            'descripcion' => $datos['descripcion'],
            'fecha_emision' => $datos['fecha_emision'],
            'fecha_vencimiento' => $datos['fecha_vencimiento'],
            'descuento_porcentaje' => $datos['descuento_porcentaje'],
            'id_mercado_publico' => $datos['id_mercado_publico'] ?: null,
        ]);
        $this->cotizacion->lineas()->delete();
        $cotizacion = $this->cotizacion;
    } else {
        $cotizacion = Cotizacion::create([
            'cliente_id' => $datos['cliente_id'],
            'numero_cotizacion' => Cotizacion::generarNumero(),
            'descripcion' => $datos['descripcion'],
            'fecha_emision' => $datos['fecha_emision'],
            'fecha_vencimiento' => $datos['fecha_vencimiento'],
            'descuento_porcentaje' => $datos['descuento_porcentaje'],
            'estado' => EstadoCotizacion::Borrador,
            'id_mercado_publico' => $datos['id_mercado_publico'] ?: null,
        ]);
    }

    foreach ($datos['lineas'] as $indice => $linea) {
        $detalleExtendido = trim((string) ($linea['detalle_extendido'] ?? ''));

        if ($detalleExtendido !== '') {
            $detalleExtendido = strip_tags(
                $detalleExtendido,
                '<strong><b><em><i><a><ul><ol><li><br><div><h1><blockquote><pre>'
            );
            $detalleExtendido = preg_replace('/\son\w+\s*=\s*"[^"]*"/i', '', $detalleExtendido);
            $detalleExtendido = preg_replace('/\son\w+\s*=\s*\'[^\']*\'/i', '', $detalleExtendido);
            $detalleExtendido = preg_replace('/(href\s*=\s*["\'])\s*javascript:[^"\']*/i', '$1', $detalleExtendido);
        }

        $cotizacion->lineas()->create([
            'descripcion' => $linea['descripcion'],
            'detalle_extendido' => $detalleExtendido !== '' ? $detalleExtendido : null,
            'unidad' => $linea['unidad'],
            'cantidad' => $linea['cantidad'],
            'valor_unitario' => $linea['valor_unitario'],
            'subtotal_calculado' => $linea['cantidad'] * $linea['valor_unitario'],
            'orden' => $indice,
        ]);
    }

    if ($this->nuevoAdjunto) {
        $cotizacion->update(['archivo_adjunto' => $this->nuevoAdjunto->store('cotizaciones', 'local')]);
    }

    $cotizacion->recalcularTotales();

    session()->flash('status', $this->cotizacion ? 'Cotización actualizada.' : 'Cotización creada.');

    $this->redirectRoute('gestion.cotizaciones.show', $cotizacion, navigate: true);
};

?>

<div>
    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h1 class="text-lg font-semibold text-gray-900">
                        {{ $cotizacion ? 'Editar cotización '.$cotizacion->numero_cotizacion : 'Nueva cotización' }}
                    </h1>

                    @if (session('error'))
                        <div class="mt-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form wire:submit="guardar" class="mt-6 space-y-6">
                        {{-- Cliente --}}
                        <div class="relative">
                            <x-input-label for="clienteSearch" value="Cliente" />

                            @if ($cliente_id)
                                <div class="mt-1 flex items-center justify-between rounded-md border border-gray-300 px-3 py-2">
                                    <span class="text-sm text-gray-900">{{ $clienteSearch }}</span>
                                    <button type="button" wire:click="quitarCliente" class="text-xs text-teal-600 hover:text-teal-500">
                                        Cambiar
                                    </button>
                                </div>
                            @else
                                <x-text-input id="clienteSearch" wire:model.live.debounce.300ms="clienteSearch" type="text"
                                    placeholder="Buscar cliente por nombre..." class="mt-1 block w-full" autocomplete="off" />

                                @if ($this->clientesSugeridos->isNotEmpty())
                                    <div class="absolute z-10 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg">
                                        @foreach ($this->clientesSugeridos as $sugerido)
                                            <button type="button" wire:click="seleccionarCliente({{ $sugerido->id }})"
                                                wire:key="sugerido-{{ $sugerido->id }}"
                                                class="block w-full px-3 py-2 text-left text-sm hover:bg-gray-50">
                                                {{ $sugerido->nombre }}
                                                @if ($sugerido->nombre_empresa)
                                                    <span class="text-gray-500">— {{ $sugerido->nombre_empresa }}</span>
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                            <x-input-error :messages="$errors->get('cliente_id')" class="mt-2" />
                        </div>

                        {{-- Descripción --}}
                        <div>
                            <x-input-label for="descripcion" value="Descripción de la cotización" />
                            <x-text-input id="descripcion" wire:model="descripcion" type="text" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                        </div>

                        {{-- Fechas --}}
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <x-input-label for="fecha_emision" value="Fecha de emisión" />
                                <x-text-input id="fecha_emision" wire:model="fecha_emision" type="date" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('fecha_emision')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="fecha_vencimiento" value="Fecha de vencimiento" />
                                <x-text-input id="fecha_vencimiento" wire:model="fecha_vencimiento" type="date" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('fecha_vencimiento')" class="mt-2" />
                            </div>
                        </div>

                        {{-- Líneas de detalle --}}
                        <div>
                            <x-input-label value="Líneas de detalle" />

                            <div class="mt-2 space-y-3">
                                @foreach ($lineas as $indice => $linea)
                                    <div class="rounded-md border border-gray-200 p-3" wire:key="linea-{{ $linea['_key'] ?? $indice }}">
                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
                                            <div class="sm:flex-1">
                                                <input type="text" wire:model="lineas.{{ $indice }}.descripcion"
                                                    placeholder="Nombre del producto o servicio"
                                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                                <x-input-error :messages="$errors->get('lineas.'.$indice.'.descripcion')" class="mt-1" />
                                                <label class="mt-1 flex items-center gap-1.5 text-xs text-gray-600">
                                                    <input type="checkbox" wire:model.live="lineas.{{ $indice }}.mostrar_detalle"
                                                        class="rounded border-gray-300 text-teal-600 shadow-sm focus:ring-teal-500">
                                                    Agregar detalle
                                                </label>
                                            </div>
                                            <div class="sm:w-20">
                                                <input type="text" wire:model="lineas.{{ $indice }}.unidad" placeholder="Unidad"
                                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                                <x-input-error :messages="$errors->get('lineas.'.$indice.'.unidad')" class="mt-1" />
                                            </div>
                                            <div class="sm:w-24">
                                                <input type="number" step="0.01" min="0"
                                                    wire:model.live="lineas.{{ $indice }}.cantidad" placeholder="Cantidad"
                                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                                <x-input-error :messages="$errors->get('lineas.'.$indice.'.cantidad')" class="mt-1" />
                                            </div>
                                            <div class="sm:w-36">
                                                <input type="number" step="0.01" min="0"
                                                    wire:model.live="lineas.{{ $indice }}.valor_unitario" placeholder="Valor unitario"
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
                                                    class="text-sm text-red-600 hover:text-red-500">
                                                    Quitar
                                                </button>
                                            </div>
                                        </div>

                                        @if ($linea['mostrar_detalle'] ?? false)
                                            <div class="mt-3" wire:ignore>
                                                <input type="hidden" id="trix-input-{{ $linea['_key'] ?? $indice }}"
                                                    value="{{ $linea['detalle_extendido'] ?? '' }}">
                                                <trix-editor input="trix-input-{{ $linea['_key'] ?? $indice }}"
                                                    x-on:trix-change="$wire.set('lineas.{{ $indice }}.detalle_extendido', $event.target.value)"></trix-editor>
                                            </div>
                                            <x-input-error :messages="$errors->get('lineas.'.$indice.'.detalle_extendido')" class="mt-1" />
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <button type="button" wire:click="agregarLinea"
                                class="mt-3 text-sm font-medium text-teal-600 hover:text-teal-500">
                                + Agregar línea
                            </button>
                            <x-input-error :messages="$errors->get('lineas')" class="mt-2" />
                        </div>

                        {{-- Descuento e ID Mercado Público --}}
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <x-input-label for="descuento_porcentaje" value="Descuento (%)" />
                                <input id="descuento_porcentaje" type="number" step="0.01" min="0" max="100"
                                    wire:model.live="descuento_porcentaje"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                                <x-input-error :messages="$errors->get('descuento_porcentaje')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="id_mercado_publico" value="ID Mercado Público (opcional)" />
                                <x-text-input id="id_mercado_publico" wire:model="id_mercado_publico" type="text"
                                    class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('id_mercado_publico')" class="mt-2" />
                            </div>
                        </div>

                        {{-- Adjunto --}}
                        <div>
                            <x-input-label for="nuevoAdjunto" value="Especificaciones técnicas (opcional, PDF/Word/Excel)" />
                            <input id="nuevoAdjunto" type="file" wire:model="nuevoAdjunto"
                                class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200">
                            <x-input-error :messages="$errors->get('nuevoAdjunto')" class="mt-2" />
                            @if ($cotizacion?->archivo_adjunto)
                                <p class="mt-1 text-xs text-gray-500">Ya existe un archivo adjunto. Subir uno nuevo lo reemplaza.</p>
                            @endif
                        </div>

                        {{-- Resumen --}}
                        <div class="rounded-md bg-gray-50 p-4">
                            <dl class="space-y-1 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Subtotal</dt>
                                    <dd class="text-gray-900">${{ number_format($this->resumen['subtotal'], 0, ',', '.') }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Base gravada (tras descuento)</dt>
                                    <dd class="text-gray-900">${{ number_format($this->resumen['baseGravada'], 0, ',', '.') }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">IVA (19%)</dt>
                                    <dd class="text-gray-900">${{ number_format($this->resumen['iva'], 0, ',', '.') }}</dd>
                                </div>
                                <div class="flex justify-between border-t border-gray-200 pt-1 font-semibold">
                                    <dt class="text-gray-900">Total</dt>
                                    <dd class="text-gray-900">${{ number_format($this->resumen['total'], 0, ',', '.') }}</dd>
                                </div>
                            </dl>
                        </div>

                        {{-- Acciones --}}
                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ $cotizacion ? route('gestion.cotizaciones.show', $cotizacion) : route('gestion.cotizaciones.index') }}"
                                wire:navigate class="text-sm text-gray-600 hover:text-gray-900">
                                Cancelar
                            </a>
                            <x-primary-button>
                                {{ $cotizacion ? 'Guardar cambios' : 'Guardar borrador' }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
