<?php
    
	/**
     *Module: CustomerEmployee
     */
    // Route::get('customer-health-damage-restriction-document', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionDocument\Http\Controllers\CustomerHealthDamageRestrictionDocumentController@show');
    // Route::post('customer-health-damage-restriction-document/save', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionDocument\Http\Controllers\CustomerHealthDamageRestrictionDocumentController@store');
    // Route::post('customer-health-damage-restriction-document/update', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionDocument\Http\Controllers\CustomerHealthDamageRestrictionDocumentController@update');
    // Route::post('customer-health-damage-restriction-document/delete', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionDocument\Http\Controllers\CustomerHealthDamageRestrictionDocumentController@destroy');
    // Route::post('customer-health-damage-restriction-document/import', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionDocument\Http\Controllers\CustomerHealthDamageRestrictionDocumentController@import');
    // Route::post('customer-health-damage-restriction-document/upload', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionDocument\Http\Controllers\CustomerHealthDamageRestrictionDocumentController@upload');
    // Route::match(['post'], 'customer-health-damage-restriction-document', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionDocument\Http\Controllers\CustomerHealthDamageRestrictionDocumentController@index');
    // Route::match(['get', 'post'], 'customer-health-damage-restriction-document/download', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionDocument\Http\Controllers\CustomerHealthDamageRestrictionDocumentController@download');
    
    Route::match(['post'], 'customer-health-damage-restriction-document', 'AdeN\Api\Modules\Customer\HealthDamageRestrictionDocument\Http\Controllers\CustomerHealthDamageRestrictionDocumentController@indexHealthDamageRestrictionDocument');