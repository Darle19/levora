<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Show the application registration form.
     */
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    /**
     * Handle a registration request for the application.
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // User fields
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            'phone' => ['nullable', 'string', 'max:255'],

            // Agency fields
            'agency_name' => ['required', 'string', 'max:255'],
            'legal_name' => ['required', 'string', 'max:255'],
            'legal_address' => ['required', 'string', 'max:255'],
            'agency_phone' => ['required', 'string', 'max:255'],
            'agency_mobile' => ['nullable', 'string', 'max:255'],
            'agency_email' => ['required', 'string', 'email', 'max:255'],
            'director' => ['required', 'string', 'max:255'],
            'inn' => ['required', 'string', 'max:255'],
        ]);

        try {
            DB::beginTransaction();

            // Create agency (inactive until admin approves)
            $agency = Agency::create([
                'name' => $validated['agency_name'],
                'legal_name' => $validated['legal_name'],
                'legal_address' => $validated['legal_address'],
                'phone' => $validated['agency_phone'],
                'mobile' => $validated['agency_mobile'] ?? null,
                'email' => $validated['agency_email'],
                'director' => $validated['director'],
                'inn' => $validated['inn'],
                'is_active' => false,
            ]);

            // Create user (inactive until admin approves)
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'agency_id' => $agency->id,
                'is_active' => false,
            ]);

            DB::commit();

            return redirect()->route('registration.pending');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Registration failed', ['error' => $e->getMessage(), 'email' => $validated['email'] ?? null]);
            return back()->withErrors(['error' => 'Registration failed. Please try again.'])->withInput();
        }
    }
}
