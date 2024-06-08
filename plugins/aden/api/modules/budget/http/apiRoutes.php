<?php
    
	/**
     *Module: Budget
     */
    /*Route::get('budget/get', 'AdeN\Api\Modules\Budget\Http\Controllers\BudgetController@show');
    Route::post('budget/save', 'AdeN\Api\Modules\Budget\Http\Controllers\BudgetController@store');
    Route::post('budget/delete', 'AdeN\Api\Modules\Budget\Http\Controllers\BudgetController@destroy');
    Route::post('budget/import', 'AdeN\Api\Modules\Budget\Http\Controllers\BudgetController@import');
    Route::post('budget/upload', 'AdeN\Api\Modules\Budget\Http\Controllers\BudgetController@upload');*/
    Route::match(['get', 'post'], 'budget-v2', 'AdeN\Api\Modules\Budget\Http\Controllers\BudgetController@index');
	Route::match(['get', 'post'], 'budget/download', 'AdeN\Api\Modules\Budget\Http\Controllers\BudgetController@download');