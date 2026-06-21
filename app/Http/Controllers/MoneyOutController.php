<?php

namespace App\Http\Controllers;

use App\Models\GeneralOverhead;
use App\Models\MaterialPurchase;
use App\Models\OtherExpense;
use App\Models\Project;
use App\Models\WagePayment;
use App\Models\WorkerAdvance;
use App\Support\Money;
use Illuminate\Http\Request;

class MoneyOutController extends Controller
{
    public const EXPENSE_CATEGORIES = ['transport', 'equipment_rental', 'fuel', 'utility', 'rent', 'tools', 'food_chai', 'permits_govt', 'bank_charges', 'misc'];

    public function index()
    {
        $materialSpent = (int) MaterialPurchase::sum('amount_paisa');
        $wagesPaid     = (int) WagePayment::sum('amount_paisa');
        $advNet        = (int) WorkerAdvance::where('type', 'advance_given')->sum('amount_paisa')
                       - (int) WorkerAdvance::where('type', 'recovery')->sum('amount_paisa');
        $otherTotal    = (int) OtherExpense::sum('amount_paisa');
        $overheadTotal = (int) GeneralOverhead::sum('amount_paisa');

        return view('money_out.index', [
            'materialSpent' => $materialSpent,
            'labourPaid'    => $wagesPaid + $advNet,
            'otherTotal'    => $otherTotal,
            'overheadTotal' => $overheadTotal,
            'grandTotal'    => $materialSpent + $wagesPaid + $advNet + $otherTotal + $overheadTotal,
            'expenses'      => OtherExpense::with('project')->orderByDesc('date')->orderByDesc('id')
                                ->paginate(15, ['*'], 'epage')->withQueryString(),
            'overheads'     => GeneralOverhead::orderByDesc('month')->orderBy('category')
                                ->paginate(15, ['*'], 'opage')->withQueryString(),
            'projects'      => Project::orderBy('name')->get(['id', 'name']),
            'categories'    => self::EXPENSE_CATEGORIES,
        ]);
    }

    public function storeExpense(Request $request)
    {
        $v = $request->validate([
            'project_id'  => ['nullable', 'exists:projects,id'],
            'date'        => ['required', 'date'],
            'category'    => ['required', 'in:' . implode(',', self::EXPENSE_CATEGORIES)],
            'description' => ['nullable', 'string', 'max:255'],
            'amount'      => ['required', 'numeric', 'min:0'],
            'paid_to'     => ['nullable', 'string', 'max:255'],
            'notes'       => ['nullable', 'string'],
        ]);

        OtherExpense::create([
            'project_id'   => $v['project_id'] ?? null,
            'date'         => $v['date'],
            'category'     => $v['category'],
            'description'  => $v['description'] ?? null,
            'amount_paisa' => Money::toPaisa($v['amount']),
            'paid_to'      => $v['paid_to'] ?? null,
            'notes'        => $v['notes'] ?? null,
        ]);

        return back()->with('status', 'Expense record ho gaya.');
    }

    public function destroyExpense(OtherExpense $otherExpense)
    {
        $otherExpense->delete();

        return back()->with('status', 'Expense hata diya.');
    }

    public function storeOverhead(Request $request)
    {
        $v = $request->validate([
            'month'    => ['required', 'string', 'max:7'], // YYYY-MM
            'category' => ['required', 'string', 'max:100'],
            'amount'   => ['required', 'numeric', 'min:0'],
            'notes'    => ['nullable', 'string'],
        ]);

        GeneralOverhead::create([
            'month'        => $v['month'],
            'category'     => $v['category'],
            'amount_paisa' => Money::toPaisa($v['amount']),
            'notes'        => $v['notes'] ?? null,
        ]);

        return back()->with('status', 'Overhead record ho gaya.');
    }

    public function destroyOverhead(GeneralOverhead $generalOverhead)
    {
        $generalOverhead->delete();

        return back()->with('status', 'Overhead hata diya.');
    }
}
