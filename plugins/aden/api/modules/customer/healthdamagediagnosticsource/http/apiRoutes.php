<?php
    
	/**
     *Module: CustomerEmployee
     */
    // Route::get('customer-health-damage-diagnostic-source', 'AdeN\Api\Modules\Customer\HealthDamageDiagnosticSource\Http\Controllers\CustomerHealthDamageDiagnosticSourceController@show');
    // Route::post('customer-health-damage-diagnostic-source/save', 'AdeN\Api\Modules\Customer\HealthDamageDiagnosticSource\Http\Controllers\CustomerHealthDamageDiagnosticSourceController@store');
    // Route::post('customer-health-damage-diagnostic-source/update', 'AdeN\Api\Modules\Customer\HealthDamageDiagnosticSource\Http\Controllers\CustomerHealthDamageDiagnosticSourceController@update');
    // Route::post('customer-health-damage-diagnostic-source/delete', 'AdeN\Api\Modules\Customer\HealthDamageDiagnosticSource\Http\Controllers\CustomerHealthDamageDiagnosticSourceController@destroy');
    // Route::post('customer-health-damage-diagnostic-source/import', 'AdeN\Api\Modules\Customer\HealthDamageDiagnosticSource\Http\Controllers\CustomerHealthDamageDiagnosticSourceController@import');
    // Route::post('customer-health-damage-diagnostic-source/upload', 'AdeN\Api\Modules\Customer\HealthDamageDiagnosticSource\Http\Controllers\CustomerHealthDamageDiagnosticSourceController@upload');
    // Route::match(['post'], 'customer-health-damage-diagnostic-source', 'AdeN\Api\Modules\Customer\HealthDamageDiagnosticSource\Http\Controllers\CustomerHealthDamageDiagnosticSourceController@index');
    // Route::match(['get', 'post'], 'customer-health-damage-diagnostic-source/download', 'AdeN\Api\Modules\Customer\HealthDamageDiagnosticSource\Http\Controllers\CustomerHealthDamageDiagnosticSourceController@download');
    
    Route::match(['post'], 'customer-health-damage-diagnostic-source', 'AdeN\Api\Modules\Customer\HealthDamageDiagnosticSource\Http\Controllers\CustomerHealthDamageDiagnosticSourceController@indexHealthDamageDiagnosticSource');
