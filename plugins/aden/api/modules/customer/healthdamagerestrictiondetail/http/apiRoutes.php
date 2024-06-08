<?php
    
	/**
     *Module: CustomerEmployee
     */
    // Route::get('customer-health-damage-restriction-detail', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionDetail\Http\Controllers\CustomerHealthDamageRestrictionDetailController@show');
    // Route::post('customer-health-damage-restriction-detail/save', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionDetail\Http\Controllers\CustomerHealthDamageRestrictionDetailController@store');
    // Route::post('customer-health-damage-restriction-detail/update', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionDetail\Http\Controllers\CustomerHealthDamageRestrictionDetailController@update');
    // Route::post('customer-health-damage-restriction-detail/delete', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionDetail\Http\Controllers\CustomerHealthDamageRestrictionDetailController@destroy');
    // Route::post('customer-health-damage-restriction-detail/import', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionDetail\Http\Controllers\CustomerHealthDamageRestrictionDetailController@import');
    // Route::post('customer-health-damage-restriction-detail/upload', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionDetail\Http\Controllers\CustomerHealthDamageRestrictionDetailController@upload');
    // Route::match(['post'], 'customer-health-damage-restriction-detail', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionDetail\Http\Controllers\CustomerHealthDamageRestrictionDetailController@index');
    // Route::match(['get', 'post'], 'customer-health-damage-restriction-detail/download', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionDetail\Http\Controllers\CustomerHealthDamageRestrictionDetailController@download');
    
    Route::match(['post'], 'customer-health-damage-restriction-detail', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionDetail\Http\Controllers\CustomerHealthDamageRestrictionDetailController@indexHealthDamageRestrictionDetail');
