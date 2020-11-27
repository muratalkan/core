<?php

Route::group(['middleware' => ['admin']], function () {
    Route::get('/go_servisleri', "GoEngine\MainController@all");
});
