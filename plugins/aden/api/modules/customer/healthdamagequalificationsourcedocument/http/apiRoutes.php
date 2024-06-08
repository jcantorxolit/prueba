<?php
    
	/**
     *Module: CustomerEmployee
     */
    // Route::get('customer-health-damage-qs-document', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSource\Http\Controllers\CustomerHealthDamageQualificationSourceDocumentController@show');
    // Route::post('customer-health-damage-qs-document/save', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceDocument\Http\Controllers\CustomerHealthDamageQualificationSourceDocumentController@store');
    // Route::post('customer-health-damage-qs-document/update', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceDocument\Http\Controllers\CustomerHealthDamageQualificationSourceDocumentController@update');
    // Route::post('customer-health-damage-qs-document/delete', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceDocument\Http\Controllers\CustomerHealthDamageQualificationSourceDocumentController@destroy');
    // Route::post('customer-health-damage-qs-document/import', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceDocument\Http\Controllers\CustomerHealthDamageQualificationSourceDocumentController@import');
    // Route::post('customer-health-damage-qs-document/upload', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceDocument\Http\Controllers\CustomerHealthDamageQualificationSourceDocumentController@upload');
    // Route::match(['post'], 'customer-health-damage-qs-document', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceDocument\Http\Controllers\CustomerHealthDamageQualificationSourceDocumentController@index');
    // Route::match(['get', 'post'], 'customer-health-damage-qs-document/download', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceDocument\Http\Controllers\CustomerHealthDamageQualificationSourceDocumentController@download');
    
    Route::match(['post'], 'customer-health-damage-qs-document', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceDocument\Http\Controllers\CustomerHealthDamageQualificationSourceDocumentController@indexHealthDamageQualificationSourceDocument');