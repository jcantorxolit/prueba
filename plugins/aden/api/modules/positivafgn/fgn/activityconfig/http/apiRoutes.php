<?php
    
	/**
     *Module: Activity
     */
    Route::get('positiva-fgn-fgn-activity-config/get', 'AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfig\Http\Controllers\ActivityConfigController@show');
    Route::post('positiva-fgn-fgn-activity-config/save', 'AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfig\Http\Controllers\ActivityConfigController@store');
    Route::post('positiva-fgn-fgn-activity-config/delete', 'AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfig\Http\Controllers\ActivityConfigController@destroy');
    Route::match(['get', 'post'], 'positiva-fgn-fgn-activity-config', 'AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfig\Http\Controllers\ActivityConfigController@index');
    Route::match(['get', 'post'], 'positiva-fgn-fgn-activity-config-subtask', 'AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfig\Http\Controllers\ActivityConfigController@indexSubtask');
    Route::match(['get', 'post'], 'positiva-fgn-fgn-activity-config-activity', 'AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfig\Http\Controllers\ActivityConfigController@indexActivity');

    Route::get('positiva-fgn-fgn-activity-config/getSubtask', 'AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfig\Http\Controllers\ActivityConfigController@showSubtask');
    Route::post('positiva-fgn-fgn-activity-config/saveSubtask', 'AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfig\Http\Controllers\ActivityConfigController@storeSubtask');