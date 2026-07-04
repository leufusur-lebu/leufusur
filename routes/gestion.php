<?php

use App\Http\Controllers\Gestion\CotizacionAdjuntoController;
use App\Http\Controllers\Gestion\CotizacionPdfController;
use App\Http\Controllers\Gestion\GastoComprobanteController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::prefix('gestion')->group(function () {
    require __DIR__.'/auth.php';

    Volt::route('', 'gestion.dashboard')
        ->middleware('auth')
        ->name('dashboard');

    Route::view('perfil', 'profile')
        ->middleware('auth')
        ->name('profile');

    Route::middleware('auth')->name('gestion.')->group(function () {
        Volt::route('clientes', 'gestion.clientes.index')->name('clientes.index');
        Volt::route('clientes/crear', 'gestion.clientes.form')->name('clientes.create');
        Volt::route('clientes/{cliente}/editar', 'gestion.clientes.form')->name('clientes.edit');

        Volt::route('cotizaciones', 'gestion.cotizaciones.index')->name('cotizaciones.index');
        Volt::route('cotizaciones/crear', 'gestion.cotizaciones.form')->name('cotizaciones.create');
        Volt::route('cotizaciones/{cotizacion}/editar', 'gestion.cotizaciones.form')->name('cotizaciones.edit');
        Route::get('cotizaciones/{cotizacion}/pdf', CotizacionPdfController::class)->name('cotizaciones.pdf');
        Route::get('cotizaciones/{cotizacion}/adjunto', CotizacionAdjuntoController::class)->name('cotizaciones.adjunto');
        Volt::route('cotizaciones/{cotizacion}', 'gestion.cotizaciones.show')->name('cotizaciones.show');

        Route::get('gastos/{gasto}/comprobante', GastoComprobanteController::class)->name('gastos.comprobante');
    });
});
