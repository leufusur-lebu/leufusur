<?php

use App\Enums\TipoDocumentoGasto;
use App\Models\Cotizacion;
use App\Models\Gasto;
use Illuminate\Validation\Rule;

use function Livewire\Volt\{computed, mount, state, usesFileUploads};

usesFileUploads();

state([
    'cotizacion' => null,
    'gastoId' => null,
    'fecha_gasto' => fn () => today()->toDateString(),
    'tipo' => TipoDocumentoGasto::Factura->value,
    'numero_documento' => '',
    'proveedor' => '',
    'descripcion' => '',
    'monto_neto' => 0,
    'iva' => 0,
    'nuevoComprobante' => null,
]);

mount(function (Cotizacion $cotizacion) {
    $this->cotizacion = $cotizacion;
});

$gastos = computed(fn () => $this->cotizacion->gastos()->latest('fecha_gasto')->get());

$resumen = computed(function () {
    $gastos = $this->gastos;
    $totalGastos = $gastos->sum('total_calculado');

    return [
        'totalNeto' => $gastos->sum('monto_neto'),
        'totalIva' => $gastos->sum('iva'),
        'totalGastos' => $totalGastos,
        'margen' => (float) $this->cotizacion->total_calculado - $totalGastos,
    ];
});

$prepararNuevo = function () {
    $this->gastoId = null;
    $this->fecha_gasto = today()->toDateString();
    $this->tipo = TipoDocumentoGasto::Factura->value;
    $this->numero_documento = '';
    $this->proveedor = '';
    $this->descripcion = '';
    $this->monto_neto = 0;
    $this->iva = 0;
    $this->nuevoComprobante = null;
    $this->resetValidation();

    $this->dispatch('open-modal', 'gasto-modal');
};

$prepararEdicion = function (Gasto $gasto) {
    $this->gastoId = $gasto->id;
    $this->fecha_gasto = $gasto->fecha_gasto->toDateString();
    $this->tipo = $gasto->tipo->value;
    $this->numero_documento = $gasto->numero_documento;
    $this->proveedor = $gasto->proveedor;
    $this->descripcion = $gasto->descripcion;
    $this->monto_neto = (float) $gasto->monto_neto;
    $this->iva = (float) $gasto->iva;
    $this->nuevoComprobante = null;
    $this->resetValidation();

    $this->dispatch('open-modal', 'gasto-modal');
};

$guardar = function () {
    $datos = $this->validate([
        'fecha_gasto' => ['required', 'date'],
        'tipo' => ['required', Rule::enum(TipoDocumentoGasto::class)],
        'numero_documento' => ['required', 'string', 'max:255'],
        'proveedor' => ['required', 'string', 'max:255'],
        'descripcion' => ['required', 'string', 'max:255'],
        'monto_neto' => ['required', 'numeric', 'min:0'],
        'iva' => ['required', 'numeric', 'min:0'],
        'nuevoComprobante' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
    ]);

    // Respaldo por si el IVA no llegó calculado desde el cliente.
    if ((float) $datos['iva'] <= 0 && (float) $datos['monto_neto'] > 0) {
        $datos['iva'] = round($datos['monto_neto'] * Cotizacion::IVA);
    }

    $datos['total_calculado'] = $datos['monto_neto'] + $datos['iva'];

    $gasto = $this->gastoId
        ? Gasto::findOrFail($this->gastoId)
        : new Gasto(['cotizacion_id' => $this->cotizacion->id]);

    $gasto->fill($datos);

    if ($this->nuevoComprobante) {
        $gasto->archivo_comprobante = $this->nuevoComprobante->store('gastos', 'local');
    }

    $gasto->save();

    unset($this->gastos, $this->resumen);

    session()->flash('status', $this->gastoId ? 'Gasto actualizado.' : 'Gasto agregado.');

    $this->dispatch('close-modal', 'gasto-modal');
};

$eliminar = function (Gasto $gasto) {
    $gasto->delete();

    unset($this->gastos, $this->resumen);

    session()->flash('status', 'Gasto eliminado.');
};

