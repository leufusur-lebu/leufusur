<?php

use App\Enums\EstadoCotizacion;
use App\Models\Ampliacion;
use App\Models\Cotizacion;
use App\Models\LineaAmpliacion;
use App\Models\User;
use Livewire\Volt\Volt;

test('the ampliaciones section renders on an approved cotizacion', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);

    $this->actingAs($user)
        ->get(route('gestion.cotizaciones.show', $cotizacion))
        ->assertOk()
        ->assertSeeVolt('gestion.cotizaciones.ampliaciones')
        ->assertSee('Ampliaciones (trabajos adicionales)');
});

test('the ampliaciones section is hidden unless the cotizacion is aprobada', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Enviada]);

    $this->actingAs($user)
        ->get(route('gestion.cotizaciones.show', $cotizacion))
        ->assertDontSee('Ampliaciones (trabajos adicionales)');
});

test('can register an ampliacion with its lineas and computed totals', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.ampliaciones', ['cotizacion' => $cotizacion])
        ->call('prepararNueva')
        ->set('descripcion', 'Refuerzo de cableado no considerado')
        ->set('fecha', '2026-09-10')
        ->set('lineas.0.descripcion', 'Cable UTP extra')
        ->set('lineas.0.unidad', 'MT')
        ->set('lineas.0.cantidad', 50)
        ->set('lineas.0.valor_unitario', 1200)
        ->call('guardar')
        ->assertHasNoErrors();

    $ampliacion = $cotizacion->ampliaciones()->with('lineas')->first();

    expect($ampliacion)->not->toBeNull();
    expect($ampliacion->descripcion)->toBe('Refuerzo de cableado no considerado');
    expect($ampliacion->lineas)->toHaveCount(1);
    expect((float) $ampliacion->subtotal_calculado)->toBe(60000.0);
    expect((float) $ampliacion->iva_calculado)->toBe(11400.0);
    expect((float) $ampliacion->total_calculado)->toBe(71400.0);
});

test('can add multiple lineas that sum into the ampliacion total', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.ampliaciones', ['cotizacion' => $cotizacion])
        ->call('prepararNueva')
        ->set('descripcion', 'Adicionales varios')
        ->set('lineas.0.descripcion', 'Cable UTP extra')
        ->set('lineas.0.cantidad', 50)
        ->set('lineas.0.valor_unitario', 1200)
        ->call('agregarLinea')
        ->set('lineas.1.descripcion', 'Horas técnico')
        ->set('lineas.1.unidad', 'HH')
        ->set('lineas.1.cantidad', 4)
        ->set('lineas.1.valor_unitario', 15000)
        ->call('guardar')
        ->assertHasNoErrors();

    $ampliacion = $cotizacion->ampliaciones()->with('lineas')->first();

    expect($ampliacion->lineas)->toHaveCount(2);
    expect((float) $ampliacion->subtotal_calculado)->toBe(120000.0);
    expect((float) $ampliacion->total_calculado)->toBe(142800.0);
});

test('can edit an ampliacion and replace its lineas', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);
    $ampliacion = Ampliacion::factory()->for($cotizacion)
        ->has(LineaAmpliacion::factory()->count(3), 'lineas')
        ->create(['descripcion' => 'Original']);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.ampliaciones', ['cotizacion' => $cotizacion])
        ->call('editar', $ampliacion->id)
        ->assertSet('descripcion', 'Original')
        ->assertCount('lineas', 3)
        ->call('eliminarLinea', 2)
        ->call('eliminarLinea', 1)
        ->set('descripcion', 'Corregida')
        ->set('lineas.0.descripcion', 'Único ítem')
        ->set('lineas.0.cantidad', 2)
        ->set('lineas.0.valor_unitario', 5000)
        ->call('guardar')
        ->assertHasNoErrors();

    expect($cotizacion->ampliaciones()->count())->toBe(1);

    $ampliacion->refresh()->load('lineas');
    expect($ampliacion->descripcion)->toBe('Corregida');
    expect($ampliacion->lineas)->toHaveCount(1);
    expect((float) $ampliacion->subtotal_calculado)->toBe(10000.0);
});

test('can delete an ampliacion', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);
    $ampliacion = Ampliacion::factory()->for($cotizacion)->create();

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.ampliaciones', ['cotizacion' => $cotizacion])
        ->call('eliminar', $ampliacion->id);

    $this->assertModelMissing($ampliacion);
});

test('requires descripcion and at least one linea with values', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.ampliaciones', ['cotizacion' => $cotizacion])
        ->call('prepararNueva')
        ->set('descripcion', '')
        ->set('lineas.0.descripcion', '')
        ->set('lineas.0.cantidad', '')
        ->set('lineas.0.valor_unitario', '')
        ->call('guardar')
        ->assertHasErrors(['descripcion', 'lineas.0.descripcion', 'lineas.0.cantidad', 'lineas.0.valor_unitario']);
});

test('eliminarLinea keeps at least one line', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.ampliaciones', ['cotizacion' => $cotizacion])
        ->call('prepararNueva')
        ->call('eliminarLinea', 0)
        ->assertCount('lineas', 1);
});

test('cotizacion facturable and total helpers include ampliaciones', function () {
    $cotizacion = Cotizacion::factory()->create([
        'base_gravada_calculada' => 100000,
        'iva_calculado' => 19000,
        'total_calculado' => 119000,
    ]);
    Ampliacion::factory()->for($cotizacion)->create([
        'subtotal_calculado' => 50000,
        'iva_calculado' => 9500,
        'total_calculado' => 59500,
    ]);

    expect($cotizacion->netoParaFactura())->toBe(150000.0);
    expect($cotizacion->ivaParaFactura())->toBe(28500.0);
    expect($cotizacion->totalConAmpliaciones())->toBe(178500.0);
});

test('factura de venta prefills neto and iva including ampliaciones', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create([
        'estado' => EstadoCotizacion::Aprobada,
        'base_gravada_calculada' => 200000,
        'iva_calculado' => 38000,
        'total_calculado' => 238000,
    ]);
    Ampliacion::factory()->for($cotizacion)->create([
        'subtotal_calculado' => 100000,
        'iva_calculado' => 19000,
        'total_calculado' => 119000,
    ]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.factura-venta', ['parent' => $cotizacion->fresh()])
        ->assertSet('monto_neto', 300000.0)
        ->assertSet('iva', 57000.0);
});

test('deleting a cotizacion cascades to its ampliaciones and their lineas', function () {
    $cotizacion = Cotizacion::factory()->create();
    $ampliacion = Ampliacion::factory()->for($cotizacion)
        ->has(LineaAmpliacion::factory()->count(2), 'lineas')
        ->create();

    $cotizacion->delete();

    $this->assertDatabaseMissing('ampliaciones', ['id' => $ampliacion->id]);
    $this->assertDatabaseMissing('lineas_ampliacion', ['ampliacion_id' => $ampliacion->id]);
});
