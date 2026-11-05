<?php

namespace App\Http\Controllers;

use App\Http\Requests\PortfolioRequest;
use App\Http\Requests\ProfessionalRequest;
use App\Models\JobCategory;
use App\Models\PortfolioEntry;
use App\Models\User;
use App\Services\DirectoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfessionalController extends Controller
{
    public function index(Request $r, DirectoryService $s)
    {
        $d = $r->validate(['q' => 'nullable|string|max:100', 'city' => 'nullable|string|max:80', 'category' => 'nullable|integer|exists:job_categories,id', 'activity' => ['nullable', Rule::in(array_keys(config('network.activities')))], 'availability' => ['nullable', Rule::in(array_keys(config('network.availability')))]]);
        $q = $s->publicQuery()->with('profile', 'skills', 'role', 'roles');
        if (! empty($d['q'])) {
            $v = $d['q'];
            $q->where(fn ($q) => $q->where('name', 'like', '%'.$v.'%')->orWhere('last_name', 'like', '%'.$v.'%')->orWhereHas('profile', fn ($p) => $p->where('headline', 'like', '%'.$v.'%')->orWhere('specialty', 'like', '%'.$v.'%')));
        }
        foreach (['city' => 'city', 'activity' => 'activity_type', 'availability' => 'availability'] as $key => $column) {
            if (! empty($d[$key])) {
                $q->whereHas('profile', fn ($p) => $p->where($column, $d[$key]));
            }
        }
        if (! empty($d['category'])) {
            $q->whereHas('skills', fn ($c) => $c->where('job_categories.id', $d['category']));
        }

        return view('professionals.index', ['professionals' => $q->latest('id')->paginate(12)->withQueryString(), 'categories' => JobCategory::all()]);
    }

    public function show(User $user, DirectoryService $s)
    {
        abort_unless($s->visible($user), 404);
        $user->load('profile', 'skills', 'portfolioEntries', 'role', 'roles');

        return response()->view('professionals.show', ['professional' => $user])->header('Cache-Control', 'private, no-store');
    }

    public function edit()
    {
        abort_unless(auth()->user()->hasRole('worker'), 403);

        auth()->user()->profile()->firstOrCreate(['user_id' => auth()->id()], ['city' => '']);

        return view('account.professional', ['user' => auth()->user()->load('profile', 'skills', 'portfolioEntries'), 'categories' => JobCategory::all()]);
    }

    public function update(ProfessionalRequest $r, DirectoryService $s)
    {
        $s->save($r->user(), $r->validated());

        return back()->with('success', 'پروفایل حرفه‌ای ذخیره شد؛ انتشار عمومی تنها پس از بررسی مدیر است.');
    }

    public function addPortfolio(PortfolioRequest $r, DirectoryService $s)
    {
        $s->addPortfolio($r->user(), $r->validated(), $r->file('image'));

        return back()->with('success', 'نمونه‌کار ذخیره شد؛ برای انتشار مجدد بررسی می‌شود.');
    }

    public function updatePortfolio(PortfolioRequest $r, PortfolioEntry $entry, DirectoryService $s)
    {
        $s->updatePortfolio($r->user(), $entry, $r->validated());

        return back()->with('success', 'نمونه‌کار ویرایش شد؛ انتشار دوباره بررسی می‌شود.');
    }

    public function deletePortfolio(PortfolioEntry $entry, DirectoryService $s)
    {
        $s->deletePortfolio(auth()->user(), $entry);

        return back()->with('success', 'نمونه‌کار حذف شد.');
    }

    public function image(PortfolioEntry $entry, DirectoryService $s)
    {
        $entry->load('user');
        $own = auth()->check() && (auth()->id() === $entry->user_id || auth()->user()->hasPermission('admin.access'));
        abort_unless($own || $s->visible($entry->user), 404);
        abort_unless($entry->image_path && Storage::disk('local')->exists($entry->image_path), 404);

        return response()->file(Storage::disk('local')->path($entry->image_path), ['Content-Type' => 'image/webp', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
