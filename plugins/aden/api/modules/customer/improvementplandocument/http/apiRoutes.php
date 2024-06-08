<?php
    
	/**
     *Module: CustomerImprovementPlanDocument
     */
    Route::get('customer-improvement-plan-document/get', 'AdeN\Api\Modules\Customer\ImprovementPlanDocument\Http\Controllers\CustomerImprovementPlanDocumentController@show');
    Route::post('customer-improvement-plan-document/save', 'AdeN\Api\Modules\Customer\ImprovementPlanDocument\Http\Controllers\CustomerImprovementPlanDocumentController@store');
    Route::post('customer-improvement-plan-document/delete', 'AdeN\Api\Modules\Customer\ImprovementPlanDocument\Http\Controllers\CustomerImprovementPlanDocumentController@destroy');
    Route::post('customer-improvement-plan-document/import', 'AdeN\Api\Modules\Customer\ImprovementPlanDocument\Http\Controllers\CustomerImprovementPlanDocumentController@import');
    Route::post('customer-improvement-plan-document/upload', 'AdeN\Api\Modules\Customer\ImprovementPlanDocument\Http\Controllers\CustomerImprovementPlanDocumentController@upload');
    Route::post('customer-improvement-plan-document/export', 'AdeN\Api\Modules\Customer\ImprovementPlanDocument\Http\Controllers\CustomerImprovementPlanDocumentController@export');
    Route::match(['get', 'post'], 'customer-improvement-plan-document', 'AdeN\Api\Modules\Customer\ImprovementPlanDocument\Http\Controllers\CustomerImprovementPlanDocumentController@index');
	Route::match(['get', 'post'], 'customer-improvement-plan-document/download', 'AdeN\Api\Modules\Customer\ImprovementPlanDocument\Http\Controllers\CustomerImprovementPlanDocumentController@download');