<?php
    
	/**
     *Module: CustomerInternalProjectDocument
     */
    Route::get('customer-project-document/get', 'AdeN\Api\Modules\Project\Documents\Http\Controllers\ProjectDocumentController@show');
    Route::post('customer-project-document/save', 'AdeN\Api\Modules\Project\Documents\Http\Controllers\ProjectDocumentController@store');
    Route::post('customer-project-document/delete', 'AdeN\Api\Modules\Project\Documents\Http\Controllers\ProjectDocumentController@destroy');
    Route::post('customer-project-document/import', 'AdeN\Api\Modules\Project\Documents\Http\Controllers\ProjectDocumentController@import');
    Route::post('customer-project-document/upload', 'AdeN\Api\Modules\Project\Documents\Http\Controllers\ProjectDocumentController@upload');
    Route::match(['get', 'post'], 'customer-project-document', 'AdeN\Api\Modules\Project\Documents\Http\Controllers\ProjectDocumentController@index');
	Route::match(['get', 'post'], 'customer-project-document/download', 'AdeN\Api\Modules\Project\Documents\Http\Controllers\ProjectDocumentController@download');
	Route::match(['get', 'post'], 'customer-project-document/export', 'AdeN\Api\Modules\Project\Documents\Http\Controllers\ProjectDocumentController@export');