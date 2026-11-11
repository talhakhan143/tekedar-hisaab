<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\MaterialPurchase;
use App\Models\Project;
use App\Models\Vendor;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MaterialPurchaseController extends Controller
{
    public function index()
    {
        $purchases = MaterialPurchase::with(['project', 'vendor'])->orderByDesc('date')->orderByDesc('id')
            ->paginate(25)->withQueryString();

        // totalSpent poora bill hai (maal + kiraya + mazdoori + deegar), is liye
        // ye waise hi amount_paisa se banta hai. Saath me teen charges ke jor
        // bhi bhej dete hain taake list me alag alag dikh saken.
        return view('materials.index', [
            'purchases'         => $purchases,
            'totalSpent'        => (int) MaterialPurchase::sum('amount_paisa'),
            'totalDue'          => (int) MaterialPurchase::sum('balance_due_paisa'),
            'totalFreight'      => (int) MaterialPurchase::sum('freight_paisa'),
            'totalLabour'       => (int) MaterialPurchase::sum('labour_paisa'),
            'totalOtherCharges' => (int) MaterialPurchase::sum('other_charges_paisa'),
        ]);
    }

    public function create(Request $request)
    {
        return view('materials.create', [
            'projects'        => Project::orderBy('name')->get(['id', 'name']),
            'vendors'         => Vendor::orderBy('name')->get(['id', 'name', 'type']),
            'selectedProject' => $request->integer('project'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $purchase = MaterialPurchase::create($data);

        return redirect()->route('materials.index')->with('status', 'Purchase record ho gayi.')
            ->with('voucher_url', route('vouchers.material', $purchase))
            ->with('voucher_label', '🖨 Invoice');
    }

    public function edit(MaterialPurchase $materialPurchase)
    {
        return view('materials.edit', [
            'purchase' => $materialPurchase,
            'projects' => Project::orderBy('name')->get(['id', 'name']),
            'vendors'  => Vendor::orderBy('name')->get(['id', 'name', 'type']),
        ]);
    }

    public function update(Request $request, MaterialPurchase $materialPurchase)
    {
        $data = $this->validatedData($request);

        // Log project reassignment (material bought for A, used in B).
        $oldProject = $materialPurchase->project_id;
        if ((int) $oldProject !== (int) $data['project_id']) {
            AuditLog::record(
                action: 'purchase_reassigned',
                subject: $materialPurchase,
                description: "Purchase #{$materialPurchase->id} project reassigned",
                old: ['project_id' => $oldProject],
                new: ['project_id' => $data['project_id']],
            );
        }

        $materialPurchase->update($data);

        return redirect()->route('materials.index')->with('status', 'Purchase update ho gayi.');
    }

    public function destroy(MaterialPurchase $materialPurchase)
    {
        $materialPurchase->delete();

        return back()->with('status', 'Purchase hata di (soft delete).');
    }

    /** Pay off (part of) a purchase's outstanding udhaar. */
    public function pay(Request $request, MaterialPurchase $materialPurchase)
    {
        $v = $request->validate(['amount' => ['required', 'numeric', 'min:0']]);
        $pay = Money::toPaisa($v['amount']);
        $balance = (int) $materialPurchase->balance_due_paisa;
        $pay = min($pay, $balance); // never overpay

        $materialPurchase->update([
            'amount_paid_paisa' => (int) $materialPurchase->amount_paid_paisa + $pay,
            'balance_due_paisa' => $balance - $pay,
        ]);

        $back = $materialPurchase->project_id
            ? route('projects.show', ['project' => $materialPurchase->project_id, 'tab' => 'materials'])
            : url()->previous();

        return redirect($back)->with('status', 'Vendor ko payment ho gayi (udhaar kam).')
            ->with('voucher_url', route('vouchers.material', $materialPurchase))
            ->with('voucher_label', '🖨 Invoice');
    }

    private function validatedData(Request $request): array
    {
        $v = $request->validate([
            'project_id'    => ['nullable', 'exists:projects,id'],
            'vendor_id'     => ['nullable', 'exists:vendors,id'],
            'date'          => ['required', 'date'],
            'item_name'     => ['required', 'string', 'max:255'],
            'qty'           => ['required', 'numeric', 'min:0'],
            'unit'          => ['nullable', 'string', 'max:50'],
            'rate_per_unit' => ['required', 'numeric', 'min:0'],
            'amount_paid'   => ['nullable', 'numeric', 'min:0'],
            'freight'       => ['nullable', 'numeric', 'min:0'],
            'labour_charge' => ['nullable', 'numeric', 'min:0'],
            'other_charges' => ['nullable', 'numeric', 'min:0'],
            'wastage_qty'   => ['nullable', 'numeric', 'min:0'],
            'notes'         => ['nullable', 'string'],
        ]);

        // Maal ki qeemat alag likhi jati hai, aur us par laga kiraya/mazdoori/
        // deegar alag. Bill (amount_paisa) in sab ka jor hai, is liye vendor ka
        // udhaar poora nikalta hai, magar lagat ki taqseem me kiraya transport
        // me, mazdoori labour me aur deegar misc me jati hai, material me nahi.
        $goods   = (int) round((float) $v['qty'] * Money::toPaisa($v['rate_per_unit']));
        $freight = Money::toPaisa($v['freight'] ?? 0);
        $labour  = Money::toPaisa($v['labour_charge'] ?? 0);
        $other   = Money::toPaisa($v['other_charges'] ?? 0);
        $amount  = $goods + $freight + $labour + $other;
        $paid    = Money::toPaisa($v['amount_paid'] ?? 0);

        // Pehle yahan min($paid, $amount) tha. Us ka natija ye nikalta tha ke
        // agar edit me kiraya ya mazdoori kam kar di jaye to pehle se likhi
        // hui payment chup chaap ghata di jati thi, yani diya hua paisa kahin
        // ghayab ho jata. Ab saaf mana karte hain, maalik khud faisla kare.
        if ($paid > $amount) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Diya hua paisa bill se zyada nahi ho sakta. Bill (maal + kiraya + mazdoori + deegar) = '
                    . Money::format($amount) . ' aur aap ' . Money::format($paid)
                    . ' daal rahe hain. Pehle qeemat ya charges theek karo, warna di hui raqam kam karo.',
            ]);
        }

        return [
            'project_id'          => $v['project_id'] ?? null,
            'vendor_id'           => $v['vendor_id'] ?? null,
            'date'                => $v['date'],
            'item_name'           => $v['item_name'],
            'qty'                 => $v['qty'],
            'unit'                => $v['unit'] ?? null,
            'rate_per_unit_paisa' => Money::toPaisa($v['rate_per_unit']),
            'goods_amount_paisa'  => $goods,
            'freight_paisa'       => $freight,
            'labour_paisa'        => $labour,
            'other_charges_paisa' => $other,
            'amount_paisa'        => $amount,
            'amount_paid_paisa'   => $paid,
            'balance_due_paisa'   => $amount - $paid,
            'wastage_qty'         => $v['wastage_qty'] ?? null,
            'notes'               => $v['notes'] ?? null,
        ];
    }
}
