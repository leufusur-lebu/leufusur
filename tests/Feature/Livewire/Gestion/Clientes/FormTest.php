<?php

use App\Enums\TipoCliente;
use App\Models\Cliente;
use App\Models\User;
use Livewire\Volt\Volt;

test('guests are redirected to login', function () {
    $this->get(route('gestion.clientes.create'))
        ->assertRedirect(route('login'));
});

test('can render the create form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('gestion.clientes.create'))
        ->assertOk()
        ->assertSeeVolt('gestion.clientes.form');
});

test('can create a new cliente with valid data', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('gestion.clientes.form')
        ->set('tipo', TipoCliente::Persona->value)
        ->set('nombre', 'Ana Torres')
        ->set('email', 'ana@example.com')
        ->set('telefono', '+56911112222')
        ->set('rut_run', '12.345.678-5')
        ->set('direccion', 'Calle Falsa 123')
        ->call('guardar')
        ->assertRedirect(route('gestion.clientes.index'));

    $this->assertDatabaseHas('clientes', [
        'nombre' => 'Ana Torres',
        'email' => 'ana@example.com',
        'rut_run' => '12.345.678-5',
    ]);
});

test('nombre_empresa is discarded when tipo is empresa', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('gestion.clientes.form')
        ->set('tipo', TipoCliente::Empresa->value)
        ->set('nombre', 'Constructora Sur SpA')
        ->set('nombre_empresa', 'Esto no debería guardarse')
        ->set('email', 'contacto@constructorasur.cl')
        ->set('telefono', '+56922223333')
        ->set('rut_run', '76.123.456-0')
        ->set('direccion', 'Ruta 5 Sur km 10')
        ->call('guardar');

    $this->assertDatabaseHas('clientes', [
        'nombre' => 'Constructora Sur SpA',
        'nombre_empresa' => null,
    ]);
});

test('rejects an invalid rut', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('gestion.clientes.form')
        ->set('tipo', TipoCliente::Persona->value)
        ->set('nombre', 'Ana Torres')
        ->set('email', 'ana@example.com')
        ->set('telefono', '+56911112222')
        ->set('rut_run', '12.345.678-9')
        ->set('direccion', 'Calle Falsa 123')
        ->call('guardar')
        ->assertHasErrors('rut_run');

    $this->assertDatabaseMissing('clientes', ['nombre' => 'Ana Torres']);
});

test('rejects a duplicate rut', function () {
    $user = User::factory()->create();
    $existente = Cliente::factory()->create(['rut_run' => '12.345.678-5']);

    $this->actingAs($user);

    Volt::test('gestion.clientes.form')
        ->set('tipo', TipoCliente::Persona->value)
        ->set('nombre', 'Otra Persona')
        ->set('email', 'otra@example.com')
        ->set('telefono', '+56911112222')
        ->set('rut_run', $existente->rut_run)
        ->set('direccion', 'Calle Falsa 123')
        ->call('guardar')
        ->assertHasErrors('rut_run');
});

test('requires the mandatory fields', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('gestion.clientes.form')
        ->set('nombre', '')
        ->set('email', '')
        ->set('telefono', '')
        ->set('rut_run', '')
        ->set('direccion', '')
        ->call('guardar')
        ->assertHasErrors(['nombre', 'email', 'telefono', 'rut_run', 'direccion']);
});

test('can render the edit form pre-filled with existing data', function () {
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create(['nombre' => 'Cliente Existente']);

    $this->actingAs($user);

    Volt::test('gestion.clientes.form', ['cliente' => $cliente])
        ->assertSet('nombre', 'Cliente Existente')
        ->assertSet('rut_run', $cliente->rut_run);
});

test('can update an existing cliente', function () {
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create(['nombre' => 'Nombre Original']);

    $this->actingAs($user);

    Volt::test('gestion.clientes.form', ['cliente' => $cliente])
        ->set('nombre', 'Nombre Actualizado')
        ->call('guardar')
        ->assertRedirect(route('gestion.clientes.index'));

    expect($cliente->fresh()->nombre)->toBe('Nombre Actualizado');
});

test('updating a cliente does not flag its own rut as duplicate', function () {
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create(['rut_run' => '12.345.678-5']);

    $this->actingAs($user);

    Volt::test('gestion.clientes.form', ['cliente' => $cliente])
        ->set('nombre', 'Nombre Actualizado')
        ->set('rut_run', '12.345.678-5')
        ->call('guardar')
        ->assertHasNoErrors();
});
