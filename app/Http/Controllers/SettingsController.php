<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:setting', ['only' => ['index', 'updateAppearance']]);
    }

    public function index()
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        $defaults = [
            'brand_name' => 'Citra Nugerah Karya',
            'font' => 'Nunito',
            'logo_path' => null,
            'favicon_path' => null,
            // Attendance Defaults
            'attendance_in_start' => '07:00',
            'attendance_in_end' => '09:00',
            'attendance_out_start' => '16:00',
            'attendance_out_end' => '18:00',
        ];

        $settings = array_merge($defaults, $settings);

        return view('settings.index', compact('settings'));
    }

    public function updateAppearance(Request $request)
    {
        $request->validate([
            'brand_name' => 'required|string|max:100',
            'font' => ['required', Rule::in(['Nunito', 'Inter', 'Roboto', 'Poppins', 'Open Sans'])],
            'logo' => 'nullable|image|mimes:png|max:2048',
            'favicon' => 'nullable|image|mimes:ico,png,jpg,jpeg,gif|max:2048',
        ]);
        
        $this->putSetting('brand_name', $request->brand_name);
        $this->putSetting('font', $request->font);

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

        return redirect()->route('settings.index')->with('success', 'Appearance updated successfully!');
    }

    public function updateAbsenceTime(Request $request)
    {
        $request->validate([
            'attendance_in_start' => 'required|date_format:H:i',
            'attendance_in_end' => 'required|date_format:H:i',
            'attendance_out_start' => 'required|date_format:H:i',
            'attendance_out_end' => 'required|date_format:H:i',
        ]);

        $this->putSetting('attendance_in_start', $request->attendance_in_start);
        $this->putSetting('attendance_in_end', $request->attendance_in_end);
        $this->putSetting('attendance_out_start', $request->attendance_out_start);
        $this->putSetting('attendance_out_end', $request->attendance_out_end);

        cache()->forget('app_settings');

        return redirect()->route('settings.index')->with('success', 'Attendance times updated successfully!');
    }

    protected function putSetting(string $key, $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}