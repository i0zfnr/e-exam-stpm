<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $profile = match ($user->role) {
            'pelajar' => DB::table('students')->where('user_id', $user->id)->first(),
            'guru' => DB::table('teachers')->where('user_id', $user->id)->first(),
            'admin' => DB::table('admins')->where('user_id', $user->id)->first(),
            default => null,
        };
        $name = $profile->full_name ?? $user->name ?? $user->email;

        return view('dashboard', compact('user', 'profile', 'name'));
    }
}
