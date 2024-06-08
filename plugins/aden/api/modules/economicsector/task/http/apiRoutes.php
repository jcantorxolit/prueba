<?php
    
	/**
     *Module: EconomicSectorTask
     */
    Route::get('economic-sector-task/get', 'AdeN\Api\Modules\EconomicSector\Task\Http\Controllers\EconomicSectorTaskController@show');
    Route::post('economic-sector-task/save', 'AdeN\Api\Modules\EconomicSector\Task\Http\Controllers\EconomicSectorTaskController@store');
    Route::post('economic-sector-task/batch', 'AdeN\Api\Modules\EconomicSector\Task\Http\Controllers\EconomicSectorTaskController@batch');
    Route::post('economic-sector-task/delete', 'AdeN\Api\Modules\EconomicSector\Task\Http\Controllers\EconomicSectorTaskController@destroy');
    Route::post('economic-sector-task/import', 'AdeN\Api\Modules\EconomicSector\Task\Http\Controllers\EconomicSectorTaskController@import');
    Route::post('economic-sector-task/upload', 'AdeN\Api\Modules\EconomicSector\Task\Http\Controllers\EconomicSectorTaskController@upload');
    Route::match(['get', 'post'], 'economic-sector-task', 'AdeN\Api\Modules\EconomicSector\Task\Http\Controllers\EconomicSectorTaskController@index');
	Route::match(['get', 'post'], 'economic-sector-task/download', 'AdeN\Api\Modules\EconomicSector\Task\Http\Controllers\EconomicSectorTaskController@download');