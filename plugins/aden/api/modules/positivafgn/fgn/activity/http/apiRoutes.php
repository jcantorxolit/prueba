<?php
    
	/**
     *Module: Activity
     */
    Route::get('positiva-fgn-fgn-activity/get', 'AdeN\Api\Modules\PositivaFgn\Fgn\Activity\Http\Controllers\ActivityController@show');
    Route::get('positiva-fgn-fgn-activity/getClear', 'AdeN\Api\Modules\PositivaFgn\Fgn\Activity\Http\Controllers\ActivityController@showClear');
    Route::post('positiva-fgn-fgn-activity/save', 'AdeN\Api\Modules\PositivaFgn\Fgn\Activity\Http\Controllers\ActivityController@store');
    Route::post('positiva-fgn-fgn-activity/delete', 'AdeN\Api\Modules\PositivaFgn\Fgn\Activity\Http\Controllers\ActivityController@destroy');
    Route::match(['get', 'post'], 'positiva-fgn-fgn-activity', 'AdeN\Api\Modules\PositivaFgn\Fgn\Activity\Http\Controllers\ActivityController@index');
    Route::match(['get', 'post'], 'positiva-fgn-fgn-activity/download-template', 'AdeN\Api\Modules\PositivaFgn\Fgn\Activity\Http\Controllers\ActivityController@downloadTemplate');