<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-document', 'AdeN\Api\Modules\Customer\Document\Http\Controllers\CustomerDocumentController@show');
    Route::post('customer-document/save', 'AdeN\Api\Modules\Customer\Document\Http\Controllers\CustomerDocumentController@store');
    Route::post('customer-document/update', 'AdeN\Api\Modules\Customer\Document\Http\Controllers\CustomerDocumentController@update');
    Route::post('customer-document/delete', 'AdeN\Api\Modules\Customer\Document\Http\Controllers\CustomerDocumentController@destroy');
    Route::post('customer-document/import', 'AdeN\Api\Modules\Customer\Document\Http\Controllers\CustomerDocumentController@import');
    Route::post('customer-document/export', 'AdeN\Api\Modules\Customer\Document\Http\Controllers\CustomerDocumentController@export');
    Route::post('customer-document/upload', 'AdeN\Api\Modules\Customer\Document\Http\Controllers\CustomerDocumentController@upload');
    Route::match(['post'], 'customer-document', 'AdeN\Api\Modules\Customer\Document\Http\Controllers\CustomerDocumentController@index');
	Route::match(['get', 'post'], 'customer-document/download', 'AdeN\Api\Modules\Customer\Document\Http\Controllers\CustomerDocumentController@download');