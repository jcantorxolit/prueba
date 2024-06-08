<?php

	/**
     *Module: Agent
     */
    Route::get('event-agent', 'AdeN\Api\Modules\Agent\Http\Controllers\AgentController@show');
    Route::post('event-agent/save', 'AdeN\Api\Modules\Agent\Http\Controllers\AgentController@store');
    Route::post('event-agent/delete', 'AdeN\Api\Modules\Agent\Http\Controllers\AgentController@destroy');
    Route::post('event-agent/import', 'AdeN\Api\Modules\Agent\Http\Controllers\AgentController@import');
    Route::post('event-agent/upload', 'AdeN\Api\Modules\Agent\Http\Controllers\AgentController@upload');
    Route::match(['post'], 'event-agent', 'AdeN\Api\Modules\Agent\Http\Controllers\AgentController@index');
    Route::match(['post'], 'event-agent-available', 'AdeN\Api\Modules\Agent\Http\Controllers\AgentController@indexAvailable');
    Route::match(['post'], 'event-agent-available-batch', 'AdeN\Api\Modules\Agent\Http\Controllers\AgentController@indexAvailableBatch');
	Route::match(['get', 'post'], 'event-agent/download', 'AdeN\Api\Modules\Agent\Http\Controllers\AgentController@download');
