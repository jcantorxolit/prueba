<?php
    
	/**
     *Module: Campus
     */
    Route::get('positiva-fgn-campus/get', 'AdeN\Api\Modules\PositivaFgn\Campus\Http\Controllers\CampusController@show');
    Route::post('positiva-fgn-campus/save', 'AdeN\Api\Modules\PositivaFgn\Campus\Http\Controllers\CampusController@store');
    Route::post('positiva-fgn-campus/delete', 'AdeN\Api\Modules\PositivaFgn\Campus\Http\Controllers\CampusController@destroy');
    Route::match(['get', 'post'], 'positiva-fgn-campus', 'AdeN\Api\Modules\PositivaFgn\Campus\Http\Controllers\CampusController@index');