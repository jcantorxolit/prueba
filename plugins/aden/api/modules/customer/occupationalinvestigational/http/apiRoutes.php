<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-occupational-investigation-al/get', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAl\Http\Controllers\CustomerOccupationalInvestigationController@show');
    Route::post('customer-occupational-investigation-al/save', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAl\Http\Controllers\CustomerOccupationalInvestigationController@store');
    Route::post('customer-occupational-investigation-al/update', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAl\Http\Controllers\CustomerOccupationalInvestigationController@update');
    Route::post('customer-occupational-investigation-al/delete', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAl\Http\Controllers\CustomerOccupationalInvestigationController@destroy');
    Route::post('customer-occupational-investigation-al/re-open', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAl\Http\Controllers\CustomerOccupationalInvestigationController@updateStatus');
    Route::post('customer-occupational-investigation-al/import', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAl\Http\Controllers\CustomerOccupationalInvestigationController@import');
    Route::post('customer-occupational-investigation-al/upload', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAl\Http\Controllers\CustomerOccupationalInvestigationController@upload');
    Route::match(['post'], 'customer-occupational-investigation-al', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAl\Http\Controllers\CustomerOccupationalInvestigationController@index');
	Route::match(['get', 'post'], 'customer-occupational-investigation-al/download', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAl\Http\Controllers\CustomerOccupationalInvestigationController@download');
	Route::match(['get', 'post'], 'customer-occupational-investigation-al/export-pdf', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAl\Http\Controllers\CustomerOccupationalInvestigationController@exportPdf');
	Route::match(['get', 'post'], 'customer-occupational-investigation-al/stream-pdf', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAl\Http\Controllers\CustomerOccupationalInvestigationController@streamPdf');