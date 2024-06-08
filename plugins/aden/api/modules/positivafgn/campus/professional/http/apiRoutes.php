<?php
    
	/**
     *Module: Campus
     */
    Route::get('positiva-fgn-campus-professional/get', 'AdeN\Api\Modules\PositivaFgn\Campus\Professional\Http\Controllers\ProfessionalController@show');
    Route::post('positiva-fgn-campus-professional/save', 'AdeN\Api\Modules\PositivaFgn\Campus\Professional\Http\Controllers\ProfessionalController@store');
    Route::match(['get', 'post'], 'positiva-fgn-campus-professional', 'AdeN\Api\Modules\PositivaFgn\Campus\Professional\Http\Controllers\ProfessionalController@index');