<?php

use App\Enums\EstadoProyecto;
use App\Models\Gasto;
use App\Models\Proyecto;
use App\Models\User;
use Livewire\Volt\Volt;

test('guests are redirected to login', function () {
    $proyecto = Proyecto::factory()->create();

    $this->get(route('gestion.proyectos.show', $proyecto))
        ->assertRedirect(route('login'));
});

test('can render the show page with its gastos and factura sections', function () {
    $user = User::factory()->create();
    $proyecto = Proyecto::factory()->create(['nombre' => 'Proyecto de prueba']);

    $this->actingAs($user)
        ->get(route('gestion.proyectos.show', $proyecto))
        ->assertOk()
        ->assertSee('Proyecto de prueba')
        ->assertSeeVolt('gestion.cotizaciones.gastos')
        ->assertSeeVolt('gestion.cotizaciones.factura-venta');
});

test('can change the estado', function () {
    $user = User::factory()->create();
    $proyecto = Proyecto::factory()->create(['estado' => EstadoProyecto::Activo]);

    $this->actingAs($user);

    Volt::test('gestion.proyectos.show', ['proyecto' => $proyecto])
        ->call('cambiarEstado', EstadoProyecto::Facturado->value);

    expect($proyecto->fresh()->estado)->toBe(EstadoProyecto::Facturado);
});

test('eliminar deletes the proyecto and redirects to the index', function () {
    $user = User::factory()->create();
    $proyecto = Proyecto::factory()
        ->has(Gasto::factory()->count(2), 'gastos')
        ->create();

    $this->actingAs($user);

    Volt::test('gestion.proyectos.show', ['proyecto' => $proyecto])
        ->call('eliminar')
        ->assertRedirect(route('gestion.proyectos.index'));

    $this->assertDatabaseMissing('proyectos', ['id' => $proyecto->id]);
    $this->assertDatabaseMissing('gastos', ['proyecto_id' => $proyecto->id]);
});

test('gastos component attaches a gasto to the proyecto', function () {
    $user = User::factory()->create();
    $proyecto = Proyecto::factory()->create();

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.gastos', ['parent' => $proyecto])
        ->call('prepararNuevo')
        ->set('numero_documento', 'INSUMO-1')
        ->set('proveedor', 'Ferretería')
        ->set('descripcion', 'Materiales')
        ->set('monto_neto', 80000)
        ->call('guardar')
        ->assertHasNoErrors();

    $gasto = Gasto::firstWhere('numero_documento', 'INSUMO-1');

    expect($gasto)->not->toBeNull();
    expect($gasto->proyecto_id)->toBe($proyecto->id);
    expect($gasto->cotizacion_id)->toBeNull();
    expect((float) $gasto->iva)->toBe(15200.0);
});

test('factura de venta attaches to the proyecto', function () {
    $user = User::factory()->create();
    $proyecto = Proyecto::factory()->create();

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.factura-venta', ['parent' => $proyecto])
        ->set('numero_factura', '5001')
        ->set('fecha_emision', '2026-08-15')
        ->set('monto_neto', 500000)
        ->set('iva', 95000)
        ->call('guardar')
        ->assertHasNoErrors();

    $factura = $proyecto->facturaVenta()->first();

    expect($factura)->not->toBeNull();
    expect($factura->proyecto_id)->toBe($proyecto->id);
    expect($factura->cotizacion_id)->toBeNull();
    expect((float) $factura->total_calculado)->toBe(595000.0);
});
