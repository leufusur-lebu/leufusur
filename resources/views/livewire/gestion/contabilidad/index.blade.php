<?php

use App\Enums\EstadoCotizacion;
use App\Enums\TipoDocumentoGasto;
use App\Models\Cotizacion;
use App\Models\FacturaVenta;
use App\Models\Gasto;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

use function Livewire\Volt\{computed, layout, state, usesFileUploads};

usesFileUploads();
layout('layouts.gestion');

state([
    // Modal de gastos generales
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

/**
 * Resumen de IVA por período mensual con arrastre de remanente (lógica F29):
 * si el crédito supera al débito en un mes, el excedente se acumula como remanente
 * a favor y se descuenta en los meses siguientes antes de determinar el IVA a pagar.
 */
$resumenIva = computed(function () {
    $debitosPorMes = FacturaVenta::all()->groupBy(fn ($f) => $f->fecha_emision->format('Y-m'));
    $creditosPorMes = Gasto::all()->groupBy(fn ($g) => $g->fecha_gasto->format('Y-m'));

    $periodos = $debitosPorMes->keys()
        ->merge($creditosPorMes->keys())
        ->unique()
        ->sort()
        ->values();

    $remanente = 0.0;
    $filas = [];

    foreach ($periodos as $periodo) {
        $debito = (float) ($debitosPorMes[$periodo] ?? collect())->sum('iva');
        $credito = (float) ($creditosPorMes[$periodo] ?? collect())->sum('iva');

        $posicion = $debito - $credito - $remanente;
        $aPagar = max($posicion, 0.0);
        $remanente = $posicion < 0 ? -$posicion : 0.0;

        $filas[] = [
            'periodo' => Carbon::createFromFormat('Y-m', $periodo)->startOfMonth(),
            'debito' => $debito,
            'credito' => $credito,
            'aPagar' => $aPagar,
            'remanente' => $remanente,
        ];
    }

    // Más reciente primero.
    return array_reverse($filas);
});

$totales = computed(function () {
    $filas = $this->resumenIva;
    $actual = $filas[0] ?? null;

    return [
        'debito' => array_sum(array_column($filas, 'debito')),
        'credito' => array_sum(array_column($filas, 'credito')),
        'aPagarActual' => $actual['aPagar'] ?? 0.0,
        'remanenteActual' => $actual['remanente'] ?? 0.0,
        'periodoActual' => $actual['periodo'] ?? null,
    ];
});

/**
 * Rentabilidad por proyecto: ingreso facturado (o cotizado si aún no se factura)
 * menos los gastos netos del proyecto.
 */
$rentabilidad = computed(function () {
    return Cotizacion::where('estado', EstadoCotizacion::Aprobada)
        ->with(['facturaVenta', 'gastos', 'cliente'])
        ->orderByDesc('aprobada_en')
        ->get()
        ->map(function (Cotizacion $cotizacion) {
            $ingresoNeto = $cotizacion->facturaVenta
                ? (float) $cotizacion->facturaVenta->monto_neto
                : (float) $cotizacion->base_gravada_calculada;
            $gastoNeto = (float) $cotizacion->gastos->sum('monto_neto');
            $margen = $ingresoNeto - $gastoNeto;

            return [
                'cotizacion' => $cotizacion,
                'ingresoNeto' => $ingresoNeto,
                'gastoNeto' => $gastoNeto,
                'margen' => $margen,
                'margenPorcentaje' => $ingresoNeto > 0 ? ($margen / $ingresoNeto) * 100 : 0.0,
                'facturada' => (bool) $cotizacion->facturaVenta,
            ];
        });
});

$gastosGenerales = computed(fn () => Gasto::whereNull('cotizacion_id')->latest('fecha_gasto')->get());

$updatedMontoNeto = function () {
    $this->iva = round(((float) $this->monto_neto) * Cotizacion::IVA);
};

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

    $this->dispatch('open-modal', 'gasto-general-modal');
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

    $this->dispatch('open-modal', 'gasto-general-modal');
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

    $datos['total_calculado'] = $datos['monto_neto'] + $datos['iva'];

    // Gasto general: sin cotización asociada.
    $gasto = $this->gastoId
        ? Gasto::findOrFail($this->gastoId)
        : new Gasto(['cotizacion_id' => null]);

    $gasto->fill($datos);

    if ($this->nuevoComprobante) {
        $gasto->archivo_comprobante = $this->nuevoComprobante->store('gastos', 'local');
    }

    $gasto->save();

    unset($this->gastosGenerales, $this->resumenIva, $this->totales);

    session()->flash('status', $this->gastoId ? 'Gasto general actualizado.' : 'Gasto general agregado.');

    $this->dispatch('close-modal', 'gasto-general-modal');
};

$eliminar = function (Gasto $gasto) {
    $gasto->delete();

    unset($this->gastosGenerales, $this->resumenIva, $this->totales);

    session()->flash('status', 'Gasto general eliminado.');
};

?>

<div>
    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            <div>
                <h1 class="text-lg font-semibold text-gray-900">Contabilidad</h1>
                <p class="mt-1 text-sm text-gray-500">IVA a pagar al SII y rentabilidad de los proyectos.</p>
            </div>

            {{-- Tarjetas resumen --}}
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-gray-500">IVA débito acumulado</p>
                    <p class="mt-2 text-xl font-semibold text-gray-900">${{ number_format($this->totales['debito'], 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-gray-400">Ventas emitidas</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-gray-500">IVA crédito acumulado</p>
                    <p class="mt-2 text-xl font-semibold text-gray-900">${{ number_format($this->totales['credito'], 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-gray-400">Compras y gastos</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-inset ring-teal-100">
                    <p class="text-xs uppercase tracking-wide text-gray-500">IVA a pagar (período actual)</p>
                    <p class="mt-2 text-xl font-semibold text-teal-700">${{ number_format($this->totales['aPagarActual'], 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-gray-400">
                        @if ($this->totales['periodoActual'])
                            {{ ucfirst($this->totales['periodoActual']->translatedFormat('F Y')) }}
                        @else
                            Sin movimientos
                        @endif
                    </p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Remanente a favor</p>
                    <p class="mt-2 text-xl font-semibold text-gray-900">${{ number_format($this->totales['remanenteActual'], 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-gray-400">Crédito arrastrado</p>
                </div>
            </div>

            {{-- IVA por mes --}}
            <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-gray-900">IVA por período (F29)</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Período</th>
                                <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wide text-gray-500">IVA débito</th>
                                <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wide text-gray-500">IVA crédito</th>
                                <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Remanente</th>
                                <th class="py-2 pl-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">A pagar SII</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($this->resumenIva as $fila)
                                <tr wire:key="periodo-{{ $fila['periodo']->format('Y-m') }}">
                                    <td class="py-2 text-sm font-medium text-gray-900">{{ ucfirst($fila['periodo']->translatedFormat('F Y')) }}</td>
                                    <td class="px-3 py-2 text-right text-sm text-gray-500">${{ number_format($fila['debito'], 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right text-sm text-gray-500">${{ number_format($fila['credito'], 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right text-sm text-gray-500">${{ number_format($fila['remanente'], 0, ',', '.') }}</td>
                                    <td class="py-2 pl-3 text-right text-sm font-semibold {{ $fila['aPagar'] > 0 ? 'text-teal-700' : 'text-gray-400' }}">
                                        ${{ number_format($fila['aPagar'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-sm text-gray-500">Aún no hay movimientos con IVA.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Rentabilidad por proyecto --}}
            <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-gray-900">Rentabilidad por proyecto</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Cotización</th>
                                <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Cliente</th>
                                <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Ingreso neto</th>
                                <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Gasto neto</th>
                                <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Margen</th>
                                <th class="py-2 pl-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">%</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($this->rentabilidad as $fila)
                                <tr wire:key="rent-{{ $fila['cotizacion']->id }}">
                                    <td class="py-2 text-sm">
                                        <a href="{{ route('gestion.cotizaciones.show', $fila['cotizacion']) }}" wire:navigate
                                            class="font-medium text-teal-600 hover:text-teal-500">{{ $fila['cotizacion']->numero_cotizacion }}</a>
                                        @unless ($fila['facturada'])
                                            <span class="ml-1 text-xs text-amber-600">(sin facturar)</span>
                                        @endunless
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-500">{{ $fila['cotizacion']->cliente->nombre }}</td>
                                    <td class="px-3 py-2 text-right text-sm text-gray-500">${{ number_format($fila['ingresoNeto'], 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right text-sm text-gray-500">${{ number_format($fila['gastoNeto'], 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right text-sm font-semibold {{ $fila['margen'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                        ${{ number_format($fila['margen'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-2 pl-3 text-right text-sm text-gray-500">{{ number_format($fila['margenPorcentaje'], 1, ',', '.') }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-sm text-gray-500">Aún no hay proyectos aprobados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Gastos generales --}}
            <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Gastos generales</h2>
                        <p class="mt-1 text-xs text-gray-500">Gastos de la empresa sin proyecto (arriendo, servicios, etc.). Suman crédito fiscal.</p>
                    </div>
                    <button type="button" wire:click="prepararNuevo"
                        class="rounded-md bg-teal-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-500">
                        + Agregar gasto
                    </button>
                </div>

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
                            @forelse ($this->gastosGenerales as $gasto)
                                <tr wire:key="gasto-general-{{ $gasto->id }}">
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
                                            wire:confirm="¿Eliminar este gasto general? Esta acción no se puede deshacer."
                                            class="ml-2 font-medium text-red-600 hover:text-red-500">
                                            Eliminar
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-sm text-gray-500">Aún no hay gastos generales registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Modal gasto general --}}
            <x-modal name="gasto-general-modal" :show="$errors->isNotEmpty()">
                <form wire:submit="guardar" class="p-6">
                    <h2 class="text-lg font-medium text-gray-900">
                        {{ $gastoId ? 'Editar gasto general' : 'Agregar gasto general' }}
                    </h2>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="fecha_gasto_general" value="Fecha del gasto" />
                            <x-text-input id="fecha_gasto_general" wire:model="fecha_gasto" type="date" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('fecha_gasto')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="tipo_general" value="Tipo de documento" />
                            <select id="tipo_general" wire:model="tipo"
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
                            <x-input-label for="numero_documento_general" value="N° de documento" />
                            <x-text-input id="numero_documento_general" wire:model="numero_documento" type="text" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('numero_documento')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="proveedor_general" value="Proveedor" />
                            <x-text-input id="proveedor_general" wire:model="proveedor" type="text" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('proveedor')" class="mt-2" />
                        </div>
                    </div>

                    <div class="mt-4">
                        <x-input-label for="descripcion_general" value="Descripción" />
                        <x-text-input id="descripcion_general" wire:model="descripcion" type="text" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="monto_neto_general" value="Monto neto" />
                            <input id="monto_neto_general" type="number" step="0.01" min="0" wire:model.live="monto_neto"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                            <x-input-error :messages="$errors->get('monto_neto')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="iva_general" value="IVA (autocalculado, editable)" />
                            <input id="iva_general" type="number" step="0.01" min="0" wire:model="iva"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                            <x-input-error :messages="$errors->get('iva')" class="mt-2" />
                        </div>
                    </div>

                    <div class="mt-4">
                        <x-input-label for="nuevoComprobante_general" value="Comprobante (opcional, PDF o imagen)" />
                        <input id="nuevoComprobante_general" type="file" wire:model="nuevoComprobante"
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
    </div>
</div>
