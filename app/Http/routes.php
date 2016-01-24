<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::group(['namespace' => 'API'], function() {
	Route::get('api',             'ApiController@getIndex');
	Route::get('api/receive',     'ApiController@receive');
	Route::get('api/callback',    'ApiController@callback');
	Route::get('api/blocknotify', 'ApiController@blocknotify');

	Route::get('api/{guid}/balance',              'ApiController@balance');
	Route::get('api/{guid}/address-balance',      'ApiController@addressBalance');
	Route::get('api/{guid}/core-balance',         'ApiController@coreBalance');
	Route::get('api/{guid}/new-address',          'ApiController@newAddress');
	Route::get('api/{guid}/fee-address',          'ApiController@feeAddress');
	Route::get('api/{guid}/validate-transaction', 'ApiController@validateTransaction');
	Route::get('api/{guid}/validate-address',     'ApiController@validateAddress');
	Route::get('api/{guid}/tx-confirmations',     'ApiController@txConfirmations');
	Route::get('api/{guid}/address-transactions', 'ApiController@addressTransactions');
	Route::get('api/{guid}/unpaid-invoices',      'ApiController@unpaidInvoices');
	Route::get('api/{guid}/payment',              'ApiController@payment');
});

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

Route::group(['middleware' => ['web']], function () {
    //
});
