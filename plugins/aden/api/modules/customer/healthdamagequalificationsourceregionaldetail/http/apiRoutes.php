<?php
    
	/**
     *Module: CustomerEmployee
     */
    // Route::get('customer-health-damage-qs-regional-detail', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceRegionalDetail\Http\Controllers\CustomerHealthDamageQualificationSourceRegionalDetailDocumentController@show');
    // Route::post('customer-health-damage-qs-regional-detail/save', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceRegionalDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceRegionalDetailDocumentController@store');
    // Route::post('customer-health-damage-qs-regional-detail/update', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceRegionalDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceRegionalDetailDocumentController@update');
    // Route::post('customer-health-damage-qs-regional-detail/delete', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceRegionalDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceRegionalDetailDocumentController@destroy');
    // Route::post('customer-health-damage-qs-regional-detail/import', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceRegionalDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceRegionalDetailDocumentController@import');
    // Route::post('customer-health-damage-qs-regional-detail/upload', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceRegionalDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceRegionalDetailDocumentController@upload');
    // Route::match(['post'], 'customer-health-damage-qs-regional-detail', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceRegionalDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceRegionalDetailDocumentController@index');
    // Route::match(['get', 'post'], 'customer-health-damage-qs-regional-detail/download', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceRegionalDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceRegionalDetailDocumentController@download');
    
    Route::match(['post'], 'customer-health-damage-qs-regional-detail', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceRegionalDetail\Http\Controllers\CustomerHealthDamageQualificationSourceRegionalDetailController@indexHealthDamageQualificationSourceRegionalDetail');
