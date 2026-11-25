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

test('la página de inicio enlaza a las redes sociales y WhatsApp', function () {
    $response = $this->get('/');

    $response->assertSee('https://www.facebook.com/profile.php?id=61596128236063');
    $response->assertSee('https://wa.me/56962471305');
});
