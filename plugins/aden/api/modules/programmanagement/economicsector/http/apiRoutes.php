<?php
    
	/**
     *Module: ProgramManagementEconomicSector
     */
    Route::get('program-management-economic-sector/get', 'AdeN\Api\Modules\ProgramManagement\EconomicSector\Http\Controllers\ProgramManagementEconomicSectorController@show');
    Route::post('program-management-economic-sector/save', 'AdeN\Api\Modules\ProgramManagement\EconomicSector\Http\Controllers\ProgramManagementEconomicSectorController@store');
    Route::post('program-management-economic-sector/delete', 'AdeN\Api\Modules\ProgramManagement\EconomicSector\Http\Controllers\ProgramManagementEconomicSectorController@destroy');
    Route::post('program-management-economic-sector/import', 'AdeN\Api\Modules\ProgramManagement\EconomicSector\Http\Controllers\ProgramManagementEconomicSectorController@import');
    Route::post('program-management-economic-sector/upload', 'AdeN\Api\Modules\ProgramManagement\EconomicSector\Http\Controllers\ProgramManagementEconomicSectorController@upload');
    Route::match(['get', 'post'], 'program-management-economic-sector', 'AdeN\Api\Modules\ProgramManagement\EconomicSector\Http\Controllers\ProgramManagementEconomicSectorController@index');
	Route::match(['get', 'post'], 'program-management-economic-sector/download', 'AdeN\Api\Modules\ProgramManagement\EconomicSector\Http\Controllers\ProgramManagementEconomicSectorController@download');