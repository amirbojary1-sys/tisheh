<?php

namespace App\Http\Controllers;

use App\Models\JobCategory;
use App\Models\JobRequest;
use App\Models\MaterialProduct;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('home', ['jobs' => JobRequest::published()->with('category', 'user')->latest()->take(3)->get(), 'products' => MaterialProduct::published()->with('category', 'user.location', 'images')->latest()->take(4)->get(), 'categories' => JobCategory::orderBy('id')->limit(7)->get(), 'stats' => ['jobs' => JobRequest::published()->count(), 'products' => MaterialProduct::published()->count(), 'sellers' => MaterialProduct::published()->distinct()->count('user_id')]]);
    }
}
