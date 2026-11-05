<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Models\JobApplication;
use App\Models\JobRequest;
use App\Models\MaterialProduct;
use App\Services\AuditService;
use App\Services\DirectoryService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $u = auth()->user()->load('profile', 'role', 'roles', 'location');

        return view('dashboard', ['user' => $u, 'jobs' => JobRequest::with('category')->where('user_id', $u->id)->latest()->get(), 'applications' => JobApplication::with('job')->where('user_id', $u->id)->latest()->get(), 'products' => MaterialProduct::with('category', 'images')->where('user_id', $u->id)->latest()->get()]);
    }

    public function update(ProfileRequest $r)
    {
        $data = $r->validated();
        DB::transaction(function () use ($r, $data) {
            $r->user()->update(collect($data)->only('name', 'last_name')->all());
            $r->user()->profile()->updateOrCreate(['user_id' => $r->user()->id], collect($data)->only('city', 'bio', 'specialty', 'business_name')->all());
            if ($r->user()->hasRole('seller')) {
                $location = ['city' => $data['city']];
                if (isset($data['business_name'])) {
                    $location['name'] = $data['business_name'];
                }if (isset($data['address'])) {
                    $location['address'] = $data['address'];
                }$r->user()->location()->update($location);
            }
            app(DirectoryService::class)->invalidate($r->user());
            app(AuditService::class)->record('profile.updated', $r->user());
        });

        return back()->with('success', 'اطلاعات حساب ذخیره شد.');
    }
}
