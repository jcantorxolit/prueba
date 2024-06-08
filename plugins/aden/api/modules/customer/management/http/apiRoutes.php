<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-management', 'AdeN\Api\Modules\Customer\Management\Http\Controllers\CustomerManagementController@show');
    Route::post('customer-management/save', 'AdeN\Api\Modules\Customer\Management\Http\Controllers\CustomerManagementController@store');
    Route::post('customer-management/update', 'AdeN\Api\Modules\Customer\Management\Http\Controllers\CustomerManagementController@update');
    Route::post('customer-management/delete', 'AdeN\Api\Modules\Customer\Management\Http\Controllers\CustomerManagementController@destroy');
    Route::post('customer-management/import', 'AdeN\Api\Modules\Customer\Management\Http\Controllers\CustomerManagementController@import');
    Route::post('customer-management/upload', 'AdeN\Api\Modules\Customer\Management\Http\Controllers\CustomerManagementController@upload');
    Route::match(['post'], 'customer-management', 'AdeN\Api\Modules\Customer\Management\Http\Controllers\CustomerManagementController@index');
    Route::match(['post'], 'customer-management-summary', 'AdeN\Api\Modules\Customer\Management\Http\Controllers\CustomerManagementController@indexSummary');
    Route::match(['post'], 'customer-management-summary-indicator', 'AdeN\Api\Modules\Customer\Management\Http\Controllers\CustomerManagementController@indexSummaryIndicator');
    Route::match(['post'], 'customer-management-summary-responsible', 'AdeN\Api\Modules\Customer\Management\Http\Controllers\CustomerManagementController@indexSummaryResponsible');
    Route::match(['post'], 'customer-management-setting', 'AdeN\Api\Modules\Customer\Management\Http\Controllers\CustomerManagementController@indexAvailableEconomicSector');
	Route::match(['get', 'post'], 'customer-management/download', 'AdeN\Api\Modules\Customer\Management\Http\Controllers\CustomerManagementController@download');