<?php

use App\Enums\EstadoCotizacion;
use App\Models\Cotizacion;
use App\Models\FacturaVenta;
use App\Models\Gasto;
use App\Models\Proyecto;
use App\Models\User;
use Livewire\Volt\Volt;

test('guests are redirected to login', function () {
    $this->get(route('gestion.contabilidad.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the contabilidad page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('gestion.contabilidad.index'))
        ->assertOk()
        ->assertSee('Contabilidad')
        ->assertSee('IVA por período (F29)');
});

test('iva a pagar is debito minus credito within the same month', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);

    // Débito (venta) 100.000 de IVA; crédito (gasto) 40.000, mismo mes.
    FacturaVenta::factory()->create([
        'cotizacion_id' => $cotizacion->id,
        'fecha_emision' => '2026-08-05',
        'monto_neto' => 526316,
        'iva' => 100000,
        'total_calculado' => 626316,
    ]);
    Gasto::factory()->create([
        'cotizacion_id' => $cotizacion->id,
        'fecha_gasto' => '2026-08-10',
        'monto_neto' => 210526,
        'iva' => 40000,
        'total_calculado' => 250526,
    ]);

    $this->actingAs($user);

    $resumen = Volt::test('gestion.contabilidad.index')->get('resumenIva');

    expect($resumen)->toHaveCount(1);
    expect($resumen[0]['debito'])->toBe(100000.0);
    expect($resumen[0]['credito'])->toBe(40000.0);
    expect($resumen[0]['aPagar'])->toBe(60000.0);
    expect($resumen[0]['remanente'])->toBe(0.0);
});

test('excess credito carries forward as remanente to the next month', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);

    // Julio: crédito 50.000 > débito 0 -> remanente 50.000.
    Gasto::factory()->create([
        'cotizacion_id' => $cotizacion->id,
        'fecha_gasto' => '2026-07-15',
        'monto_neto' => 263158,
        'iva' => 50000,
        'total_calculado' => 313158,
    ]);
    // Agosto: débito 80.000, crédito 0 -> posicion 80.000 - 50.000 remanente = 30.000 a pagar.
    FacturaVenta::factory()->create([
        'cotizacion_id' => $cotizacion->id,
        'fecha_emision' => '2026-08-05',
        'monto_neto' => 421053,
        'iva' => 80000,
        'total_calculado' => 501053,
    ]);

    $this->actingAs($user);

    $resumen = Volt::test('gestion.contabilidad.index')->get('resumenIva');

    // Más reciente primero: agosto en [0], julio en [1].
    expect($resumen)->toHaveCount(2);
    expect($resumen[1]['periodo']->format('Y-m'))->toBe('2026-07');
    expect($resumen[1]['remanente'])->toBe(50000.0);
    expect($resumen[1]['aPagar'])->toBe(0.0);

    expect($resumen[0]['periodo']->format('Y-m'))->toBe('2026-08');
    expect($resumen[0]['aPagar'])->toBe(30000.0);
    expect($resumen[0]['remanente'])->toBe(0.0);
});

test('rentabilidad uses factura venta neto minus gastos neto', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create([
        'estado' => EstadoCotizacion::Aprobada,
        'base_gravada_calculada' => 400000,
    ]);
    FacturaVenta::factory()->create([
        'cotizacion_id' => $cotizacion->id,
        'monto_neto' => 500000,
        'iva' => 95000,
        'total_calculado' => 595000,
    ]);
    Gasto::factory()->create(['cotizacion_id' => $cotizacion->id, 'monto_neto' => 120000, 'iva' => 22800, 'total_calculado' => 142800]);
    Gasto::factory()->create(['cotizacion_id' => $cotizacion->id, 'monto_neto' => 80000, 'iva' => 15200, 'total_calculado' => 95200]);

    $this->actingAs($user);

    $rentabilidad = Volt::test('gestion.contabilidad.index')->get('rentabilidad');

    $fila = $rentabilidad->first();
    expect($fila['ingresoNeto'])->toBe(500000.0);
    expect($fila['gastoNeto'])->toBe(200000.0);
    expect($fila['margen'])->toBe(300000.0);
    expect($fila['facturada'])->toBeTrue();
});

test('rentabilidad falls back to cotizacion base when not yet facturada', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create([
        'estado' => EstadoCotizacion::Aprobada,
        'base_gravada_calculada' => 400000,
    ]);

    $this->actingAs($user);

    $rentabilidad = Volt::test('gestion.contabilidad.index')->get('rentabilidad');

    $fila = $rentabilidad->first();
    expect($fila['ingresoNeto'])->toBe(400000.0);
    expect($fila['facturada'])->toBeFalse();
});

