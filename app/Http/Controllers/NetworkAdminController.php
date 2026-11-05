<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use App\Services\DirectoryService;
use App\Services\IdentityService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NetworkAdminController extends Controller
{
    public function identities(Request $r)
    {
        $d = $r->validate(['status' => ['nullable', Rule::in(array_keys(config('network.identity_statuses')))]]);
        $q = User::with('profile', 'role')->whereHas('profile', fn ($p) => $p->where('identity_status', $d['status'] ?? 'pending'));

        return view('admin.identities', ['users' => $q->latest()->paginate(20)]);
    }

    public function identity(User $user)
    {
        $user->load('profile', 'role');
        abort_unless($user->profile, 404);
        app(AuditService::class)->record('identity.viewed', $user, ['revision' => $user->profile?->identity_revision]);

        return response()->view('admin.identity', ['user' => $user])->header('Cache-Control', 'no-store, private');
    }

    public function reviewIdentity(Request $r, User $user, IdentityService $s)
    {
        $d = $r->validate(['decision' => 'required|in:reviewed,rejected', 'revision' => 'required|integer|min:0', 'note' => 'required|string|min:10|max:1000']);
        $d['revision'] = (int) $d['revision'];
        $s->review($user, $d);

        return back()->with('success', 'نتیجه بررسی اداری ثبت شد؛ تأیید رسمی مالکیت نیست.');
    }

    public function professionals(Request $r)
    {
        $d = $r->validate(['user' => 'nullable|integer|exists:users,id', 'status' => ['nullable', Rule::in(array_keys(config('network.directory_statuses')))]]);
        $q = User::with('profile', 'role', 'skills', 'portfolioEntries')->whereHas('profile');
        if (! empty($d['user'])) {
            $q->whereKey($d['user']);
        } else {
            $q->whereHas('profile', fn ($p) => $p->where('directory_status', $d['status'] ?? 'pending'));
        }

        return view('admin.professionals', ['users' => $q->latest()->paginate(15)->withQueryString()]);
    }

    public function reviewProfessional(Request $r, User $user, DirectoryService $s)
    {
        $d = $r->validate(['decision' => 'required|in:published,rejected', 'revision' => 'required|integer|min:0', 'note' => 'required|string|min:5|max:1000']);
        $d['revision'] = (int) $d['revision'];
        $s->review($user, $d);

        return back()->with('success', 'بررسی انتشار پروفایل ثبت شد.');
    }
}
