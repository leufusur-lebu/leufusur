<?php

use App\Enums\EstadoCotizacion;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Gasto;
use App\Models\User;
use Livewire\Volt\Volt;

test('guests are redirected to login', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

test('shows an empty state with no data', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Aún no hay cotizaciones registradas.');
});

test('shows correct aggregate statistics', function () {
    $user = User::factory()->create();
    Cliente::factory()->count(2)->create();

    Cotizacion::factory()->create(['estado' => EstadoCotizacion::Borrador]);
    Cotizacion::factory()->create(['estado' => EstadoCotizacion::Enviada]);
    $aprobada = Cotizacion::factory()->create(['estado' => EstadoCotizacion::Aprobada]);
    Cotizacion::factory()->create(['estado' => EstadoCotizacion::Rechazada]);

    Gasto::factory()->create(['cotizacion_id' => $aprobada->id, 'total_calculado' => 50000]);
    Gasto::factory()->create(['cotizacion_id' => $aprobada->id, 'total_calculado' => 25000]);

    $this->actingAs($user);

    $estadisticas = Volt::test('gestion.dashboard')->get('estadisticas');

    expect($estadisticas['cotizacionesActivas'])->toBe(2);
    expect($estadisticas['cotizacionesAprobadas'])->toBe(1);
    expect((float) $estadisticas['totalGastos'])->toBe(75000.0);
    // 2 clientes propios + 4 auto-generados por las cotizaciones de factory
    expect($estadisticas['totalClientes'])->toBe(6);
});

test('shows the 5 most recent cotizaciones', function () {
    $user = User::factory()->create();
    Cotizacion::factory()->count(7)->create();

    $this->actingAs($user);

    $recientes = Volt::test('gestion.dashboard')->get('ultimasCotizaciones');

    expect($recientes)->toHaveCount(5);
});

test('quick action links point to the right routes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('gestion.cotizaciones.create'), false)
        ->assertSee(route('gestion.clientes.create'), false)
        ->assertSee(route('gestion.cotizaciones.index'), false);
});
