<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Detrás de Cloudflare la petición llega al contenedor como http, pero el visitante
// navega en https (cabecera CF-Visitor). La marcamos como segura para que TODA la
// petición se trate como https: así las URLs firmadas (p. ej. la subida temporal de
// Livewire) se generan Y validan con el mismo esquema y no las bloquea el navegador.
if (str_contains($_SERVER['HTTP_CF_VISITOR'] ?? '', '"scheme":"https"')) {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = 443;
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
