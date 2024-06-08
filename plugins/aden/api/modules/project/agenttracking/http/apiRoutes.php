<?php
    
	/**
     *Module: CustomerProjectAgentTracking
     */
    Route::get('customer-project-agent-tracking/get', 'AdeN\Api\Modules\Project\AgentTracking\Http\Controllers\CustomerProjectAgentTrackingController@show');
    Route::post('customer-project-agent-tracking/save', 'AdeN\Api\Modules\Project\AgentTracking\Http\Controllers\CustomerProjectAgentTrackingController@store');
    Route::post('customer-project-agent-tracking/delete', 'AdeN\Api\Modules\Project\AgentTracking\Http\Controllers\CustomerProjectAgentTrackingController@destroy');
    Route::post('customer-project-agent-tracking/import', 'AdeN\Api\Modules\Project\AgentTracking\Http\Controllers\CustomerProjectAgentTrackingController@import');
    Route::post('customer-project-agent-tracking/upload', 'AdeN\Api\Modules\Project\AgentTracking\Http\Controllers\CustomerProjectAgentTrackingController@upload');
    Route::match(['get', 'post'], 'customer-project-agent-tracking', 'AdeN\Api\Modules\Project\AgentTracking\Http\Controllers\CustomerProjectAgentTrackingController@index');
	Route::match(['get', 'post'], 'customer-project-agent-tracking/download', 'AdeN\Api\Modules\Project\AgentTracking\Http\Controllers\CustomerProjectAgentTrackingController@download');