<?php

use App\Enums\TipoMovimiento;
use App\Models\FacturaVenta;
use App\Models\Gasto;
use App\Models\MovimientoBancario;
use App\Models\Sueldo;
use Illuminate\Support\Carbon;

use function Livewire\Volt\{computed, layout, state, usesFileUploads};

usesFileUploads();
layout('layouts.gestion');

state([
    'filtro' => 'todos', // todos | conciliados | pendientes

    // Formulario de movimiento (alta/edición)
    'movId' => null,
    'fecha' => fn () => today()->toDateString(),
    'glosa' => '',
    'tipo' => TipoMovimiento::Cargo->value,
    'monto' => 0,
    'conciliado' => false,

    // Importación de cartola
    'archivoCsv' => null,
    'preview' => [],       // filas parseadas para previsualizar
    'previewErrores' => 0, // filas descartadas

    // Vinculación al sistema
    'movVinculandoId' => null,
]);

$movimientos = computed(function () {
    $query = MovimientoBancario::with('conciliable')->orderByDesc('fecha')->orderByDesc('id');

    if ($this->filtro === 'conciliados') {
        $query->where('conciliado', true);
    } elseif ($this->filtro === 'pendientes') {
        $query->where('conciliado', false);
    }

    return $query->get();
});

$resumen = computed(function () {
    $todos = MovimientoBancario::all();

    return [
        'abonos' => (float) $todos->where('tipo', TipoMovimiento::Abono)->sum('monto'),
        'cargos' => (float) $todos->where('tipo', TipoMovimiento::Cargo)->sum('monto'),
        'saldo' => (float) $todos->sum(fn (MovimientoBancario $m) => $m->montoConSigno()),
        'pendientes' => $todos->where('conciliado', false)->count(),
    ];
});

// Saldo corriente por movimiento (acumulado por fecha e id ascendente).
$saldos = computed(function () {
    $acumulado = 0.0;
    $mapa = [];

    foreach (MovimientoBancario::orderBy('fecha')->orderBy('id')->get() as $m) {
        $acumulado += $m->montoConSigno();
        $mapa[$m->id] = $acumulado;
    }

    return $mapa;
});

$prepararNuevo = function () {
    $this->reset(['movId', 'glosa', 'monto']);
    $this->fecha = today()->toDateString();
    $this->tipo = TipoMovimiento::Cargo->value;
    $this->conciliado = false;
    $this->resetValidation();

    $this->dispatch('open-modal', 'movimiento-modal');
};

$prepararEdicion = function (MovimientoBancario $movimiento) {
    $this->movId = $movimiento->id;
    $this->fecha = $movimiento->fecha->toDateString();
    $this->glosa = $movimiento->glosa;
    $this->tipo = $movimiento->tipo->value;
    $this->monto = (float) $movimiento->monto;
    $this->conciliado = $movimiento->conciliado;
    $this->resetValidation();

    $this->dispatch('open-modal', 'movimiento-modal');
};

$guardar = function () {
    $datos = $this->validate([
        'fecha' => ['required', 'date'],
        'glosa' => ['required', 'string', 'max:255'],
        'tipo' => ['required', \Illuminate\Validation\Rule::enum(TipoMovimiento::class)],
        'monto' => ['required', 'numeric', 'min:1'],
    ]);

    $datos['conciliado'] = (bool) $this->conciliado;

    $mov = $this->movId ? MovimientoBancario::findOrFail($this->movId) : new MovimientoBancario;
    $mov->fill($datos)->save();

    $this->reset(['movId', 'glosa', 'monto']);
    $this->fecha = today()->toDateString();
    $this->tipo = TipoMovimiento::Cargo->value;
    $this->conciliado = false;
    unset($this->movimientos, $this->resumen, $this->saldos);

    session()->flash('status', $this->movId ? 'Movimiento actualizado.' : 'Movimiento registrado.');

    $this->dispatch('close-modal', 'movimiento-modal');
};

