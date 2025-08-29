<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/*Route::get('/', function () {
    return view('welcome');
});*/

Auth::routes();

Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])
    ->middleware(['auth']);

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])
    ->name('home')
    ->middleware(['auth']);



Route::group(['prefix' => 'private', 'middleware' => ['auth']], function () {
    Route::resource('users', App\Http\Controllers\UserController::class)
        ->only(['index','edit','update','create','store'])
        ->middleware(['access.granted:users']);

    Route::get('/users/{user}/journal', [App\Http\Controllers\UserController::class, 'journal'])
        ->name('users.journal')
        ->middleware(['access.granted:users']);

    Route::get('/users/{user}/last-day-requests-count', [App\Http\Controllers\UserController::class, 'lastDayRequestsCount'])
        ->name('users.last_day_requests_count')
        ->middleware(['access.granted:users']);

    Route::get('/users/download-excel', [App\Http\Controllers\UserController::class, 'downloadExcel'])
        ->name('users.download.excel')
        ->middleware(['access.granted:users']);

    Route::post('/users/{user}/password', [App\Http\Controllers\UserController::class, 'password'])
        ->name('users.password')
        ->middleware(['access.granted:users']);

    Route::get('/users/{user}/password-generate', [App\Http\Controllers\UserController::class, 'passwordGenerate'])
        ->name('users.password.generate')
        ->middleware(['access.granted:users']);

    Route::match(array('GET','POST'),'/users/mass-password-generate', [App\Http\Controllers\UserController::class, 'massPasswordGenerate'])
        ->name('users.mass.password.generate')
        ->middleware(['access.granted:users']);

    Route::match(array('GET','POST'),'/users/import-excel', [App\Http\Controllers\UserController::class, 'importExcel'])
        ->name('users.import.excel')
        ->middleware(['access.granted:users']);

    Route::match(['GET'], '/users/{user}/limits', [App\Http\Controllers\UserController::class, 'limits'])
        ->name('users.limits')
        ->middleware(['access.granted:users']);

    Route::match(['POST'], '/users/{user}/limits', [App\Http\Controllers\UserController::class, 'addLimit'])
        ->name('users.limits.add')
        ->middleware(['access.granted:users']);

    Route::match(['POST'], '/users/{user}/parallel', [App\Http\Controllers\UserController::class, 'parallelQueriesLimit'])
        ->name('users.limits.parallel')
        ->middleware(['access.granted:users']);

    Route::match(['GET'], '/users/{user}/limits/{limit}/remove', [App\Http\Controllers\UserController::class, 'removeLimit'])
        ->name('users.limits.remove')
        ->middleware(['access.granted:users']);

    /* */

    Route::resource('proxies', App\Http\Controllers\ProxyController::class)
        ->only(['index','edit','update','create','store'])
        ->middleware(['access.granted:manage_system']);

    /* */

    Route::resource('sources', App\Http\Controllers\SourceController::class)
        ->only(['index','edit','update','create','store'])
        ->middleware(['access.granted:manage_system']);

    /* */

    Route::any('/check', [App\Http\Controllers\CheckController::class, 'index'])
        ->name('check')
        ->middleware(['access.granted:check']);

    Route::get('/history', [App\Http\Controllers\HistoryController::class, 'index'])
        ->name('history')
        ->middleware(['access.granted:history']);

    Route::get('/history/{requestNew}/details', [App\Http\Controllers\HistoryController::class, 'details'])
        ->name('history-details')
        ->middleware(['access.granted:history']);

    /* */

    Route::resource('source-accounts', App\Http\Controllers\SourceAccountController::class)
        ->only(['index','edit','update','create','store','destroy'])
        ->middleware(['access.granted:source_account']);

    Route::get('/source-accounts/{source_account}/remove', [App\Http\Controllers\SourceAccountController::class, 'remove'])
        ->name('source-accounts.remove')
        ->middleware(['access.granted:source_account']);

    /* */

    Route::resource('check-types', App\Http\Controllers\CheckTypeController::class)
        ->only(['index','edit','update','create','store'])
        ->middleware(['access.granted:manage_system']);


    Route::put('/check-types/{check_type}/update-status', [App\Http\Controllers\CheckTypeController::class, 'updateStatus'])
        ->name('check-types.update-status')
        ->middleware(['access.granted:manage_system'])
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

    /* */

    Route::resource('fields', App\Http\Controllers\FieldController::class)
        ->only(['index','update'])
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
        ->middleware(['access.granted:messages']);

    Route::get('/fields/{source}/edit', [App\Http\Controllers\FieldController::class, 'edit'])
        ->name('fields.edit')
        ->middleware(['access.granted:messages']);

    Route::get('/fields/download-excel', [App\Http\Controllers\FieldController::class, 'downloadExcel'])
        ->name('fields.download.excel')
        ->middleware(['access.granted:messages']);
    /* */

    Route::resource('messages', App\Http\Controllers\MessageController::class)
        ->only(['index','edit','update','create','store','delete'])
        ->middleware(['access.granted:messages']);

    Route::get('/messages/{message}/remove', [App\Http\Controllers\MessageController::class, 'remove'])
        ->name('messages.remove')
        ->middleware(['access.granted:messages']);

    Route::get('/messages/{message}/user/{user}/remove', [App\Http\Controllers\MessageController::class, 'removeUser'])
        ->name('messages.user.remove')
        ->middleware(['access.granted:messages']);

    Route::get('/messages/{message}/client/{client}/remove', [App\Http\Controllers\MessageController::class, 'removeClient'])
        ->name('messages.client.remove')
        ->middleware(['access.granted:messages']);

    /* */

    Route::resource('accesses', App\Http\Controllers\AccessController::class)
        ->only(['index','edit','update','create','store'])
        ->middleware(['access.granted:access_levels_all']);

    Route::get('/accesses/{access}/forms', [App\Http\Controllers\AccessFormController::class, 'forms'])
        ->name('accesses.forms')
        ->middleware(['access.granted:access_levels_all']);

    Route::post('/accesses/{access}/forms/hide-field', [App\Http\Controllers\AccessFormController::class, 'hideField'])
        ->name('accesses.forms.hide.field')
        ->middleware(['access.granted:access_levels_all'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

    Route::post('/accesses/{access}/sources', [App\Http\Controllers\AccessController::class, 'sourceAdd'])
        ->name('accesses.sources.add')
        ->middleware(['access.granted:access_levels_all']);

    Route::get('/accesses/{access}/sources/{source}', [App\Http\Controllers\AccessController::class, 'sourceRemove'])
        ->name('accesses.sources.remove')
        ->middleware(['access.granted:access_levels_all']);

    /* */

    Route::resource('clients', App\Http\Controllers\ClientController::class)
        ->only(['index','edit','update','create','store'])
        ->middleware(['access.granted:clients_own']);

    Route::match(['GET'], '/clients/{client}/limits', [App\Http\Controllers\ClientController::class, 'limits'])
        ->name('clients.limits')
        ->middleware(['access.granted:clients_own']);

    Route::match(['POST'], '/clients/{client}/limits', [App\Http\Controllers\ClientController::class, 'addLimit'])
        ->name('clients.limits.add')
        ->middleware(['access.granted:clients_own']);

    Route::match(['GET'], '/clients/{client}/limits/{limit}/remove', [App\Http\Controllers\ClientController::class, 'removeLimit'])
        ->name('clients.limits.remove')
        ->middleware(['access.granted:clients_own']);

    Route::get('/clients/download-excel', [App\Http\Controllers\ClientController::class, 'downloadExcel'])
        ->name('clients.download.excel')
        ->middleware(['access.granted:clients_own']);

    Route::get('/clients/{client}/journal', [App\Http\Controllers\ClientController::class, 'journal'])
        ->name('clients.journal')
        ->middleware(['access.granted:clients_own']);

    Route::get('/clients/{client}/last-day-requests-count', [App\Http\Controllers\ClientController::class, 'lastDayRequestsCount'])
        ->name('clients.last_day_requests_count')
        ->middleware(['access.granted:clients_own']);


    Route::get('/clients/select-search', [App\Http\Controllers\ClientController::class, 'selectSearch'])
        ->name('clients.select.search')
        ->middleware(['access.granted:clients_own']);

});

