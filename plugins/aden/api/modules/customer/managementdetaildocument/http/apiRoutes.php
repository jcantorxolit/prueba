<?php
    
	/**
     *Module: CustomerManagementDetailDocument
     */
    Route::get('customer-management-detail-document/get', 'AdeN\Api\Modules\Customer\ManagementDetailDocument\Http\Controllers\CustomerManagementDetailDocumentController@show');
    Route::post('customer-management-detail-document/save', 'AdeN\Api\Modules\Customer\ManagementDetailDocument\Http\Controllers\CustomerManagementDetailDocumentController@store');
    Route::post('customer-management-detail-document/delete', 'AdeN\Api\Modules\Customer\ManagementDetailDocument\Http\Controllers\CustomerManagementDetailDocumentController@destroy');
    Route::post('customer-management-detail-document/import', 'AdeN\Api\Modules\Customer\ManagementDetailDocument\Http\Controllers\CustomerManagementDetailDocumentController@import');
    Route::post('customer-management-detail-document/import-historical', 'AdeN\Api\Modules\Customer\ManagementDetailDocument\Http\Controllers\CustomerManagementDetailDocumentController@importHistorical');
    Route::post('customer-management-detail-document/upload', 'AdeN\Api\Modules\Customer\ManagementDetailDocument\Http\Controllers\CustomerManagementDetailDocumentController@upload');
    Route::match(['get', 'post'], 'customer-management-detail-document', 'AdeN\Api\Modules\Customer\ManagementDetailDocument\Http\Controllers\CustomerManagementDetailDocumentController@index');
    Route::match(['get', 'post'], 'customer-management-detail-document-available', 'AdeN\Api\Modules\Customer\ManagementDetailDocument\Http\Controllers\CustomerManagementDetailDocumentController@indexAvailable');
    Route::match(['get', 'post'], 'customer-management-detail-document-available-previous', 'AdeN\Api\Modules\Customer\ManagementDetailDocument\Http\Controllers\CustomerManagementDetailDocumentController@indexAvailablePreviousPeriod');
	Route::match(['get', 'post'], 'customer-management-detail-document/download', 'AdeN\Api\Modules\Customer\ManagementDetailDocument\Http\Controllers\CustomerManagementDetailDocumentController@download');