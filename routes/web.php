<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

use App\Http\Controllers\Api\Attendance\CDataController;
use App\Http\Controllers\Api\Attendance\DeviceCmdController;
use App\Http\Controllers\Api\Attendance\GetRequestController;

Route::group([
    'prefix' => 'iclock',
], function () {
    Route::match(['get', 'post'], 'cdata', CDataController::class)->name('cdata');
    Route::match(['get', 'post'], 'cdata.aspx', CDataController::class);
    
    Route::get('getrequest', GetRequestController::class)->name('getrequest');
    Route::get('getrequest.aspx', GetRequestController::class);
    
    Route::match(['get', 'post'], 'devicecmd', DeviceCmdController::class)->name('devicecmd');
    Route::match(['get', 'post'], 'devicecmd.aspx', DeviceCmdController::class);
    
    Route::match(['get', 'post'], 'test', fn () => response('OK'))->name('test');
});
