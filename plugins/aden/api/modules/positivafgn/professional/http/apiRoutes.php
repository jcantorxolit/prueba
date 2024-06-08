<?php

/**
 *Module: Positiva FGN - Professionals
 */
Route::post('positiva-fgn-professional', 'AdeN\Api\Modules\PositivaFgn\Professional\Http\Controllers\ProfessionalController@index');
Route::get('positiva-fgn-professional/get', 'AdeN\Api\Modules\PositivaFgn\Professional\Http\Controllers\ProfessionalController@show');
Route::post('positiva-fgn-professional/save', 'AdeN\Api\Modules\PositivaFgn\Professional\Http\Controllers\ProfessionalController@store');
Route::post('positiva-fgn-professional/delete', 'AdeN\Api\Modules\PositivaFgn\Professional\Http\Controllers\ProfessionalController@destroy');

Route::post('positiva-fgn-professional/saveSectional', 'AdeN\Api\Modules\PositivaFgn\Professional\Http\Controllers\ProfessionalController@storeSectional');
Route::post('positiva-fgn-professional/listSectional', 'AdeN\Api\Modules\PositivaFgn\Professional\Http\Controllers\ProfessionalController@indexSectional');
Route::post('positiva-fgn-professional/deleteSectional', 'AdeN\Api\Modules\PositivaFgn\Professional\Http\Controllers\ProfessionalController@destroySectional');
