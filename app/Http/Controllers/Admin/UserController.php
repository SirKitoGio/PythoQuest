<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();

        return view('admin.users.index', [
            'users' => User::query()
                ->withCount(['sightings', 'identifications'])
                ->when($search, fn ($query) => $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'roles' => UserRole::cases(),
            'search' => $search,
        ]);
    }

    public function update(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        $previous = $user->role;
        $user->forceFill(['role' => $request->validated('role')])->save();

        AuditLog::record('user.role_changed', $user, ['role' => [$previous->value, $user->role->value]]);

        return back()->with('status', "{$user->name} is now: {$user->role->label()}.");
    }
}
