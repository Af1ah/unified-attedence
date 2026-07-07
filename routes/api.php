<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

use App\Http\Controllers\Api\Attendance\CDataController;
use App\Http\Controllers\Api\Attendance\DeviceCmdController;
use App\Http\Controllers\Api\Attendance\GetRequestController;

Route::group([
    'prefix' => 'iclock',
], function () {
    Route::match(['get', 'post'], 'cdata', CDataController::class)->name('cdata');
    Route::get('getrequest', GetRequestController::class)->name('getrequest');
    Route::match(['get', 'post'], 'devicecmd', DeviceCmdController::class)->name('devicecmd');
    Route::match(['get', 'post'], 'test', fn () => response('OK'))->name('test');
});

