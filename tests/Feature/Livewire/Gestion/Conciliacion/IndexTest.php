<?php

use App\Enums\TipoMovimiento;
use App\Models\FacturaVenta;
use App\Models\Gasto;
use App\Models\MovimientoBancario;
use App\Models\Sueldo;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Volt\Volt;

test('guests are redirected to login', function () {
    $this->get(route('gestion.conciliacion.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('gestion.conciliacion.index'))
        ->assertOk()
        ->assertSee('Conciliación bancaria');
});

test('can register a movimiento', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Volt::test('gestion.conciliacion.index')
        ->call('prepararNuevo')
        ->set('fecha', '2026-08-10')
        ->set('glosa', 'Pago proveedor')
        ->set('tipo', TipoMovimiento::Cargo->value)
        ->set('monto', 142800)
        ->call('guardar')
        ->assertHasNoErrors();

    $mov = MovimientoBancario::firstWhere('glosa', 'Pago proveedor');
    expect($mov)->not->toBeNull();
    expect($mov->tipo)->toBe(TipoMovimiento::Cargo);
    expect((float) $mov->monto)->toBe(142800.0);
});

test('resumen and running saldo are computed correctly', function () {
    $user = User::factory()->create();
    MovimientoBancario::factory()->abono()->create(['fecha' => '2026-08-01', 'monto' => 1000000]);
    MovimientoBancario::factory()->cargo()->create(['fecha' => '2026-08-05', 'monto' => 300000]);

    $this->actingAs($user);

    $componente = Volt::test('gestion.conciliacion.index');
    $resumen = $componente->get('resumen');
    $saldos = $componente->get('saldos');

    expect($resumen['abonos'])->toBe(1000000.0);
    expect($resumen['cargos'])->toBe(300000.0);
    expect($resumen['saldo'])->toBe(700000.0);
    expect($resumen['pendientes'])->toBe(2);

    // El saldo corriente acumula por fecha: 1.000.000 y luego 700.000.
    expect(array_values($saldos))->toBe([1000000.0, 700000.0]);
});

test('can toggle conciliado', function () {
    $user = User::factory()->create();
    $mov = MovimientoBancario::factory()->create(['conciliado' => false]);

    $this->actingAs($user);

    Volt::test('gestion.conciliacion.index')
        ->call('toggleConciliado', $mov->id);

    expect($mov->fresh()->conciliado)->toBeTrue();
});

test('filter shows only pendientes', function () {
    $user = User::factory()->create();
    MovimientoBancario::factory()->create(['glosa' => 'PENDIENTE-X', 'conciliado' => false]);
    MovimientoBancario::factory()->create(['glosa' => 'CONCILIADO-Y', 'conciliado' => true]);

    $this->actingAs($user);

    $lista = Volt::test('gestion.conciliacion.index')
        ->set('filtro', 'pendientes')
        ->get('movimientos');

    expect($lista->pluck('glosa')->all())->toContain('PENDIENTE-X');
    expect($lista->pluck('glosa')->all())->not->toContain('CONCILIADO-Y');
});

test('linking an abono to a factura de venta marks it conciliado', function () {
    $user = User::factory()->create();
    $factura = FacturaVenta::factory()->create(['total_calculado' => 357000]);
    $mov = MovimientoBancario::factory()->abono()->create(['monto' => 357000]);

    $this->actingAs($user);

    Volt::test('gestion.conciliacion.index')
        ->call('prepararVincular', $mov->id)
        ->call('vincular', FacturaVenta::class, $factura->id);

    $mov->refresh();
    expect($mov->conciliado)->toBeTrue();
    expect($mov->conciliable_id)->toBe($factura->id);
    expect($mov->conciliable)->toBeInstanceOf(FacturaVenta::class);
});

test('candidatos for a cargo are gastos and sueldos', function () {
    $user = User::factory()->create();
    $gasto = Gasto::factory()->create(['total_calculado' => 142800, 'numero_documento' => 'G-1']);
    $sueldo = Sueldo::factory()->create(['monto' => 800000]);
    $mov = MovimientoBancario::factory()->cargo()->create(['monto' => 142800]);

    $this->actingAs($user);

    $candidatos = Volt::test('gestion.conciliacion.index')
        ->call('prepararVincular', $mov->id)
        ->get('candidatos');

    $tipos = collect($candidatos)->pluck('type')->all();
    expect($tipos)->toContain(Gasto::class);
    expect($tipos)->toContain(Sueldo::class);
    // El gasto de igual monto debe quedar primero (orden por cercanía).
    expect($candidatos->first()['id'])->toBe($gasto->id);
});

test('desvincular clears the link', function () {
    $user = User::factory()->create();
    $factura = FacturaVenta::factory()->create();
    $mov = MovimientoBancario::factory()->abono()->create([
        'conciliable_type' => FacturaVenta::class,
        'conciliable_id' => $factura->id,
        'conciliado' => true,
    ]);

    $this->actingAs($user);

    Volt::test('gestion.conciliacion.index')
        ->call('desvincular', $mov->id);

    expect($mov->fresh()->conciliable_id)->toBeNull();
});

test('can import movimientos from a CSV cartola', function () {
    $user = User::factory()->create();

    $csv = "Fecha;Glosa;Cargo;Abono\n"
        ."05/08/2026;Pago proveedor;142.800;\n"
        ."10/08/2026;Transferencia cliente;;357.000\n";

    $this->actingAs($user);

    $componente = Volt::test('gestion.conciliacion.index')
        ->set('archivoCsv', UploadedFile::fake()->createWithContent('cartola.csv', $csv));

    $preview = $componente->get('preview');
    expect($preview)->toHaveCount(2);

    $componente->call('confirmarImport');

    expect(MovimientoBancario::count())->toBe(2);
    $cargo = MovimientoBancario::where('tipo', TipoMovimiento::Cargo)->first();
    expect((float) $cargo->monto)->toBe(142800.0);
    $abono = MovimientoBancario::where('tipo', TipoMovimiento::Abono)->first();
    expect((float) $abono->monto)->toBe(357000.0);
});
