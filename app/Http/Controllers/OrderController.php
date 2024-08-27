<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        // Fetch orders with in one query with eager loading of customer and items relations

        //TODO:: WE CAN ADD CACHE HERE for performance improvement
        // - we can store total amount on the order table to avoid calculating it every time
        // - we can store items count on the order table to avoid calculating it every time
        // - we add pagination to avoid loading all orders at once

        $orders = Order::with(['customer', 'items.product'])
            ->get()
            ->map(function ($order) {
                $totalAmount = $order->items->sum(fn ($item) => $item->price * $item->quantity);
                $itemsCount = $order->items->count();
                $lastAddedToCart = $order->items->last()->created_at ?? null;
                $completedOrderExists = $order->status === 'completed';

                return [
                    'order_id' => $order->id,
                    'customer_name' => $order->customer->name,
                    'total_amount' => $totalAmount,
                    'items_count' => $itemsCount,
                    'last_added_to_cart' => $lastAddedToCart,
                    'completed_order_exists' => $completedOrderExists,
                    'created_at' => $order->created_at,
                    'completed_at' => $order->completed_at, // Directly use the completed_at field
                ];
            });

        // Sort by completed_at if the order is completed, else use a default date
        $sortedOrderData = $orders->sortByDesc(function ($order) {
            return $order['completed_order_exists'] ? $order['completed_at'] : null;
        });

        return view('orders.index', ['orders' => $sortedOrderData]);
    }

    public function createCollection()
    {

        $offices = [
            ['office' => 'Dallas HQ', 'city' => 'Dallas'],
            ['office' => 'Dallas South', 'city' => 'Dallas'],
            ['office' => 'Austin Branch', 'city' => 'Austin'],
        ];

        $output = collect($offices)->mapToGroups(function ($office) {
            $employees = [
                ['name' => 'John', 'city' => 'Dallas'],
                ['name' => 'Jane', 'city' => 'Austin'],
                ['name' => 'Jake', 'city' => 'Dallas'],
                ['name' => 'Jill', 'city' => 'Dallas'],
            ];
            $employeesInCity = collect($employees)->where('city', $office['city'])
                ->pluck('name')
                ->all();

            return [$office['city'] => [$office['office'] => $employeesInCity]];
        })->map(function ($offices) {
            return $offices->collapse();
        })->toArray();

        dd($output);
    }
}
