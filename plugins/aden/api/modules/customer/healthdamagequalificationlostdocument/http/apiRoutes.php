<?php
    
	/**
     *Module: CustomerEmployee
     */
    // Route::get('customer-health-damage-ql-document', 'AdeN\Api\Modules\Customer\HealthDamageQualificationLost\Http\Controllers\CustomerHealthDamageQualificationLostDocumentController@show');
    // Route::post('customer-health-damage-ql-document/save', 'AdeN\Api\Modules\Customer\HealthDamageQualificationLostDocument\Http\Controllers\CustomerHealthDamageQualificationLostDocumentController@store');
    // Route::post('customer-health-damage-ql-document/update', 'AdeN\Api\Modules\Customer\HealthDamageQualificationLostDocument\Http\Controllers\CustomerHealthDamageQualificationLostDocumentController@update');
    // Route::post('customer-health-damage-ql-document/delete', 'AdeN\Api\Modules\Customer\HealthDamageQualificationLostDocument\Http\Controllers\CustomerHealthDamageQualificationLostDocumentController@destroy');
    // Route::post('customer-health-damage-ql-document/import', 'AdeN\Api\Modules\Customer\HealthDamageQualificationLostDocument\Http\Controllers\CustomerHealthDamageQualificationLostDocumentController@import');
    // Route::post('customer-health-damage-ql-document/upload', 'AdeN\Api\Modules\Customer\HealthDamageQualificationLostDocument\Http\Controllers\CustomerHealthDamageQualificationLostDocumentController@upload');
    // Route::match(['post'], 'customer-health-damage-ql-document', 'AdeN\Api\Modules\Customer\HealthDamageQualificationLostDocument\Http\Controllers\CustomerHealthDamageQualificationLostDocumentController@index');
    // Route::match(['get', 'post'], 'customer-health-damage-ql-document/download', 'AdeN\Api\Modules\Customer\HealthDamageQualificationLostDocument\Http\Controllers\CustomerHealthDamageQualificationLostDocumentController@download');
    
    Route::match(['post'], 'customer-health-damage-ql-document', 'AdeN\Api\Modules\Customer\HealthDamageQualificationLostDocument\Http\Controllers\CustomerHealthDamageQualificationLostDocumentController@indexHealthDamageQualificationLostDocument');