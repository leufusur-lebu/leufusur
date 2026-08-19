<?php

use App\Models\Cotizacion;

use function Livewire\Volt\{computed, mount, state, usesFileUploads};

usesFileUploads();

// Componente reutilizable: la factura de venta de un "padre" (Cotización o Proyecto).
// El padre solo necesita exponer la relación facturaVenta().
state([
    'parent' => null,
    'numero_factura' => '',
    'fecha_emision' => fn () => today()->toDateString(),
    'descripcion' => '',
    'monto_neto' => 0,
    'iva' => 0,
    'nuevoArchivo' => null,
]);

mount(function ($parent) {
    $this->parent = $parent;

    if ($factura = $parent->facturaVenta) {
        $this->numero_factura = $factura->numero_factura;
        $this->fecha_emision = $factura->fecha_emision->toDateString();
        $this->descripcion = (string) $factura->descripcion;
        $this->monto_neto = (float) $factura->monto_neto;
        $this->iva = (float) $factura->iva;
    } else {
        // Prefill con los montos cotizados si el padre es una cotización (normalmente se
        // factura lo cotizado, pero es editable). Un proyecto directo parte en cero.
        $this->monto_neto = (float) ($parent->base_gravada_calculada ?? 0);
        $this->iva = (float) ($parent->iva_calculado ?? 0);
    }
});

$factura = computed(fn () => $this->parent->facturaVenta);

$updatedMontoNeto = function () {
    $this->iva = round(((float) $this->monto_neto) * Cotizacion::IVA);
};

$guardar = function () {
    $datos = $this->validate([
        'numero_factura' => ['required', 'string', 'max:255'],
        'fecha_emision' => ['required', 'date'],
        'descripcion' => ['nullable', 'string', 'max:255'],
        'monto_neto' => ['required', 'numeric', 'min:0'],
        'iva' => ['required', 'numeric', 'min:0'],
        'nuevoArchivo' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
    ]);

    $datos['total_calculado'] = $datos['monto_neto'] + $datos['iva'];

    $factura = $this->parent->facturaVenta ?? $this->parent->facturaVenta()->make();
    $factura->fill($datos);

    if ($this->nuevoArchivo) {
        $factura->archivo_pdf = $this->nuevoArchivo->store('facturas-venta', 'local');
    }

    $factura->save();

    $this->nuevoArchivo = null;
    unset($this->factura);
    $this->parent->refresh();

    session()->flash('status', 'Factura de venta guardada.');
};

$eliminar = function () {
    $this->parent->facturaVenta?->delete();

    unset($this->factura);
    $this->parent->refresh();

    $this->numero_factura = '';
    $this->fecha_emision = today()->toDateString();
    $this->descripcion = '';
    $this->monto_neto = (float) ($this->parent->base_gravada_calculada ?? 0);
    $this->iva = (float) ($this->parent->iva_calculado ?? 0);

    session()->flash('status', 'Factura de venta eliminada.');
};

?>

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-gray-900">Factura de venta (Leufu Sur)</h2>
        @if ($this->factura)
            <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">
                Emitida
            </span>
        @else
            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                Pendiente
            </span>
        @endif
    </div>

    <p class="mt-1 text-xs text-gray-500">
        Documento con el que Leufu Sur cobra este proyecto. Determina el IVA débito del mes de emisión.
    </p>

    <form wire:submit="guardar" class="mt-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="numero_factura" value="N° de factura (folio)" />
                <x-text-input id="numero_factura" wire:model="numero_factura" type="text" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('numero_factura')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="fecha_emision" value="Fecha de emisión" />
                <x-text-input id="fecha_emision" wire:model="fecha_emision" type="date" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('fecha_emision')" class="mt-2" />
            </div>
        </div>

        <div class="mt-4">
            <x-input-label for="descripcion" value="Descripción (opcional)" />
            <x-text-input id="descripcion" wire:model="descripcion" type="text" class="mt-1 block w-full" />
            <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="monto_neto_venta" value="Monto neto" />
                <input id="monto_neto_venta" type="number" step="0.01" min="0" wire:model.live="monto_neto"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                <x-input-error :messages="$errors->get('monto_neto')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="iva_venta" value="IVA (autocalculado, editable)" />
                <input id="iva_venta" type="number" step="0.01" min="0" wire:model="iva"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                <x-input-error :messages="$errors->get('iva')" class="mt-2" />
            </div>
        </div>

        <div class="mt-4">
            <x-input-label for="nuevoArchivo" value="Archivo de la factura (opcional, PDF o imagen)" />
            <input id="nuevoArchivo" type="file" wire:model="nuevoArchivo"
                class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200">
            <x-input-error :messages="$errors->get('nuevoArchivo')" class="mt-2" />
            @if ($this->factura?->archivo_pdf)
                <a href="{{ route('gestion.facturas-venta.archivo', $this->factura) }}"
                    class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-teal-600 hover:text-teal-500">
                    📎 Ver archivo actual
                </a>
            @endif
        </div>

        <div class="mt-6 flex items-center justify-between">
            <div class="text-sm text-gray-500">
                Total: <span class="font-semibold text-gray-900">${{ number_format((float) $monto_neto + (float) $iva, 0, ',', '.') }}</span>
            </div>
            <div class="flex gap-3">
                @if ($this->factura)
                    <button type="button" wire:click="eliminar"
                        wire:confirm="¿Eliminar la factura de venta de este proyecto?"
                        class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-red-700 shadow-sm ring-1 ring-inset ring-red-300 hover:bg-red-50">
                        Eliminar
                    </button>
                @endif
                <x-primary-button>
                    {{ $this->factura ? 'Guardar cambios' : 'Registrar factura' }}
                </x-primary-button>
            </div>
        </div>
    </form>
</div>
