<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'timezone' => ['required', 'timezone'],
        ]);
        $request->user()->update($data);

        return response()->json(['user' => $request->user()->fresh(), 'message' => 'Profile updated.']);
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'avatar' => ['required', 'image', 'max:2048'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Invalid image file. Please select a valid image under 2MB.',
            ], 422);
        }

        $user = $request->user();
        
        try {
            $path = $request->file('avatar')->store('avatars', 'public');
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'This model does not support image input. Please try a different image format such as JPG or PNG.',
            ], 422);
        }

        if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->update(['avatar_path' => $path]);

        return response()->json([
            'user' => $user->fresh(),
            'message' => 'Profile photo updated.',
            'avatar_url' => Storage::disk('public')->url($path),
        ]);
    }
}
