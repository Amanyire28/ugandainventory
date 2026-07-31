<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PwaVersionController extends Controller
{
    /**
     * Get the latest application version and release metadata
     */
    public function getVersion(Request $request)
    {
        return response()->json([
            'success' => true,
            'version' => config('pwa.version', '1.0.0'),
            'release_date' => config('pwa.release_date', '2026-07-31'),
            'release_notes' => config('pwa.release_notes', 'PWA Update Manager integration'),
            'min_compatible_version' => config('pwa.min_compatible_version', '1.0.0'),
        ]);
    }
}
