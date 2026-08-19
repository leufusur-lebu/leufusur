<?php

use App\Enums\EstadoProyecto;
use App\Models\Cliente;
use App\Models\Proyecto;
use App\Models\User;
use Livewire\Volt\Volt;

test('guests are redirected to login', function () {
    $this->get(route('gestion.proyectos.create'))
        ->assertRedirect(route('login'));
});

test('can create a proyecto', function () {
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create();

    $this->actingAs($user);

    Volt::test('gestion.proyectos.form')
        ->set('cliente_id', $cliente->id)
        ->set('nombre', 'Mantención de servidores')
        ->set('fecha_inicio', '2026-08-01')
        ->call('guardar')
        ->assertHasNoErrors();

    $proyecto = Proyecto::firstWhere('nombre', 'Mantención de servidores');

    expect($proyecto)->not->toBeNull();
    expect($proyecto->cliente_id)->toBe($cliente->id);
    expect($proyecto->estado)->toBe(EstadoProyecto::Activo);
});

test('cliente and nombre are required', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Volt::test('gestion.proyectos.form')
        ->set('cliente_id', '')
        ->set('nombre', '')
        ->call('guardar')
        ->assertHasErrors(['cliente_id', 'nombre']);
});

test('can edit an existing proyecto', function () {
    $user = User::factory()->create();
    $proyecto = Proyecto::factory()->create(['nombre' => 'Nombre original']);

    $this->actingAs($user);

    Volt::test('gestion.proyectos.form', ['proyecto' => $proyecto])
        ->assertSet('nombre', 'Nombre original')
        ->set('nombre', 'Nombre actualizado')
        ->call('guardar')
        ->assertHasNoErrors();

    expect($proyecto->fresh()->nombre)->toBe('Nombre actualizado');
});
