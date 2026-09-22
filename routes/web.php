<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AppointmentStatusController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProfessionalAvailabilityController;
use App\Http\Controllers\ProfessionalController;
use App\Http\Controllers\PrototypeController;
use App\Http\Controllers\ScheduleBlockController;
use App\Http\Controllers\ServiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    return redirect()->route($request->user() ? 'calendar.index' : 'login');
});

Route::group(['middleware' => 'guest'], function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->middleware('throttle:5,1')->name('authenticate');
});

Route::group(['middleware' => 'auth'], function () {
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::get('/appointments/{appointment}/edit', [AppointmentController::class, 'edit'])->name('appointments.edit');
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::post('/appointments/{appointment}/confirm', [AppointmentStatusController::class, 'confirm'])->name('appointments.confirm');
    Route::post('/appointments/{appointment}/complete', [AppointmentStatusController::class, 'complete'])->name('appointments.complete');
    Route::post('/appointments/{appointment}/cancel', [AppointmentStatusController::class, 'cancel'])->name('appointments.cancel');
    Route::post('/appointments/{appointment}/no-show', [AppointmentStatusController::class, 'noShow'])->name('appointments.no-show');

    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::patch('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');
    Route::patch('/services/{service}/restore', [ServiceController::class, 'restore'])->name('services.restore');

    Route::get('/professionals', [ProfessionalController::class, 'index'])->name('professionals.index');
    Route::post('/professionals', [ProfessionalController::class, 'store'])->name('professionals.store');
    Route::patch('/professionals/{professional}', [ProfessionalController::class, 'update'])->name('professionals.update');
    Route::delete('/professionals/{professional}', [ProfessionalController::class, 'destroy'])->name('professionals.destroy');
    Route::patch('/professionals/{professional}/restore', [ProfessionalController::class, 'restore'])->name('professionals.restore');
    Route::put('/professionals/{professional}/availability', [ProfessionalAvailabilityController::class, 'update'])->name('professionals.availability.update');
    Route::post('/professionals/{professional}/blocks', [ScheduleBlockController::class, 'store'])->name('professionals.blocks.store');
    Route::delete('/professionals/{professional}/blocks/{block}', [ScheduleBlockController::class, 'destroy'])->name('professionals.blocks.destroy');

    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');

    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');

    Route::patch('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');

    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');

    Route::patch('/clients/{client}/restore', [ClientController::class, 'restore'])->withTrashed()->name('clients.restore');

    Route::get('/calendar', CalendarController::class)->name('calendar.index');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::get('/prototipo/{page?}', PrototypeController::class)->name('prototype');
