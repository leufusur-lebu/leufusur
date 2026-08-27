<?php

use App\Enums\EstadoCotizacion;
use App\Models\Cotizacion;
use App\Models\FacturaVenta;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

test('factura de venta section is hidden unless the cotizacion is aprobada', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Enviada]);

    $this->actingAs($user)
        ->get(route('gestion.cotizaciones.show', $cotizacion))
        ->assertDontSee('Factura de venta (Leufu Sur)');
});

test('factura de venta section is visible when the cotizacion is aprobada', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);

    $this->actingAs($user)
        ->get(route('gestion.cotizaciones.show', $cotizacion))
        ->assertSeeVolt('gestion.cotizaciones.factura-venta')
        ->assertSee('Factura de venta (Leufu Sur)');
});

test('prefills neto and iva from the cotizacion on mount', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create([
        'estado' => EstadoCotizacion::Aprobada,
        'base_gravada_calculada' => 200000,
        'iva_calculado' => 38000,
    ]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.factura-venta', ['parent' => $cotizacion])
        ->assertSet('monto_neto', 200000.0)
        ->assertSet('iva', 38000.0);
});

test('monto_neto updates auto-calculate the iva at 19%', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.factura-venta', ['parent' => $cotizacion])
        ->set('monto_neto', 100000)
        ->assertSet('iva', 19000.0);
});

test('can register a factura de venta with correct totals', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.factura-venta', ['parent' => $cotizacion])
        ->set('numero_factura', '1234')
        ->set('fecha_emision', '2026-08-10')
        ->set('monto_neto', 300000)
        ->call('guardar')
        ->assertHasNoErrors();

    $factura = $cotizacion->facturaVenta()->first();

    expect($factura)->not->toBeNull();
    expect($factura->numero_factura)->toBe('1234');
    expect((float) $factura->monto_neto)->toBe(300000.0);
    expect((float) $factura->iva)->toBe(57000.0);
    expect((float) $factura->total_calculado)->toBe(357000.0);
});

test('only one factura de venta per cotizacion (saving twice updates)', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);
    FacturaVenta::factory()->create(['cotizacion_id' => $cotizacion->id, 'numero_factura' => '111']);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.factura-venta', ['parent' => $cotizacion])
        ->assertSet('numero_factura', '111')
        ->set('numero_factura', '222')
        ->call('guardar')
        ->assertHasNoErrors();

    expect(FacturaVenta::where('cotizacion_id', $cotizacion->id)->count())->toBe(1);
    expect($cotizacion->facturaVenta()->first()->numero_factura)->toBe('222');
});

test('requires numero_factura and fecha_emision', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.factura-venta', ['parent' => $cotizacion])
        ->set('numero_factura', '')
        ->set('fecha_emision', '')
        ->call('guardar')
        ->assertHasErrors(['numero_factura', 'fecha_emision']);
});

test('can delete the factura de venta', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);
    $factura = FacturaVenta::factory()->create(['cotizacion_id' => $cotizacion->id]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.factura-venta', ['parent' => $cotizacion])
        ->call('eliminar');

    $this->assertModelMissing($factura);
});

test('archivo download 404s when there is no file', function () {
    $user = User::factory()->create();
    $factura = FacturaVenta::factory()->create();

    $this->actingAs($user)
        ->get(route('gestion.facturas-venta.archivo', $factura))
        ->assertNotFound();
});

test('archivo download works when a file is attached', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $path = UploadedFile::fake()->create('factura.pdf', 10)->store('facturas-venta', 'local');
    $factura = FacturaVenta::factory()->create(['archivo_pdf' => $path]);

    $this->actingAs($user)
        ->get(route('gestion.facturas-venta.archivo', $factura))
        ->assertOk();
});

test('can mark a factura de venta as pagada with fecha de pago', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.factura-venta', ['parent' => $cotizacion])
        ->set('numero_factura', '5555')
        ->set('fecha_emision', '2026-08-10')
        ->set('monto_neto', 300000)
        ->set('pagada', true)
        ->set('fecha_pago', '2026-08-20')
        ->call('guardar')
        ->assertHasNoErrors();

    $factura = $cotizacion->facturaVenta()->first();
    expect($factura->pagada)->toBeTrue();
    expect($factura->fecha_pago->toDateString())->toBe('2026-08-20');
});

test('marking pagada without a fecha defaults it to today', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.factura-venta', ['parent' => $cotizacion])
        ->set('numero_factura', '5556')
        ->set('fecha_emision', '2026-08-10')
        ->set('monto_neto', 100000)
        ->set('pagada', true)
        ->set('fecha_pago', '')
        ->call('guardar')
        ->assertHasNoErrors();

    expect($cotizacion->facturaVenta()->first()->fecha_pago->toDateString())->toBe(today()->toDateString());
});

test('unmarking pagada clears the fecha de pago', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);
    FacturaVenta::factory()->create(['cotizacion_id' => $cotizacion->id, 'pagada' => true, 'fecha_pago' => '2026-08-01']);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.factura-venta', ['parent' => $cotizacion])
        ->assertSet('pagada', true)
        ->set('pagada', false)
        ->call('guardar')
        ->assertHasNoErrors();

    $factura = $cotizacion->facturaVenta()->first();
    expect($factura->pagada)->toBeFalse();
    expect($factura->fecha_pago)->toBeNull();
});
