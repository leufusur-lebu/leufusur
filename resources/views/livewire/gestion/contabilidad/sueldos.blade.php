<?php

use App\Models\Sueldo;
use Illuminate\Support\Carbon;

use function Livewire\Volt\{computed, state};

state([
    'sueldoId' => null,
    'fecha' => fn () => today()->toDateString(),
    'monto' => 0,
    'glosa' => '',
]);

$sueldos = computed(fn () => Sueldo::latest('fecha')->get());

$totalSueldos = computed(fn () => (float) $this->sueldos->sum('monto'));

$totalMesActual = computed(function () {
    return (float) $this->sueldos
        ->filter(fn (Sueldo $s) => $s->fecha->isSameMonth(now()))
        ->sum('monto');
});

$prepararNuevo = function () {
    $this->reset(['sueldoId', 'monto', 'glosa']);
    $this->fecha = today()->toDateString();
    $this->resetValidation();

    $this->dispatch('open-modal', 'sueldo-modal');
};

$prepararEdicion = function (Sueldo $sueldo) {
    $this->sueldoId = $sueldo->id;
    $this->fecha = $sueldo->fecha->toDateString();
    $this->monto = (float) $sueldo->monto;
    $this->glosa = (string) $sueldo->glosa;
    $this->resetValidation();

    $this->dispatch('open-modal', 'sueldo-modal');
};

$guardar = function () {
    $datos = $this->validate([
        'fecha' => ['required', 'date'],
        'monto' => ['required', 'numeric', 'min:1'],
        'glosa' => ['nullable', 'string', 'max:255'],
    ]);

    $sueldo = $this->sueldoId ? Sueldo::findOrFail($this->sueldoId) : new Sueldo;
    $sueldo->fill($datos)->save();

    $this->reset(['sueldoId', 'monto', 'glosa']);
    $this->fecha = today()->toDateString();
    unset($this->sueldos, $this->totalSueldos, $this->totalMesActual);

    session()->flash('status', $this->sueldoId ? 'Sueldo actualizado.' : 'Sueldo registrado.');

    $this->dispatch('close-modal', 'sueldo-modal');
};

$eliminar = function (Sueldo $sueldo) {
    $sueldo->delete();

    unset($this->sueldos, $this->totalSueldos, $this->totalMesActual);

    session()->flash('status', 'Sueldo eliminado.');
};

?>

<div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-sm font-semibold text-gray-900">Sueldo empresarial</h2>
            <p class="mt-1 text-xs text-gray-500">
                Sueldos que se paga la empresa. Mes actual:
                <span class="font-medium text-gray-700">${{ number_format($this->totalMesActual, 0, ',', '.') }}</span>
                · Total: <span class="font-medium text-gray-700">${{ number_format($this->totalSueldos, 0, ',', '.') }}</span>
            </p>
        </div>
        <button type="button" wire:click="prepararNuevo" x-on:click="$dispatch('open-modal', 'sueldo-modal')"
            class="rounded-md bg-teal-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-500">
            + Registrar sueldo
        </button>
    </div>

    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Fecha</th>
                    <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Glosa</th>
                    <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Monto</th>
                    <th class="py-2 pl-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($this->sueldos as $sueldo)
                    <tr wire:key="sueldo-{{ $sueldo->id }}">
                        <td class="py-2 text-sm text-gray-500">{{ $sueldo->fecha->format('d-m-Y') }}</td>
                        <td class="px-3 py-2 text-sm text-gray-500">{{ $sueldo->glosa ?: '—' }}</td>
                        <td class="px-3 py-2 text-right text-sm text-gray-900">${{ number_format($sueldo->monto, 0, ',', '.') }}</td>
                        <td class="py-2 pl-3 text-right text-sm">
                            <button type="button" wire:click="prepararEdicion({{ $sueldo->id }})" class="font-medium text-teal-600 hover:text-teal-500">Editar</button>
                            <button type="button" wire:click="eliminar({{ $sueldo->id }})"
                                wire:confirm="¿Eliminar este sueldo? Esta acción no se puede deshacer."
                                class="ml-2 font-medium text-red-600 hover:text-red-500">Eliminar</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-6 text-center text-sm text-gray-500">Aún no hay sueldos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal registrar/editar sueldo --}}
    <x-modal name="sueldo-modal" :show="$errors->isNotEmpty()">
        <form wire:submit="guardar" class="p-6" x-data
            x-on:open-modal.window="$event.detail === 'sueldo-modal' && $nextTick(() => setTimeout(() => document.getElementById('sueldo_monto')?.focus(), 150))">
            <h2 class="text-lg font-medium text-gray-900">{{ $sueldoId ? 'Editar sueldo' : 'Registrar sueldo' }}</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="sueldo_fecha" value="Fecha de pago" />
                    <x-date-picker id="sueldo_fecha" model="fecha" class="mt-1" />
                    <x-input-error :messages="$errors->get('fecha')" class="mt-2" />
                </div>
                <div x-data="{ monto: @entangle('monto') }">
                    <x-input-label for="sueldo_monto" value="Monto" />
                    <input id="sueldo_monto" type="number" step="1" min="0" x-model="monto"
                        x-on:focus="if (parseFloat(monto) === 0) monto = ''" x-on:blur="if (monto === '' || monto === null) monto = 0"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                    <x-input-error :messages="$errors->get('monto')" class="mt-2" />
                </div>
            </div>

            <div class="mt-4">
                <x-input-label for="sueldo_glosa" value="Glosa (opcional)" />
                <x-text-input id="sueldo_glosa" wire:model="glosa" type="text" class="mt-1 block w-full" placeholder="Ej: Sueldo agosto" />
                <x-input-error :messages="$errors->get('glosa')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-primary-button>{{ $sueldoId ? 'Guardar cambios' : 'Registrar sueldo' }}</x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
