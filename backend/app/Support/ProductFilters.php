<?php

namespace App\Support;

use App\Models\DressStyle;
use App\Models\ProductColor;
use App\Models\ProductSize;
use Throwable;

class ProductFilters
{
    public const COLORS = [
        'Green', 'Red', 'Yellow', 'Orange', 'Cyan', 'Blue', 'Purple', 'Pink', 'White', 'Black',
    ];

    public const SIZES = [
        'XX-Small', 'X-Small', 'Small', 'Medium', 'Large', 'X-Large', 'XX-Large', '3X-Large', '4X-Large',
    ];

    public const DRESS_STYLES = [
        'Casual', 'Formal', 'Party', 'Gym',
    ];

    public static function colorNames(): array
    {
        try {
            $names = ProductColor::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->pluck('name')
                ->all();

            return $names ?: self::COLORS;
        } catch (Throwable) {
            return self::COLORS;
        }
    }

    public static function sizeNames(): array
    {
        try {
            $names = ProductSize::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->pluck('name')
                ->all();

            return $names ?: self::SIZES;
        } catch (Throwable) {
            return self::SIZES;
        }
    }

    public static function dressStyleNames(): array
    {
        try {
            $names = DressStyle::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->pluck('name')
                ->all();

            return $names ?: self::DRESS_STYLES;
        } catch (Throwable) {
            return self::DRESS_STYLES;
        }
    }

    public static function colorsWithHex(): array
    {
        try {
            $rows = ProductColor::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['name', 'hex']);

            if ($rows->isNotEmpty()) {
                return $rows->map(fn ($c) => [
                    'name' => $c->name,
                    'hex' => $c->hex ?: '#000000',
                ])->all();
            }
        } catch (Throwable) {
            // fall through
        }

        $fallbackHex = [
            'Green' => '#00C12B', 'Red' => '#F50606', 'Yellow' => '#F5DD06', 'Orange' => '#F57906',
            'Cyan' => '#06CAF5', 'Blue' => '#063AF5', 'Purple' => '#7D06F5', 'Pink' => '#F506A4',
            'White' => '#FFFFFF', 'Black' => '#000000',
        ];

        return array_map(
            fn ($name) => ['name' => $name, 'hex' => $fallbackHex[$name] ?? '#000000'],
            self::COLORS
        );
    }
}
