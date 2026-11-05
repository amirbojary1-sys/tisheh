<?php

namespace App\Http\Controllers;

use App\Models\JobRequest;
use App\Models\MaterialProduct;
use App\Models\Report;
use App\Services\AuditService;
use App\Services\NoticeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index', ['reports' => Report::where('user_id', auth()->id())->latest()->paginate(15)]);
    }

    public function create(Request $r)
    {
        $d = $r->validate(['type' => 'nullable|in:job,product', 'id' => 'nullable|integer|required_with:type']);

        return view('reports.create', ['target' => $d]);
    }

    public function store(Request $r)
    {
        $d = $r->validate(['subject_type' => 'required|in:job,product,support', 'subject_id' => 'nullable|integer|required_unless:subject_type,support', 'reason' => 'required|string|max:120', 'description' => 'required|string|min:15|max:4000']);
        if ($d['subject_type'] !== 'support') {
            $m = ($d['subject_type'] === 'job' ? JobRequest::query() : MaterialProduct::query())->findOrFail($d['subject_id']);
            Gate::authorize('view', $m);
        } else {
            $d['subject_id'] = null;
        }DB::transaction(function () use ($d) {
            $report = Report::create([...$d, 'user_id' => auth()->id()]);
            app(AuditService::class)->record('report.created', $report);
            app(NoticeService::class)->admins('گزارش یا درخواست پشتیبانی جدید', $report->reason, '/admin/reports/'.$report->id);
        });

        return redirect()->route('reports.index')->with('success', 'گزارش شما با امکان پیگیری ثبت شد.');
    }
}
