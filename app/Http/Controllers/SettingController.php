<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function edit()
    {
        return view('settings.edit', ['settings' => Setting::all_settings()]);
    }

    public function update(Request $request)
    {
        $v = $request->validate([
            'company_name'       => ['required', 'string', 'max:255'],
            'default_retention'  => ['required', 'numeric', 'min:0', 'max:100'],
            'default_wastage'    => ['required', 'numeric', 'min:0', 'max:100'],
            'allocate_overheads' => ['nullable', 'boolean'],
            'worker_roles'       => ['nullable', 'string', 'max:1000'],
            'logo'               => ['nullable', 'image', 'max:2048'],
        ]);

        Setting::put('company_name', $v['company_name']);
        Setting::put('default_retention', $v['default_retention']);
        Setting::put('default_wastage', $v['default_wastage']);
        Setting::put('allocate_overheads', $request->boolean('allocate_overheads') ? '1' : '0');

        // Normalise worker roles to a clean comma list (lowercase, trimmed, unique).
        $roles = collect(explode(',', (string) ($v['worker_roles'] ?? '')))
            ->map(fn ($r) => strtolower(trim($r)))->filter()->unique()->values();
        Setting::put('worker_roles', $roles->isNotEmpty() ? $roles->implode(',') : Setting::DEFAULTS['worker_roles']);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('public/branding');
            Setting::put('company_logo', Storage::url($path));
        }

        return back()->with('status', 'Settings save ho gayi.');
    }
}
