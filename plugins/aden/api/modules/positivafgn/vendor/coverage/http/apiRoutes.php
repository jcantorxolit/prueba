<?php
    
	/**
     *Module: VendorCoverage
     */
    Route::get('positiva-fgn-vendor-coverage/get', 'AdeN\Api\Modules\PositivaFgn\Vendor\Coverage\Http\Controllers\CoverageController@show');
    Route::post('positiva-fgn-vendor-coverage/save', 'AdeN\Api\Modules\PositivaFgn\Vendor\Coverage\Http\Controllers\CoverageController@store');
    Route::post('positiva-fgn-vendor-coverage/delete', 'AdeN\Api\Modules\PositivaFgn\Vendor\Coverage\Http\Controllers\CoverageController@destroy');
    Route::match(['get', 'post'], 'positiva-fgn-vendor-coverage', 'AdeN\Api\Modules\PositivaFgn\Vendor\Coverage\Http\Controllers\CoverageController@index');