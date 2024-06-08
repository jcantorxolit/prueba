<?php
    
	/**
     *Module: CustomerProjectAgent
     */
    Route::get('customer-project-agent/get', 'AdeN\Api\Modules\Project\Agent\Http\Controllers\CustomerProjectAgentController@show');
    Route::post('customer-project-agent/save', 'AdeN\Api\Modules\Project\Agent\Http\Controllers\CustomerProjectAgentController@store');
    Route::post('customer-project-agent/delete', 'AdeN\Api\Modules\Project\Agent\Http\Controllers\CustomerProjectAgentController@destroy');
    Route::post('customer-project-agent/import', 'AdeN\Api\Modules\Project\Agent\Http\Controllers\CustomerProjectAgentController@import');
    Route::post('customer-project-agent/upload', 'AdeN\Api\Modules\Project\Agent\Http\Controllers\CustomerProjectAgentController@upload');
    Route::match(['get', 'post'], 'customer-project-agent', 'AdeN\Api\Modules\Project\Agent\Http\Controllers\CustomerProjectAgentController@index');
	Route::match(['get', 'post'], 'customer-project-agent/download', 'AdeN\Api\Modules\Project\Agent\Http\Controllers\CustomerProjectAgentController@download');