<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6',
            'role_id' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status' => 422,
                'message' => 'Validation Errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $registerUserData = $validator->validated();

        $role_id = $request->input('role_id', 2);
        $create_user_data = [
            'first_name' => $registerUserData['first_name'],
            'last_name' => $registerUserData['last_name'],
            'email' => $registerUserData['email'],
            'phone' => $registerUserData['phone'],
            'password' => Hash::make($registerUserData['password']),
            'role_id' => $role_id,
        ];

        $user = User::create($create_user_data);
        return response()->json([
            'success' => true,
            'status' => 201,
            'message' => 'User Created Successfully',
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string|min:8'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status' => 422,
                'message' => 'Validation Errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $loginUserData = $validator->validated();
        $user = User::where('email', $loginUserData['email'])->first();

        if (!$user || !Hash::check($loginUserData['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'status' => 401,
                'message' => 'Invalid Credentials',
            ], 401);
        }

        $token = $user->createToken($user->name . '-AuthToken')->plainTextToken;

        return response()->json([
            'success' => true,
            'access_token' => $token,
            'user' => $user
        ], 200);
    }


    public function changePassword(Request $request)
    {
        $user = auth()->user();

        // تحقق مما إذا كان المستخدم مسجل الدخول
        if (!$user) {
            return response()->json([
                'success' => false,
                'status' => 401,
                'message' => 'User is not authenticated',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6',
            'logout_other_sessions' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status' => 422,
                'message' => 'Validation Errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // التحقق من كلمة المرور الحالية
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'status' => 401,
                'message' => 'Current password is incorrect',
            ], 401);
        }

        // تحديث كلمة المرور
        $user->password = Hash::make($request->new_password);
        $user->save();

        // تحقق إذا كان المستخدم يريد إنهاء جميع الجلسات الأخرى
        if ($request->logout_other_sessions) {
            // حذف جميع التوكنات الحالية وإنشاء توكن جديد
            $user->tokens()->delete();
            $newToken = $user->createToken($user->name . '-AuthToken')->plainTextToken;

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Password changed and other sessions logged out',
                'access_token' => $newToken, // إرجاع التوكن الجديد
            ], 200);
        }

        // إذا اختار المستخدم عدم إنهاء الجلسات الأخرى
        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => 'Password changed successfully',
        ], 200);
    }



    public function logout()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'status' => 401,
                'message' => 'User is not authenticated',
            ], 401);
        }

        // حذف جميع الـ tokens الخاصة بالمستخدم المصادق
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => 'Logged Out Successfully',
        ], 200);
    }
}
