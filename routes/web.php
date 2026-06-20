<?php

use App\Http\Controllers\AIController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\GithubLoginController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\GoogleLoginController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SiteGeneratorController;
use Illuminate\Support\Facades\Route;

// Public Routes

Route::get('/', function () {
    return view('welcome');
});
// About Page
Route::get('/about', function () {
    return view('pages.about');
})->name('about');

// Contact Page
Route::get('/contact', [ContactController::class, 'contact'])->name('contact');
Route::post('/contact', [ContactController::class, 'submitContactForm'])->name('contact.submit');


Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified.email'])->name('dashboard');

// Authenticated User Routes
Route::middleware(['auth', 'site.auth', 'verified.email'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/project/{id}/publish', [SiteGeneratorController::class, 'publish']);
    Route::get('/project/{id}/download', [SiteGeneratorController::class, 'download']);


});

// Google OAuth Routes
Route::get('/auth/google', [GoogleLoginController::class, 'redirectToGoogle'])->name('google.login');
Route::get('/auth/google/callback', [GoogleLoginController::class, 'handleGoogleCallback'])->name('google.callback');
// GitHub OAuth Routes
Route::get('/auth/github', [GithubLoginController::class, 'redirectToGithub'])->name('github.login');
Route::get('/auth/github/callback', [GithubLoginController::class, 'handleGithubCallback'])->name('github.callback');

// Email Verification Routes
Route::get('email/verify-email', [EmailVerificationController::class, 'verifyEmail'])->name('email.verify');
Route::post('/verify-email/code', [EmailVerificationController::class, 'verifyWithCode'])->name('verification.verify-code');
Route::get('/email/verify/link/{id}/{hash}', [EmailVerificationController::class, 'verifyWithLink'])->name('email.verify.link');
Route::post('email/verify-email/resend', [EmailVerificationController::class, 'resend'])->name('verification.resend');

// Generate website route

Route::post('/generate-site', [SiteGeneratorController::class, 'generate']);
Route::get('/preview/{slug}', [SiteGeneratorController::class, 'previewBySlug'])
    ->name('preview');
Route::post('/project/{id}/edit', [SiteGeneratorController::class, 'edit']);
Route::post('/project/{id}/chat-edit', [SiteGeneratorController::class, 'chatEdit']);
Route::get('/project/{id}/messages', [SiteGeneratorController::class, 'messages']);
Route::put('/project/{id}', [SiteGeneratorController::class, 'update']);
Route::get('/project/{id}/status', [SiteGeneratorController::class, 'status']);
Route::get('/project/{id}/get-html', [SiteGeneratorController::class, 'getHtml']);
Route::get('/render/{slug}', [SiteGeneratorController::class, 'renderProject']);


// update code
Route::put('/project/{id}/update-html', [SiteGeneratorController::class, 'updateHtml']);
// download code

// Served Published
Route::get('/site/{id}', [SiteGeneratorController::class, 'servePublished']);

// Image upload route
Route::post('/upload-image', [SiteGeneratorController::class, 'uploadImage']);









require __DIR__ . '/auth.php';
