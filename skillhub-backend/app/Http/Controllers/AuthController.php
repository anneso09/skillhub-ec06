<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{



  public function profile(Request $request)
{
    return response()->json([
        'id' => $request->auth_user_id,
        'email' => $request->auth_user_email,
        'role' => $request->auth_user_role
    ]);
}

  public function logout()
{
    return response()->json([
        'message' => 'Déconnexion côté client'
    ]);
}
}