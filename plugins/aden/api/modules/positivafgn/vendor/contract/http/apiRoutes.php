<?php
    
	/**
     *Module: VendorCoverage
     */
    Route::get('positiva-fgn-vendor-contract/get', 'AdeN\Api\Modules\PositivaFgn\Vendor\Contract\Http\Controllers\ContractController@show');
    Route::post('positiva-fgn-vendor-contract/save', 'AdeN\Api\Modules\PositivaFgn\Vendor\Contract\Http\Controllers\ContractController@store');
    Route::post('positiva-fgn-vendor-contract/delete', 'AdeN\Api\Modules\PositivaFgn\Vendor\Contract\Http\Controllers\ContractController@destroy');
    Route::match(['get', 'post'], 'positiva-fgn-vendor-contract', 'AdeN\Api\Modules\PositivaFgn\Vendor\Contract\Http\Controllers\ContractController@index');