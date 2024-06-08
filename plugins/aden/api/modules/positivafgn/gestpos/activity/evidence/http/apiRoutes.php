<?php
    
	/**
     *Module: Evidence
     */

    Route::post('positiva-fgn-gestpos-activity-evidence/save', 'AdeN\Api\Modules\PositivaFgn\GestPos\Activity\Evidence\Http\Controllers\EvidenceController@store');
    Route::post('positiva-fgn-gestpos-activity-evidence/delete', 'AdeN\Api\Modules\PositivaFgn\GestPos\Activity\Evidence\Http\Controllers\EvidenceController@destroy');
    Route::match(['get', 'post'], 'positiva-fgn-gestpos-activity-evidence', 'AdeN\Api\Modules\PositivaFgn\GestPos\Activity\Evidence\Http\Controllers\EvidenceController@index');