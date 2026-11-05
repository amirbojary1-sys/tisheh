<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplicationRequest;
use App\Http\Requests\JobRequestForm;
use App\Models\JobApplication;
use App\Models\JobCategory;
use App\Models\JobRequest;
use App\Services\AuditService;
use App\Services\ListingService;
use App\Services\NoticeService;
use App\Support\Format;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class JobController extends Controller
{
    public function index(Request $r)
    {
        $f = $r->validate(['q' => 'nullable|string|max:100', 'city' => 'nullable|string|max:80', 'category' => 'nullable|integer', 'sort' => 'nullable|in:newest,wage', 'min_wage' => 'nullable|integer|min:0|max:100000000000', 'max_wage' => ['nullable', 'integer', 'min:0', 'max:100000000000', ...($r->filled('min_wage') ? ['gte:min_wage'] : [])], 'start_from' => 'nullable|date_format:Y-m-d', 'start_to' => ['nullable', 'date_format:Y-m-d', ...($r->filled('start_from') ? ['after_or_equal:start_from'] : [])]]);
        $q = JobRequest::published()->with('category', 'user')->when($f['q'] ?? null, fn ($q, $v) => $q->where('title', 'like', '%'.$v.'%'))->when($f['city'] ?? null, fn ($q, $v) => $q->where('city', 'like', '%'.$v.'%'))->when($f['category'] ?? null, fn ($q, $v) => $q->where('job_category_id', $v))->when($f['min_wage'] ?? null, fn ($q, $v) => $q->where('wage', '>=', $v));
        $q->when($f['max_wage'] ?? null, fn ($q, $v) => $q->where('wage', '<=', $v))->when($f['start_from'] ?? null, fn ($q, $v) => $q->whereDate('start_date', '>=', $v))->when($f['start_to'] ?? null, fn ($q, $v) => $q->whereDate('start_date', '<=', $v));
        ($f['sort'] ?? '') === 'wage' ? $q->orderByDesc('wage') : $q->latest();

        return view('jobs.index', ['jobs' => $q->paginate(12)->withQueryString(), 'categories' => JobCategory::all()]);
    }

    public function create()
    {
        return view('jobs.form', ['job' => new JobRequest, 'categories' => JobCategory::all()]);
    }

    public function store(JobRequestForm $r, ListingService $s)
    {
        $j = $s->saveJob($r->validated());

        return redirect()->route('jobs.show', $j)->with('success', 'درخواست ذخیره شد. انتشار عمومی به تأیید مدیر نیاز دارد.');
    }

    public function edit(JobRequest $job)
    {
        Gate::authorize('update', $job);

        return view('jobs.form', ['job' => $job, 'categories' => JobCategory::all()]);
    }

    public function update(JobRequestForm $r, JobRequest $job, ListingService $s)
    {
        Gate::authorize('update', $job);
        $s->saveJob($r->validated(), $job);

        return redirect()->route('jobs.show', $job)->with('success', 'تغییرات ذخیره شد؛ برای انتشار مجدد بررسی خواهد شد.');
    }

    public function show(JobRequest $job)
    {
        Gate::authorize('view', $job);
        $job->load('category', 'user');
        $applications = auth()->id() === $job->user_id ? $job->applications()->with('user.profile')->get() : collect();
        $canDelete = auth()->id() === $job->user_id
            && $applications->isEmpty()
            && ! DB::table('conversations')->where('context_type', 'job')->where('context_id', $job->id)->exists()
            && ! DB::table('reports')->where('subject_type', 'job')->where('subject_id', $job->id)->exists();

        return view('jobs.show', compact('job', 'applications', 'canDelete'));
    }

    public function apply(ApplicationRequest $r, JobRequest $job, ListingService $s)
    {
        Gate::authorize('apply', $job);
        $data = $r->validated();
        $s->apply($job, $data['message']);

        return back()->with('success', 'اعلام آمادگی شما ثبت شد؛ وضعیت در حساب کاربری قابل پیگیری است.');
    }

    public function application(Request $r, JobApplication $application)
    {
        Gate::authorize('update', $application->job);
        $data = $r->validate(['status' => 'required|in:accepted,rejected']);
        DB::transaction(function () use ($application, $data) {
            $application->update($data);
            app(AuditService::class)->record('application.reviewed', $application, $data);
            app(NoticeService::class)->send($application->user, 'نتیجه اعلام آمادگی', Format::status($application->status), '/dashboard');
        });

        return back()->with('success', 'وضعیت متقاضی به‌روز شد.');
    }

    public function close(Request $r, JobRequest $job)
    {
        Gate::authorize('update', $job);
        $d = $r->validate(['status' => 'required|in:completed,cancelled']);
        DB::transaction(function () use ($job, $d) {
            $job->update($d);
            app(AuditService::class)->record('job.closed', $job, $d);
        });

        return back()->with('success', 'وضعیت درخواست تغییر کرد.');
    }

    public function destroy(JobRequest $job)
    {
        Gate::authorize('delete', $job);
        DB::transaction(function () use ($job) {
            $locked = JobRequest::whereKey($job->id)->lockForUpdate()->firstOrFail();
            $hasHistory = $locked->applications()->exists()
                || DB::table('conversations')->where('context_type', 'job')->where('context_id', $locked->id)->exists()
                || DB::table('reports')->where('subject_type', 'job')->where('subject_id', $locked->id)->exists();

            if ($hasHistory) {
                throw ValidationException::withMessages([
                    'job' => 'این آگهی سابقه درخواست، گفت‌وگو یا گزارش دارد و قابل حذف نیست؛ آن را ببندید یا لغو کنید.',
                ]);
            }

            app(AuditService::class)->record('job.deleted', $locked, ['title' => $locked->title]);
            $locked->delete();
        });

        return redirect()->route('jobs.index')->with('success', 'آگهی حذف شد.');
    }
}
