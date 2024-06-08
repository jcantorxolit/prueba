<?php

	/**
     *Module: Task
     */

    Route::get('positiva-fgn-gestpos/get', 'AdeN\Api\Modules\PositivaFgn\GestPos\Task\Http\Controllers\TaskController@show');
    Route::post('positiva-fgn-gestpos/save', 'AdeN\Api\Modules\PositivaFgn\GestPos\Task\Http\Controllers\TaskController@store');
    Route::post('positiva-fgn-gestpos/delete', 'AdeN\Api\Modules\PositivaFgn\GestPos\Task\Http\Controllers\TaskController@destroy');
    Route::post('positiva-fgn-gestpos/config', 'AdeN\Api\Modules\PositivaFgn\GestPos\Task\Http\Controllers\TaskController@config');
    Route::match(['get', 'post'], 'positiva-fgn-gestpos', 'AdeN\Api\Modules\PositivaFgn\GestPos\Task\Http\Controllers\TaskController@index');

    Route::get('positiva-fgn-gestpos/download-template', 'AdeN\Api\Modules\PositivaFgn\GestPos\Task\Http\Controllers\TaskController@downloadTemplate');
