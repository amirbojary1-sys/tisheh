<?php

namespace App\Http\Controllers;

use App\Http\Requests\ModerationRequest;
use App\Models\AuditLog;
use App\Models\ContentPage;
use App\Models\JobCategory;
use App\Models\JobRequest;
use App\Models\MaterialCategory;
use App\Models\MaterialOrder;
use App\Models\MaterialProduct;
use App\Models\Report;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\AdminService;
use App\Services\AuditService;
use App\Support\Format;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function index()
    {
        $daily = collect(range(6, 0))->map(function ($days) {
            $date = now()->subDays($days);

            return ['label' => Format::date($date), 'users' => User::whereDate('created_at', $date->toDateString())->count(), 'jobs' => JobRequest::whereDate('created_at', $date->toDateString())->count(), 'products' => MaterialProduct::whereDate('created_at', $date->toDateString())->count()];
        });

        return view('admin.index', ['counts' => ['users' => User::count(), 'jobs' => JobRequest::count(), 'products' => MaterialProduct::count(), 'orders' => MaterialOrder::count(), 'pending' => JobRequest::where('status', 'pending')->count() + MaterialProduct::where('status', 'pending')->count(), 'reports' => Report::whereIn('status', ['open', 'reviewing'])->count()], 'daily' => $daily, 'recent' => AuditLog::with('user')->latest('id')->take(7)->get(), 'latestUsers' => User::with('role')->latest()->take(5)->get()]);
    }

    public function users(Request $r)
    {
        $d = $r->validate(['q' => 'nullable|string|max:100', 'role' => 'nullable|exists:roles,name', 'status' => 'nullable|in:active,suspended']);
        $q = User::with('role', 'roles', 'profile')->when($d['q'] ?? null, fn ($q, $v) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$v.'%')->orWhere('last_name', 'like', '%'.$v.'%')->orWhere('mobile', 'like', '%'.$v.'%')))->when($d['role'] ?? null, fn ($q, $v) => $q->where(fn ($q) => $q->whereHas('role', fn ($q) => $q->where('name', $v))->orWhereHas('roles', fn ($q) => $q->where('name', $v))))->when($d['status'] ?? null, fn ($q, $v) => $q->where('status', $v));

        return view('admin.users', ['users' => $q->latest()->paginate(20)->withQueryString(), 'roles' => Role::all()]);
    }

    public function showUser(User $user)
    {
        $user->load('role', 'roles', 'profile', 'location');

        return view('admin.user', ['user' => $user, 'logs' => AuditLog::with('user')->where(fn ($q) => $q->where('user_id', $user->id)->orWhere(fn ($q) => $q->where('subject_type', User::class)->where('subject_id', $user->id)))->latest('id')->take(20)->get(), 'jobCount' => JobRequest::where('user_id', $user->id)->count(), 'productCount' => MaterialProduct::where('user_id', $user->id)->count()]);
    }

    public function user(Request $r, User $user, AdminService $s)
    {
        abort_if($user->id === auth()->id() || $user->hasRole('admin'), 403);
        $d = $r->validate(['status' => 'required|in:active,suspended', 'name' => 'sometimes|required|string|max:60', 'last_name' => 'sometimes|required|string|max:80', 'city' => 'sometimes|required|string|max:80', 'reason' => 'nullable|string|max:1000', 'role_id' => 'prohibited', 'mobile_verified_at' => 'prohibited', 'password' => 'prohibited']);
        $s->user($user, $d);

        return back()->with('success', 'تغییر کاربر و دلیل آن ثبت شد.');
    }

    public function listings(Request $r, string $type)
    {
        $this->type($type);
        $d = $r->validate(['q' => 'nullable|string|max:100', 'status' => 'nullable|in:draft,pending,in_review,published,rejected,inactive,completed,cancelled']);
        $q = ($type === 'job' ? JobRequest::query() : MaterialProduct::query())->with('user', 'category')->when($d['q'] ?? null, fn ($q, $v) => $q->where($type === 'job' ? 'title' : 'name', 'like', '%'.$v.'%'))->when($d['status'] ?? null, fn ($q, $v) => $q->where('status', $v));

        return view('admin.listings', ['records' => $q->latest()->paginate(20)->withQueryString(), 'type' => $type]);
    }

    public function moderate(ModerationRequest $r, string $type, int $id, AdminService $s)
    {
        $this->type($type);
        $s->moderate($type, $id, $r->validated());

        return back()->with('success', 'نتیجه بررسی و اعلان کاربر ثبت شد.');
    }

    public function listingStatus(Request $r, string $type, int $id, AdminService $s)
    {
        $this->type($type);
        $d = $r->validate(['status' => 'required|in:pending,rejected', 'moderation_note' => 'required|string|min:5|max:1000']);
        $s->moderate($type, $id, $d, false);

        return back()->with('success', 'آگهی از انتشار خارج و رویداد ثبت شد.');
    }

    public function categories()
    {
        return view('admin.categories', ['jobs' => JobCategory::all(), 'products' => MaterialCategory::all()]);
    }

    public function category(Request $r, string $type, AdminService $s, ?int $id = null)
    {
        $this->type($type);
        $table = $type === 'job' ? 'job_categories' : 'material_categories';
        $d = $r->validate(['name' => ['required', 'string', 'max:100', Rule::unique($table)->ignore($id)], 'slug' => ['required', 'regex:/^[a-z0-9-]+$/', 'max:100', Rule::unique($table)->ignore($id)]]);
        $s->category($type, $id, $d);

        return back()->with('success', 'دسته‌بندی ذخیره شد.');
    }

    public function deleteCategory(string $type, int $id, AdminService $s)
    {
        $this->type($type);
        $s->category($type, $id, [], true);

        return back()->with('success', 'دسته‌بندی بدون آگهی حذف شد.');
    }

    public function reports(Request $r)
    {
        $d = $r->validate(['status' => 'nullable|in:open,reviewing,resolved,dismissed']);

        return view('admin.reports', ['reports' => Report::with('user', 'assignee')->when($d['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->latest()->paginate(20)->withQueryString()]);
    }

    public function report(Report $report)
    {
        $report->load('user', 'assignee');

        return view('admin.report', compact('report'));
    }

    public function reviewReport(Request $r, Report $report, AdminService $s)
    {
        $d = $r->validate(['status' => 'required|in:reviewing,resolved,dismissed', 'admin_response' => 'required|string|min:5|max:4000']);
        $s->report($report, $d);

        return back()->with('success', 'پاسخ برای گزارش‌دهنده ثبت و اعلان ارسال شد.');
    }

    public function settings()
    {
        return view('admin.settings', ['settings' => Setting::all()->pluck('value', 'key')]);
    }

    public function saveSettings(Request $r, AdminService $s)
    {
        $d = $r->validate(['support_phone' => ['nullable', 'regex:/^0[0-9]{10}$/'], 'support_email' => 'nullable|email|max:150', 'announcement' => 'nullable|string|max:500', 'publication_fee_rial' => 'required|integer|between:0,100000000000', 'subscription_fee_rial' => 'required|integer|between:0,100000000000', 'payments_enabled' => 'prohibited', 'wallet_enabled' => 'prohibited']);
        $s->settings($d);

        return back()->with('success', 'تنظیمات ذخیره شد. مبالغ صرفاً پیکربندی‌اند؛ دریافت وجه فعال نشده است.');
    }

    public function pages()
    {
        return view('admin.pages', ['pages' => ContentPage::latest()->get()]);
    }

    public function savePage(Request $r, ?ContentPage $page = null)
    {
        $d = $r->validate(['title' => 'required|string|max:150', 'slug' => ['required', 'regex:/^[a-z0-9-]+$/', 'max:100', Rule::unique('content_pages')->ignore($page?->id)], 'body' => 'required|string|min:20|max:20000', 'published' => 'required|boolean']);
        DB::transaction(function () use ($page, $d) {
            $p = $page ?? new ContentPage;
            $p->fill($d)->save();
            app(AuditService::class)->record('content.saved', $p, ['published' => $p->published]);
        });

        return back()->with('success', 'محتوا ذخیره شد؛ HTML به‌صورت متن امن نمایش داده می‌شود.');
    }

    public function audit(Request $r)
    {
        $d = $r->validate(['action' => 'nullable|string|max:100', 'user_id' => 'nullable|integer', 'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from']);
        $q = AuditLog::with('user')->when($d['action'] ?? null, fn ($q, $v) => $q->where('action', 'like', '%'.$v.'%'))->when($d['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))->when($d['from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))->when($d['to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v));
        if ($r->boolean('export')) {
            return $this->csv($q);
        }

        return view('admin.audit', ['logs' => $q->latest('id')->paginate(30)->withQueryString()]);
    }

    public function orders(Request $r)
    {
        $d = $r->validate(['status' => 'nullable|in:awaiting_seller,confirmed,ready_to_ship,shipped,delivered,cancelled', 'q' => 'nullable|string|max:100']);

        return view('admin.orders', ['orders' => MaterialOrder::with('buyer', 'seller')->when($d['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->when($d['q'] ?? null, fn ($q, $v) => $q->where('reference', 'like', '%'.$v.'%'))->latest()->paginate(20)->withQueryString()]);
    }

    public function roles()
    {
        $roles = Role::with('permissions')->get();
        foreach ($roles as $role) {
            $role->users_count = User::where(fn ($q) => $q->where('role_id', $role->id)->orWhereHas('roles', fn ($r) => $r->where('roles.id', $role->id)))->count();
        }

        return view('admin.roles', ['roles' => $roles]);
    }

    public function finance()
    {
        return view('admin.finance', ['count' => MaterialOrder::count(), 'volume' => MaterialOrder::whereNot('status', 'cancelled')->sum('subtotal')]);
    }

    private function type(string $type): void
    {
        abort_unless(in_array($type, ['job', 'product']), 404);
    }

    private function csv($q)
    {
        app(AuditService::class)->record('audit.exported', auth()->user());

        return response()->streamDownload(function () use ($q) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['ID', 'UTC timestamp', 'Actor', 'Action', 'Subject', 'Subject ID'], ',', '"', '');
            foreach ($q->latest('id')->limit(5000)->cursor() as $log) {
                $row = [$log->id, $log->created_at->toISOString(), $log->user_id, $log->action, class_basename($log->subject_type), $log->subject_id];
                $row = array_map(fn ($v) => preg_match('/^[=+@\-\t\r]/', (string) $v) ? "'".$v : $v, $row);
                fputcsv($f, $row, ',', '"', '');
            }fclose($f);
        }, 'tisheh-audit.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function system()
    {
        return view('admin.system', [
            'expired' => MaterialOrder::where('status', 'awaiting_seller')->where('reservation_expires_at', '<=', now())->count(),
            'lastRun' => Cache::get('tisheh.reservations_last_run'),
            'checks' => [
                'حالت تولید' => app()->environment('production'),
                'نمایش خطا خاموش' => ! config('app.debug'),
                'آدرس HTTPS' => str_starts_with(config('app.url'), 'https://'),
                'کوکی امن' => config('session.secure') === true,
                'نشست رمزنگاری‌شده' => config('session.encrypt') === true,
                'دمو خاموش' => ! config('integrations.demo_enabled'),
                'پرداخت و کیف پول واقعی خاموش' => ! config('tisheh.payments_enabled') && ! config('tisheh.wallet_enabled'),
            ],
        ]);
    }
}
