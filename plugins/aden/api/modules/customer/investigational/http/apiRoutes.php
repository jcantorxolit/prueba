<?php
    
	/**
     *Module: CustomerEmployee
     */
    // Route::get('customer-investigation-al', 'AdeN\Api\Modules\Customer\InvestigationAl\Http\Controllers\CustomerInvestigationAlController@show');
    // Route::post('customer-investigation-al/save', 'AdeN\Api\Modules\Customer\InvestigationAl\Http\Controllers\CustomerInvestigationAlController@store');
    // Route::post('customer-investigation-al/update', 'AdeN\Api\Modules\Customer\InvestigationAl\Http\Controllers\CustomerInvestigationAlController@update');
    // Route::post('customer-investigation-al/delete', 'AdeN\Api\Modules\Customer\InvestigationAl\Http\Controllers\CustomerInvestigationAlController@destroy');
    // Route::post('customer-investigation-al/import', 'AdeN\Api\Modules\Customer\InvestigationAl\Http\Controllers\CustomerInvestigationAlController@import');
    // Route::post('customer-investigation-al/upload', 'AdeN\Api\Modules\Customer\InvestigationAl\Http\Controllers\CustomerInvestigationAlController@upload');
    // Route::match(['post'], 'customer-investigation-al', 'AdeN\Api\Modules\Customer\InvestigationAl\Http\Controllers\CustomerInvestigationAlController@index');
    // Route::match(['get', 'post'], 'customer-investigation-al/download', 'AdeN\Api\Modules\Customer\InvestigationAl\Http\Controllers\CustomerInvestigationAlController@download');
    
    Route::match(['post'], 'customer-investigation-al', 'AdeN\Api\Modules\Customer\InvestigationAl\Http\Controllers\CustomerInvestigationAlController@index');
    Route::match(['post'], 'customer-investigation-al-tracing', 'AdeN\Api\Modules\Customer\InvestigationAl\Http\Controllers\CustomerInvestigationAlController@indexTracking');
    