<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->requireCompanyId();

        $agents = User::where('role', 'agent')
            ->where('company_id', $companyId)
            ->withCount('tickets')
            ->withSum('tickets', 'price_paid')
            ->latest()
            ->paginate(10);

        return view('admin.agents.index', compact('agents'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'avatar'   => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
        ]);

        $avatarFilename = null;
        if ($request->hasFile('avatar')) {
            $avatarFilename = 'avatar_agent_' . time() . '.' . $request->avatar->getClientOriginalExtension();
            $request->avatar->move(public_path('images/avatars'), $avatarFilename);
        }

        User::create([
            'company_id' => auth()->user()->requireCompanyId(),
            'name'       => $data['name'],
            'email'      => $data['email'],
            'password'   => $data['password'],
            'role'       => 'agent',
            'avatar'     => $avatarFilename,
        ]);

        return redirect()->route('admin.agents.index')->with('status', 'Agent created successfully.');
    }

    public function setTelegramMenuButton()
    {
        $token = config('services.telegram.bot_token');
        if (!$token) {
            return back()->with('error', 'TELEGRAM_BOT_TOKEN is not set in .env');
        }

        $loginUrl = url('/login');
        $menuButton = [
            'type' => 'web_app',
            'text' => 'Open App',
            'web_app' => ['url' => $loginUrl],
        ];

        $url = "https://api.telegram.org/bot{$token}/setChatMenuButton";
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode(['menu_button' => $menuButton]),
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        $data = json_decode($response ?: '{}', true);

        if ($data['ok'] ?? false) {
            return back()->with('status', "Bot menu button set. Agents can tap 'Open App' in the bot to open the login page.");
        }

        return back()->with('error', 'Failed to set menu button: ' . ($data['description'] ?? 'Unknown error'));
    }

    public function suspend(User $user)
    {
        if ($user->role !== 'agent') {
            return back()->with('error', 'Only agent accounts can be suspended.');
        }

        if ((int) $user->company_id !== auth()->user()->requireCompanyId()) {
            abort(404);
        }

        $user->update(['is_suspended' => ! $user->is_suspended]);

        $message = $user->is_suspended
            ? "Agent \"{$user->name}\" has been suspended."
            : "Agent \"{$user->name}\" has been reactivated.";

        return back()->with('status', $message);
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->role !== 'agent') {
            return back()->with('error', 'Only agent accounts can be deleted.');
        }

        if ((int) $user->company_id !== auth()->user()->requireCompanyId()) {
            abort(404);
        }

        if ($user->tickets()->exists()) {
            return back()->with(
                'error',
                'Cannot delete an agent who has recorded ticket sales. Each ticket must keep the agent who sold it. Suspend the account instead.'
            );
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.agents.index')
            ->with('status', "Agent \"{$name}\" has been deleted.");
    }
}
