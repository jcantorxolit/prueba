<?php
    
	/**
     *Module: EconomicSector
     */
    Route::get('positiva-fgn-vendor/get', 'AdeN\Api\Modules\PositivaFgn\Vendor\Http\Controllers\VendorController@show');
    Route::post('positiva-fgn-vendor/save', 'AdeN\Api\Modules\PositivaFgn\Vendor\Http\Controllers\VendorController@store');
    Route::post('positiva-fgn-vendor/delete', 'AdeN\Api\Modules\PositivaFgn\Vendor\Http\Controllers\VendorController@destroy');
    Route::post('positiva-fgn-vendor-detail/delete', 'AdeN\Api\Modules\PositivaFgn\Vendor\Http\Controllers\VendorController@destroyDetail');
    Route::post('positiva-fgn-vendor/import', 'AdeN\Api\Modules\PositivaFgn\Vendor\Http\Controllers\VendorController@import');
    Route::match(['get', 'post'], 'positiva-fgn-vendor', 'AdeN\Api\Modules\PositivaFgn\Vendor\Http\Controllers\VendorController@index');
	Route::match(['get', 'post'], 'positiva-fgn-vendor/download', 'AdeN\Api\Modules\PositivaFgn\Vendor\Http\Controllers\VendorController@download');