$eliminar = function (MovimientoBancario $movimiento) {
    $movimiento->delete();

    unset($this->movimientos, $this->resumen, $this->saldos);

    session()->flash('status', 'Movimiento eliminado.');
};

$toggleConciliado = function (MovimientoBancario $movimiento) {
    $movimiento->update(['conciliado' => ! $movimiento->conciliado]);

    unset($this->movimientos, $this->resumen);
};

// --- Importación de cartola CSV ---

$updatedArchivoCsv = function () {
    $this->validate(['archivoCsv' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

    $contenido = file_get_contents($this->archivoCsv->getRealPath());
    // Detectar delimitador y normalizar saltos de línea.
    $lineas = preg_split('/\r\n|\r|\n/', trim($contenido));
    $primera = $lineas[0] ?? '';
    $delim = substr_count($primera, ';') > substr_count($primera, ',') ? ';' : ',';

    $filas = [];
    $errores = 0;
    $mapa = null;

    foreach ($lineas as $i => $linea) {
        if (trim($linea) === '') {
            continue;
        }
        $cols = str_getcsv($linea, $delim);

        // La primera fila con "fecha" en algún encabezado define el mapeo de columnas.
        if ($mapa === null && $this->pareceEncabezado($cols)) {
            $mapa = $this->mapearColumnas($cols);

            continue;
        }

        $fila = $this->parsearFila($cols, $mapa);
        if ($fila === null) {
            $errores++;

            continue;
        }
        $filas[] = $fila;
    }

    $this->preview = $filas;
    $this->previewErrores = $errores;

    if (count($filas) > 0) {
        $this->dispatch('open-modal', 'import-modal');
    } else {
        $this->addError('archivoCsv', 'No se reconocieron movimientos en el archivo. Revisa el formato (fecha, glosa, monto o cargo/abono).');
    }
};

$pareceEncabezado = function (array $cols): bool {
    $texto = mb_strtolower(implode(' ', $cols));

    return str_contains($texto, 'fecha') || str_contains($texto, 'glosa') || str_contains($texto, 'descrip');
};

$mapearColumnas = function (array $cols): array {
    $mapa = ['fecha' => null, 'glosa' => null, 'cargo' => null, 'abono' => null, 'monto' => null];
    foreach ($cols as $idx => $col) {
        $c = mb_strtolower(trim($col));
        if ($mapa['fecha'] === null && str_contains($c, 'fecha')) {
            $mapa['fecha'] = $idx;
        } elseif ($mapa['glosa'] === null && (str_contains($c, 'glosa') || str_contains($c, 'descrip') || str_contains($c, 'detalle'))) {
            $mapa['glosa'] = $idx;
        } elseif ($mapa['cargo'] === null && (str_contains($c, 'cargo') || str_contains($c, 'giro') || str_contains($c, 'débito') || str_contains($c, 'debito'))) {
            $mapa['cargo'] = $idx;
        } elseif ($mapa['abono'] === null && (str_contains($c, 'abono') || str_contains($c, 'depósito') || str_contains($c, 'deposito') || str_contains($c, 'crédito') || str_contains($c, 'credito'))) {
            $mapa['abono'] = $idx;
        } elseif ($mapa['monto'] === null && str_contains($c, 'monto')) {
            $mapa['monto'] = $idx;
        }
    }

    return $mapa;
};

$parsearFecha = function (string $valor): ?string {
    $valor = trim($valor);
    foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'Y/m/d'] as $formato) {
        try {
            $fecha = Carbon::createFromFormat($formato, $valor);
            if ($fecha !== false) {
                return $fecha->format('Y-m-d');
            }
        } catch (\Throwable) {
            // probar el siguiente formato
        }
    }

    return null;
};

$parsearMonto = function (string $valor): float {
    // Quita símbolos y separadores de miles; usa el último separador como decimal si aplica.
    $limpio = preg_replace('/[^0-9,.\-]/', '', $valor);
    $limpio = str_replace('.', '', $limpio);   // miles con punto (formato chileno)
    $limpio = str_replace(',', '.', $limpio);  // coma decimal -> punto

    return abs((float) $limpio);
};

