<?php
    
	/**
     *Module: CustomerEmployee
     */
    // Route::get('customer-health-damage-diagnostic-source-detail', 'AdeN\Api\Modules\Customer\HealthDamageDiagnosticSourceDetail\Http\Controllers\CustomerHealthDamageDiagnosticSourceDetailController@show');
    // Route::post('customer-health-damage-diagnostic-source-detail/save', 'AdeN\Api\Modules\Customer\HealthDamageDiagnosticSourceDetail\Http\Controllers\CustomerHealthDamageDiagnosticSourceDetailController@store');
    // Route::post('customer-health-damage-diagnostic-source-detail/update', 'AdeN\Api\Modules\Customer\HealthDamageDiagnosticSourceDetail\Http\Controllers\CustomerHealthDamageDiagnosticSourceDetailController@update');
    // Route::post('customer-health-damage-diagnostic-source-detail/delete', 'AdeN\Api\Modules\Customer\HealthDamageDiagnosticSourceDetail\Http\Controllers\CustomerHealthDamageDiagnosticSourceDetailController@destroy');
    // Route::post('customer-health-damage-diagnostic-source-detail/import', 'AdeN\Api\Modules\Customer\HealthDamageDiagnosticSourceDetail\Http\Controllers\CustomerHealthDamageDiagnosticSourceDetailController@import');
    // Route::post('customer-health-damage-diagnostic-source-detail/upload', 'AdeN\Api\Modules\Customer\HealthDamageDiagnosticSourceDetail\Http\Controllers\CustomerHealthDamageDiagnosticSourceDetailController@upload');
    // Route::match(['post'], 'customer-health-damage-diagnostic-source-detail', 'AdeN\Api\Modules\Customer\HealthDamageDiagnosticSourceDetail\Http\Controllers\CustomerHealthDamageDiagnosticSourceDetailController@index');
    // Route::match(['get', 'post'], 'customer-health-damage-diagnostic-source-detail/download', 'AdeN\Api\Modules\Customer\HealthDamageDiagnosticSourceDetail\Http\Controllers\CustomerHealthDamageDiagnosticSourceDetailController@download');
    
    Route::match(['post'], 'customer-health-damage-diagnostic-source-detail', 'AdeN\Api\Modules\Customer\HealthDamageDiagnosticSourceDetail\Http\Controllers\CustomerHealthDamageDiagnosticSourceDetailController@indexHealthDamageDiagnosticSourceDetail');
