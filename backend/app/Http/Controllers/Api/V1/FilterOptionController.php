<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Support\ProductFilters;

class FilterOptionController extends Controller
{
    public function index()
    {
        return ApiResponse::success([
            'colors' => ProductFilters::colorsWithHex(),
            'sizes' => ProductFilters::sizeNames(),
            'dress_styles' => ProductFilters::dressStyleNames(),
        ]);
    }
}
