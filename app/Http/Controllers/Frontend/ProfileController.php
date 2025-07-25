<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    // HAPUS __construct() dengan middleware

    public function index()
    {
        // Dummy user data for testing
        $user = (object) ['name' => 'Test User', 'email' => 'test@example.com'];
        $recentOrders = collect(); // Empty for now
        $totalSpent = 0;
        $totalOrders = 0;

        return view('frontend.profile.index', compact('user', 'recentOrders', 'totalSpent', 'totalOrders'));
    }

    public function edit()
    {
        $user = (object) ['name' => 'Test User', 'email' => 'test@example.com'];
        return view('frontend.profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        return redirect()->route('profile.index')->with('success', 'Profile updated!');
    }
}