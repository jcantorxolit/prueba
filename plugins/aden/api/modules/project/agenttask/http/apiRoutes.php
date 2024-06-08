<?php
    
	/**
     *Module: CustomerProjectAgentTask
     */
    Route::get('customer-project-agent-task/get', 'AdeN\Api\Modules\Project\AgentTask\Http\Controllers\CustomerProjectAgentTaskController@show');
    Route::post('customer-project-agent-task/save', 'AdeN\Api\Modules\Project\AgentTask\Http\Controllers\CustomerProjectAgentTaskController@store');
    Route::post('customer-project-agent-task/delete', 'AdeN\Api\Modules\Project\AgentTask\Http\Controllers\CustomerProjectAgentTaskController@destroy');
    Route::post('customer-project-agent-task/import', 'AdeN\Api\Modules\Project\AgentTask\Http\Controllers\CustomerProjectAgentTaskController@import');
    Route::post('customer-project-agent-task/upload', 'AdeN\Api\Modules\Project\AgentTask\Http\Controllers\CustomerProjectAgentTaskController@upload');
    Route::match(['get', 'post'], 'customer-project-agent-task', 'AdeN\Api\Modules\Project\AgentTask\Http\Controllers\CustomerProjectAgentTaskController@index');
	Route::match(['get', 'post'], 'customer-project-agent-task/download', 'AdeN\Api\Modules\Project\AgentTask\Http\Controllers\CustomerProjectAgentTaskController@download');