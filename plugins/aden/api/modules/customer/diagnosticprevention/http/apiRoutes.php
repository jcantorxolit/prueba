<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-diagnostic-prevention', 'AdeN\Api\Modules\Customer\DiagnosticPrevention\Http\Controllers\CustomerDiagnosticPreventionController@show');
    Route::post('customer-diagnostic-prevention/save', 'AdeN\Api\Modules\Customer\DiagnosticPrevention\Http\Controllers\CustomerDiagnosticPreventionController@store');
    Route::post('customer-diagnostic-prevention/update', 'AdeN\Api\Modules\Customer\DiagnosticPrevention\Http\Controllers\CustomerDiagnosticPreventionController@update');
    Route::post('customer-diagnostic-prevention/delete', 'AdeN\Api\Modules\Customer\DiagnosticPrevention\Http\Controllers\CustomerDiagnosticPreventionController@destroy');
    Route::post('customer-diagnostic-prevention/import', 'AdeN\Api\Modules\Customer\DiagnosticPrevention\Http\Controllers\CustomerDiagnosticPreventionController@import');
    Route::post('customer-diagnostic-prevention/upload', 'AdeN\Api\Modules\Customer\DiagnosticPrevention\Http\Controllers\CustomerDiagnosticPreventionController@upload');
    Route::match(['post'], 'customer-diagnostic-prevention', 'AdeN\Api\Modules\Customer\DiagnosticPrevention\Http\Controllers\CustomerDiagnosticPreventionController@index');
    Route::match(['post'], 'customer-diagnostic-prevention-question', 'AdeN\Api\Modules\Customer\DiagnosticPrevention\Http\Controllers\CustomerDiagnosticPreventionController@indexQuestion');
	Route::match(['get', 'post'], 'customer-diagnostic-prevention/download', 'AdeN\Api\Modules\Customer\DiagnosticPrevention\Http\Controllers\CustomerDiagnosticPreventionController@download');