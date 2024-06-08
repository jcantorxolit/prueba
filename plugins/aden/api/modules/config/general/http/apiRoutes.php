<?php
    
	/**
     *Module: ConfigGeneral
     */
    Route::get('config-general/get', 'AdeN\Api\Modules\Config\General\Http\Controllers\ConfigGeneralController@show');
    Route::post('config-general/save', 'AdeN\Api\Modules\Config\General\Http\Controllers\ConfigGeneralController@store');
    Route::post('config-general/delete', 'AdeN\Api\Modules\Config\General\Http\Controllers\ConfigGeneralController@destroy');
    Route::post('config-general/import', 'AdeN\Api\Modules\Config\General\Http\Controllers\ConfigGeneralController@import');
    Route::post('config-general/upload', 'AdeN\Api\Modules\Config\General\Http\Controllers\ConfigGeneralController@upload');
    Route::match(['get', 'post'], 'config-general', 'AdeN\Api\Modules\Config\General\Http\Controllers\ConfigGeneralController@index');
	Route::match(['get', 'post'], 'config-general/download', 'AdeN\Api\Modules\Config\General\Http\Controllers\ConfigGeneralController@download');