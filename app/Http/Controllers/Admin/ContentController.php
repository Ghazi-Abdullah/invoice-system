<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentPage;
use Illuminate\Http\JsonResponse;

class ContentController extends Controller
{
    /**
     * Get privacy policy content
     */
    public function privacy(): JsonResponse
    {
        $content = ContentPage::where('slug', 'privacy-policy')
            ->where('is_active', true)
            ->first();

        if (!$content) {
            return response()->json([
                'message' => 'Privacy policy not found',
            ], 404);
        }

        return response()->json([
            'last_updated' => $content->updated_at->toDateString(),
            'introduction' => $content->introduction,
            'sections' => $content->sections,
        ]);
    }

    /**
     * Get terms of service content
     */
    public function terms(): JsonResponse
    {
        $content = ContentPage::where('slug', 'terms-of-service')
            ->where('is_active', true)
            ->first();

        if (!$content) {
            return response()->json([
                'message' => 'Terms of service not found',
            ], 404);
        }

        return response()->json([
            'version' => $content->version,
            'effective_date' => $content->effective_date->toDateString(),
            'acceptance_text' => $content->acceptance_text,
            'sections' => $content->sections,
        ]);
    }
}
