<?php
    
	/**
     *Module: CustomerProjectAgentTaskTracking
     */
    Route::get('customer-project-agent-task-tracking/get', 'AdeN\Api\Modules\Project\AgentTaskTracking\Http\Controllers\CustomerProjectAgentTaskTrackingController@show');
    Route::post('customer-project-agent-task-tracking/save', 'AdeN\Api\Modules\Project\AgentTaskTracking\Http\Controllers\CustomerProjectAgentTaskTrackingController@store');
    Route::post('customer-project-agent-task-tracking/delete', 'AdeN\Api\Modules\Project\AgentTaskTracking\Http\Controllers\CustomerProjectAgentTaskTrackingController@destroy');
    Route::post('customer-project-agent-task-tracking/import', 'AdeN\Api\Modules\Project\AgentTaskTracking\Http\Controllers\CustomerProjectAgentTaskTrackingController@import');
    Route::post('customer-project-agent-task-tracking/upload', 'AdeN\Api\Modules\Project\AgentTaskTracking\Http\Controllers\CustomerProjectAgentTaskTrackingController@upload');
    Route::match(['get', 'post'], 'customer-project-agent-task-tracking', 'AdeN\Api\Modules\Project\AgentTaskTracking\Http\Controllers\CustomerProjectAgentTaskTrackingController@index');
	Route::match(['get', 'post'], 'customer-project-agent-task-tracking/download', 'AdeN\Api\Modules\Project\AgentTaskTracking\Http\Controllers\CustomerProjectAgentTaskTrackingController@download');