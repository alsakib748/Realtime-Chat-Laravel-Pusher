<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\ChatRoom;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();
        $this->ensureUserInGeneralRoom($user);

        return redirect()->intended(route('chat.index'));
    }

    public function ensureUserInGeneralRoom($user)
    {
        $generalRoom = ChatRoom::firstOrCreate([
            'type' => 'general'
        ], [
            'name' => 'General Chat',
            'created_by' => 1,
            'last_message_at' => now()
        ]);

        if (!$generalRoom->users()->where('user_id', $user->id)->exists()) {
            $generalRoom->users()->attach($user->id, [
                'joined_at' => now(),
                'is_active' => true,
                'last_read_at' => now()
            ]);
        }

    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            $user->setOffline();
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
