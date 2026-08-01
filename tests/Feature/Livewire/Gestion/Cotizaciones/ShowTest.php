<?php

use App\Enums\EstadoCotizacion;
use App\Models\Cotizacion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

test('guests are redirected to login', function () {
    $cotizacion = Cotizacion::factory()->create();

    $this->get(route('gestion.cotizaciones.show', $cotizacion))
        ->assertRedirect(route('login'));
});

test('can render the show page', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create();

    $this->actingAs($user)
        ->get(route('gestion.cotizaciones.show', $cotizacion))
        ->assertOk()
        ->assertSee($cotizacion->numero_cotizacion);
});

test('marcarEnviada moves a borrador to enviada', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Borrador]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.show', ['cotizacion' => $cotizacion])
        ->call('marcarEnviada');

    $cotizacion->refresh();

    expect($cotizacion->estado)->toBe(EstadoCotizacion::Enviada);
    expect($cotizacion->enviada_en)->not->toBeNull();
});

test('marcarAprobada from borrador is rejected', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Borrador]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.show', ['cotizacion' => $cotizacion])
        ->call('marcarAprobada');

    expect($cotizacion->fresh()->estado)->toBe(EstadoCotizacion::Borrador);
});

test('marcarAprobada moves an enviada cotizacion to aprobada', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Enviada]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.show', ['cotizacion' => $cotizacion])
        ->call('marcarAprobada');

    $cotizacion->refresh();

    expect($cotizacion->estado)->toBe(EstadoCotizacion::Aprobada);
    expect($cotizacion->aprobada_en)->not->toBeNull();
});

test('marcarRechazada moves an enviada cotizacion to rechazada', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Enviada]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.show', ['cotizacion' => $cotizacion])
        ->call('marcarRechazada');

    $cotizacion->refresh();

    expect($cotizacion->estado)->toBe(EstadoCotizacion::Rechazada);
    expect($cotizacion->rechazada_en)->not->toBeNull();
});

test('duplicar creates a new borrador with copied lineas and redirects to edit', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);
    $cotizacion->lineas()->create([
        'descripcion' => 'Servicio A', 'cantidad' => 2, 'valor_unitario' => 15000, 'subtotal_calculado' => 30000, 'orden' => 0,
    ]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.show', ['cotizacion' => $cotizacion])
        ->call('duplicar');

    $nueva = Cotizacion::where('id', '!=', $cotizacion->id)->latest('id')->first();

    expect($nueva->estado)->toBe(EstadoCotizacion::Borrador);
    expect($nueva->cliente_id)->toBe($cotizacion->cliente_id);
    expect($nueva->lineas)->toHaveCount(1);
    expect((float) $nueva->total_calculado)->toBe(30000 * 1.19);
});

test('eliminar deletes the cotizacion and redirects to the index', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create();
    $cotizacion->lineas()->create([
        'descripcion' => 'Servicio A', 'cantidad' => 1, 'valor_unitario' => 10000, 'subtotal_calculado' => 10000, 'orden' => 0,
    ]);

    $this->actingAs($user);

    Volt::test('gestion.cotizaciones.show', ['cotizacion' => $cotizacion])
        ->call('eliminar')
        ->assertRedirect(route('gestion.cotizaciones.index'));

    $this->assertDatabaseMissing('cotizaciones', ['id' => $cotizacion->id]);
    $this->assertDatabaseMissing('lineas_cotizacion', ['cotizacion_id' => $cotizacion->id]);
});

test('pdf export returns a downloadable pdf', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create();
    $cotizacion->lineas()->create([
        'descripcion' => 'Servicio A', 'cantidad' => 1, 'valor_unitario' => 10000, 'subtotal_calculado' => 10000, 'orden' => 0,
    ]);

    $response = $this->actingAs($user)->get(route('gestion.cotizaciones.pdf', $cotizacion));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('adjunto download 404s when there is no attachment', function () {
    $user = User::factory()->create();
    $cotizacion = Cotizacion::factory()->create();

    $this->actingAs($user)
        ->get(route('gestion.cotizaciones.adjunto', $cotizacion))
        ->assertNotFound();
});

test('adjunto download works when a file is attached', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $path = UploadedFile::fake()->create('especificaciones.pdf', 10)->store('cotizaciones', 'local');
    $cotizacion = Cotizacion::factory()->create(['archivo_adjunto' => $path]);

    $this->actingAs($user)
        ->get(route('gestion.cotizaciones.adjunto', $cotizacion))
        ->assertOk();
});
