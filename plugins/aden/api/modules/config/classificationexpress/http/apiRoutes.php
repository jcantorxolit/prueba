<?php
    
	/**
     *Module: ConfigClassificationExpress
     */
    Route::get('config-classification-express/get', 'AdeN\Api\Modules\Config\ClassificationExpress\Http\Controllers\ConfigClassificationExpressController@show');
    Route::post('config-classification-express/save', 'AdeN\Api\Modules\Config\ClassificationExpress\Http\Controllers\ConfigClassificationExpressController@store');
    Route::post('config-classification-express/delete', 'AdeN\Api\Modules\Config\ClassificationExpress\Http\Controllers\ConfigClassificationExpressController@destroy');
    Route::post('config-classification-express/import', 'AdeN\Api\Modules\Config\ClassificationExpress\Http\Controllers\ConfigClassificationExpressController@import');
    Route::post('config-classification-express/upload', 'AdeN\Api\Modules\Config\ClassificationExpress\Http\Controllers\ConfigClassificationExpressController@upload');
    Route::match(['get', 'post'], 'config-classification-express', 'AdeN\Api\Modules\Config\ClassificationExpress\Http\Controllers\ConfigClassificationExpressController@index');
	Route::match(['get', 'post'], 'config-classification-express/download', 'AdeN\Api\Modules\Config\ClassificationExpress\Http\Controllers\ConfigClassificationExpressController@download');