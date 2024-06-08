<?php
    
	/**
     *Module: EconomicSector
     */
    Route::get('positiva-fgn-consultant-sectional/get', 'AdeN\Api\Modules\PositivaFgn\Consultant\Sectional\Http\Controllers\SectionalController@show');
    Route::post('positiva-fgn-consultant-sectional/save', 'AdeN\Api\Modules\PositivaFgn\Consultant\Sectional\Http\Controllers\SectionalController@store');
    Route::match(['get', 'post'], 'positiva-fgn-consultant-sectional', 'AdeN\Api\Modules\PositivaFgn\Consultant\Sectional\Http\Controllers\SectionalController@index');