$parsearFila = function (array $cols, ?array $mapa): ?array {
    // Sin encabezado reconocido: asumir posiciones [fecha, glosa, monto].
    if ($mapa === null) {
        $mapa = ['fecha' => 0, 'glosa' => 1, 'cargo' => null, 'abono' => null, 'monto' => 2];
    }

    $fechaRaw = $cols[$mapa['fecha']] ?? '';
    $fecha = $this->parsearFecha((string) $fechaRaw);
    if ($fecha === null) {
        return null;
    }

    $glosa = trim((string) ($cols[$mapa['glosa']] ?? '')) ?: 'Movimiento';

    $tipo = null;
    $monto = 0.0;

    if ($mapa['cargo'] !== null || $mapa['abono'] !== null) {
        $cargo = $this->parsearMonto((string) ($cols[$mapa['cargo']] ?? ''));
        $abono = $this->parsearMonto((string) ($cols[$mapa['abono']] ?? ''));
        if ($abono > 0) {
            $tipo = TipoMovimiento::Abono->value;
            $monto = $abono;
        } elseif ($cargo > 0) {
            $tipo = TipoMovimiento::Cargo->value;
            $monto = $cargo;
        }
    } else {
        $raw = (string) ($cols[$mapa['monto']] ?? '');
        $monto = $this->parsearMonto($raw);
        $tipo = str_contains($raw, '-') ? TipoMovimiento::Cargo->value : TipoMovimiento::Abono->value;
    }

    if ($tipo === null || $monto <= 0) {
        return null;
    }

    return ['fecha' => $fecha, 'glosa' => mb_substr($glosa, 0, 255), 'tipo' => $tipo, 'monto' => $monto];
};

$confirmarImport = function () {
    foreach ($this->preview as $fila) {
        MovimientoBancario::create([
            'fecha' => $fila['fecha'],
            'glosa' => $fila['glosa'],
            'tipo' => $fila['tipo'],
            'monto' => $fila['monto'],
            'conciliado' => false,
        ]);
    }

    $cantidad = count($this->preview);
    $this->archivoCsv = null;
    $this->preview = [];
    $this->previewErrores = 0;
    unset($this->movimientos, $this->resumen, $this->saldos);

    session()->flash('status', $cantidad.' movimiento(s) importado(s).');

    $this->dispatch('close-modal', 'import-modal');
};

// --- Vinculación al sistema ---

$prepararVincular = function (MovimientoBancario $movimiento) {
    $this->movVinculandoId = $movimiento->id;

    $this->dispatch('open-modal', 'vincular-modal');
};

$candidatos = computed(function () {
    if (! $this->movVinculandoId) {
        return collect();
    }

    $mov = MovimientoBancario::find($this->movVinculandoId);
    if (! $mov) {
        return collect();
    }

    // Registros ya vinculados a algún movimiento (para excluirlos).
    $usados = MovimientoBancario::whereNotNull('conciliable_id')
        ->where('id', '!=', $mov->id)
        ->get()
        ->groupBy('conciliable_type')
        ->map(fn ($g) => $g->pluck('conciliable_id')->all());

    $items = collect();

    if ($mov->tipo === TipoMovimiento::Abono) {
        // Ingresos: facturas de venta.
        FacturaVenta::with(['cotizacion', 'proyecto'])->get()
            ->reject(fn ($f) => in_array($f->id, $usados[FacturaVenta::class] ?? []))
            ->each(function ($f) use ($items) {
                $items->push([
                    'type' => FacturaVenta::class,
                    'id' => $f->id,
                    'etiqueta' => 'Factura '.$f->numero_factura,
                    'detalle' => $f->cotizacion?->numero_cotizacion ?? $f->proyecto?->nombre ?? '',
                    'monto' => (float) $f->total_calculado,
                ]);
            });
    } else {
        // Egresos: gastos y sueldos.
        Gasto::get()
            ->reject(fn ($g) => in_array($g->id, $usados[Gasto::class] ?? []))
            ->each(function ($g) use ($items) {
                $items->push([
                    'type' => Gasto::class,
                    'id' => $g->id,
                    'etiqueta' => 'Gasto '.$g->numero_documento,
                    'detalle' => $g->proveedor,
                    'monto' => (float) $g->total_calculado,
                ]);
            });
        Sueldo::get()
            ->reject(fn ($s) => in_array($s->id, $usados[Sueldo::class] ?? []))
            ->each(function ($s) use ($items) {
                $items->push([
                    'type' => Sueldo::class,
                    'id' => $s->id,
                    'etiqueta' => 'Sueldo '.$s->fecha->format('m-Y'),
                    'detalle' => $s->glosa ?? '',
                    'monto' => (float) $s->monto,
                ]);
            });
    }

    // Ordenar por cercanía de monto al del movimiento.
    return $items->sortBy(fn ($i) => abs($i['monto'] - (float) $mov->monto))->values();
});

