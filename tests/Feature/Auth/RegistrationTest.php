<?php

namespace Tests\Feature\Auth;

use Livewire\Volt\Volt;

test('registration route is disabled', function () {
    $response = $this->get('/gestion/register');

    $response->assertNotFound();
});

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.register');
})->skip('Registro deshabilitado: sistema privado de un solo administrador. Reactivar junto con la ruta en routes/auth.php.');

test('new users can register', function () {
    $component = Volt::test('pages.auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password');

    $component->call('register');

    $component->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
})->skip('Registro deshabilitado: sistema privado de un solo administrador. Reactivar junto con la ruta en routes/auth.php.');
