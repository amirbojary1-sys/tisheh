<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoPaymentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PublicImageController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::view('/terms', 'terms')->name('terms');
Route::middleware('guest')->group(function () {
    Route::view('/register', 'auth.register')->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
});
Route::middleware('auth')->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/items/{product}', [CartController::class, 'add'])->middleware('throttle:writes')->name('cart.items.add');
    Route::patch('/cart/items/{product}', [CartController::class, 'update'])->middleware('throttle:writes')->name('cart.items.update');
    Route::delete('/cart/items/{product}', [CartController::class, 'remove'])->middleware('throttle:writes')->name('cart.items.remove');
    Route::post('/cart/checkout', [CartController::class, 'checkout'])->middleware('throttle:writes')->name('cart.checkout');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::put('/account/password', [PasswordController::class, 'update'])->middleware('throttle:password-change')->name('password.change');
    Route::post('/orders/{order}/accept-quote', [OrderController::class, 'acceptQuote'])->middleware('throttle:writes')->name('orders.accept-quote');
    Route::delete('/materials/{product}/images/{image}', [ProductController::class, 'deleteImage'])->middleware('throttle:writes')->name('products.images.delete');
    Route::put('/profile', [DashboardController::class, 'update'])->middleware('throttle:writes')->name('profile.update');
    Route::middleware(['permission:jobs.create', 'throttle:writes'])->group(function () {
        Route::get('/jobs/create', [JobController::class, 'create'])->name('jobs.create');
        Route::post('/jobs', [JobController::class, 'store'])->name('jobs.store');
        Route::get('/jobs/{job}/edit', [JobController::class, 'edit'])->name('jobs.edit');
        Route::put('/jobs/{job}', [JobController::class, 'update'])->name('jobs.update');
        Route::post('/jobs/{job}/close', [JobController::class, 'close'])->name('jobs.close');
        Route::delete('/jobs/{job}', [JobController::class, 'destroy'])->name('jobs.destroy');
        Route::patch('/applications/{application}', [JobController::class, 'application'])->name('applications.update');
    });
    Route::post('/jobs/{job}/apply', [JobController::class, 'apply'])->middleware('throttle:writes')->name('jobs.apply');
    Route::middleware(['permission:products.create', 'throttle:writes'])->group(function () {
        Route::get('/materials/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/materials', [ProductController::class, 'store'])->name('products.store');
        Route::get('/materials/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/materials/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::post('/materials/{product}/deactivate', [ProductController::class, 'deactivate'])->name('products.deactivate');
    });
    Route::prefix('admin')->middleware(['permission:admin.access'])->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('admin');
        Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
        Route::get('/users/{user}', [AdminController::class, 'showUser'])->name('admin.users.show');
        Route::get('/listings/{type}', [AdminController::class, 'listings'])->name('admin.listings');
        Route::patch('/listings/{type}/{id}', [AdminController::class, 'listingStatus'])->middleware('throttle:writes')->name('admin.listing.status');
        Route::get('/categories', [AdminController::class, 'categories'])->name('admin.categories');
        Route::post('/categories/{type}', [AdminController::class, 'category'])->middleware('throttle:writes')->name('admin.categories.store');
        Route::put('/categories/{type}/{id}', [AdminController::class, 'category'])->middleware('throttle:writes')->name('admin.categories.update');
        Route::delete('/categories/{type}/{id}', [AdminController::class, 'deleteCategory'])->middleware('throttle:writes')->name('admin.categories.delete');
        Route::get('/reports', [AdminController::class, 'reports'])->name('admin.reports');
        Route::get('/reports/{report}', [AdminController::class, 'report'])->name('admin.reports.show');
        Route::patch('/reports/{report}', [AdminController::class, 'reviewReport'])->middleware('throttle:writes')->name('admin.reports.update');
        Route::get('/settings', [AdminController::class, 'settings'])->name('admin.settings');
        Route::put('/settings', [AdminController::class, 'saveSettings'])->middleware('throttle:writes')->name('admin.settings.update');
        Route::get('/pages', [AdminController::class, 'pages'])->name('admin.pages');
        Route::post('/pages', [AdminController::class, 'savePage'])->middleware('throttle:writes')->name('admin.pages.store');
        Route::put('/pages/{page}', [AdminController::class, 'savePage'])->middleware('throttle:writes')->name('admin.pages.update');
        Route::get('/audit', [AdminController::class, 'audit'])->name('admin.audit');
        Route::get('/orders', [AdminController::class, 'orders'])->name('admin.orders');
        Route::get('/roles', [AdminController::class, 'roles'])->name('admin.roles');
        Route::get('/finance', [AdminController::class, 'finance'])->name('admin.finance');

        Route::patch('/moderate/{type}/{id}', [AdminController::class, 'moderate'])->middleware('throttle:writes')->name('admin.moderate');
        Route::patch('/users/{user}', [AdminController::class, 'user'])->middleware('throttle:writes')->name('admin.user');
    });
});
Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{job}', [JobController::class, 'show'])->name('jobs.show');
Route::get('/materials', [ProductController::class, 'index'])->name('products.index');
Route::get('/compare', [ProductController::class, 'compare'])->name('products.compare');
Route::get('/materials/{product}', [ProductController::class, 'show'])->name('products.show');

Route::get('/pages/{slug}', [ContentController::class, 'show'])->name('content.show');
Route::middleware('auth')->group(function () {
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages/start/{type}/{id}', [MessageController::class, 'start'])->middleware('throttle:writes')->name('messages.start');
    Route::get('/messages/{conversation}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{conversation}', [MessageController::class, 'send'])->middleware('throttle:writes')->name('messages.send');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/create', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/reports', [ReportController::class, 'store'])->middleware('throttle:writes')->name('reports.store');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/materials/{product}/order', [OrderController::class, 'store'])->middleware('throttle:writes')->name('orders.store');
    Route::patch('/orders/{order}', [OrderController::class, 'update'])->middleware('throttle:writes')->name('orders.update');
});

Route::middleware('guest')->group(function () {
    Route::get('/login/otp', [OtpController::class, 'form'])->name('otp.form');
    Route::post('/login/otp', [OtpController::class, 'request'])->middleware('throttle:otp-send')->name('otp.request');
    Route::get('/login/otp/code', [OtpController::class, 'code'])->name('otp.code');
    Route::post('/login/otp/code', [OtpController::class, 'verify'])->middleware('throttle:otp-verify')->name('otp.verify');
});
Route::middleware('auth')->group(function () {
    Route::post('/orders/{order}/demo-payment', [DemoPaymentController::class, 'start'])->middleware('throttle:writes')->name('demo.payments.start');
    Route::get('/demo/payments/{attempt}', [DemoPaymentController::class, 'show'])->name('demo.payments.show');
    Route::post('/demo/payments/{attempt}', [DemoPaymentController::class, 'complete'])->middleware('throttle:writes')->name('demo.payments.complete');
    Route::get('/admin/demo-payments', [DemoPaymentController::class, 'admin'])->middleware('permission:admin.access')->name('admin.demo-payments');
});

// Strict public-image fallbacks for hosts where storage symlinks are unavailable.
Route::get('/storage/products/{file}', [PublicImageController::class, 'product'])->where('file', '[a-f0-9-]{36}\.webp');
Route::get('/storage/samples/{file}', [PublicImageController::class, 'sample'])->where('file', '(?:cement|brick|steel|sand)\.svg');
Route::get('/admin/system', [AdminController::class, 'system'])->middleware(['auth', 'permission:admin.access'])->name('admin.system');
