@props([
    'model',
    'mode' => 'date',
])

@php
    $placeholder = $mode === 'time' ? '--:--' : 'dd-mm-aaaa';

    // Configuración de Flatpickr según el modo. En 'date'/'datetime' usamos altInput para
    // mostrar un formato bonito (d-m-Y) mientras la propiedad guarda el valor técnico (Y-m-d).
    $fpConfig = match ($mode) {
        'time' => [
            'enableTime' => true,
            'noCalendar' => true,
            'time_24hr' => true,
            'dateFormat' => 'H:i',
            'minuteIncrement' => 5,
        ],
        'datetime' => [
            'enableTime' => true,
            'time_24hr' => true,
            'dateFormat' => 'Y-m-d H:i',
            'altInput' => true,
            'altFormat' => 'd-m-Y H:i',
        ],
        default => [
            'dateFormat' => 'Y-m-d',
            'altInput' => true,
            'altFormat' => 'd-m-Y',
        ],
    };
@endphp

<div wire:ignore
    x-data="{ value: @entangle($model) }"
    x-init="
        const fp = window.flatpickr($refs.input, {
            ...@js($fpConfig),
            locale: 'es',
            disableMobile: true,
            defaultDate: value || null,
            onChange: (dates, str) => { value = str },
        });
        $watch('value', (v) => { if ((v || '') !== fp.input.value) fp.setDate(v || null, false) });
    ">
    <input type="text" x-ref="input" readonly placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'block w-full cursor-pointer rounded-md border-gray-300 bg-white shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm']) }}>
</div>
