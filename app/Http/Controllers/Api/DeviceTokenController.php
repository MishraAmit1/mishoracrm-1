<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string',
            'device_id'    => 'nullable|string',
            'device_name'  => 'nullable|string',
            'platform'     => 'required|string',
            'app_version'  => 'nullable|string',
        ]);

        DeviceToken::updateOrCreate(
            [
                'device_token' => $request->device_token,
            ],
            [
                'tenant_id'   => Auth::user()->tenant_id ?? null,
                'user_id'     => Auth::id(),
                'device_id'   => $request->device_id,
                'device_name' => $request->device_name,
                'platform'    => $request->platform,
                'app_version' => $request->app_version,
                'is_active'   => true,
                'last_used_at'=> now(),
            ]
        );

        return response()->json([
            'success' => true,
        ]);
    }
}