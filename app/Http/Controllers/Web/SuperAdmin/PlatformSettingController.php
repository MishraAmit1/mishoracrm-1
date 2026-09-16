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
            'app_id'             => PlatformSetting::get('meta_app_id'),
            'app_secret'         => PlatformSetting::get('meta_app_secret'),
            'ig_app_id'          => PlatformSetting::get('meta_ig_app_id'),
            'ig_app_secret'      => PlatformSetting::get('meta_ig_app_secret'),
            'wa_config_id'       => PlatformSetting::get('meta_wa_embedded_config_id'),
        ]);
    }

    public function saveMetaApp(Request $request): RedirectResponse
    {
        $existing   = PlatformSetting::get('meta_app_secret');
        $existingIg = PlatformSetting::get('meta_ig_app_secret');

        $request->validate([
            'app_id'        => ['required', 'string', 'max:255'],
            'app_secret'    => [$existing ? 'nullable' : 'required', 'string', 'max:255'],
            // Instagram API "with Instagram Login" uses its own App ID / Secret,
            // shown under Meta App → Instagram → API setup with Instagram login.
            // Optional — falls back to the Facebook app credentials when blank.
            'ig_app_id'     => ['nullable', 'string', 'max:255'],
            'ig_app_secret' => ['nullable', 'string', 'max:255'],
            // WhatsApp Embedded Signup configuration, from
            // App → Facebook Login for Business → Configurations.
            'wa_config_id'  => ['nullable', 'string', 'max:255'],
        ]);

        PlatformSetting::set('meta_app_id', $request->app_id);
        if ($request->filled('app_secret')) {
            PlatformSetting::set('meta_app_secret', $request->app_secret);
        }
        PlatformSetting::set('meta_ig_app_id', $request->ig_app_id ?? '');
        if ($request->filled('ig_app_secret')) {
            PlatformSetting::set('meta_ig_app_secret', $request->ig_app_secret);
        }
        PlatformSetting::set('meta_wa_embedded_config_id', $request->wa_config_id ?? '');

        return back()->with('success', 'Meta App credentials saved.');
    }
}
