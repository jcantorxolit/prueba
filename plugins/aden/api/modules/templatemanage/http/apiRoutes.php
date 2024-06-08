<?php

	/**
     *Module: TemplateManage
     */
    Route::get('template-manage/get', 'AdeN\Api\Modules\TemplateManage\Http\Controllers\TemplateManageController@show');
    Route::post('template-manage/save', 'AdeN\Api\Modules\TemplateManage\Http\Controllers\TemplateManageController@store');
    Route::post('template-manage/delete', 'AdeN\Api\Modules\TemplateManage\Http\Controllers\TemplateManageController@destroy');
    Route::post('template-manage/import', 'AdeN\Api\Modules\TemplateManage\Http\Controllers\TemplateManageController@import');
    Route::post('template-manage/upload', 'AdeN\Api\Modules\TemplateManage\Http\Controllers\TemplateManageController@upload');
    Route::post('template-manage/publish', 'AdeN\Api\Modules\TemplateManage\Http\Controllers\TemplateManageController@publish');
    Route::match(['get', 'post'], 'template-manage', 'AdeN\Api\Modules\TemplateManage\Http\Controllers\TemplateManageController@index');
    Route::match(['get', 'post'], 'template-manage/download', 'AdeN\Api\Modules\TemplateManage\Http\Controllers\TemplateManageController@download');

