<?php
    
	/**
     *Module: CustomerInternalProjectDocument
     */
    Route::get('customer-internal-project-document/get', 'AdeN\Api\Modules\Customer\InternalProjectDocument\Http\Controllers\CustomerInternalProjectDocumentController@show');
    Route::post('customer-internal-project-document/save', 'AdeN\Api\Modules\Customer\InternalProjectDocument\Http\Controllers\CustomerInternalProjectDocumentController@store');
    Route::post('customer-internal-project-document/delete', 'AdeN\Api\Modules\Customer\InternalProjectDocument\Http\Controllers\CustomerInternalProjectDocumentController@destroy');
    Route::post('customer-internal-project-document/import', 'AdeN\Api\Modules\Customer\InternalProjectDocument\Http\Controllers\CustomerInternalProjectDocumentController@import');
    Route::post('customer-internal-project-document/upload', 'AdeN\Api\Modules\Customer\InternalProjectDocument\Http\Controllers\CustomerInternalProjectDocumentController@upload');
    Route::match(['get', 'post'], 'customer-internal-project-document', 'AdeN\Api\Modules\Customer\InternalProjectDocument\Http\Controllers\CustomerInternalProjectDocumentController@index');
	Route::match(['get', 'post'], 'customer-internal-project-document/download', 'AdeN\Api\Modules\Customer\InternalProjectDocument\Http\Controllers\CustomerInternalProjectDocumentController@download');