<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function edit()
    {
        return view('settings.edit', [
            'settings'      => Setting::all_settings(),
            'contractTypes' => Project::CONTRACT_TYPES,
            // Har qism ki apni list, waise hi jaise save hui hai (comma wali
            // raw string), taake maalik usi textarea me edit kar sake.
            'itemLists'     => collect(Project::CONTRACT_TYPES)->mapWithKeys(fn ($label, $type) => [
                $type => Setting::get(Setting::itemsKeyFor($type)),
            ])->all(),
        ]);
    }

    public function update(Request $request)
    {
        $v = $request->validate([
            'company_name'       => ['required', 'string', 'max:255'],
            'company_address'    => ['nullable', 'string', 'max:255'],
            'company_phone'      => ['nullable', 'string', 'max:100'],
            'default_wastage'    => ['required', 'numeric', 'min:0', 'max:100'],
            'allocate_overheads' => ['nullable', 'boolean'],
            'worker_roles'       => ['nullable', 'string', 'max:1000'],
            'common_items'       => ['nullable', 'string', 'max:1000'],
            'common_units'       => ['nullable', 'string', 'max:1000'],
            'items'              => ['nullable', 'array'],
            'items.*'            => ['nullable', 'string', 'max:4000'],
            'logo'               => ['nullable', 'image', 'max:2048'],
        ]);

        Setting::put('company_name', $v['company_name']);
        Setting::put('company_address', $v['company_address'] ?? '');
        Setting::put('company_phone', $v['company_phone'] ?? '');
        Setting::put('default_wastage', $v['default_wastage']);
        Setting::put('allocate_overheads', $request->boolean('allocate_overheads') ? '1' : '0');

        // Normalise worker roles to a clean comma list (lowercase, trimmed, unique).
        $roles = collect(explode(',', (string) ($v['worker_roles'] ?? '')))
            ->map(fn ($r) => strtolower(trim($r)))->filter()->unique()->values();
        Setting::put('worker_roles', $roles->isNotEmpty() ? $roles->implode(',') : Setting::DEFAULTS['worker_roles']);

        // Aam cheezein aur units: trim + khaali nikal do + unique, magar case
        // waisa hi rehne do. "SFT" ko "sft" banana maalik ki likhi hui cheez
        // bigaarna hai, aur ye categories nahi sirf labels hain.
        foreach (['common_items', 'common_units'] as $key) {
            $list = collect(explode(',', (string) ($v[$key] ?? '')))
                ->map(fn ($x) => trim($x))->filter()->unique()->values();
            Setting::put($key, $list->isNotEmpty() ? $list->implode(',') : Setting::DEFAULTS[$key]);
        }

        // Har kaam ki qism ki apni cheezon ki list. Form se `items[<type>]`
        // aata hai, magar key par bharosa nahi karte: sirf asli contract types
        // save hoti hain, baqi chup chaap chhod dete hain. Normalisation bilkul
        // common_items jaisi hai (trim + khaali nikal do + unique, case waisa hi),
        // taake saari listein ek hi tarah chalein.
        foreach ((array) ($v['items'] ?? []) as $type => $raw) {
            if (! array_key_exists($type, Project::CONTRACT_TYPES)) {
                continue;
            }
            $key  = Setting::itemsKeyFor($type);
            $list = collect(explode(',', (string) $raw))
                ->map(fn ($x) => trim($x))->filter()->unique()->values();
            Setting::put($key, $list->isNotEmpty() ? $list->implode(',') : Setting::DEFAULTS[$key]);
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('public/branding');
            Setting::put('company_logo', Storage::url($path));
        }

        return back()->with('status', 'Settings save ho gayi.');
    }
}
