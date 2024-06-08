<?php

/**
 *Module: Sectional
 */
Route::post('positiva-fgn-sectional', 'AdeN\Api\Modules\PositivaFgn\Sectional\Http\Controllers\SectionalController@index');
Route::post('positiva-fgn-sectional/show', 'AdeN\Api\Modules\PositivaFgn\Sectional\Http\Controllers\SectionalController@show');
Route::post('positiva-fgn-sectional/save', 'AdeN\Api\Modules\PositivaFgn\Sectional\Http\Controllers\SectionalController@store');
Route::post('positiva-fgn-sectional/delete', 'AdeN\Api\Modules\PositivaFgn\Sectional\Http\Controllers\SectionalController@destroy');

Route::post('positiva-fgn-sectional/listProfessionals', 'AdeN\Api\Modules\PositivaFgn\Sectional\Http\Controllers\SectionalController@indexProfessional');
Route::post('positiva-fgn-sectional/professional', 'AdeN\Api\Modules\PositivaFgn\Sectional\Http\Controllers\SectionalController@indexSectionalXProfessional');
Route::post('positiva-fgn-sectional/deleteSectionalProfessional', 'AdeN\Api\Modules\PositivaFgn\Sectional\Http\Controllers\SectionalController@destroySectionalXProfessional');