<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformSettingController extends Controller
{
    public function metaApp(): View
    {
        return view('superadmin.platform-settings.meta', [
            'app_id'     => PlatformSetting::get('meta_app_id'),
            'app_secret' => PlatformSetting::get('meta_app_secret'),
        ]);
    }

    public function saveMetaApp(Request $request): RedirectResponse
    {
        $existing = PlatformSetting::get('meta_app_secret');

        $request->validate([
            'app_id'     => ['required', 'string', 'max:255'],
            'app_secret' => [$existing ? 'nullable' : 'required', 'string', 'max:255'],
        ]);

        PlatformSetting::set('meta_app_id', $request->app_id);
        if ($request->filled('app_secret')) {
            PlatformSetting::set('meta_app_secret', $request->app_secret);
        }

        return back()->with('success', 'Meta App credentials saved.');
    }
}
