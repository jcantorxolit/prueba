<?php
    
	/**
     *Module: CustomerEmployee
     */
    // Route::get('customer-health-damage-restriction', 'AdeN\Api\Modules\Customer\HealthDamageRestriction\Http\Controllers\CustomerHealthDamageRestrictionController@show');
    // Route::post('customer-health-damage-restriction/save', 'AdeN\Api\Modules\Customer\HealthDamageRestriction\Http\Controllers\CustomerHealthDamageRestrictionController@store');
    // Route::post('customer-health-damage-restriction/update', 'AdeN\Api\Modules\Customer\HealthDamageRestriction\Http\Controllers\CustomerHealthDamageRestrictionController@update');
    // Route::post('customer-health-damage-restriction/delete', 'AdeN\Api\Modules\Customer\HealthDamageRestriction\Http\Controllers\CustomerHealthDamageRestrictionController@destroy');
    // Route::post('customer-health-damage-restriction/import', 'AdeN\Api\Modules\Customer\HealthDamageRestriction\Http\Controllers\CustomerHealthDamageRestrictionController@import');
    // Route::post('customer-health-damage-restriction/upload', 'AdeN\Api\Modules\Customer\HealthDamageRestriction\Http\Controllers\CustomerHealthDamageRestrictionController@upload');
    // Route::match(['post'], 'customer-health-damage-restriction', 'AdeN\Api\Modules\Customer\HealthDamageRestriction\Http\Controllers\CustomerHealthDamageRestrictionController@index');
    // Route::match(['get', 'post'], 'customer-health-damage-restriction/download', 'AdeN\Api\Modules\Customer\HealthDamageRestriction\Http\Controllers\CustomerHealthDamageRestrictionController@download');
    
    Route::match(['post'], 'customer-health-damage-restriction', 'AdeN\Api\Modules\Customer\HealthDamageRestriction\Http\Controllers\CustomerHealthDamageRestrictionController@indexHealthDamageRestriction');
