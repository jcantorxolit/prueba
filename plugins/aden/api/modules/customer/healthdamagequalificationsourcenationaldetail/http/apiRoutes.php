<?php
    
	/**
     *Module: CustomerEmployee
     */
    // Route::get('customer-health-damage-qs-national-detail', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceNationalDetail\Http\Controllers\CustomerHealthDamageQualificationSourceNationalDetailDocumentController@show');
    // Route::post('customer-health-damage-qs-national-detail/save', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceNationalDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceNationalDetailDocumentController@store');
    // Route::post('customer-health-damage-qs-national-detail/update', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceNationalDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceNationalDetailDocumentController@update');
    // Route::post('customer-health-damage-qs-national-detail/delete', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceNationalDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceNationalDetailDocumentController@destroy');
    // Route::post('customer-health-damage-qs-national-detail/import', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceNationalDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceNationalDetailDocumentController@import');
    // Route::post('customer-health-damage-qs-national-detail/upload', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceNationalDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceNationalDetailDocumentController@upload');
    // Route::match(['post'], 'customer-health-damage-qs-national-detail', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceNationalDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceNationalDetailDocumentController@index');
    // Route::match(['get', 'post'], 'customer-health-damage-qs-national-detail/download', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceNationalDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceNationalDetailDocumentController@download');
    
    Route::match(['post'], 'customer-health-damage-qs-national-detail', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceNationalDetail\Http\Controllers\CustomerHealthDamageQualificationSourceNationalDetailController@indexHealthDamageQualificationSourceNationalDetail');
