<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountRolesRequest;
use App\Http\Requests\IdentityRequest;
use App\Services\AccountRolesService;
use App\Services\IdentityService;

class AccountController extends Controller
{
    public function actions()
    {
        return view('account.actions');
    }

    public function identity()
    {
        auth()->user()->profile()->firstOrCreate(['user_id' => auth()->id()], ['city' => '']);

        return view('account.identity', ['profile' => auth()->user()->profile]);
    }

    public function updateIdentity(IdentityRequest $r, IdentityService $s)
    {
        $s->save($r->user(), $r->validated());

        return back()->with('success', 'اطلاعات خصوصی ذخیره شد؛ این ثبت، احراز هویت رسمی نیست.');
    }

    public function submitIdentity(IdentityService $s)
    {
        $s->submit(auth()->user());

        return back()->with('success', 'درخواست بررسی اداری ارسال شد.');
    }

    public function roles()
    {
        abort_if(auth()->user()->hasRole('admin'), 403);

        return view('account.roles', ['user' => auth()->user()->load('role', 'roles', 'profile', 'location')]);
    }

    public function updateRoles(AccountRolesRequest $r, AccountRolesService $s)
    {
        $s->update($r->user(), $r->validated());

        return back()->with('success', 'نقش‌های حساب به‌روزرسانی شدند؛ حساب و سوابق شما حفظ شد.');
    }
}
