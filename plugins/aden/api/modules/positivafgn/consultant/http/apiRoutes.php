<?php

	/**
     *Module: EconomicSector
     */
    Route::get('positiva-fgn-consultant/get', 'AdeN\Api\Modules\PositivaFgn\Consultant\Http\Controllers\ConsultantController@show');
    Route::post('positiva-fgn-consultant/save', 'AdeN\Api\Modules\PositivaFgn\Consultant\Http\Controllers\ConsultantController@store');
    Route::post('positiva-fgn-consultant/delete', 'AdeN\Api\Modules\PositivaFgn\Consultant\Http\Controllers\ConsultantController@destroy');
    Route::post('positiva-fgn-consultant-detail/delete', 'AdeN\Api\Modules\PositivaFgn\Consultant\Http\Controllers\ConsultantController@destroyDetail');
    Route::post('positiva-fgn-consultant/import', 'AdeN\Api\Modules\PositivaFgn\Consultant\Http\Controllers\ConsultantController@import');
    Route::get('positiva-fgn-consultant/download-template', 'AdeN\Api\Modules\PositivaFgn\Consultant\Http\Controllers\ConsultantController@downloadTemplate');
    Route::match(['get', 'post'], 'positiva-fgn-consultant', 'AdeN\Api\Modules\PositivaFgn\Consultant\Http\Controllers\ConsultantController@index');
	Route::match(['get', 'post'], 'positiva-fgn-consultant/download', 'AdeN\Api\Modules\PositivaFgn\Consultant\Http\Controllers\ConsultantController@download');
