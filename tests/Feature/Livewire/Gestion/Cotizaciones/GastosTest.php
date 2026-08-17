<?php

use App\Enums\EstadoCotizacion;
use App\Enums\TipoDocumentoGasto;
use App\Models\Cotizacion;
use App\Models\Gasto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

test('gastos section is hidden unless the cotizacion is aprobada', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Enviada]);

    $this->actingAs($user)
        ->get(route('gestion.cotizaciones.show', $cotizacion))
        ->assertDontSee('Agregar gasto');
});

test('gastos section is visible when the cotizacion is aprobada', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);

    $this->actingAs($user)
        ->get(route('gestion.cotizaciones.show', $cotizacion))
        ->assertSeeVolt('gestion.cotizaciones.gastos')
        ->assertSee('Agregar gasto');
});

test('iva is derived from monto_neto on save when left at zero', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);

    $this->actingAs($user);

    // El IVA se calcula en el cliente (Alpine); si llega en cero, guardar lo deriva del neto.
    Volt::test('gestion.cotizaciones.gastos', ['cotizacion' => $cotizacion])
        ->call('prepararNuevo')
        ->set('numero_documento', 'F-AUTO')
        ->set('proveedor', 'Proveedor')
        ->set('descripcion', 'Compra')
        ->set('monto_neto', 100000)
        ->call('guardar')
        ->assertHasNoErrors();

    $gasto = Gasto::firstWhere('numero_documento', 'F-AUTO');

    expect((float) $gasto->iva)->toBe(19000.0);
    expect((float) $gasto->total_calculado)->toBe(119000.0);
});

test('can add a gasto with correct totals', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.gastos', ['cotizacion' => $cotizacion])
        ->call('prepararNuevo')
        ->set('tipo', TipoDocumentoGasto::Factura->value)
        ->set('numero_documento', 'F-1001')
        ->set('proveedor', 'Distribuidora XYZ')
        ->set('descripcion', 'Compra de equipos')
        ->set('monto_neto', 100000)
        ->call('guardar')
        ->assertHasNoErrors();

    $gasto = Gasto::firstWhere('numero_documento', 'F-1001');

    expect($gasto)->not->toBeNull();
    expect($gasto->cotizacion_id)->toBe($cotizacion->id);
    expect((float) $gasto->monto_neto)->toBe(100000.0);
    expect((float) $gasto->iva)->toBe(19000.0);
    expect((float) $gasto->total_calculado)->toBe(119000.0);
});

test('requires the mandatory fields', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.gastos', ['cotizacion' => $cotizacion])
        ->call('prepararNuevo')
        ->set('numero_documento', '')
        ->set('proveedor', '')
        ->set('descripcion', '')
        ->call('guardar')
        ->assertHasErrors(['numero_documento', 'proveedor', 'descripcion']);
});

test('can edit an existing gasto', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);
    $gasto = Gasto::factory()->create(['cotizacion_id' => $cotizacion->id, 'proveedor' => 'Proveedor original']);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.gastos', ['cotizacion' => $cotizacion])
        ->call('prepararEdicion', $gasto->id)
        ->assertSet('proveedor', 'Proveedor original')
        ->set('proveedor', 'Proveedor actualizado')
        ->call('guardar')
        ->assertHasNoErrors();

    expect($gasto->fresh()->proveedor)->toBe('Proveedor actualizado');
});

test('can delete a gasto', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);
    $gasto = Gasto::factory()->create(['cotizacion_id' => $cotizacion->id]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.gastos', ['cotizacion' => $cotizacion])
        ->call('eliminar', $gasto->id);

    $this->assertModelMissing($gasto);
});

test('resumen totals and margen compare against the cotizacion total', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create([
        'estado' => EstadoCotizacion::Aprobada,
        'total_calculado' => 500000,
    ]);
    Gasto::factory()->create(['cotizacion_id' => $cotizacion->id, 'monto_neto' => 100000, 'iva' => 19000, 'total_calculado' => 119000]);
    Gasto::factory()->create(['cotizacion_id' => $cotizacion->id, 'monto_neto' => 50000, 'iva' => 9500, 'total_calculado' => 59500]);

    $this->actingAs($user);

    $resumen = Volt::test('gestion.cotizaciones.gastos', ['cotizacion' => $cotizacion])
        ->get('resumen');

    expect((float) $resumen['totalNeto'])->toBe(150000.0);
    expect((float) $resumen['totalIva'])->toBe(28500.0);
    expect((float) $resumen['totalGastos'])->toBe(178500.0);
    expect((float) $resumen['margen'])->toBe(321500.0);
});

test('comprobante download 404s when there is no attachment', function () {
    $user = User::factory()->create();
    $gasto = Gasto::factory()->create();

    $this->actingAs($user)
        ->get(route('gestion.gastos.comprobante', $gasto))
        ->assertNotFound();
});

test('comprobante download works when a file is attached', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $path = UploadedFile::fake()->create('boleta.pdf', 10)->store('gastos', 'local');
    $gasto = Gasto::factory()->create(['archivo_comprobante' => $path]);

    $this->actingAs($user)
        ->get(route('gestion.gastos.comprobante', $gasto))
        ->assertOk();
});

test('guests are redirected to login for comprobante download', function () {
    $gasto = Gasto::factory()->create();

    $this->get(route('gestion.gastos.comprobante', $gasto))
        ->assertRedirect(route('login'));
});
