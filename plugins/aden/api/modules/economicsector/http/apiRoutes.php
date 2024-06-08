<?php
    
	/**
     *Module: EconomicSector
     */
    Route::get('economic-sector/get', 'AdeN\Api\Modules\EconomicSector\Http\Controllers\EconomicSectorController@show');
    Route::post('economic-sector/save', 'AdeN\Api\Modules\EconomicSector\Http\Controllers\EconomicSectorController@store');
    Route::post('economic-sector/delete', 'AdeN\Api\Modules\EconomicSector\Http\Controllers\EconomicSectorController@destroy');
    Route::post('economic-sector/import', 'AdeN\Api\Modules\EconomicSector\Http\Controllers\EconomicSectorController@import');
    Route::post('economic-sector/upload', 'AdeN\Api\Modules\EconomicSector\Http\Controllers\EconomicSectorController@upload');
    Route::match(['get', 'post'], 'economic-sector', 'AdeN\Api\Modules\EconomicSector\Http\Controllers\EconomicSectorController@index');
	Route::match(['get', 'post'], 'economic-sector/download', 'AdeN\Api\Modules\EconomicSector\Http\Controllers\EconomicSectorController@download');