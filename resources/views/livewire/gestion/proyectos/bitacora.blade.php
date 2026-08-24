<?php

use App\Models\Actividad;
use App\Models\ActividadFoto;
use App\Models\Proyecto;
use Illuminate\Support\Facades\Storage;

use function Livewire\Volt\{computed, mount, state, usesFileUploads};

usesFileUploads();

state([
    'proyecto' => null,
    'actividadId' => null,
    'fecha' => fn () => today()->toDateString(),
    'hora_inicio' => '',
    'hora_termino' => '',
    'lugar' => '',
    'descripcion' => '',
    'fotos' => [],
]);

mount(function (Proyecto $proyecto) {
    $this->proyecto = $proyecto;
});

$actividades = computed(fn () => $this->proyecto->actividades()->with('fotos')->get());

$totalHoras = computed(function () {
    $minutos = (int) $this->actividades->sum(fn (Actividad $a) => $a->duracionEnMinutos());

    return intdiv($minutos, 60).'h '.str_pad((string) ($minutos % 60), 2, '0', STR_PAD_LEFT).'m';
});

$prepararNueva = function () {
    $this->reset(['actividadId', 'hora_inicio', 'hora_termino', 'lugar', 'descripcion', 'fotos']);
    $this->fecha = today()->toDateString();
    $this->resetValidation();

    $this->dispatch('open-modal', 'actividad-modal');
};

$prepararEdicion = function (Actividad $actividad) {
    $this->actividadId = $actividad->id;
    $this->fecha = $actividad->fecha->toDateString();
    $this->hora_inicio = substr($actividad->hora_inicio, 0, 5);
    $this->hora_termino = substr($actividad->hora_termino, 0, 5);
    $this->lugar = $actividad->lugar;
    $this->descripcion = $actividad->descripcion;
    $this->fotos = [];
    $this->resetValidation();

    $this->dispatch('open-modal', 'actividad-modal');
};

$guardar = function () {
    $datos = $this->validate([
        'fecha' => ['required', 'date'],
        'hora_inicio' => ['required', 'date_format:H:i'],
        'hora_termino' => ['required', 'date_format:H:i'],
        'lugar' => ['required', 'string', 'max:255'],
        'descripcion' => ['required', 'string', 'max:2000'],
        'fotos' => ['nullable', 'array', 'max:15'],
        'fotos.*' => ['image', 'max:10240'],
    ]);

    // Comparación de strings 'HH:MM' con ceros a la izquierda: es correcta lexicográficamente.
    if ($this->hora_termino <= $this->hora_inicio) {
        $this->addError('hora_termino', 'La hora de término debe ser posterior a la de inicio.');

        return;
    }

    $actividad = $this->actividadId
        ? Actividad::findOrFail($this->actividadId)
        : $this->proyecto->actividades()->make();

    $actividad->fill([
        'fecha' => $datos['fecha'],
        'hora_inicio' => $datos['hora_inicio'],
        'hora_termino' => $datos['hora_termino'],
        'lugar' => $datos['lugar'],
        'descripcion' => $datos['descripcion'],
    ]);
    $actividad->save();

    foreach ($this->fotos ?? [] as $foto) {
        $actividad->fotos()->create([
            'ruta' => $foto->store('actividades', 'local'),
        ]);
    }

    $this->reset(['actividadId', 'hora_inicio', 'hora_termino', 'lugar', 'descripcion', 'fotos']);
    $this->fecha = today()->toDateString();
    unset($this->actividades, $this->totalHoras);

    session()->flash('status', $this->actividadId ? 'Actividad actualizada.' : 'Actividad registrada.');

    $this->dispatch('close-modal', 'actividad-modal');
};

$eliminarFoto = function (ActividadFoto $foto) {
    Storage::disk('local')->delete($foto->ruta);
    $foto->delete();

    unset($this->actividades);
};

$eliminar = function (Actividad $actividad) {
    foreach ($actividad->fotos as $foto) {
        Storage::disk('local')->delete($foto->ruta);
    }
    $actividad->delete();

    unset($this->actividades, $this->totalHoras);

    session()->flash('status', 'Actividad eliminada.');
};

