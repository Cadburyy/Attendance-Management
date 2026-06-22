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
        $this->middleware('permission:setting', ['only' => ['index', 'updateAppearance', 'updateAbsenceTime', 'updateGeolocation', 'updateLiveness']]);
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
            // Shift 1 (Morning)
            'shift_1_in_start' => '06:00', 'shift_1_in_end' => '08:00',
            'shift_1_out_start' => '14:00', 'shift_1_out_end' => '16:00',
            // Shift 2 (Afternoon)
            'shift_2_in_start' => '14:00', 'shift_2_in_end' => '15:00',
            'shift_2_out_start' => '21:00', 'shift_2_out_end' => '23:00',
            // Shift 3 (Night)
            'shift_3_in_start' => '21:00', 'shift_3_in_end' => '22:00',
            'shift_3_out_start' => '05:00', 'shift_3_out_end' => '07:00',
            // Geolocation settings
            'office_latitude' => '-6.200000',
            'office_longitude' => '106.816666',
            'office_radius' => '100',
            'geolocation_enabled' => '0',
            // Liveness settings
            'liveness_enabled' => '1',
            'liveness_blink' => '1',
            'liveness_turn_left' => '1',
            'liveness_turn_right' => '1',
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
        $rules = [];
        for ($i = 1; $i <= 3; $i++) {
            $rules["shift_{$i}_in_start"] = 'required|date_format:H:i';
            $rules["shift_{$i}_in_end"] = 'required|date_format:H:i';
            $rules["shift_{$i}_out_start"] = 'required|date_format:H:i';
            $rules["shift_{$i}_out_end"] = 'required|date_format:H:i';
        }
        $request->validate($rules);

        for ($i = 1; $i <= 3; $i++) {
            $this->putSetting("shift_{$i}_in_start", $request->input("shift_{$i}_in_start"));
            $this->putSetting("shift_{$i}_in_end", $request->input("shift_{$i}_in_end"));
            $this->putSetting("shift_{$i}_out_start", $request->input("shift_{$i}_out_start"));
            $this->putSetting("shift_{$i}_out_end", $request->input("shift_{$i}_out_end"));
        }

        cache()->forget('app_settings');

        return redirect()->route('settings.index')->with('success', 'Shift times updated successfully!');
    }

    public function updateGeolocation(Request $request)
    {
        $request->validate([
            'office_latitude' => 'required|numeric|between:-90,90',
            'office_longitude' => 'required|numeric|between:-180,180',
            'office_radius' => 'required|integer|min:10|max:5000', // meters
            'geolocation_enabled' => 'required|boolean',
        ]);

        $this->putSetting('office_latitude', $request->office_latitude);
        $this->putSetting('office_longitude', $request->office_longitude);
        $this->putSetting('office_radius', $request->office_radius);
        $this->putSetting('geolocation_enabled', $request->geolocation_enabled);

        cache()->forget('app_settings');

        return redirect()->route('settings.index')->with('success', 'Geolocation settings updated successfully!');
    }

    public function updateLiveness(Request $request)
    {
        $request->validate([
            'liveness_enabled' => 'required|boolean',
            'liveness_blink' => 'required|boolean',
            'liveness_turn_left' => 'required|boolean',
            'liveness_turn_right' => 'required|boolean',
        ]);

        $this->putSetting('liveness_enabled', $request->liveness_enabled);
        $this->putSetting('liveness_blink', $request->liveness_blink);
        $this->putSetting('liveness_turn_left', $request->liveness_turn_left);
        $this->putSetting('liveness_turn_right', $request->liveness_turn_right);

        cache()->forget('app_settings');

        return redirect()->route('settings.index')->with('success', 'Liveness settings updated successfully!');
    }

    protected function putSetting(string $key, $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}