?>

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-gray-900">Gastos</h2>
        <button type="button" wire:click="prepararNuevo"
            class="rounded-md bg-teal-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-500">
            + Agregar gasto
        </button>
    </div>

    {{-- Resumen comparativo --}}
    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-md bg-gray-50 p-3">
            <p class="text-xs text-gray-500">Total neto</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">${{ number_format($this->resumen['totalNeto'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-md bg-gray-50 p-3">
            <p class="text-xs text-gray-500">Total IVA</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">${{ number_format($this->resumen['totalIva'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-md bg-gray-50 p-3">
            <p class="text-xs text-gray-500">Total gastos</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">${{ number_format($this->resumen['totalGastos'], 0, ',', '.') }}</p>
        </div>
        <div @class([
            'rounded-md p-3',
            'bg-green-50' => $this->resumen['margen'] >= 0,
            'bg-red-50' => $this->resumen['margen'] < 0,
        ])>
            <p class="text-xs text-gray-500">Margen vs. cotización</p>
            <p @class([
                'mt-1 text-sm font-semibold',
                'text-green-700' => $this->resumen['margen'] >= 0,
                'text-red-700' => $this->resumen['margen'] < 0,
            ])>${{ number_format($this->resumen['margen'], 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Tabla de gastos --}}
    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Fecha</th>
                    <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Tipo</th>
                    <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Documento</th>
                    <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Proveedor</th>
                    <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Total</th>
                    <th class="py-2 pl-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($this->gastos as $gasto)
                    <tr wire:key="gasto-{{ $gasto->id }}">
                        <td class="py-2 text-sm text-gray-500">{{ $gasto->fecha_gasto->format('d-m-Y') }}</td>
                        <td class="px-3 py-2 text-sm text-gray-500">{{ $gasto->tipo->label() }}</td>
                        <td class="px-3 py-2 text-sm text-gray-500">{{ $gasto->numero_documento }}</td>
                        <td class="px-3 py-2 text-sm text-gray-500">{{ $gasto->proveedor }}</td>
                        <td class="px-3 py-2 text-right text-sm text-gray-900">${{ number_format($gasto->total_calculado, 0, ',', '.') }}</td>
                        <td class="py-2 pl-3 text-right text-sm">
                            @if ($gasto->archivo_comprobante)
                                <a href="{{ route('gestion.gastos.comprobante', $gasto) }}" class="font-medium text-gray-600 hover:text-gray-900">📎</a>
                            @endif
                            <button type="button" wire:click="prepararEdicion({{ $gasto->id }})" class="ml-2 font-medium text-teal-600 hover:text-teal-500">
                                Editar
                            </button>
                            <button type="button" wire:click="eliminar({{ $gasto->id }})"
                                wire:confirm="¿Eliminar este gasto? Esta acción no se puede deshacer."
                                class="ml-2 font-medium text-red-600 hover:text-red-500">
                                Eliminar
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-sm text-gray-500">Aún no hay gastos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal agregar/editar --}}
    <x-modal name="gasto-modal" :show="$errors->isNotEmpty()">
        <form wire:submit="guardar" class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                {{ $gastoId ? 'Editar gasto' : 'Agregar gasto' }}
            </h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="fecha_gasto" value="Fecha del gasto" />
                    <x-text-input id="fecha_gasto" wire:model="fecha_gasto" type="date" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('fecha_gasto')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="tipo" value="Tipo de documento" />
                    <select id="tipo" wire:model="tipo"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                        @foreach (TipoDocumentoGasto::cases() as $opcion)
                            <option value="{{ $opcion->value }}">{{ $opcion->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('tipo')" class="mt-2" />
                </div>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="numero_documento" value="N° de documento" />
                    <x-text-input id="numero_documento" wire:model="numero_documento" type="text" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('numero_documento')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="proveedor" value="Proveedor" />
                    <x-text-input id="proveedor" wire:model="proveedor" type="text" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('proveedor')" class="mt-2" />
                </div>
            </div>

            <div class="mt-4">
                <x-input-label for="descripcion" value="Descripción" />
                <x-text-input id="descripcion" wire:model="descripcion" type="text" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
            </div>

            {{-- IVA calculado en el cliente (Alpine) para no cerrar el modal:
                 un wire:model.live dispararía un re-render por tecla que reinicia el modal de Breeze. --}}
            <div class="mt-4 grid gap-4 sm:grid-cols-2" x-data="{
                neto: @entangle('monto_neto'),
                iva: @entangle('iva'),
                recalcularIva() { this.iva = Math.round((parseFloat(this.neto) || 0) * 0.19) },
            }">
                <div>
                    <x-input-label for="monto_neto" value="Monto neto" />
                    <input id="monto_neto" type="number" step="0.01" min="0" x-model="neto" x-on:input="recalcularIva()"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                    <x-input-error :messages="$errors->get('monto_neto')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="iva" value="IVA (autocalculado, editable)" />
                    <input id="iva" type="number" step="0.01" min="0" x-model="iva"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                    <x-input-error :messages="$errors->get('iva')" class="mt-2" />
                </div>
            </div>

            <div class="mt-4">
                <x-input-label for="nuevoComprobante" value="Comprobante (opcional, PDF o imagen)" />
                <input id="nuevoComprobante" type="file" wire:model="nuevoComprobante"
                    class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200">
                <x-input-error :messages="$errors->get('nuevoComprobante')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>
                <x-primary-button>
                    {{ $gastoId ? 'Guardar cambios' : 'Agregar gasto' }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
