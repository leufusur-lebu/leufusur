<?php

test('la página de inicio carga correctamente', function () {
    $this->get('/')
        ->assertStatus(200)
        ->assertSee('Leufu Sur');
});

test('la página de inicio muestra las secciones principales', function () {
    $response = $this->get('/');

    $response->assertSee('Nosotros');
    $response->assertSee('Servicios');
    $response->assertSee('Contacto');
});

test('la página de inicio destaca los servicios clave', function () {
    $response = $this->get('/');

    $response->assertSee('Cámaras de seguridad');
    $response->assertSee('Desarrollo web');
    $response->assertSee('Redes de datos');
    $response->assertSee('Mercado Público');
});
