<?php

/**
 *Module: CustomerProjectComments
 */
Route::get('customer-project-comment/get', 'AdeN\Api\Modules\Customer\ProjectComments\Http\Controllers\CustomerProjectCommentController@show');
Route::post('customer-project-comment/save', 'AdeN\Api\Modules\Customer\ProjectComments\Http\Controllers\CustomerProjectCommentController@store');
Route::post('customer-project-comment/delete', 'AdeN\Api\Modules\Customer\ProjectComments\Http\Controllers\CustomerProjectCommentController@destroy');

Route::match(['get', 'post'], 'customer-project-comment', 'AdeN\Api\Modules\Customer\ProjectComments\Http\Controllers\CustomerProjectCommentController@index');
Route::match(['get', 'post'], 'customer-project-comment/download', 'AdeN\Api\Modules\Customer\ProjectComments\Http\Controllers\CustomerProjectCommentController@download');