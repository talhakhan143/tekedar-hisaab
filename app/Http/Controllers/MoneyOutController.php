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
        OtherExpense::create($this->validatedExpense($request));

        return back()->with('status', 'Expense record ho gaya.');
    }

    public function editExpense(OtherExpense $otherExpense)
    {
        return view('money_out.edit-expense', [
            'expense'    => $otherExpense,
            'projects'   => $this->projectOptions($otherExpense->project_id),
            'categories' => self::EXPENSE_CATEGORIES,
        ]);
    }

    public function updateExpense(Request $request, OtherExpense $otherExpense)
    {
        // Add karne wale rule hi edit par bhi lagte hain, warna edit se aisa
        // expense ban sakta jo form se add hi nahi ho sakta tha.
        $otherExpense->update($this->validatedExpense($request));

        return redirect()->route('money-out')->with('status', 'Expense durust ho gaya.');
    }

    public function destroyExpense(OtherExpense $otherExpense)
    {
        $otherExpense->delete();

        return back()->with('status', 'Expense hata diya.');
    }

    public function storeOverhead(Request $request)
    {
        GeneralOverhead::create($this->validatedOverhead($request));

        return back()->with('status', 'Overhead record ho gaya.');
    }

    public function editOverhead(GeneralOverhead $generalOverhead)
    {
        return view('money_out.edit-overhead', ['overhead' => $generalOverhead]);
    }

    public function updateOverhead(Request $request, GeneralOverhead $generalOverhead)
    {
        $generalOverhead->update($this->validatedOverhead($request));

        return redirect()->route('money-out')->with('status', 'Overhead durust ho gaya.');
    }

    public function destroyOverhead(GeneralOverhead $generalOverhead)
    {
        $generalOverhead->delete();

        return back()->with('status', 'Overhead hata diya.');
    }

    /** storeExpense() aur updateExpense() dono isi se guzarte hain. */
    private function validatedExpense(Request $request): array
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

        return [
            'project_id'   => $v['project_id'] ?? null,
            'date'         => $v['date'],
            'category'     => $v['category'],
            'description'  => $v['description'] ?? null,
            'amount_paisa' => Money::toPaisa($v['amount']),
            'paid_to'      => $v['paid_to'] ?? null,
            'notes'        => $v['notes'] ?? null,
        ];
    }

    /** storeOverhead() aur updateOverhead() dono isi se guzarte hain. */
    private function validatedOverhead(Request $request): array
    {
        $v = $request->validate([
            'month'    => ['required', 'string', 'max:7'], // YYYY-MM
            'category' => ['required', 'string', 'max:100'],
            'amount'   => ['required', 'numeric', 'min:0'],
            'notes'    => ['nullable', 'string'],
        ]);

        return [
            'month'        => $v['month'],
            'category'     => $v['category'],
            'amount_paisa' => Money::toPaisa($v['amount']),
            'notes'        => $v['notes'] ?? null,
        ];
    }

    /**
     * Expense ka project dropdown. Yahan project_id nullable hai (general/office
     * kharcha), magar project soft-delete ho jane par bhi purani row ka project
     * list me wapis daal dete hain, warna edit karte hi kharcha chup chaap
     * "General" ban jata.
     */
    private function projectOptions(?int $projectId): \Illuminate\Database\Eloquent\Collection
    {
        $projects = Project::orderBy('name')->get(['id', 'name']);

        if ($projectId && ! $projects->contains('id', $projectId)) {
            $gone = Project::withTrashed()->find($projectId, ['id', 'name']);

            if ($gone) {
                $gone->name = $gone->name . ' (mita hua project)';
                $projects->prepend($gone);
            }
        }

        return $projects;
    }
}
