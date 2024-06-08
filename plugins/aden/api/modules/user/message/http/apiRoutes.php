<?php
    
	/**
     *Module: UserMessage
     */
    Route::get('user-message/get', 'AdeN\Api\Modules\User\Message\Http\Controllers\UserMessageController@show');
    Route::post('user-message/save', 'AdeN\Api\Modules\User\Message\Http\Controllers\UserMessageController@store');
    Route::post('user-message/delete', 'AdeN\Api\Modules\User\Message\Http\Controllers\UserMessageController@destroy');
    Route::post('user-message/import', 'AdeN\Api\Modules\User\Message\Http\Controllers\UserMessageController@import');
    Route::post('user-message/upload', 'AdeN\Api\Modules\User\Message\Http\Controllers\UserMessageController@upload');
    Route::match(['get', 'post'], 'user-message', 'AdeN\Api\Modules\User\Message\Http\Controllers\UserMessageController@index');
	Route::match(['get', 'post'], 'user-message/download', 'AdeN\Api\Modules\User\Message\Http\Controllers\UserMessageController@download');