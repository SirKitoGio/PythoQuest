<?php

namespace App\Http\Controllers;

use App\Actions\EscalateIdentification;
use App\Models\Identification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class IdentificationEscalationController extends Controller
{
    public function store(Identification $identification, EscalateIdentification $escalate): RedirectResponse
    {
        Gate::authorize('escalate', $identification);

        $escalate->handle($identification);

        return back()->with('status', 'Sent to an expert reviewer. You will be notified when it is identified.');
    }
}
