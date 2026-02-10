<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
  public function index(Request $request)
  {
    $users = User::all();

    return response()->json([
      $users
    ], 200);
  }

  public function register(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'name' => 'required|string|max:255',
      'email' => 'required|email|unique:users',
      'password' => [
        'required',
        'confirmed',
        Password::min(8)
          ->mixedCase()
          ->numbers()
          ->symbols()
          ->uncompromised()
      ]
    ]);

    if ($validator->fails()) {
      return response()->json([
        'errors' => $validator->errors()
      ], 422);
    }

    $user = User::create([
      'name' => $request->name,
      'email' => $request->email,
      'password' => Hash::make($request->password)
    ]);

    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
      'user' => $user,
      'token' => $token
    ], 201);
  }

  public function login(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'email' => 'required|email',
      'password' => 'required|string'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'errors' => $validator->errors()
      ], 422);
    }

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
      return response()->json([
        'message' => 'Invalid credentials'
      ], 401);
    }

    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
      'user' => $user,
      'token' => $token
    ], 200);
  }

  public function logout(Request $request)
  {
    $request->user()->currentAccessToken()->delete();

    return response()->json([
      'message' => 'Logged out successfully'
    ], 200);
  }

  public function delete(Request $request)
  {
    $user = $request->user();

    $user->tokens()->delete();

    $user->delete();

    return response()->json([
      'message' => 'User deleted successfully',
    ], 200);
  }

  public function get(Request $request)
  {
    $user = $request->user();

    return response()->json($user, 200);
  }
}
