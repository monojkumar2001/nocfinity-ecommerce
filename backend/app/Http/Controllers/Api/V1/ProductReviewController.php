<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductReviewController extends Controller
{
    public function index(string $slug)
    {
        $product = $this->findProduct($slug);

        $reviews = ProductReview::query()
            ->with('user:id,name')
            ->where('product_id', $product->id)
            ->where('is_approved', true)
            ->latest()
            ->paginate(10);

        return ApiResponse::success(
            $reviews->getCollection()->map(fn (ProductReview $review) => $this->transform($review))->values(),
            'Reviews retrieved.',
            200,
            [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'average_rating' => round((float) ProductReview::query()
                    ->where('product_id', $product->id)
                    ->where('is_approved', true)
                    ->avg('rating'), 1),
                'reviews_count' => $reviews->total(),
            ]
        );
    }

    public function store(Request $request, string $slug)
    {
        $product = $this->findProduct($slug);
        $user = $request->user();

        if ($user->isStaff()) {
            throw ValidationException::withMessages([
                'rating' => ['Staff accounts cannot submit product reviews.'],
            ]);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);

        if (!$this->customerPurchasedProduct($user->id, $product->id)) {
            throw ValidationException::withMessages([
                'rating' => ['You can only review products you have purchased.'],
            ]);
        }

        if (ProductReview::query()->where('product_id', $product->id)->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'rating' => ['You have already reviewed this product.'],
            ]);
        }

        $title = isset($validated['title']) ? $this->plainText($validated['title']) : null;
        $body = isset($validated['body']) ? $this->plainText($validated['body']) : null;

        $review = ProductReview::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => $validated['rating'],
            'title' => $title ?: null,
            'body' => $body ?: null,
            'is_approved' => true,
        ]);

        return ApiResponse::success(
            $this->transform($review->load('user:id,name')),
            'Review submitted.',
            201
        );
    }

    private function findProduct(string $slug): Product
    {
        return Product::query()
            ->where(fn ($q) => $q->where('slug', $slug)->orWhere('id', $slug))
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function customerPurchasedProduct(int $userId, int $productId): bool
    {
        return Order::query()
            ->where('user_id', $userId)
            ->whereIn('status', ['paid', 'processing', 'shipped', 'delivered'])
            ->whereHas('items', fn ($q) => $q->where('product_id', $productId))
            ->exists();
    }

    private function transform(ProductReview $review): array
    {
        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'title' => $review->title,
            'body' => $review->body,
            'created_at' => optional($review->created_at)?->toIso8601String(),
            'user' => [
                'id' => $review->user?->id,
                'name' => $review->user?->name,
            ],
        ];
    }

    private function plainText(string $value): string
    {
        $value = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $value) ?? '';
        $value = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $value) ?? '';

        return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
