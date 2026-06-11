<?php
namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController
{
    public function login(Request $request)
    {
        // validasi input dari user
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        try {
            // pake JWTAuth biar bisa langsung cek email & password ke database sekaligus bikin token jwt
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['message' => 'Invalid email or password'], 401);
            }
        } catch (JWTException $e) {
            // kalau gagal bikin token
            return response()->json(['message' => 'Could not create token'], 500);
        }

        // ambil user berdasarkan token yang berhasil dibuat
        // ini bukan login biasa pake session web.
        $user = auth('api')->user();

        // ngirim token dan user data ke react biar bisa dipake buat request berikutnya tanpa harus login lagi
        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_admin' => $user->is_admin,
                'is_active' => $user->is_active,
            ],
            'expires_in' => auth('api')->factory()->getTTL() * 60, // getTLL : menghasilkan menit, jadinya dikali 60 supaya dikirim ke react sesuai detik 
        ]);
    }


    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:15',
            'is_admin' => 'boolean',
        ]);

        //hash password
        $validated['password'] = Hash::make($validated['password']);

        //create user
        $user = User::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
            ]
        ], 201);
    }

    public function user(Request $request)
    {
        $user = auth('api')->user();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_admin' => $user->is_admin,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at,
            ]
        ]);
    }

    //refresh token
    public function refresh()
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());

            return response()->json([
                'status' => 'success',
                'token' => $token,
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ]);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Could not refresh token'], 500);
        }
    }

    //logout user (hapus token)
    public function logout()
    {
        auth('api')->logout();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout successful'
        ]);
    }

    //ganti password
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = auth('api')->user();

        //check current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Current password is incorrect'
            ], 401);
        }

        //update password
        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Password changed successfully'
        ]);
    }
}
