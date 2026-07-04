<?php

use App\Enums\TipoCliente;
use App\Models\Cliente;
use App\Models\User;
use Livewire\Volt\Volt;

test('guests are redirected to login', function () {
    $this->get(route('gestion.clientes.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the clientes list', function () {
    $user = User::factory()->create();
    Cliente::factory()->create(['nombre' => 'Constructora Sur']);

    $this->actingAs($user)
        ->get(route('gestion.clientes.index'))
        ->assertOk()
        ->assertSee('Constructora Sur');
});

test('search filters clientes by name', function () {
    $user = User::factory()->create();
    Cliente::factory()->create(['nombre' => 'Juan Pérez']);
    Cliente::factory()->create(['nombre' => 'María López']);

    $this->actingAs($user);

    Volt::test('gestion.clientes.index')
        ->set('search', 'Juan')
        ->assertSee('Juan Pérez')
        ->assertDontSee('María López');
});

test('search filters clientes by rut', function () {
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create(['nombre' => 'Empresa Objetivo']);
    Cliente::factory()->create(['nombre' => 'Otra Empresa']);

    $this->actingAs($user);

    Volt::test('gestion.clientes.index')
        ->set('search', $cliente->rut_run)
        ->assertSee('Empresa Objetivo')
        ->assertDontSee('Otra Empresa');
});

test('filter by tipo narrows the list', function () {
    $user = User::factory()->create();
    Cliente::factory()->create(['nombre' => 'Empresa Uno', 'tipo' => TipoCliente::Empresa]);
    Cliente::factory()->create(['nombre' => 'Persona Uno', 'tipo' => TipoCliente::Persona]);

    $this->actingAs($user);

    Volt::test('gestion.clientes.index')
        ->set('tipo', TipoCliente::Empresa->value)
        ->assertSee('Empresa Uno')
        ->assertDontSee('Persona Uno');
});

test('can delete a cliente without cotizaciones', function () {
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create();

    $this->actingAs($user);

    Volt::test('gestion.clientes.index')
        ->call('eliminar', $cliente->id);

    $this->assertModelMissing($cliente);
});
