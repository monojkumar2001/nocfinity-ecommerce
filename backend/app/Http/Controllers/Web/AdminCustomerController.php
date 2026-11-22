<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminCustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = User::query()
            ->customers()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)->orWhere('email', 'like', $term);
                });
            })
            ->withCount('orders')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function show(User $user)
    {
        abort_if($user->isStaff(), 404);

        $user->load(['orders' => fn ($q) => $q->orderByDesc('created_at')->limit(20)]);

        return view('admin.customers.show', ['customer' => $user]);
    }
}
