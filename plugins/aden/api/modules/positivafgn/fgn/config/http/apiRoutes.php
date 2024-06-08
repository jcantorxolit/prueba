<?php
    
	/**
     *Module: Config
     */
    Route::get('positiva-fgn-fgn-config/get', 'AdeN\Api\Modules\PositivaFgn\Fgn\Config\Http\Controllers\ConfigController@show');
    Route::post('positiva-fgn-fgn-config/save', 'AdeN\Api\Modules\PositivaFgn\Fgn\Config\Http\Controllers\ConfigController@store');
    Route::post('positiva-fgn-fgn-config/delete', 'AdeN\Api\Modules\PositivaFgn\Fgn\Config\Http\Controllers\ConfigController@destroy');
    Route::match(['get', 'post'], 'positiva-fgn-fgn-config', 'AdeN\Api\Modules\PositivaFgn\Fgn\Config\Http\Controllers\ConfigController@index');