$vincular = function (string $type, int $id) {
    $mov = MovimientoBancario::findOrFail($this->movVinculandoId);
    $mov->update([
        'conciliable_type' => $type,
        'conciliable_id' => $id,
        'conciliado' => true,
    ]);

    $this->movVinculandoId = null;
    unset($this->movimientos, $this->resumen, $this->candidatos);

    session()->flash('status', 'Movimiento vinculado y conciliado.');

    $this->dispatch('close-modal', 'vincular-modal');
};

$desvincular = function (MovimientoBancario $movimiento) {
    $movimiento->update(['conciliable_type' => null, 'conciliable_id' => null]);

    unset($this->movimientos);

    session()->flash('status', 'Vínculo eliminado.');
};

?>

<div>
    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            {{-- Encabezado --}}
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-lg font-semibold text-gray-900">Conciliación bancaria</h1>
                    <p class="mt-1 text-sm text-gray-500">Movimientos del banco, saldo y su cuadratura con el sistema.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <label class="inline-flex cursor-pointer items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        <span wire:loading.remove wire:target="archivoCsv">Importar cartola (CSV)</span>
                        <span wire:loading wire:target="archivoCsv">Leyendo…</span>
                        <input type="file" wire:model="archivoCsv" accept=".csv,.txt" class="hidden">
                    </label>
                    <button type="button" wire:click="prepararNuevo" x-on:click="$dispatch('open-modal', 'movimiento-modal')"
                        class="rounded-md bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-500">
                        + Agregar movimiento
                    </button>
                </div>
            </div>

            <x-input-error :messages="$errors->get('archivoCsv')" />

            {{-- Tarjetas resumen --}}
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Abonos (ingresos)</p>
                    <p class="mt-2 text-xl font-semibold text-green-700">${{ number_format($this->resumen['abonos'], 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Cargos (egresos)</p>
                    <p class="mt-2 text-xl font-semibold text-red-700">${{ number_format($this->resumen['cargos'], 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-inset ring-teal-100">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Saldo</p>
                    <p @class(['mt-2 text-xl font-semibold', 'text-gray-900' => $this->resumen['saldo'] >= 0, 'text-red-700' => $this->resumen['saldo'] < 0])>
                        ${{ number_format($this->resumen['saldo'], 0, ',', '.') }}
                    </p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Pendientes de conciliar</p>
                    <p class="mt-2 text-xl font-semibold text-amber-600">{{ $this->resumen['pendientes'] }}</p>
                </div>
            </div>

            {{-- Filtros --}}
            <div class="flex gap-2">
                @foreach (['todos' => 'Todos', 'pendientes' => 'Pendientes', 'conciliados' => 'Conciliados'] as $valor => $texto)
                    <button type="button" wire:click="$set('filtro', '{{ $valor }}')"
                        @class([
                            'rounded-full px-3 py-1 text-sm font-medium',
                            'bg-teal-600 text-white' => $filtro === $valor,
                            'bg-white text-gray-600 ring-1 ring-inset ring-gray-300 hover:bg-gray-50' => $filtro !== $valor,
                        ])>{{ $texto }}</button>
                @endforeach
            </div>

            {{-- Tabla de movimientos --}}
            <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Fecha</th>
                                <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Glosa</th>
                                <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Cargo</th>
                                <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Abono</th>
                                <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Saldo</th>
                                <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Conciliación</th>
                                <th class="py-2 pl-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($this->movimientos as $mov)
                                <tr wire:key="mov-{{ $mov->id }}">
                                    <td class="py-2 text-sm text-gray-500">{{ $mov->fecha->format('d-m-Y') }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-700">{{ $mov->glosa }}</td>
                                    <td class="px-3 py-2 text-right text-sm text-red-700">
                                        {{ $mov->tipo === App\Enums\TipoMovimiento::Cargo ? '$'.number_format($mov->monto, 0, ',', '.') : '' }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm text-green-700">
                                        {{ $mov->tipo === App\Enums\TipoMovimiento::Abono ? '$'.number_format($mov->monto, 0, ',', '.') : '' }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm text-gray-500">${{ number_format($this->saldos[$mov->id] ?? 0, 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-sm">
                                        @if ($mov->conciliable)
                                            <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">
                                                🔗 {{ $mov->conciliable_type === App\Models\FacturaVenta::class ? 'Factura' : ($mov->conciliable_type === App\Models\Gasto::class ? 'Gasto' : 'Sueldo') }}
                                            </span>
                                            <button type="button" wire:click="desvincular({{ $mov->id }})" class="ml-1 text-xs text-gray-400 hover:text-gray-600" title="Quitar vínculo">✕</button>
                                        @elseif ($mov->conciliado)
                                            <button type="button" wire:click="toggleConciliado({{ $mov->id }})" class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">✓ Conciliado</button>
                                        @else
                                            <button type="button" wire:click="toggleConciliado({{ $mov->id }})" class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">Pendiente</button>
                                        @endif
                                    </td>
                                    <td class="py-2 pl-3 text-right text-sm whitespace-nowrap">
                                        @unless ($mov->conciliable)
                                            <button type="button" wire:click="prepararVincular({{ $mov->id }})" class="font-medium text-teal-600 hover:text-teal-500">Vincular</button>
                                        @endunless
                                        <button type="button" wire:click="prepararEdicion({{ $mov->id }})" class="ml-2 font-medium text-gray-600 hover:text-gray-900">Editar</button>
                                        <button type="button" wire:click="eliminar({{ $mov->id }})"
                                            wire:confirm="¿Eliminar este movimiento?"
                                            class="ml-2 font-medium text-red-600 hover:text-red-500">Eliminar</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-sm text-gray-500">
                                        No hay movimientos {{ $filtro !== 'todos' ? '('.$filtro.')' : '' }}. Agrega uno o importa tu cartola.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <a href="{{ route('gestion.contabilidad.index') }}" wire:navigate class="inline-block text-sm text-gray-600 hover:text-gray-900">← Volver a contabilidad</a>
        </div>
    </div>

    {{-- Modal alta/edición de movimiento --}}
    <x-modal name="movimiento-modal" :show="$errors->isNotEmpty() && ! $errors->has('archivoCsv')">
        <form wire:submit="guardar" class="p-6" x-data
            x-on:open-modal.window="$event.detail === 'movimiento-modal' && $nextTick(() => setTimeout(() => document.getElementById('mov_glosa')?.focus(), 150))">
            <h2 class="text-lg font-medium text-gray-900">{{ $movId ? 'Editar movimiento' : 'Agregar movimiento' }}</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="mov_fecha" value="Fecha" />
                    <x-date-picker id="mov_fecha" model="fecha" class="mt-1" />
                    <x-input-error :messages="$errors->get('fecha')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="mov_tipo" value="Tipo" />
                    <select id="mov_tipo" wire:model="tipo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                        @foreach (TipoMovimiento::cases() as $t)
                            <option value="{{ $t->value }}">{{ $t->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('tipo')" class="mt-2" />
                </div>
            </div>

            <div class="mt-4">
                <x-input-label for="mov_glosa" value="Glosa" />
                <x-text-input id="mov_glosa" wire:model="glosa" type="text" class="mt-1 block w-full" placeholder="Ej: Transferencia cliente / Pago proveedor" />
                <x-input-error :messages="$errors->get('glosa')" class="mt-2" />
            </div>

            <div class="mt-4" x-data="{ monto: @entangle('monto') }">
                <x-input-label for="mov_monto" value="Monto" />
                <input id="mov_monto" type="number" step="1" min="0" x-model="monto"
                    x-on:focus="if (parseFloat(monto) === 0) monto = ''" x-on:blur="if (monto === '' || monto === null) monto = 0"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                <x-input-error :messages="$errors->get('monto')" class="mt-2" />
            </div>

            <label class="mt-4 flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" wire:model="conciliado" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                Marcar como conciliado
            </label>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-primary-button>{{ $movId ? 'Guardar cambios' : 'Agregar' }}</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal previsualización de importación --}}
    <x-modal name="import-modal" max-width="2xl">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">Previsualización de la cartola</h2>
            <p class="mt-1 text-sm text-gray-500">
                Se reconocieron {{ count($preview) }} movimiento(s){{ $previewErrores > 0 ? ' ('.$previewErrores.' fila(s) omitida(s))' : '' }}. Revisa y confirma.
            </p>

            <div class="mt-4 max-h-80 overflow-y-auto rounded-md ring-1 ring-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Fecha</th>
                            <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Glosa</th>
                            <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Tipo</th>
                            <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($preview as $fila)
                            <tr>
                                <td class="px-3 py-1.5 text-gray-500">{{ $fila['fecha'] }}</td>
                                <td class="px-3 py-1.5 text-gray-700">{{ $fila['glosa'] }}</td>
                                <td class="px-3 py-1.5 text-gray-500">{{ $fila['tipo'] === 'abono' ? 'Abono' : 'Cargo' }}</td>
                                <td class="px-3 py-1.5 text-right text-gray-900">${{ number_format($fila['monto'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" x-on:click="$dispatch('close-modal', 'import-modal')" wire:click="$set('preview', [])">Cancelar</x-secondary-button>
                <x-primary-button wire:click="confirmarImport">Importar {{ count($preview) }} movimiento(s)</x-primary-button>
            </div>
        </div>
    </x-modal>

    {{-- Modal vincular al sistema --}}
    <x-modal name="vincular-modal">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">Vincular con el sistema</h2>
            <p class="mt-1 text-sm text-gray-500">Elige el registro que corresponde a este movimiento (ordenados por monto más parecido).</p>

            <div class="mt-4 max-h-80 space-y-2 overflow-y-auto">
                @forelse ($this->candidatos as $c)
                    <button type="button" wire:key="cand-{{ $c['type'] }}-{{ $c['id'] }}"
                        wire:click="vincular('{{ addslashes($c['type']) }}', {{ $c['id'] }})"
                        class="flex w-full items-center justify-between rounded-md border border-gray-200 px-4 py-3 text-left hover:border-teal-300 hover:bg-teal-50">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $c['etiqueta'] }}</p>
                            @if ($c['detalle'])
                                <p class="text-xs text-gray-500">{{ $c['detalle'] }}</p>
                            @endif
                        </div>
                        <span class="text-sm font-semibold text-gray-700">${{ number_format($c['monto'], 0, ',', '.') }}</span>
                    </button>
                @empty
                    <p class="py-6 text-center text-sm text-gray-500">No hay registros disponibles para vincular.</p>
                @endforelse
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close-modal', 'vincular-modal')">Cerrar</x-secondary-button>
            </div>
        </div>
    </x-modal>
</div>
