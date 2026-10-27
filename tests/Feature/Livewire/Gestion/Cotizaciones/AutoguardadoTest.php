<?php

use App\Enums\EstadoCotizacion;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\CotizacionBorrador;
use App\Models\User;
use Livewire\Volt\Volt;

test('el autoguardado crea un borrador para una cotizacion nueva', function () {
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create();

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.form')
        ->set('cliente_id', $cliente->id)
        ->set('descripcion', 'Trabajo en curso')
        ->set('lineas.0.descripcion', 'Ítem a medio escribir')
        ->set('lineas.0.valor_unitario', 1000)
        ->call('guardarBorrador');

    $borrador = CotizacionBorrador::where('user_id', $user->id)->whereNull('cotizacion_id')->first();

    expect($borrador)->not->toBeNull();
    expect($borrador->payload['descripcion'])->toBe('Trabajo en curso');
    expect($borrador->payload['cliente_id'])->toBe($cliente->id);
    expect($borrador->payload['lineas'][0]['descripcion'])->toBe('Ítem a medio escribir');
});

test('el autoguardado no crea un borrador si no hay contenido', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.form')
        ->call('guardarBorrador');

    expect(CotizacionBorrador::where('user_id', $user->id)->count())->toBe(0);
});

test('al montar el formulario nuevo recupera el borrador guardado', function () {
    $user = User::factory()->create();
    CotizacionBorrador::create([
        'user_id' => $user->id,
        'cotizacion_id' => null,
        'payload' => [
            'descripcion' => 'Recuperado',
            'clienteSearch' => 'Pepe',
            'lineas' => [
                ['_key' => 'k1', 'descripcion' => 'Línea 1', 'detalle_extendido' => '', 'mostrar_detalle' => false, 'unidad' => 'UN', 'cantidad' => 2, 'valor_unitario' => 5000],
            ],
        ],
    ]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.form')
        ->assertSet('descripcion', 'Recuperado')
        ->assertSet('clienteSearch', 'Pepe')
        ->assertSet('borradorRecuperado', true)
        ->assertCount('lineas', 1);
});

test('guardar la cotizacion elimina el borrador', function () {
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create();
    CotizacionBorrador::create([
        'user_id' => $user->id,
        'cotizacion_id' => null,
        'payload' => ['descripcion' => 'Borrador previo'],
    ]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.form')
        ->set('cliente_id', $cliente->id)
        ->set('descripcion', 'Cotización final')
        ->set('fecha_vencimiento', today()->addDays(20)->toDateString())
        ->set('lineas.0.descripcion', 'Cámara IP')
        ->set('lineas.0.cantidad', 1)
        ->set('lineas.0.valor_unitario', 100000)
        ->call('guardar')
        ->assertHasNoErrors();

    expect(CotizacionBorrador::where('user_id', $user->id)->count())->toBe(0);
});

test('descartar el borrador lo elimina y vuelve al formulario limpio', function () {
    $user = User::factory()->create();
    CotizacionBorrador::create([
        'user_id' => $user->id,
        'cotizacion_id' => null,
        'payload' => ['descripcion' => 'Algo a medias'],
    ]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.form')
        ->assertSet('descripcion', 'Algo a medias')
        ->call('descartarBorrador')
        ->assertRedirect(route('gestion.cotizaciones.create'));

    expect(CotizacionBorrador::where('user_id', $user->id)->count())->toBe(0);
});

test('no recupera el borrador de otro usuario', function () {
    $otro = User::factory()->create();
    CotizacionBorrador::create([
        'user_id' => $otro->id,
        'cotizacion_id' => null,
        'payload' => ['descripcion' => 'Ajeno'],
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.form')
        ->assertSet('borradorRecuperado', false)
        ->assertSet('descripcion', '');
});

test('en edicion recupera el borrador de esa cotizacion por sobre lo guardado', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create([
        'descripcion' => 'Lo guardado en la BD',
        'estado' => EstadoCotizacion::Borrador,
    ]);
    CotizacionBorrador::create([
        'user_id' => $user->id,
        'cotizacion_id' => $cotizacion->id,
        'payload' => ['descripcion' => 'Edición sin guardar'],
    ]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.form', ['cotizacion' => $cotizacion])
        ->assertSet('descripcion', 'Edición sin guardar')
        ->assertSet('borradorRecuperado', true);
});

test('el borrador de una cotizacion nueva no aparece al editar una existente', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Borrador]);
    CotizacionBorrador::create([
        'user_id' => $user->id,
        'cotizacion_id' => null,
        'payload' => ['descripcion' => 'Borrador de una nueva'],
    ]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.form', ['cotizacion' => $cotizacion])
        ->assertSet('borradorRecuperado', false);
});
