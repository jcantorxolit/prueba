<?php
    
	/**
     *Module: ProfessorDocument
     */
    Route::get('information-detail/get', 'AdeN\Api\Modules\InformationDetail\Http\Controllers\InformationDetailController@show');
    Route::post('information-detail/save', 'AdeN\Api\Modules\InformationDetail\Http\Controllers\InformationDetailController@store');
    Route::post('information-detail/delete', 'AdeN\Api\Modules\InformationDetail\Http\Controllers\InformationDetailController@destroy');
    Route::post('information-detail/import', 'AdeN\Api\Modules\InformationDetail\Http\Controllers\InformationDetailController@import');
    Route::post('information-detail/upload', 'AdeN\Api\Modules\InformationDetail\Http\Controllers\InformationDetailController@upload');
    Route::match(['get', 'post'], 'information-detail', 'AdeN\Api\Modules\InformationDetail\Http\Controllers\InformationDetailController@index');
	Route::match(['get', 'post'], 'information-detail/download', 'AdeN\Api\Modules\InformationDetail\Http\Controllers\InformationDetailController@download');