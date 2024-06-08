<?php
    
	/**
     *Module: CustomerEmployee
     */
    // Route::get('customer-health-damage-qs', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSource\Http\Controllers\CustomerHealthDamageQualificationSourceController@show');
    // Route::post('customer-health-damage-qs/save', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSource\Http\Controllers\CustomerHealthDamageQualificationSourceController@store');
    // Route::post('customer-health-damage-qs/update', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSource\Http\Controllers\CustomerHealthDamageQualificationSourceController@update');
    // Route::post('customer-health-damage-qs/delete', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSource\Http\Controllers\CustomerHealthDamageQualificationSourceController@destroy');
    // Route::post('customer-health-damage-qs/import', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSource\Http\Controllers\CustomerHealthDamageQualificationSourceController@import');
    // Route::post('customer-health-damage-qs/upload', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSource\Http\Controllers\CustomerHealthDamageQualificationSourceController@upload');
    // Route::match(['post'], 'customer-health-damage-qs', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSource\Http\Controllers\CustomerHealthDamageQualificationSourceController@index');
    // Route::match(['get', 'post'], 'customer-health-damage-qs/download', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSource\Http\Controllers\CustomerHealthDamageQualificationSourceController@download');
    
    Route::match(['post'], 'customer-health-damage-qs', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSource\Http\Controllers\CustomerHealthDamageQualificationSourceController@indexHealthDamageQualificationSource');