?>

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-sm font-semibold text-gray-900">Bitácora de actividades</h2>
            <p class="mt-1 text-xs text-gray-500">Registro de los trabajos ejecutados. Total: <span class="font-medium text-gray-700">{{ $this->totalHoras }}</span></p>
        </div>
        <button type="button" wire:click="prepararNueva" x-on:click="$dispatch('open-modal', 'actividad-modal')"
            class="rounded-md bg-teal-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-500">
            + Registrar actividad
        </button>
    </div>

    {{-- Lista de actividades --}}
    <div class="mt-4 space-y-3">
        @forelse ($this->actividades as $actividad)
            <div wire:key="actividad-{{ $actividad->id }}" class="rounded-lg border border-gray-200 p-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                            <span class="font-medium text-gray-900">{{ $actividad->fecha->format('d-m-Y') }}</span>
                            <span class="text-gray-500">{{ substr($actividad->hora_inicio, 0, 5) }} – {{ substr($actividad->hora_termino, 0, 5) }}</span>
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ $actividad->duracionLegible() }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">📍 {{ $actividad->lugar }}</p>
                        <p class="mt-2 text-sm text-gray-700 whitespace-pre-line">{{ $actividad->descripcion }}</p>
                    </div>
                    <div class="flex shrink-0 gap-2 text-sm">
                        <button type="button" wire:click="prepararEdicion({{ $actividad->id }})" class="font-medium text-teal-600 hover:text-teal-500">Editar</button>
                        <button type="button" wire:click="eliminar({{ $actividad->id }})"
                            wire:confirm="¿Eliminar esta actividad y sus fotos? Esta acción no se puede deshacer."
                            class="font-medium text-red-600 hover:text-red-500">Eliminar</button>
                    </div>
                </div>

                @if ($actividad->fotos->isNotEmpty())
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($actividad->fotos as $foto)
                            <div wire:key="foto-{{ $foto->id }}" class="relative">
                                <a href="{{ route('gestion.actividad-fotos.ver', $foto) }}" target="_blank">
                                    <img src="{{ route('gestion.actividad-fotos.ver', $foto) }}" alt="Foto de la actividad"
                                        class="h-20 w-20 rounded-md object-cover ring-1 ring-gray-200">
                                </a>
                                <button type="button" wire:click="eliminarFoto({{ $foto->id }})"
                                    wire:confirm="¿Eliminar esta foto?"
                                    class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-xs font-bold text-white shadow hover:bg-red-500"
                                    title="Eliminar foto">×</button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <p class="py-6 text-center text-sm text-gray-500">Aún no hay actividades registradas.</p>
        @endforelse
    </div>

    {{-- Modal registrar/editar actividad --}}
    <x-modal name="actividad-modal" :show="$errors->isNotEmpty()">
        <form wire:submit="guardar" class="p-6" x-data
            x-on:open-modal.window="$event.detail === 'actividad-modal' && $nextTick(() => setTimeout(() => document.getElementById('act_hora_inicio')?.focus(), 150))">
            <h2 class="text-lg font-medium text-gray-900">
                {{ $actividadId ? 'Editar actividad' : 'Registrar actividad' }}
            </h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div>
                    <x-input-label for="act_fecha" value="Fecha" />
                    <x-text-input id="act_fecha" wire:model="fecha" type="date" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('fecha')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="act_hora_inicio" value="Hora inicio" />
                    <x-text-input id="act_hora_inicio" wire:model="hora_inicio" type="time" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('hora_inicio')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="act_hora_termino" value="Hora término" />
                    <x-text-input id="act_hora_termino" wire:model="hora_termino" type="time" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('hora_termino')" class="mt-2" />
                </div>
            </div>

            <div class="mt-4">
                <x-input-label for="act_lugar" value="Lugar de ejecución" />
                <x-text-input id="act_lugar" wire:model="lugar" type="text" class="mt-1 block w-full" placeholder="Ej: Local comercial, Av. Principal 123" />
                <x-input-error :messages="$errors->get('lugar')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="act_descripcion" value="Actividad realizada" />
                <textarea id="act_descripcion" wire:model="descripcion" rows="3"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                    placeholder="Describe el trabajo ejecutado…"></textarea>
                <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="act_fotos" value="Fotos (opcional, varias)" />
                <input id="act_fotos" type="file" wire:model="fotos" multiple accept="image/*"
                    class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200">
                <x-input-error :messages="$errors->get('fotos')" class="mt-2" />
                <x-input-error :messages="$errors->get('fotos.*')" class="mt-2" />
                <div wire:loading wire:target="fotos" class="mt-2 text-xs text-gray-500">Subiendo fotos…</div>
                @if ($fotos)
                    <p class="mt-2 text-xs text-gray-500">{{ count($fotos) }} foto(s) nueva(s) lista(s) para guardar.</p>
                @endif
                @if ($actividadId)
                    <p class="mt-2 text-xs text-gray-400">Las fotos ya adjuntas se administran desde la tarjeta de la actividad.</p>
                @endif
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-primary-button>{{ $actividadId ? 'Guardar cambios' : 'Registrar actividad' }}</x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
