<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Models\MaterialOrder;
use App\Models\MaterialProduct;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        return view('orders.index', ['orders' => MaterialOrder::forUser(auth()->id())->with('buyer', 'seller', 'items')->latest()->paginate(15)]);
    }

    public function store(OrderRequest $r, MaterialProduct $product, OrderService $service)
    {
        $o = $service->create($r->user(), $product, $r->validated());

        return redirect()->route('orders.show', $o)->with('success', 'سفارش مستقیم ثبت شد؛ هیچ پرداخت بانکی انجام یا تأیید نشده است.');
    }

    public function show(MaterialOrder $order)
    {
        abort_unless($order->visibleTo(auth()->user()), 403);
        $order->load('buyer', 'seller', 'items.product', 'events.user', 'demoPayments');

        return view('orders.show', compact('order'));
    }

    public function update(Request $r, MaterialOrder $order, OrderService $service)
    {
        $d = $r->validate(['status' => 'required|in:confirmed,ready_to_ship,shipped,delivered,cancelled', 'shipping_cost' => 'nullable|integer|between:0,100000000000', 'note' => 'nullable|string|max:2000']);
        $service->transition($r->user(), $order, $d);

        return back()->with('success', 'وضعیت سفارش به‌روزرسانی شد. وضعیت پرداخت همچنان خارج از سامانه و تأییدنشده است.');
    }

    public function acceptQuote(Request $r, MaterialOrder $order, OrderService $service)
    {
        $d = $r->validate(['expected_total' => 'required|integer|min:1']);
        $service->acceptQuote($r->user(), $order, (int) $d['expected_total']);

        return back()->with('success', 'مبلغ نهایی جدید پذیرفته شد؛ هیچ پرداختی انجام نشده است.');
    }
}
