<?php
    
	/**
     *Module: CustomerEmployee
     */
    // Route::get('customer-health-damage-ql', 'AdeN\Api\Modules\Customer\HealthDamageQualificationLost\Http\Controllers\CustomerHealthDamageQualificationLostController@show');
    // Route::post('customer-health-damage-ql/save', 'AdeN\Api\Modules\Customer\HealthDamageQualificationLost\Http\Controllers\CustomerHealthDamageQualificationLostController@store');
    // Route::post('customer-health-damage-ql/update', 'AdeN\Api\Modules\Customer\HealthDamageQualificationLost\Http\Controllers\CustomerHealthDamageQualificationLostController@update');
    // Route::post('customer-health-damage-ql/delete', 'AdeN\Api\Modules\Customer\HealthDamageQualificationLost\Http\Controllers\CustomerHealthDamageQualificationLostController@destroy');
    // Route::post('customer-health-damage-ql/import', 'AdeN\Api\Modules\Customer\HealthDamageQualificationLost\Http\Controllers\CustomerHealthDamageQualificationLostController@import');
    // Route::post('customer-health-damage-ql/upload', 'AdeN\Api\Modules\Customer\HealthDamageQualificationLost\Http\Controllers\CustomerHealthDamageQualificationLostController@upload');
    // Route::match(['post'], 'customer-health-damage-ql', 'AdeN\Api\Modules\Customer\HealthDamageQualificationLost\Http\Controllers\CustomerHealthDamageQualificationLostController@index');
    // Route::match(['get', 'post'], 'customer-health-damage-ql/download', 'AdeN\Api\Modules\Customer\HealthDamageQualificationLost\Http\Controllers\CustomerHealthDamageQualificationLostController@download');
    
    Route::match(['post'], 'customer-health-damage-ql', 'AdeN\Api\Modules\Customer\HealthDamageQualificationLost\Http\Controllers\CustomerHealthDamageQualificationLostController@indexHealthDamageQualificationLost');
