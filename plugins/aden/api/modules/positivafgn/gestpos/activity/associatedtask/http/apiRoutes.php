<?php
    
	/**
     *Module: AssociatedTask
     */

    Route::post('positiva-fgn-gestpos-activity-associated-task/save', 'AdeN\Api\Modules\PositivaFgn\GestPos\Activity\AssociatedTask\Http\Controllers\AssociatedTaskController@store');
    Route::post('positiva-fgn-gestpos-activity-associated-task/delete', 'AdeN\Api\Modules\PositivaFgn\GestPos\Activity\AssociatedTask\Http\Controllers\AssociatedTaskController@destroy');
    Route::match(['get', 'post'], 'positiva-fgn-gestpos-activity-associated-task', 'AdeN\Api\Modules\PositivaFgn\GestPos\Activity\AssociatedTask\Http\Controllers\AssociatedTaskController@index');
    Route::match(['get', 'post'], 'positiva-fgn-gestpos-activity-associated-task/maintask', 'AdeN\Api\Modules\PositivaFgn\GestPos\Activity\AssociatedTask\Http\Controllers\AssociatedTaskController@mainTask');