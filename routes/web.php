<?php

/**
 * Web (web-facing) route definitions.
 * Defines routes for browser-accessible pages.
 */

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
