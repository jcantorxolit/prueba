<?php

	/**
     *Module: Activity
     */

    Route::get('positiva-fgn-gestpos-activity/get', 'AdeN\Api\Modules\PositivaFgn\GestPos\Activity\Http\Controllers\ActivityController@show');
    Route::post('positiva-fgn-gestpos-activity/save', 'AdeN\Api\Modules\PositivaFgn\GestPos\Activity\Http\Controllers\ActivityController@store');
    Route::post('positiva-fgn-gestpos-activity/delete', 'AdeN\Api\Modules\PositivaFgn\GestPos\Activity\Http\Controllers\ActivityController@destroy');
    Route::post('positiva-fgn-gestpos-activity/config', 'AdeN\Api\Modules\PositivaFgn\GestPos\Activity\Http\Controllers\ActivityController@config');
    Route::match(['get', 'post'], 'positiva-fgn-gestpos-activity', 'AdeN\Api\Modules\PositivaFgn\GestPos\Activity\Http\Controllers\ActivityController@index');

    Route::get('positiva-fgn-gestpos-activity/download-template', 'AdeN\Api\Modules\PositivaFgn\GestPos\Activity\Http\Controllers\ActivityController@downloadTemplate');
