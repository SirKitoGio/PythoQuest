<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use App\Models\Sighting;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function home(Request $request)
    {
        return view('home', [
            'suggestions' => Plant::whereNotNull('habitat')->inRandomOrder()->take(3)->get(),
            'categories' => collect(Plant::CATEGORIES)->mapWithKeys(
                fn ($category) => [$category => Plant::where('category', $category)->count()]
            ),
            'recent' => $request->user()?->sightings()->with('plant')->latest()->take(3)->get() ?? collect(),
        ]);
    }

    public function about()
    {
        return view('about');
    }

    public function contact()
    {
        return view('contact');
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();

        return view('dashboard', [
            'stats' => [
                'Plant profiles' => Plant::count(),
                'My sightings' => $user->sightings()->count(),
                'Pending review' => $user->sightings()->where('status', 'pending')->count(),
                'Trusted records' => Sighting::where('status', 'trusted')->count(),
            ],
            'reviewQueueCount' => $user->isReviewer()
                ? Sighting::where('status', 'pending')->where('user_id', '!=', $user->id)->count()
                : null,
            'recent' => $user->sightings()->with('plant')->latest()->take(5)->get(),
            'scans' => $user->identifications()->with('topPlant')->latest()->take(4)->get(),
            'notifications' => $user->unreadNotifications()->take(5)->get(),
        ]);
    }
}
