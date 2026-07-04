<?php

use App\Enums\EstadoCotizacion;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\User;
use Livewire\Volt\Volt;

test('guests are redirected to login', function () {
    $this->get(route('gestion.cotizaciones.create'))
        ->assertRedirect(route('login'));
});

test('can render the create form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('gestion.cotizaciones.create'))
        ->assertOk()
        ->assertSeeVolt('gestion.cotizaciones.form');
});

test('client search finds matching clientes', function () {
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create(['nombre' => 'Juan Pérez']);
    Cliente::factory()->create(['nombre' => 'Otra Persona']);

    $this->actingAs($user);

    $sugeridos = Volt::test('gestion.cotizaciones.form')
        ->set('clienteSearch', 'Juan')
        ->get('clientesSugeridos');

    expect($sugeridos)->toHaveCount(1);
    expect($sugeridos->first()->id)->toBe($cliente->id);
});

test('can create a cotizacion with correct totals', function () {
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create();

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.form')
        ->call('seleccionarCliente', $cliente->id)
        ->set('descripcion', 'Instalación de cámaras')
        ->set('fecha_vencimiento', today()->addDays(30)->toDateString())
        ->set('descuento_porcentaje', 10)
        ->set('lineas', [
            ['descripcion' => 'Cámara IP', 'cantidad' => 2, 'valor_unitario' => 100000],
            ['descripcion' => 'Instalación', 'cantidad' => 1, 'valor_unitario' => 50000],
        ])
        ->call('guardar')
        ->assertHasNoErrors();

    $cotizacion = Cotizacion::firstWhere('descripcion', 'Instalación de cámaras');

    expect($cotizacion)->not->toBeNull();
    expect($cotizacion->cliente_id)->toBe($cliente->id);
    expect($cotizacion->estado)->toBe(EstadoCotizacion::Borrador);
    expect($cotizacion->lineas)->toHaveCount(2);
    expect((float) $cotizacion->subtotal_calculado)->toBe(250000.0);
    expect((float) $cotizacion->base_gravada_calculada)->toBe(225000.0);
    expect((float) $cotizacion->iva_calculado)->toBe(42750.0);
    expect((float) $cotizacion->total_calculado)->toBe(267750.0);
    expect($cotizacion->numero_cotizacion)->toStartWith('CTZ-'.now()->format('Ymd'));
});

test('requires a cliente and at least one linea', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.form')
        ->set('descripcion', 'Sin cliente')
        ->set('fecha_vencimiento', today()->addDays(30)->toDateString())
        ->set('lineas', [])
        ->call('guardar')
        ->assertHasErrors(['cliente_id', 'lineas']);
});

test('editing a borrador pre-fills the form', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['descripcion' => 'Cotización original']);
    $cotizacion->lineas()->create([
        'descripcion' => 'Servicio A', 'cantidad' => 1, 'valor_unitario' => 10000, 'subtotal_calculado' => 10000, 'orden' => 0,
    ]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.form', ['cotizacion' => $cotizacion])
        ->assertSet('descripcion', 'Cotización original')
        ->assertSet('cliente_id', $cotizacion->cliente_id)
        ->assertSet('lineas.0.descripcion', 'Servicio A');
});

test('editing a non-borrador cotizacion redirects to the show page', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Enviada]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.form', ['cotizacion' => $cotizacion])
        ->assertRedirect(route('gestion.cotizaciones.show', $cotizacion));
});

test('can update an existing borrador', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create();
    $cotizacion->lineas()->create([
        'descripcion' => 'Original', 'cantidad' => 1, 'valor_unitario' => 10000, 'subtotal_calculado' => 10000, 'orden' => 0,
    ]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.form', ['cotizacion' => $cotizacion])
        ->set('descripcion', 'Descripción actualizada')
        ->set('lineas', [
            ['descripcion' => 'Nueva línea', 'cantidad' => 3, 'valor_unitario' => 20000],
        ])
        ->call('guardar')
        ->assertHasNoErrors();

    $cotizacion->refresh();

    expect($cotizacion->descripcion)->toBe('Descripción actualizada');
    expect($cotizacion->lineas)->toHaveCount(1);
    expect($cotizacion->lineas->first()->descripcion)->toBe('Nueva línea');
    expect((float) $cotizacion->subtotal_calculado)->toBe(60000.0);
});
