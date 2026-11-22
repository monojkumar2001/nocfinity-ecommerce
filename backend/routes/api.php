<?php

use App\Http\Controllers\Api\V1\Admin\CouponAdminController;
use App\Http\Controllers\Api\V1\Admin\CustomerController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\OrderAdminController;
use App\Http\Controllers\Api\V1\Admin\ProfileController;
use App\Http\Controllers\Api\V1\Admin\RoleController;
use App\Http\Controllers\Api\V1\Admin\StaffController;
use App\Http\Controllers\Api\V1\AdminAuthController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\FilterOptionController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'Nocfinity API is running',
            'version' => 'v1',
        ]);
    });

    // Customer auth (Sanctum tokens, ability: customer)
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:5,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:5,1');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // Separate admin auth guard / portal (Sanctum ability: admin)
    Route::post('/admin/login', [AdminAuthController::class, 'login']);

    // Public catalog
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/filter-options', [FilterOptionController::class, 'index']);

    // Cart (guest or auth via optional sanctum)
    Route::middleware('auth.optional')->group(function () {
        Route::get('/cart', [CartController::class, 'index']);
        Route::post('/cart/items', [CartController::class, 'store']);
        Route::put('/cart/items/{cartItem}', [CartController::class, 'update']);
        Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy']);
        Route::post('/cart/coupon', [CouponController::class, 'apply']);
        Route::delete('/cart/coupon', [CouponController::class, 'remove']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'changePassword']);
        Route::post('/email/resend', [AuthController::class, 'resendVerification'])
            ->middleware('throttle:6,1');

        Route::post('/checkout', [CheckoutController::class, 'store']);
        Route::post('/checkout/confirm', [CheckoutController::class, 'confirm']);

        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);

        // Admin (staff role + admin-scoped token from /admin/login)
        Route::prefix('admin')->middleware('staff')->group(function () {
            Route::post('/logout', [AdminAuthController::class, 'logout']);
            Route::get('/me', [ProfileController::class, 'show']);
            Route::put('/profile', [ProfileController::class, 'update']);

            Route::get('/dashboard', [DashboardController::class, 'index'])
                ->middleware('permission:reports.view');

            Route::middleware('permission:products.view')->group(function () {
                Route::get('/products', [ProductController::class, 'index']);
                Route::get('/products/{product}', [ProductController::class, 'adminShow']);
            });
            Route::post('/products', [ProductController::class, 'store'])
                ->middleware('permission:products.create');
            Route::put('/products/{product}', [ProductController::class, 'update'])
                ->middleware('permission:products.edit');
            Route::delete('/products/{product}', [ProductController::class, 'destroy'])
                ->middleware('permission:products.delete');

            Route::middleware('permission:categories.view')->group(function () {
                Route::get('/categories', [CategoryController::class, 'index']);
            });
            Route::post('/categories', [CategoryController::class, 'store'])
                ->middleware('permission:categories.create');
            Route::put('/categories/{category}', [CategoryController::class, 'update'])
                ->middleware('permission:categories.edit');
            Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
                ->middleware('permission:categories.delete');

            Route::get('/orders', [OrderAdminController::class, 'index'])
                ->middleware('permission:orders.view');
            Route::get('/orders/{order}', [OrderAdminController::class, 'show'])
                ->middleware('permission:orders.view');
            Route::patch('/orders/{order}/status', [OrderAdminController::class, 'updateStatus'])
                ->middleware('permission:orders.update_status');

            Route::middleware('permission:coupons.manage')->group(function () {
                Route::get('/coupons', [CouponAdminController::class, 'index']);
                Route::post('/coupons', [CouponAdminController::class, 'store']);
                Route::put('/coupons/{coupon}', [CouponAdminController::class, 'update']);
                Route::delete('/coupons/{coupon}', [CouponAdminController::class, 'destroy']);
            });

            Route::get('/customers', [CustomerController::class, 'index'])
                ->middleware('permission:customers.view');
            Route::get('/customers/{user}', [CustomerController::class, 'show'])
                ->middleware('permission:customers.view');

            Route::middleware('permission:roles.manage')->group(function () {
                Route::get('/roles', [RoleController::class, 'index']);
                Route::post('/roles', [RoleController::class, 'store']);
                Route::put('/roles/{role}', [RoleController::class, 'update']);
                Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
                Route::get('/staff', [StaffController::class, 'index']);
                Route::post('/staff', [StaffController::class, 'store']);
                Route::put('/staff/{user}/roles', [StaffController::class, 'updateRoles']);
            });
        });
    });
});
