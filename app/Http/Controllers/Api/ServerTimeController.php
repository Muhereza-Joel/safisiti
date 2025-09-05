<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class ServerTimeController extends Controller
{
    public function index(): JsonResponse
    {
        // Return current UTC time in milliseconds
        $serverTime = round(microtime(true) * 1000);

        return response()->json([
            'server_time' => $serverTime
        ]);
    }
}
