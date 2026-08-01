<?php

use App\Enums\EstadoCotizacion;
use App\Models\Cotizacion;
use App\Models\LineaCotizacion;
use App\Models\User;
use Livewire\Volt\Volt;

test('guests are redirected to login', function () {
    $this->get(route('gestion.cotizaciones.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the cotizaciones list', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create();

    $this->actingAs($user)
        ->get(route('gestion.cotizaciones.index'))
        ->assertOk()
        ->assertSee($cotizacion->numero_cotizacion)
        ->assertSee($cotizacion->cliente->nombre);
});

test('search filters by numero, descripcion or cliente', function () {
    $user = User::factory()->create();
    $uno = Cotizacion::factory()->create(['descripcion' => 'Instalación de cámaras']);
    $dos = Cotizacion::factory()->create(['descripcion' => 'Redes de datos']);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.index')
        ->set('search', 'cámaras')
        ->assertSee($uno->numero_cotizacion)
        ->assertDontSee($dos->numero_cotizacion);
});

test('filter by estado narrows the list', function () {
    $user = User::factory()->create();
    $borrador = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Borrador]);
    $aprobada = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.index')
        ->set('estado', EstadoCotizacion::Aprobada->value)
        ->assertSee($aprobada->numero_cotizacion)
        ->assertDontSee($borrador->numero_cotizacion);
});

test('can delete a cotizacion along with its lineas and gastos', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()
        ->has(LineaCotizacion::factory()->count(2), 'lineas')
        ->create();

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.index')
        ->call('eliminar', $cotizacion->id);

    $this->assertDatabaseMissing('cotizaciones', ['id' => $cotizacion->id]);
    $this->assertDatabaseMissing('lineas_cotizacion', ['cotizacion_id' => $cotizacion->id]);
});
