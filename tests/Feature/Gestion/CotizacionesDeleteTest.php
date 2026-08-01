<?php

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('puede eliminar una cotización', function () {
    $cliente = Cliente::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['cliente_id' => $cliente->id]);

    Livewire::test('gestion.cotizaciones.index')
        ->call('eliminarCotizacion', $cotizacion->id);

    $this->assertDatabaseMissing('cotizaciones', ['id' => $cotizacion->id]);
});

test('la página de cotizaciones no muestra cotización eliminada', function () {
    $cliente = Cliente::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['cliente_id' => $cliente->id]);

    Livewire::test('gestion.cotizaciones.index')
        ->call('eliminarCotizacion', $cotizacion->id);

    Livewire::test('gestion.cotizaciones.index')
        ->assertDontSee($cotizacion->numero_cotizacion);
});
