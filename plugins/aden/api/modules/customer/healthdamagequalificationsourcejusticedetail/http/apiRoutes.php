<?php
    
	/**
     *Module: CustomerEmployee
     */
    // Route::get('customer-health-damage-qs-justice-detail', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceJusticeDetail\Http\Controllers\CustomerHealthDamageQualificationSourceJusticeDetailDocumentController@show');
    // Route::post('customer-health-damage-qs-justice-detail/save', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceJusticeDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceJusticeDetailDocumentController@store');
    // Route::post('customer-health-damage-qs-justice-detail/update', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceJusticeDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceJusticeDetailDocumentController@update');
    // Route::post('customer-health-damage-qs-justice-detail/delete', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceJusticeDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceJusticeDetailDocumentController@destroy');
    // Route::post('customer-health-damage-qs-justice-detail/import', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceJusticeDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceJusticeDetailDocumentController@import');
    // Route::post('customer-health-damage-qs-justice-detail/upload', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceJusticeDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceJusticeDetailDocumentController@upload');
    // Route::match(['post'], 'customer-health-damage-qs-justice-detail', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceJusticeDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceJusticeDetailDocumentController@index');
    // Route::match(['get', 'post'], 'customer-health-damage-qs-justice-detail/download', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceJusticeDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceJusticeDetailDocumentController@download');
    
    Route::match(['post'], 'customer-health-damage-qs-justice-detail', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceJusticeDetail\Http\Controllers\CustomerHealthDamageQualificationSourceJusticeDetailController@indexHealthDamageQualificationSourceJusticeDetail');
