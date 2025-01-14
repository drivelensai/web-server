<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ViolationController;
use App\Http\Controllers\ReportController;


Route::get('/', [ViolationController::class, 'showForm'])->name('video.upload.form');
Route::post('/upload-video', [ViolationController::class, 'store'])->name('video.upload.store');
Route::get('/video-status/{video}', [ViolationController::class, 'status'])->name('video.upload.status');

Route::get('/report', [ReportController::class, 'index'])->name('report.index');
Route::get('/report', [ReportController::class, 'fetchData'])->name('report.fetch');