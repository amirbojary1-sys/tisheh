<?php

namespace App\Http\Controllers;

use App\Models\DemoPaymentAttempt;
use App\Models\MaterialOrder;
use App\Services\DemoPaymentService;
use App\Support\DemoMode;
use Illuminate\Http\Request;

class DemoPaymentController extends Controller
{
    public function start(MaterialOrder $order, DemoPaymentService $service)
    {
        $p = $service->start(auth()->user(), $order);

        return redirect()->route('demo.payments.show', $p);
    }

    public function show(DemoPaymentAttempt $attempt)
    {
        DemoMode::ensureEnabled();
        abort_unless($attempt->user_id === auth()->id(), 403);
        $attempt->load('order');

        return view('payments.demo', compact('attempt'));
    }

    public function complete(Request $r, DemoPaymentAttempt $attempt, DemoPaymentService $service)
    {
        $d = $r->validate(['outcome' => 'required|in:success,failure,cancel']);
        $service->complete($r->user(), $attempt, $d['outcome']);

        return redirect()->route('demo.payments.show', $attempt)->with('success', 'نتیجه شبیه‌سازی ذخیره شد؛ هیچ پولی جابه‌جا نشده است.');
    }

    public function admin()
    {
        return view('admin.demo-payments', ['attempts' => DemoPaymentAttempt::with('user', 'order')->latest()->paginate(20)]);
    }
}
