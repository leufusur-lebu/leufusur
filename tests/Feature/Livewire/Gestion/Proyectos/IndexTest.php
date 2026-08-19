<?php

use App\Enums\EstadoProyecto;
use App\Models\Gasto;
use App\Models\Proyecto;
use App\Models\User;
use Livewire\Volt\Volt;

test('guests are redirected to login', function () {
    $this->get(route('gestion.proyectos.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the proyectos list', function () {
    $user = User::factory()->create();
    $proyecto = Proyecto::factory()->create(['nombre' => 'Instalación de red']);

    $this->actingAs($user)
        ->get(route('gestion.proyectos.index'))
        ->assertOk()
        ->assertSee('Instalación de red')
        ->assertSee($proyecto->cliente->nombre);
});

test('search filters by nombre or cliente', function () {
    $user = User::factory()->create();
    $uno = Proyecto::factory()->create(['nombre' => 'Cámaras de seguridad']);
    $dos = Proyecto::factory()->create(['nombre' => 'Cableado estructurado']);

    $this->actingAs($user);

    Volt::test('gestion.proyectos.index')
        ->set('search', 'Cámaras')
        ->assertSee('Cámaras de seguridad')
        ->assertDontSee('Cableado estructurado');
});

test('filter by estado narrows the list', function () {
    $user = User::factory()->create();
    $activo = Proyecto::factory()->create(['nombre' => 'Proyecto activo', 'estado' => EstadoProyecto::Activo]);
    $cerrado = Proyecto::factory()->create(['nombre' => 'Proyecto cerrado', 'estado' => EstadoProyecto::Cerrado]);

    $this->actingAs($user);

    Volt::test('gestion.proyectos.index')
        ->set('estado', EstadoProyecto::Cerrado->value)
        ->assertSee('Proyecto cerrado')
        ->assertDontSee('Proyecto activo');
});

test('can delete a proyecto along with its gastos', function () {
    $user = User::factory()->create();
    $proyecto = Proyecto::factory()
        ->has(Gasto::factory()->count(2), 'gastos')
        ->create();

    $this->actingAs($user);

    Volt::test('gestion.proyectos.index')
        ->call('eliminar', $proyecto->id);

    $this->assertDatabaseMissing('proyectos', ['id' => $proyecto->id]);
    $this->assertDatabaseMissing('gastos', ['proyecto_id' => $proyecto->id]);
});
