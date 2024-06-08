<?php
    
	/**
     *Module: CustomerEmployee
     */
    // Route::get('customer-health-damage-restriction-observation', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionObservation\Http\Controllers\CustomerHealthDamageRestrictionObservationController@show');
    // Route::post('customer-health-damage-restriction-observation/save', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionObservation\Http\Controllers\CustomerHealthDamageRestrictionObservationController@store');
    // Route::post('customer-health-damage-restriction-observation/update', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionObservation\Http\Controllers\CustomerHealthDamageRestrictionObservationController@update');
    // Route::post('customer-health-damage-restriction-observation/delete', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionObservation\Http\Controllers\CustomerHealthDamageRestrictionObservationController@destroy');
    // Route::post('customer-health-damage-restriction-observation/import', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionObservation\Http\Controllers\CustomerHealthDamageRestrictionObservationController@import');
    // Route::post('customer-health-damage-restriction-observation/upload', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionObservation\Http\Controllers\CustomerHealthDamageRestrictionObservationController@upload');
    // Route::match(['post'], 'customer-health-damage-restriction-observation', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionObservation\Http\Controllers\CustomerHealthDamageRestrictionObservationController@index');
    // Route::match(['get', 'post'], 'customer-health-damage-restriction-observation/download', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionObservation\Http\Controllers\CustomerHealthDamageRestrictionObservationController@download');
    
    Route::match(['post'], 'customer-health-damage-restriction-observation', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionObservation\Http\Controllers\CustomerHealthDamageRestrictionObservationController@indexHealthDamageRestrictionObservation');
    Route::match(['post'], 'customer-health-damage-restriction-observation-all', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionObservation\Http\Controllers\CustomerHealthDamageRestrictionObservationController@indexHealthDamageRestrictionObservationAll');
