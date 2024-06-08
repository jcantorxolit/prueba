<?php
    
	/**
     *Module: Campus
     */
    Route::get('positiva-fgn-vendor-main-contact/get', 'AdeN\Api\Modules\PositivaFgn\Vendor\Maincontact\Http\Controllers\MainContactController@show');
    Route::post('positiva-fgn-vendor-main-contact/save', 'AdeN\Api\Modules\PositivaFgn\Vendor\Maincontact\Http\Controllers\MainContactController@store');
    Route::post('positiva-fgn-vendor-main-contact/delete', 'AdeN\Api\Modules\PositivaFgn\Vendor\Maincontact\Http\Controllers\MainContactController@destroy');
    Route::post('positiva-fgn-vendor-main-contact/deleteInfo', 'AdeN\Api\Modules\PositivaFgn\Vendor\Maincontact\Http\Controllers\MainContactController@destroyInfo');