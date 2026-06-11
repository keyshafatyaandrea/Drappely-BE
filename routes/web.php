<?php
// Ini route web biasa untuk tampilan blade, bukan API.
// Kalo guru nanya kenapa ada controller API terpisah, jawab:
// web route di sini, API route di routes/api.php.

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});