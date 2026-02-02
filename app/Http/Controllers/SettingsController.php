<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:setting', ['only' => ['index']]);

        $this->middleware('permission:appearance', ['only' => ['editAppearance', 'updateAppearance']]);
    }

    public function index()
    {
        return view('settings.index');
    }

    public function editAppearance()
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        $defaults = [
            'brand_name' => 'Citra Nugerah Karya',
            'font' => 'Nunito',
            'logo_path' => null,
            'favicon_path' => null,
            'bg_color' => '#f8f9fa',
            'nav_bg_color' => '#ffffff',
        ];

        $settings = array_merge($defaults, $settings);

        return view('settings.appearance', compact('settings'));
    }

    public function updateAppearance(Request $request)
    {
        $request->validate([
            'brand_name' => 'required|string|max:100',
            'font' => ['required', Rule::in(['Nunito', 'Inter', 'Roboto', 'Poppins', 'Open Sans'])],
            'logo' => 'nullable|image|mimes:png|max:2048',
            'favicon' => 'nullable|image|mimes:ico,png,jpg,jpeg,gif|max:2048',
            'bg_color' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'nav_bg_color' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
        ]);
        
        $this->putSetting('brand_name', $request->brand_name);
        $this->putSetting('font', $request->font);
        $this->putSetting('bg_color', $request->bg_color);
        $this->putSetting('nav_bg_color', $request->nav_bg_color);

        if ($request->hasFile('logo')) {
            $oldLogoPath = Setting::where('key', 'logo_path')->value('value');
            if ($oldLogoPath && Storage::disk('public')->exists($oldLogoPath)) {
                Storage::disk('public')->delete($oldLogoPath);
            }

            $path = $request->file('logo')->store('logos', 'public');
            $this->putSetting('logo_path', $path);
        }

        if ($request->hasFile('favicon')) {
            $oldFaviconPath = Setting::where('key', 'favicon_path')->value('value');
            if ($oldFaviconPath && Storage::disk('public')->exists($oldFaviconPath)) {
                Storage::disk('public')->delete($oldFaviconPath);
            }

            $path = $request->file('favicon')->store('favicons', 'public');
            $this->putSetting('favicon_path', $path);
        }

        cache()->forget('app_settings');

        return redirect()->route('settings.appearance')->with('success', 'Appearance updated!');
    }

    protected function putSetting(string $key, $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}