<?php

/**
 *Module: CustomerProjectHistorials
 */
Route::get('customer-project-historial/get', 'AdeN\Api\Modules\Customer\ProjectHistorial\Http\Controllers\CustomerProjectHistorialController@show');
Route::post('customer-project-historial/save', 'AdeN\Api\Modules\Customer\ProjectHistorial\Http\Controllers\CustomerProjectHistorialController@store');
Route::post('customer-project-historial/delete', 'AdeN\Api\Modules\Customer\ProjectHistorial\Http\Controllers\CustomerProjectHistorialController@destroy');

Route::match(['get', 'post'], 'customer-project-historial', 'AdeN\Api\Modules\Customer\ProjectHistorial\Http\Controllers\CustomerProjectHistorialController@index');
Route::match(['get', 'post'], 'customer-project-historial/download', 'AdeN\Api\Modules\Customer\ProjectHistorial\Http\Controllers\CustomerProjectHistorialController@download');