test('can add a gasto general without a cotizacion', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Volt::test('gestion.contabilidad.index')
        ->call('prepararNuevo')
        ->set('numero_documento', 'ARR-001')
        ->set('proveedor', 'Inmobiliaria')
        ->set('descripcion', 'Arriendo oficina')
        ->set('monto_neto', 300000)
        ->call('guardar')
        ->assertHasNoErrors();

    $gasto = Gasto::firstWhere('numero_documento', 'ARR-001');

    expect($gasto)->not->toBeNull();
    expect($gasto->cotizacion_id)->toBeNull();
    expect((float) $gasto->iva)->toBe(57000.0);
});

test('gastos generales list excludes project gastos', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);
    Gasto::factory()->create(['cotizacion_id' => $cotizacion->id, 'numero_documento' => 'PROY-1']);
    Gasto::factory()->general()->create(['numero_documento' => 'GEN-1']);

    $this->actingAs($user);

    $lista = Volt::test('gestion.contabilidad.index')->get('gastosGenerales');

    expect($lista->pluck('numero_documento')->all())->toContain('GEN-1');
    expect($lista->pluck('numero_documento')->all())->not->toContain('PROY-1');
});

test('can delete a gasto general', function () {
    $user = User::factory()->create();
    $gasto = Gasto::factory()->general()->create();

    $this->actingAs($user);

    Volt::test('gestion.contabilidad.index')
        ->call('eliminar', $gasto->id);

    $this->assertModelMissing($gasto);
});

test('rentabilidad includes proyectos alongside cotizaciones', function () {
    $user = User::factory()->create();

    $proyecto = Proyecto::factory()->create(['nombre' => 'Proyecto directo X']);
    FacturaVenta::factory()->create([
        'cotizacion_id' => null,
        'proyecto_id' => $proyecto->id,
        'monto_neto' => 600000,
        'iva' => 114000,
        'total_calculado' => 714000,
    ]);
    Gasto::factory()->create(['cotizacion_id' => null, 'proyecto_id' => $proyecto->id, 'monto_neto' => 200000, 'iva' => 38000, 'total_calculado' => 238000]);

    $this->actingAs($user);

    $rentabilidad = Volt::test('gestion.contabilidad.index')->get('rentabilidad');

    $fila = $rentabilidad->firstWhere('etiqueta', 'Proyecto directo X');

    expect($fila)->not->toBeNull();
    expect($fila['origen'])->toBe('Proyecto');
    expect($fila['ingresoNeto'])->toBe(600000.0);
    expect($fila['gastoNeto'])->toBe(200000.0);
    expect($fila['margen'])->toBe(400000.0);
});

test('gastos generales list excludes proyecto gastos', function () {
    $user = User::factory()->create();
    $proyecto = Proyecto::factory()->create();
    Gasto::factory()->create(['cotizacion_id' => null, 'proyecto_id' => $proyecto->id, 'numero_documento' => 'PROY-GASTO']);
    Gasto::factory()->general()->create(['numero_documento' => 'GEN-2']);

    $this->actingAs($user);

    $lista = Volt::test('gestion.contabilidad.index')->get('gastosGenerales');

    expect($lista->pluck('numero_documento')->all())->toContain('GEN-2');
    expect($lista->pluck('numero_documento')->all())->not->toContain('PROY-GASTO');
});

test('proyecto factura and gastos feed the monthly iva summary', function () {
    $user = User::factory()->create();
    $proyecto = Proyecto::factory()->create();
    FacturaVenta::factory()->create([
        'cotizacion_id' => null,
        'proyecto_id' => $proyecto->id,
        'fecha_emision' => '2026-08-05',
        'monto_neto' => 500000,
        'iva' => 95000,
        'total_calculado' => 595000,
    ]);
    Gasto::factory()->create([
        'cotizacion_id' => null,
        'proyecto_id' => $proyecto->id,
        'fecha_gasto' => '2026-08-10',
        'monto_neto' => 100000,
        'iva' => 19000,
        'total_calculado' => 119000,
    ]);

    $this->actingAs($user);

    $resumen = Volt::test('gestion.contabilidad.index')->get('resumenIva');

    expect($resumen)->toHaveCount(1);
    expect($resumen[0]['debito'])->toBe(95000.0);
    expect($resumen[0]['credito'])->toBe(19000.0);
    expect($resumen[0]['aPagar'])->toBe(76000.0);
});
