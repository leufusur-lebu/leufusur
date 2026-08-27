<?php

use App\Enums\EstadoCotizacion;
use App\Http\Controllers\Gestion\InformeController;
use App\Models\Cotizacion;
use App\Models\FacturaVenta;
use App\Models\Gasto;
use App\Models\Sueldo;
use App\Models\User;

test('guests are redirected to login', function () {
    $this->get(route('gestion.informes.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the informes page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('gestion.informes.index'))
        ->assertOk()
        ->assertSee('Informes')
        ->assertSee('Facturas emitidas')
        ->assertSee('Facturas recibidas');
});

test('facturas emitidas report lists sales invoices with totals', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);
    FacturaVenta::factory()->create([
        'cotizacion_id' => $cotizacion->id,
        'numero_factura' => 'F-100',
        'monto_neto' => 300000,
        'iva' => 57000,
        'total_calculado' => 357000,
        'pagada' => true,
    ]);

    $datos = InformeController::datos('facturas-emitidas', null, null);

    expect($datos['columnas'])->toContain('N° factura', 'Cliente', 'Estado');
    expect($datos['filas'])->toHaveCount(1);
    expect($datos['filas'][0])->toContain('F-100', 'Pagada');
    expect($datos['totales']['Total'])->toBe('$357.000');
    expect($datos['totales']['Cobrado'])->toBe('$357.000');
});

test('facturas recibidas report lists purchase invoices', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);
    Gasto::factory()->create(['cotizacion_id' => $cotizacion->id, 'numero_documento' => 'C-9', 'proveedor' => 'Ferretería', 'monto_neto' => 100000, 'iva' => 19000, 'total_calculado' => 119000]);

    $datos = InformeController::datos('facturas-recibidas', null, null);

    expect($datos['filas'])->toHaveCount(1);
    expect($datos['filas'][0])->toContain('C-9', 'Ferretería');
    expect($datos['totales']['Total'])->toBe('$119.000');
});

test('iva report summarises debito, credito and a pagar', function () {
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);
    FacturaVenta::factory()->create(['cotizacion_id' => $cotizacion->id, 'fecha_emision' => '2026-08-05', 'iva' => 100000, 'monto_neto' => 526316, 'total_calculado' => 626316]);
    Gasto::factory()->create(['cotizacion_id' => $cotizacion->id, 'fecha_gasto' => '2026-08-10', 'iva' => 40000, 'monto_neto' => 210526, 'total_calculado' => 250526]);

    $datos = InformeController::datos('iva', null, null);

    expect($datos['totales']['IVA débito'])->toBe('$100.000');
    expect($datos['totales']['IVA crédito'])->toBe('$40.000');
    expect($datos['totales']['A pagar SII'])->toBe('$60.000');
});

test('ingresos vs gastos report computes the resultado', function () {
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);
    FacturaVenta::factory()->create(['cotizacion_id' => $cotizacion->id, 'monto_neto' => 1000000, 'iva' => 190000, 'total_calculado' => 1190000]);
    Gasto::factory()->create(['cotizacion_id' => $cotizacion->id, 'monto_neto' => 300000, 'iva' => 57000, 'total_calculado' => 357000]);
    Sueldo::factory()->create(['monto' => 400000]);

    $datos = InformeController::datos('ingresos-gastos', null, null);

    expect($datos['totales']['Ingresos'])->toBe('$1.000.000');
    expect($datos['totales']['Gastos'])->toBe('$300.000');
    expect($datos['totales']['Sueldos'])->toBe('$400.000');
    expect($datos['totales']['Resultado'])->toBe('$300.000');
});

test('date range filters the report', function () {
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);
    FacturaVenta::factory()->create(['cotizacion_id' => $cotizacion->id, 'fecha_emision' => '2026-08-15', 'numero_factura' => 'IN']);
    $c2 = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);
    FacturaVenta::factory()->create(['cotizacion_id' => $c2->id, 'fecha_emision' => '2026-06-01', 'numero_factura' => 'OUT']);

    $datos = InformeController::datos('facturas-emitidas', '2026-08-01', '2026-08-31');

    expect($datos['filas'])->toHaveCount(1);
    expect($datos['filas'][0])->toContain('IN');
});

test('can download the csv export', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);
    FacturaVenta::factory()->create(['cotizacion_id' => $cotizacion->id, 'numero_factura' => 'CSV-1']);

    $response = $this->actingAs($user)->get(route('gestion.informes.export', ['reporte' => 'facturas-emitidas', 'formato' => 'csv']));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
});

test('can download the pdf export', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('gestion.informes.export', ['reporte' => 'iva', 'formato' => 'pdf']));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('invalid report or format returns 404', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('gestion.informes.export', ['reporte' => 'inexistente', 'formato' => 'csv']))->assertNotFound();
    $this->actingAs($user)->get(route('gestion.informes.export', ['reporte' => 'iva', 'formato' => 'docx']))->assertNotFound();
});

test('export is protected by auth', function () {
    $this->get(route('gestion.informes.export', ['reporte' => 'iva', 'formato' => 'pdf']))
        ->assertRedirect(route('login'));
});
