<?php

/**
 *Module: Management
 */

Route::get('positiva-fgn-fgn-management/get', 'AdeN\Api\Modules\PositivaFgn\Management\Http\Controllers\ConfigManagementController@show');
Route::get('positiva-fgn-fgn-management/getPoblation', 'AdeN\Api\Modules\PositivaFgn\Management\Http\Controllers\ConfigManagementController@showPoblation');
Route::get('positiva-fgn-fgn-management/getPoblationBase', 'AdeN\Api\Modules\PositivaFgn\Management\Http\Controllers\ConfigManagementController@showPoblationBase');
Route::post('positiva-fgn-fgn-management/getPoblationBase/totals', 'AdeN\Api\Modules\PositivaFgn\Management\Http\Controllers\ConfigManagementController@getPoblationTotals');
Route::post('positiva-fgn-fgn-management/save', 'AdeN\Api\Modules\PositivaFgn\Management\Http\Controllers\ConfigManagementController@store');
Route::post('positiva-fgn-fgn-management/delete', 'AdeN\Api\Modules\PositivaFgn\Management\Http\Controllers\ConfigManagementController@destroy');
Route::post('positiva-fgn-fgn-management/config', 'AdeN\Api\Modules\PositivaFgn\Management\Http\Controllers\ConfigManagementController@config');

Route::post('positiva-fgn-fgn-management/activitiesProgrammingExecution', 'AdeN\Api\Modules\PositivaFgn\Management\Http\Controllers\ConfigManagementController@activitiesProgrammingExecution');
Route::post('positiva-fgn-fgn-management/filterAssignment', 'AdeN\Api\Modules\PositivaFgn\Management\Http\Controllers\ConfigManagementController@filterAssignment');



Route::post('positiva-fgn-fgn-management/compliance-logs', 'AdeN\Api\Modules\PositivaFgn\Management\Http\Controllers\ConfigManagementController@complianceLogs');
Route::post('positiva-fgn-fgn-management/compliance-logs/store', 'AdeN\Api\Modules\PositivaFgn\Management\Http\Controllers\ConfigManagementController@storeComplianceLogs');
Route::post('positiva-fgn-fgn-management/compliance-logs/show', 'AdeN\Api\Modules\PositivaFgn\Management\Http\Controllers\ConfigManagementController@showComplianceLogs');
Route::post('positiva-fgn-fgn-management/compliance-logs/totals', 'AdeN\Api\Modules\PositivaFgn\Management\Http\Controllers\ConfigManagementController@getTotalsComplianceLogs');
Route::post('positiva-fgn-fgn-management/compliance-logs/delete', 'AdeN\Api\Modules\PositivaFgn\Management\Http\Controllers\ConfigManagementController@deleteComplianceLogs');


Route::post('positiva-fgn-fgn-management/population', 'AdeN\Api\Modules\PositivaFgn\Management\Http\Controllers\ConfigManagementController@populationIndex');
Route::post('positiva-fgn-fgn-management/population/save', 'AdeN\Api\Modules\PositivaFgn\Management\Http\Controllers\ConfigManagementController@storePopulation');
Route::post('positiva-fgn-fgn-management/population/delete', 'AdeN\Api\Modules\PositivaFgn\Management\Http\Controllers\ConfigManagementController@deletePopulation');
