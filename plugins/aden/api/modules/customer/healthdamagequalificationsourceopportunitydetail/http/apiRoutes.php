<?php
    
	/**
     *Module: CustomerEmployee
     */
    // Route::get('customer-health-damage-qs-opportunity-detail', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceOpportunityDetail\Http\Controllers\CustomerHealthDamageQualificationSourceOpportunityDetailDocumentController@show');
    // Route::post('customer-health-damage-qs-opportunity-detail/save', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceOpportunityDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceOpportunityDetailDocumentController@store');
    // Route::post('customer-health-damage-qs-opportunity-detail/update', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceOpportunityDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceOpportunityDetailDocumentController@update');
    // Route::post('customer-health-damage-qs-opportunity-detail/delete', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceOpportunityDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceOpportunityDetailDocumentController@destroy');
    // Route::post('customer-health-damage-qs-opportunity-detail/import', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceOpportunityDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceOpportunityDetailDocumentController@import');
    // Route::post('customer-health-damage-qs-opportunity-detail/upload', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceOpportunityDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceOpportunityDetailDocumentController@upload');
    // Route::match(['post'], 'customer-health-damage-qs-opportunity-detail', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceOpportunityDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceOpportunityDetailDocumentController@index');
    // Route::match(['get', 'post'], 'customer-health-damage-qs-opportunity-detail/download', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceOpportunityDetailDocument\Http\Controllers\CustomerHealthDamageQualificationSourceOpportunityDetailDocumentController@download');
    
    Route::match(['post'], 'customer-health-damage-qs-opportunity-detail', 'AdeN\Api\Modules\Customer\HealthDamageQualificationSourceOpportunityDetail\Http\Controllers\CustomerHealthDamageQualificationSourceOpportunityDetailController@indexHealthDamageQualificationSourceOpportunityDetail');
