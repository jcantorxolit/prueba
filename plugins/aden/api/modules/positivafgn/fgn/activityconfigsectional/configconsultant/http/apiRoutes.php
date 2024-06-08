<?php
    
	/**
     *Module: ConfigConsultant
     */
    Route::get('positiva-fgn-fgn-activity-config-consultant/get', 'AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\ConfigConsultant\Http\Controllers\ConfigConsultantController@show');
    Route::post('positiva-fgn-fgn-activity-config-consultant/save', 'AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\ConfigConsultant\Http\Controllers\ConfigConsultantController@store');
    Route::post('positiva-fgn-fgn-activity-config-consultant/delete', 'AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\ConfigConsultant\Http\Controllers\ConfigConsultantController@destroy');
    Route::post('positiva-fgn-fgn-activity-config-consultant/config', 'AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\ConfigConsultant\Http\Controllers\ConfigConsultantController@config');
    Route::match(['get', 'post'], 'positiva-fgn-fgn-activity-config-consultant', 'AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\ConfigConsultant\Http\Controllers\ConfigConsultantController@index');