<?php
    
	/**
     *Module: CustomerTrackingDocument
     */
    Route::get('customer-tracking-document/get', 'AdeN\Api\Modules\Customer\TrackingDocument\Http\Controllers\CustomerTrackingDocumentController@show');
    Route::post('customer-tracking-document/save', 'AdeN\Api\Modules\Customer\TrackingDocument\Http\Controllers\CustomerTrackingDocumentController@store');
    Route::post('customer-tracking-document/delete', 'AdeN\Api\Modules\Customer\TrackingDocument\Http\Controllers\CustomerTrackingDocumentController@destroy');
    Route::post('customer-tracking-document/import', 'AdeN\Api\Modules\Customer\TrackingDocument\Http\Controllers\CustomerTrackingDocumentController@import');
    Route::post('customer-tracking-document/import-historical', 'AdeN\Api\Modules\Customer\TrackingDocument\Http\Controllers\CustomerTrackingDocumentController@importHistorical');
    Route::post('customer-tracking-document/upload', 'AdeN\Api\Modules\Customer\TrackingDocument\Http\Controllers\CustomerTrackingDocumentController@upload');
    Route::post('customer-tracking-document/export', 'AdeN\Api\Modules\Customer\TrackingDocument\Http\Controllers\CustomerTrackingDocumentController@export');
    Route::match(['get', 'post'], 'customer-tracking-document', 'AdeN\Api\Modules\Customer\TrackingDocument\Http\Controllers\CustomerTrackingDocumentController@index');
	Route::match(['get', 'post'], 'customer-tracking-document/download', 'AdeN\Api\Modules\Customer\TrackingDocument\Http\Controllers\CustomerTrackingDocumentController@download');