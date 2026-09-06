<?php

namespace App\Traits;

use App\Models\Company;
use Illuminate\Http\Request;

/**
 * Shared helper for resolving the authenticated company via explicit query.
 *
 * Using Company::where('user_id', ...) instead of $request->user()->company
 * avoids stale relationship caching that can occur between requests
 * (e.g. after POST creates a company, subsequent GET may return null
 * via the HasOne accessor).
 */
trait HasCompany
{
    /**
     * Resolve the authenticated user's company via direct DB query.
     * Returns null if no company profile exists.
     */
    protected function resolveCompany(Request $request): ?Company
    {
        return Company::where('user_id', $request->user()->id)->first();
    }
}
