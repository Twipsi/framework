<?php

use Twipsi\Facades\Route;

Route::any('/', function() {
    return 'test route';
});

Route::any('/admin/login', function() {
    return 'test route';
})->name('fakeroute');