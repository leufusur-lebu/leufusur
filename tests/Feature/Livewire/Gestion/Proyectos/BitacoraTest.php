<?php

use App\Models\Actividad;
use App\Models\ActividadFoto;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

test('bitacora section renders on the proyecto show page', function () {
    $user = User::factory()->create();
    $proyecto = Proyecto::factory()->create();

    $this->actingAs($user)
        ->get(route('gestion.proyectos.show', $proyecto))
        ->assertOk()
        ->assertSeeVolt('gestion.proyectos.bitacora')
        ->assertSee('Bitácora de actividades');
});

test('can register an actividad', function () {
    $user = User::factory()->create();
    $proyecto = Proyecto::factory()->create();

    $this->actingAs($user);

    Volt::test('gestion.proyectos.bitacora', ['proyecto' => $proyecto])
        ->call('prepararNueva')
        ->set('fecha', '2026-08-20')
        ->set('hora_inicio', '09:00')
        ->set('hora_termino', '12:30')
        ->set('lugar', 'Local comercial Lebu')
        ->set('descripcion', 'Instalación de 4 cámaras')
        ->call('guardar')
        ->assertHasNoErrors();

    $actividad = Actividad::firstWhere('lugar', 'Local comercial Lebu');

    expect($actividad)->not->toBeNull();
    expect($actividad->proyecto_id)->toBe($proyecto->id);
    expect($actividad->duracionEnMinutos())->toBe(210);
    expect($actividad->duracionLegible())->toBe('3h 30m');
});

test('required fields are validated', function () {
    $user = User::factory()->create();
    $proyecto = Proyecto::factory()->create();

    $this->actingAs($user);

    Volt::test('gestion.proyectos.bitacora', ['proyecto' => $proyecto])
        ->call('prepararNueva')
        ->set('hora_inicio', '')
        ->set('hora_termino', '')
        ->set('lugar', '')
        ->set('descripcion', '')
        ->call('guardar')
        ->assertHasErrors(['hora_inicio', 'hora_termino', 'lugar', 'descripcion']);
});

test('hora_termino must be after hora_inicio', function () {
    $user = User::factory()->create();
    $proyecto = Proyecto::factory()->create();

    $this->actingAs($user);

    Volt::test('gestion.proyectos.bitacora', ['proyecto' => $proyecto])
        ->call('prepararNueva')
        ->set('hora_inicio', '14:00')
        ->set('hora_termino', '13:00')
        ->set('lugar', 'X')
        ->set('descripcion', 'Y')
        ->call('guardar')
        ->assertHasErrors(['hora_termino']);
});

test('can attach photos when registering an actividad', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $proyecto = Proyecto::factory()->create();

    $this->actingAs($user);

    Volt::test('gestion.proyectos.bitacora', ['proyecto' => $proyecto])
        ->call('prepararNueva')
        ->set('hora_inicio', '09:00')
        ->set('hora_termino', '10:00')
        ->set('lugar', 'Terreno')
        ->set('descripcion', 'Trabajo con respaldo fotográfico')
        ->set('fotos', [
            UploadedFile::fake()->image('antes.jpg'),
            UploadedFile::fake()->image('despues.jpg'),
        ])
        ->call('guardar')
        ->assertHasNoErrors();

    $actividad = Actividad::firstWhere('lugar', 'Terreno');

    expect($actividad->fotos)->toHaveCount(2);
    Storage::disk('local')->assertExists($actividad->fotos->first()->ruta);
});

test('can edit an existing actividad', function () {
    $user = User::factory()->create();
    $proyecto = Proyecto::factory()->create();
    $actividad = Actividad::factory()->create(['proyecto_id' => $proyecto->id, 'lugar' => 'Lugar original']);

    $this->actingAs($user);

    Volt::test('gestion.proyectos.bitacora', ['proyecto' => $proyecto])
        ->call('prepararEdicion', $actividad->id)
        ->assertSet('lugar', 'Lugar original')
        ->set('lugar', 'Lugar actualizado')
        ->call('guardar')
        ->assertHasNoErrors();

    expect($actividad->fresh()->lugar)->toBe('Lugar actualizado');
});

test('can delete an actividad and its photos', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $proyecto = Proyecto::factory()->create();
    $actividad = Actividad::factory()->create(['proyecto_id' => $proyecto->id]);
    $path = UploadedFile::fake()->image('f.jpg')->store('actividades', 'local');
    $foto = ActividadFoto::factory()->create(['actividad_id' => $actividad->id, 'ruta' => $path]);

    $this->actingAs($user);

    Volt::test('gestion.proyectos.bitacora', ['proyecto' => $proyecto])
        ->call('eliminar', $actividad->id);

    $this->assertModelMissing($actividad);
    $this->assertModelMissing($foto);
    Storage::disk('local')->assertMissing($path);
});

test('can delete a single photo', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $proyecto = Proyecto::factory()->create();
    $actividad = Actividad::factory()->create(['proyecto_id' => $proyecto->id]);
    $path = UploadedFile::fake()->image('f.jpg')->store('actividades', 'local');
    $foto = ActividadFoto::factory()->create(['actividad_id' => $actividad->id, 'ruta' => $path]);

    $this->actingAs($user);

    Volt::test('gestion.proyectos.bitacora', ['proyecto' => $proyecto])
        ->call('eliminarFoto', $foto->id);

    $this->assertModelMissing($foto);
    Storage::disk('local')->assertMissing($path);
    expect($actividad->fresh())->not->toBeNull();
});

test('deleting a proyecto cascades its actividades and photos', function () {
    $user = User::factory()->create();
    $proyecto = Proyecto::factory()->create();
    $actividad = Actividad::factory()
        ->has(ActividadFoto::factory()->count(2), 'fotos')
        ->create(['proyecto_id' => $proyecto->id]);

    $proyecto->delete();

    $this->assertDatabaseMissing('actividades', ['id' => $actividad->id]);
    $this->assertDatabaseMissing('actividad_fotos', ['actividad_id' => $actividad->id]);
});

test('foto route serves the image and 404s when missing', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $path = UploadedFile::fake()->image('f.jpg')->store('actividades', 'local');
    $conArchivo = ActividadFoto::factory()->create(['ruta' => $path]);
    $sinArchivo = ActividadFoto::factory()->create(['ruta' => 'actividades/no-existe.jpg']);

    $this->actingAs($user)
        ->get(route('gestion.actividad-fotos.ver', $conArchivo))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('gestion.actividad-fotos.ver', $sinArchivo))
        ->assertNotFound();
});

test('foto route is protected by auth', function () {
    $foto = ActividadFoto::factory()->create();

    $this->get(route('gestion.actividad-fotos.ver', $foto))
        ->assertRedirect(route('login'));
});
