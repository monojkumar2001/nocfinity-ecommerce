<?php

use App\Http\Controllers\Web\AdminCategoryController;
use App\Http\Controllers\Web\AdminColorController;
use App\Http\Controllers\Web\AdminCouponController;
use App\Http\Controllers\Web\AdminCustomerController;
use App\Http\Controllers\Web\AdminDashboardController;
use App\Http\Controllers\Web\AdminDressStyleController;
use App\Http\Controllers\Web\AdminOrderController;
use App\Http\Controllers\Web\AdminProductController;
use App\Http\Controllers\Web\AdminRoleController;
use App\Http\Controllers\Web\AdminSettingsController;
use App\Http\Controllers\Web\AdminSizeController;
use App\Http\Controllers\Web\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'show'])->name('login');
Route::get('/login', [LoginController::class, 'show']);
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth:web')->group(function () {
    Route::get('/dashboard', [LoginController::class, 'dashboard'])->name('dashboard');
});

Route::middleware(['auth:admin', 'staff.web'])->prefix('admin')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);

    Route::get('/settings', [AdminSettingsController::class, 'edit'])->name('admin.settings.edit');
    Route::put('/settings', [AdminSettingsController::class, 'update'])->name('admin.settings.update');

    Route::middleware('perm:products.view')->group(function () {
        Route::get('/products', [AdminProductController::class, 'index'])->name('admin.products.index');
    });
    Route::middleware('perm:products.create')->group(function () {
        Route::get('/products/create', [AdminProductController::class, 'create'])->name('admin.products.create');
        Route::post('/products', [AdminProductController::class, 'store'])->name('admin.products.store');
    });
    Route::middleware('perm:products.edit')->group(function () {
        Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('admin.products.edit');
        Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('admin.products.update');
    });
    Route::middleware('perm:products.delete')->group(function () {
        Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('admin.products.destroy');
    });

    Route::middleware('perm:orders.view')->group(function () {
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('admin.orders.index');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('admin.orders.show');
    });
    Route::middleware('perm:orders.update_status')->group(function () {
        Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('admin.orders.status');
    });

    Route::middleware('perm:categories.view')->group(function () {
        Route::get('/categories', [AdminCategoryController::class, 'index'])->name('admin.categories.index');
    });
    Route::middleware('perm:categories.create')->group(function () {
        Route::get('/categories/create', [AdminCategoryController::class, 'create'])->name('admin.categories.create');
        Route::post('/categories', [AdminCategoryController::class, 'store'])->name('admin.categories.store');
    });
    Route::middleware('perm:categories.edit')->group(function () {
        Route::get('/categories/{category}/edit', [AdminCategoryController::class, 'edit'])->name('admin.categories.edit');
        Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('admin.categories.update');
    });
    Route::middleware('perm:categories.delete')->group(function () {
        Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('admin.categories.destroy');
    });

    Route::middleware('perm:attributes.manage')->group(function () {
        Route::get('/colors', [AdminColorController::class, 'index'])->name('admin.colors.index');
        Route::get('/colors/create', [AdminColorController::class, 'create'])->name('admin.colors.create');
        Route::post('/colors', [AdminColorController::class, 'store'])->name('admin.colors.store');
        Route::get('/colors/{color}/edit', [AdminColorController::class, 'edit'])->name('admin.colors.edit');
        Route::put('/colors/{color}', [AdminColorController::class, 'update'])->name('admin.colors.update');
        Route::delete('/colors/{color}', [AdminColorController::class, 'destroy'])->name('admin.colors.destroy');

        Route::get('/sizes', [AdminSizeController::class, 'index'])->name('admin.sizes.index');
        Route::get('/sizes/create', [AdminSizeController::class, 'create'])->name('admin.sizes.create');
        Route::post('/sizes', [AdminSizeController::class, 'store'])->name('admin.sizes.store');
        Route::get('/sizes/{size}/edit', [AdminSizeController::class, 'edit'])->name('admin.sizes.edit');
        Route::put('/sizes/{size}', [AdminSizeController::class, 'update'])->name('admin.sizes.update');
        Route::delete('/sizes/{size}', [AdminSizeController::class, 'destroy'])->name('admin.sizes.destroy');

        Route::get('/dress-styles', [AdminDressStyleController::class, 'index'])->name('admin.dress-styles.index');
        Route::get('/dress-styles/create', [AdminDressStyleController::class, 'create'])->name('admin.dress-styles.create');
        Route::post('/dress-styles', [AdminDressStyleController::class, 'store'])->name('admin.dress-styles.store');
        Route::get('/dress-styles/{dress_style}/edit', [AdminDressStyleController::class, 'edit'])->name('admin.dress-styles.edit');
        Route::put('/dress-styles/{dress_style}', [AdminDressStyleController::class, 'update'])->name('admin.dress-styles.update');
        Route::delete('/dress-styles/{dress_style}', [AdminDressStyleController::class, 'destroy'])->name('admin.dress-styles.destroy');
    });

    Route::middleware('perm:coupons.manage')->group(function () {
        Route::get('/coupons', [AdminCouponController::class, 'index'])->name('admin.coupons.index');
        Route::get('/coupons/create', [AdminCouponController::class, 'create'])->name('admin.coupons.create');
        Route::post('/coupons', [AdminCouponController::class, 'store'])->name('admin.coupons.store');
        Route::get('/coupons/{coupon}/edit', [AdminCouponController::class, 'edit'])->name('admin.coupons.edit');
        Route::put('/coupons/{coupon}', [AdminCouponController::class, 'update'])->name('admin.coupons.update');
        Route::delete('/coupons/{coupon}', [AdminCouponController::class, 'destroy'])->name('admin.coupons.destroy');
    });

    Route::middleware('perm:customers.view')->group(function () {
        Route::get('/customers', [AdminCustomerController::class, 'index'])->name('admin.customers.index');
        Route::get('/customers/{user}', [AdminCustomerController::class, 'show'])->name('admin.customers.show');
    });

    Route::middleware('perm:roles.manage')->group(function () {
        Route::get('/roles', [AdminRoleController::class, 'index'])->name('admin.roles.index');
        Route::post('/roles', [AdminRoleController::class, 'store'])->name('admin.roles.store');
        Route::get('/roles/staff', [AdminRoleController::class, 'staff'])->name('admin.roles.staff');
        Route::put('/roles/staff/{user}', [AdminRoleController::class, 'updateStaffRoles'])->name('admin.roles.staff.update');
        Route::put('/roles/{role}', [AdminRoleController::class, 'update'])->name('admin.roles.update');
        Route::delete('/roles/{role}', [AdminRoleController::class, 'destroy'])->name('admin.roles.destroy');
    });
});
