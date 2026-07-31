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
            'total_shifts' => '1',
            'shift_1_in_start' => '06:00', 'shift_1_in_end' => '08:00',
            'shift_1_out_start' => '14:00', 'shift_1_out_end' => '16:00',
            'office_latitude' => '-6.200000',
            'office_longitude' => '106.816666',
            'office_radius' => '100',
            'geolocation_enabled' => '0',
            'liveness_enabled' => '1',
            'liveness_blink' => '1',
            'liveness_turn_left' => '1',
            'liveness_turn_right' => '1',
        ];

        $settings = array_merge($defaults, $settings);

        $totalShifts = (int)($settings['total_shifts'] ?? 1);
        $shifts = [];
        for ($i = 1; $i <= $totalShifts; $i++) {
            $shifts[$i] = [
                'in_start' => $settings["shift_{$i}_in_start"] ?? '00:00',
                'in_end' => $settings["shift_{$i}_in_end"] ?? '00:00',
                'out_start' => $settings["shift_{$i}_out_start"] ?? '00:00',
                'out_end' => $settings["shift_{$i}_out_end"] ?? '00:00',
            ];
        }

        return view('settings.index', compact('settings', 'shifts'));
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

    #. Shift Configuration Settings
    public function updateAbsenceTime(Request $request)
    {
        $request->validate([
            'shifts' => 'required|array|min:1|max:20',
            'shifts.*.in_start' => 'required|date_format:H:i',
            'shifts.*.in_end' => 'required|date_format:H:i',
            'shifts.*.out_start' => 'required|date_format:H:i',
            'shifts.*.out_end' => 'required|date_format:H:i',
        ]);

        // Validate Shift Overlaps
        $spans = [];
        foreach (array_values($request->shifts) as $i => $s) {
            $spans[] = ['num' => $i + 1, 'start' => $s['in_start'], 'end' => $s['out_start']];
        }

        foreach ($spans as $a) {
            foreach ($spans as $b) {
                if ($a['num'] >= $b['num']) continue;

                // Skip pairs involving night shifts (spans midnight)
                if ($a['end'] < $a['start'] || $b['end'] < $b['start']) continue;

                if ($a['start'] < $b['end'] && $b['start'] < $a['end']) {
                    return back()->withInput()->withErrors([
                        'shifts' => "Shift {$a['num']} dan shift {$b['num']} saling tumpang tindih. Shift dengan nomor lebih besar tidak akan pernah aktif."
                    ]);
                }
            }
        }

        $totalShifts = count($request->shifts);
        $this->putSetting('total_shifts', $totalShifts);

        $i = 1;
        foreach ($request->shifts as $shift) {
            $this->putSetting("shift_{$i}_in_start", $shift['in_start']);
            $this->putSetting("shift_{$i}_in_end", $shift['in_end']);
            $this->putSetting("shift_{$i}_out_start", $shift['out_start']);
            $this->putSetting("shift_{$i}_out_end", $shift['out_end']);
            $i++;
        }

        for ($j = $totalShifts + 1; $j <= 20; $j++) {
            Setting::whereIn('key', [
                "shift_{$j}_in_start",
                "shift_{$j}_in_end",
                "shift_{$j}_out_start",
                "shift_{$j}_out_end"
            ])->delete();
        }

        cache()->forget('app_settings');

        return redirect()->route('settings.index')->with('success', 'Shift times updated successfully!');
    }

    #. Geolocation Configuration Settings
    public function updateGeolocation(Request $request)
    {
        $request->validate([
            'office_latitude' => 'required|numeric|between:-90,90',
            'office_longitude' => 'required|numeric|between:-180,180',
            'office_radius' => 'required|integer|min:10|max:5000',
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