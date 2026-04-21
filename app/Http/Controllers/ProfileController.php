<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
        ], [
            'avatar.required' => 'Please select a photo to upload.',
            'avatar.image'    => 'The file must be an image.',
            'avatar.max'      => 'Photo must be smaller than 2 MB.',
        ]);

        $user = auth()->user();

        // Remove old avatar file
        if ($user->avatar && file_exists(public_path('images/avatars/' . $user->avatar))) {
            unlink(public_path('images/avatars/' . $user->avatar));
        }

        $filename = 'avatar_' . $user->id . '_' . time() . '.' . $request->avatar->getClientOriginalExtension();
        $request->avatar->move(public_path('images/avatars'), $filename);

        $user->update(['avatar' => $filename]);

        return back()->with('avatar_status', 'Photo updated successfully.');
    }

    public function removeAvatar(Request $request)
    {
        $user = auth()->user();

        if ($user->avatar && file_exists(public_path('images/avatars/' . $user->avatar))) {
            unlink(public_path('images/avatars/' . $user->avatar));
        }

        $user->update(['avatar' => null]);

        return back()->with('avatar_status', 'Photo removed.');
    }
}
