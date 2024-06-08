<?php
    
	/**
     *Module: EconomicSector
     */
    Route::get('help-roles-profile/get', 'AdeN\Api\Modules\HelpRolesProfiles\Http\Controllers\HelpRolesProfilesController@show');
    Route::post('help-roles-profile/save', 'AdeN\Api\Modules\HelpRolesProfiles\Http\Controllers\HelpRolesProfilesController@store');
    Route::post('help-roles-profile/delete', 'AdeN\Api\Modules\HelpRolesProfiles\Http\Controllers\HelpRolesProfilesController@destroy');
    Route::post('help-roles-profile/import', 'AdeN\Api\Modules\HelpRolesProfiles\Http\Controllers\HelpRolesProfilesController@import');
    Route::post('help-roles-profile/upload', 'AdeN\Api\Modules\HelpRolesProfiles\Http\Controllers\HelpRolesProfilesController@upload');
    Route::post('help-roles-profile/gettext', 'AdeN\Api\Modules\HelpRolesProfiles\Http\Controllers\HelpRolesProfilesController@getText');
    Route::match(['get', 'post'], 'help-roles-profile', 'AdeN\Api\Modules\HelpRolesProfiles\Http\Controllers\HelpRolesProfilesController@index');
	Route::match(['get', 'post'], 'help-roles-profile/download', 'AdeN\Api\Modules\HelpRolesProfiles\Http\Controllers\HelpRolesProfilesController@download');