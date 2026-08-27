<?php

use App\Models\Sueldo;
use App\Models\User;
use Livewire\Volt\Volt;

test('sueldos section renders on the contabilidad page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('gestion.contabilidad.index'))
        ->assertOk()
        ->assertSeeVolt('gestion.contabilidad.sueldos')
        ->assertSee('Sueldo empresarial');
});

test('can register a sueldo', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Volt::test('gestion.contabilidad.sueldos')
        ->call('prepararNuevo')
        ->set('fecha', '2026-08-05')
        ->set('monto', 800000)
        ->set('glosa', 'Sueldo agosto')
        ->call('guardar')
        ->assertHasNoErrors();

    $sueldo = Sueldo::firstWhere('glosa', 'Sueldo agosto');

    expect($sueldo)->not->toBeNull();
    expect((float) $sueldo->monto)->toBe(800000.0);
});

test('monto is required and must be positive', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Volt::test('gestion.contabilidad.sueldos')
        ->call('prepararNuevo')
        ->set('monto', 0)
        ->call('guardar')
        ->assertHasErrors(['monto']);
});

test('can edit a sueldo', function () {
    $user = User::factory()->create();
    $sueldo = Sueldo::factory()->create(['monto' => 500000]);

    $this->actingAs($user);

    Volt::test('gestion.contabilidad.sueldos')
        ->call('prepararEdicion', $sueldo->id)
        ->assertSet('monto', 500000.0)
        ->set('monto', 900000)
        ->call('guardar')
        ->assertHasNoErrors();

    expect((float) $sueldo->fresh()->monto)->toBe(900000.0);
});

test('can delete a sueldo', function () {
    $user = User::factory()->create();
    $sueldo = Sueldo::factory()->create();

    $this->actingAs($user);

    Volt::test('gestion.contabilidad.sueldos')
        ->call('eliminar', $sueldo->id);

    $this->assertModelMissing($sueldo);
});

test('total sueldos is summed', function () {
    $user = User::factory()->create();
    Sueldo::factory()->create(['monto' => 600000]);
    Sueldo::factory()->create(['monto' => 400000]);

    $this->actingAs($user);

    $total = Volt::test('gestion.contabilidad.sueldos')->get('totalSueldos');

    expect($total)->toBe(1000000.0);
});
