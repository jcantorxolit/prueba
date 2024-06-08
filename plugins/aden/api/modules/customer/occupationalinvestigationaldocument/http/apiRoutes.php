<?php
    
	/**
     *Module: CustomerOccupationalInvestigationDocument
     */
    Route::get('customer-occupational-investigation-document/get', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAlDocument\Http\Controllers\CustomerOccupationalInvestigationDocumentController@show');
    Route::post('customer-occupational-investigation-document/save', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAlDocument\Http\Controllers\CustomerOccupationalInvestigationDocumentController@store');
    Route::post('customer-occupational-investigation-document/delete', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAlDocument\Http\Controllers\CustomerOccupationalInvestigationDocumentController@destroy');
    Route::post('customer-occupational-investigation-document/import', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAlDocument\Http\Controllers\CustomerOccupationalInvestigationDocumentController@import');
    Route::post('customer-occupational-investigation-document/import-historical', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAlDocument\Http\Controllers\CustomerOccupationalInvestigationDocumentController@importHistorical');
    Route::post('customer-occupational-investigation-document/upload', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAlDocument\Http\Controllers\CustomerOccupationalInvestigationDocumentController@upload');
    Route::post('customer-occupational-investigation-document/export', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAlDocument\Http\Controllers\CustomerOccupationalInvestigationDocumentController@export');
    Route::match(['get', 'post'], 'customer-occupational-investigation-document', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAlDocument\Http\Controllers\CustomerOccupationalInvestigationDocumentController@index');
	Route::match(['get', 'post'], 'customer-occupational-investigation-document/download', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAlDocument\Http\Controllers\CustomerOccupationalInvestigationDocumentController@download');