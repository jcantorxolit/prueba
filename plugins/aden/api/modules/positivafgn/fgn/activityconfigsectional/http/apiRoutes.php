<?php
    
	/**
     *Module: ActivityConfigSectional
     */
    Route::get('positiva-fgn-fgn-activity-config-sectional/get', 'AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\Http\Controllers\ActivityConfigSectionalController@show');
    Route::get('positiva-fgn-fgn-activity-config-sectional/getClear', 'AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\Http\Controllers\ActivityConfigSectionalController@showClear');
    Route::post('positiva-fgn-fgn-activity-config-sectional/save', 'AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\Http\Controllers\ActivityConfigSectionalController@store');
    Route::post('positiva-fgn-fgn-activity-config-sectional/delete', 'AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\Http\Controllers\ActivityConfigSectionalController@destroy');
    Route::match(['get', 'post'], 'positiva-fgn-fgn-activity-config-sectional', 'AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\Http\Controllers\ActivityConfigSectionalController@index');
    Route::get('positiva-fgn-fgn-activity-config-sectional/recalculated', 'AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\Http\Controllers\ActivityConfigSectionalController@recalculated');
    Route::get('positiva-fgn-fgn-activity-config-sectional/herede-indicators', 'AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\Http\Controllers\ActivityConfigSectionalController@heredeIndicators');