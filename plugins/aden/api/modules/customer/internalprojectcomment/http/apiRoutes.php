<?php
    
	/**
     *Module: CustomerInternalProjectComment
     */
    Route::get('customer-internal-project-comment/get', 'AdeN\Api\Modules\Customer\InternalProjectComment\Http\Controllers\CustomerInternalProjectCommentController@show');
    Route::post('customer-internal-project-comment/save', 'AdeN\Api\Modules\Customer\InternalProjectComment\Http\Controllers\CustomerInternalProjectCommentController@store');
    Route::post('customer-internal-project-comment/delete', 'AdeN\Api\Modules\Customer\InternalProjectComment\Http\Controllers\CustomerInternalProjectCommentController@destroy');
    Route::post('customer-internal-project-comment/import', 'AdeN\Api\Modules\Customer\InternalProjectComment\Http\Controllers\CustomerInternalProjectCommentController@import');
    Route::post('customer-internal-project-comment/upload', 'AdeN\Api\Modules\Customer\InternalProjectComment\Http\Controllers\CustomerInternalProjectCommentController@upload');
    Route::match(['get', 'post'], 'customer-internal-project-comment', 'AdeN\Api\Modules\Customer\InternalProjectComment\Http\Controllers\CustomerInternalProjectCommentController@index');
	Route::match(['get', 'post'], 'customer-internal-project-comment/download', 'AdeN\Api\Modules\Customer\InternalProjectComment\Http\Controllers\CustomerInternalProjectCommentController@download');