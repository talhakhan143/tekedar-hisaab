<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\DB;

/**
 * Jis project ko hata diya gaya ho, us ka paisa kisi hisaab me na aaye.
 *
 * Masla jo is ne theek kiya: project soft delete hota hai, magar us ke saath
 * lagi client payments, purchases, kharche, haazri, mazdoori aur peshgi apni
 * jagah zinda reh jati thin. Dashboard ke saare total seedhe Model::sum() hain
 * jo sirf row ka apna deleted_at dekhte hain, parent project ka nahi, is liye
 * hataye gaye project ka paisa chupke se ginta rehta tha. Live par isi wajah se
 * ek hataye gaye project ki 19,00,000 ki peshgi "Total Billed" aur cash profit
 * dono me shaamil thi, jab ke us project ka apna page 404 deta tha.
 *
 * Ye scope read ke waqt lagta hai, is liye koi data mitata nahi: row DB me
 * waise hi padi rehti hai aur project wapas restore karne par khud ba khud
 * dobara ginne lag jati hai.
 *
 * project_id nullable hai (bina project wala aam kharcha), aur NULL ka matlab
 * "kisi project ka nahi" hai, "hataye gaye project ka" nahi, is liye aisi rows
 * hamesha shaamil rehti hain.
 */
class LiveProjectScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $table = $model->getTable();

        $builder->where(function (Builder $q) use ($table) {
            $q->whereNull($table . '.project_id')
                ->orWhereExists(function ($sub) use ($table) {
                    $sub->select(DB::raw(1))
                        ->from('projects')
                        ->whereColumn('projects.id', $table . '.project_id')
                        ->whereNull('projects.deleted_at');
                });
        });
    }
}
