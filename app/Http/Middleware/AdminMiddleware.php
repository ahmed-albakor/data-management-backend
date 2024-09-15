<?php

namespace App\Http\Middleware;

use App\Models\Data;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $authenticated = false;
        $authorizationHeader = $request->header('Authorization');

        if ($authorizationHeader !== null && str_contains($authorizationHeader, "Bearer ")) {
            $parts = explode("|", $authorizationHeader);
            $access_token = $parts[1];
            $hashedToken = hash('sha256', $access_token);

            $userId = DB::table('personal_access_tokens')
                ->where('token', $hashedToken)
                ->value('tokenable_id');

            $authenticated = $userId != null;
        }

        if (!$authenticated) {
            return response()->json([
                'success' => false,
                'status' => 401,
                'message' => 'Unauthenticated',
            ], 401);
        }



        return $next($request);
    }
}
