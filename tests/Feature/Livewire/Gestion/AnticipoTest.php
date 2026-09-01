<?php

use App\Models\Cotizacion;
use App\Models\Proyecto;
use App\Models\User;
use Livewire\Volt\Volt;

test('prefills the anticipo fields from the parent on mount', function () {
    $user = User::factory()->create();
    $proyecto = Proyecto::factory()->conAnticipo(250000, '2026-08-20')->create();

    $this->actingAs($user);

    Volt::test('gestion.anticipo', ['parent' => $proyecto])
        ->assertSet('anticipo_monto', 250000.0)
        ->assertSet('anticipo_pagado', true)
        ->assertSet('anticipo_fecha_pago', '2026-08-20');
});

test('can register the anticipo on a proyecto', function () {
    $user = User::factory()->create();
    $proyecto = Proyecto::factory()->create();

    $this->actingAs($user);

    Volt::test('gestion.anticipo', ['parent' => $proyecto])
        ->set('anticipo_monto', 300000)
        ->set('anticipo_pagado', true)
        ->set('anticipo_fecha_pago', '2026-08-25')
        ->call('guardar')
        ->assertHasNoErrors();

    $proyecto->refresh();
    expect((float) $proyecto->anticipo_monto)->toBe(300000.0);
    expect($proyecto->anticipo_pagado)->toBeTrue();
    expect($proyecto->anticipo_fecha_pago->toDateString())->toBe('2026-08-25');
});

test('can register the anticipo on a cotizacion', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create();

    $this->actingAs($user);

    Volt::test('gestion.anticipo', ['parent' => $cotizacion])
        ->set('anticipo_monto', 400000)
        ->set('anticipo_pagado', true)
        ->set('anticipo_fecha_pago', '2026-09-01')
        ->call('guardar')
        ->assertHasNoErrors();

    $cotizacion->refresh();
    expect((float) $cotizacion->anticipo_monto)->toBe(400000.0);
    expect($cotizacion->anticipo_pagado)->toBeTrue();
    expect($cotizacion->anticipo_fecha_pago->toDateString())->toBe('2026-09-01');
});

test('marking as paid without a fecha defaults it to today', function () {
    $user = User::factory()->create();
    $proyecto = Proyecto::factory()->create();

    $this->actingAs($user);

    Volt::test('gestion.anticipo', ['parent' => $proyecto])
        ->set('anticipo_monto', 150000)
        ->set('anticipo_pagado', true)
        ->set('anticipo_fecha_pago', '')
        ->call('guardar')
        ->assertHasNoErrors();

    expect($proyecto->refresh()->anticipo_fecha_pago->toDateString())->toBe(today()->toDateString());
});

test('registering an unpaid anticipo leaves the fecha null', function () {
    $user = User::factory()->create();
    $proyecto = Proyecto::factory()->create();

    $this->actingAs($user);

    Volt::test('gestion.anticipo', ['parent' => $proyecto])
        ->set('anticipo_monto', 200000)
        ->set('anticipo_pagado', false)
        ->call('guardar')
        ->assertHasNoErrors();

    $proyecto->refresh();
    expect((float) $proyecto->anticipo_monto)->toBe(200000.0);
    expect($proyecto->anticipo_pagado)->toBeFalse();
    expect($proyecto->anticipo_fecha_pago)->toBeNull();
});

test('sugerirMitad fills half of the referencia monto', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create();

    $this->actingAs($user);

    Volt::test('gestion.anticipo', ['parent' => $cotizacion, 'referenciaMonto' => 600000])
        ->call('sugerirMitad')
        ->assertSet('anticipo_monto', 300000.0);
});

test('eliminar clears the anticipo', function () {
    $user = User::factory()->create();
    $proyecto = Proyecto::factory()->conAnticipo(250000, '2026-08-20')->create();

    $this->actingAs($user);

    Volt::test('gestion.anticipo', ['parent' => $proyecto])
        ->call('eliminar')
        ->assertSet('anticipo_monto', 0)
        ->assertSet('anticipo_pagado', false);

    $proyecto->refresh();
    expect($proyecto->anticipo_monto)->toBeNull();
    expect($proyecto->anticipo_pagado)->toBeFalse();
    expect($proyecto->anticipo_fecha_pago)->toBeNull();
});

test('anticipo_monto is required to save', function () {
    $user = User::factory()->create();
    $proyecto = Proyecto::factory()->create();

    $this->actingAs($user);

    Volt::test('gestion.anticipo', ['parent' => $proyecto])
        ->set('anticipo_monto', '')
        ->call('guardar')
        ->assertHasErrors(['anticipo_monto']);
});
