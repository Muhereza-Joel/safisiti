<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class VersionCheckController extends Controller
{
    public function check(Request $request)
    {
        $latestVersion = '1.1.2';
        $mandatory = false;
        $maintenanceMode = false;

        // Determine if update is available - you can use any logic here
        $hasUpdate = false; // Set this based on your business logic

        // You can also check against current app version from request
        $currentVersion = $request->header('User-Agent'); // Or pass as parameter
        // $hasUpdate = version_compare($latestVersion, $currentVersion, '>');

        return response()->json([
            'latest_version' => $latestVersion,
            'mandatory' => $mandatory,
            'playstore_url' => $this->getStoreUrl($request),
            'message' => 'This update includes important security and performance improvements.',
            'release_notes' => "- Improved UI\n- Fixed data sync issues\n- Enhanced security",
            'min_supported_version' => '1.0.0',
            'maintenance_mode' => $maintenanceMode,
            'has_update' => $hasUpdate, // The key flag that controls update display
        ]);
    }

    private function getStoreUrl(Request $request)
    {
        $userAgent = $request->header('User-Agent');

        // Detect platform from User-Agent or use a parameter
        if (strpos($userAgent, 'iOS') !== false || $request->has('platform') && $request->get('platform') === 'ios') {
            return 'https://apps.apple.com/app/idXXXXXXXX';
        }

        // Default to Android
        return 'https://play.google.com/store/apps/details?id=com.muherezajoel.safisitiapp';
    }
}
