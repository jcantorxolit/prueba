<?php
    
	/**
     *Module: Report
     */
    /*Route::get('report/get', 'AdeN\Api\Modules\Report\Http\Controllers\ReportController@show');
    Route::post('report/save', 'AdeN\Api\Modules\Report\Http\Controllers\ReportController@store');
    Route::post('report/delete', 'AdeN\Api\Modules\Report\Http\Controllers\ReportController@destroy');
    Route::post('report/import', 'AdeN\Api\Modules\Report\Http\Controllers\ReportController@import');
    Route::post('report/upload', 'AdeN\Api\Modules\Report\Http\Controllers\ReportController@upload');*/
    Route::match(['get', 'post'], 'report/v2', 'AdeN\Api\Modules\Report\Http\Controllers\ReportController@index');
	Route::match(['get', 'post'], 'report/download', 'AdeN\Api\Modules\Report\Http\Controllers\ReportController@download');