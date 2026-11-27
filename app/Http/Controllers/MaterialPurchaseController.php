<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\MaterialPurchase;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Vendor;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MaterialPurchaseController extends Controller
{
    public function index()
    {
        $purchases = MaterialPurchase::with(['project', 'vendor', 'driver'])->orderByDesc('date')->orderByDesc('id')
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
        $projects = Project::orderBy('name')->get();

        return view('materials.create', [
            'projects'        => $projects,
            'vendors'         => Vendor::orderBy('name')->get(['id', 'name', 'type']),
            'drivers'         => Vendor::where('type', 'driver')->orderBy('name')->get(['id', 'name']),
            'itemsByProject'  => $this->itemsByProject($projects),
            'commonItems'     => Setting::itemsForContractType(null), // fallback jab koi project na chuna ho
            'commonUnits'     => Setting::commonUnits(),
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
        $projects = Project::orderBy('name')->get();

        return view('materials.edit', [
            'purchase'       => $materialPurchase,
            'projects'       => $projects,
            'vendors'        => Vendor::orderBy('name')->get(['id', 'name', 'type']),
            'drivers'        => Vendor::where('type', 'driver')->orderBy('name')->get(['id', 'name']),
            'itemsByProject' => $this->itemsByProject($projects),
            'commonItems'    => Setting::itemsForContractType(null), // fallback jab koi project na chuna ho
            'commonUnits'    => Setting::commonUnits(),
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

    /**
     * Har project ke liye cheezon ke suggest hone wale naam: project id => naam.
     *
     * Is safe par project dropdown se chunna parta hai, is liye list render ke
     * waqt fix nahi ki ja sakti. Interior wale project aur Gray Structure wale
     * project ki cheezein ek jaisi nahi hotin, to poora naqsha bhej dete hain
     * aur form chunav badalne par usi project ki list dikha deta hai.
     *
     * Kharcha: Project::itemSuggestions() har project par 3 queries leta hai
     * (us qism ki list, common list, aur us project me pehle likhe gaye naam),
     * yani kul 1 + 3N. Asli local data (1 project) par naapa gaya: 4 queries.
     * Ek hi grouped query wala tareeqa 12 projects par 37 se 14 queries kar
     * deta hai, magar us me naam ka tarteeb badal jata hai (ek hi tareekh wali
     * rows ka aapas ka order badal jata hai), aur model ka dedupe wala hisaab
     * do jagah likhna parta hai. Project itne nahi hote ke ye sauda theek ho,
     * is liye hisaab ek hi jagah (model me) rehne diya hai. Projects bohat
     * barh jayen to behtar ilaaj Setting::get() ko ek dafa parhna hai, kyunke
     * 3 me se 2 queries wohi dohrai hui settings reads hain.
     *
     * @param  \Illuminate\Support\Collection<int, Project>  $projects
     * @return array<int, array<int, string>>
     */
    private function itemsByProject($projects): array
    {
        return $projects->mapWithKeys(fn (Project $p) => [$p->id => $p->itemSuggestions()])->all();
    }

    private function validatedData(Request $request): array
    {
        $v = $request->validate([
            'project_id'    => ['nullable', 'exists:projects,id'],
            'vendor_id'     => ['nullable', 'exists:vendors,id'],
            'driver_id'     => ['nullable', 'exists:vendors,id'],
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

        // Yahan jo paisa likha ja raha hai wo SUPPLIER ko diya gaya hai, is
        // liye us ki hadd poora bill nahi balki supplier ka hissa hai. Jab
        // driver chuna ho to kiraya driver ke khaate me jata hai (dekho
        // MaterialPurchase::supplierBillPaisa()), is liye wo is hadd me se
        // nikal jata hai. Driver ko kiraya alag se "Kiraya do" se diya jata hai.
        $driverId     = $v['driver_id'] ?? null;
        $supplierBill = $amount - ($driverId ? $freight : 0);

        // Pehle yahan min($paid, $amount) tha. Us ka natija ye nikalta tha ke
        // agar edit me kiraya ya mazdoori kam kar di jaye to pehle se likhi
        // hui payment chup chaap ghata di jati thi, yani diya hua paisa kahin
        // ghayab ho jata. Ab saaf mana karte hain, maalik khud faisla kare.
        if ($paid > $supplierBill) {
            throw ValidationException::withMessages([
                'amount_paid' => $driverId
                    ? 'Supplier ko diya hua paisa us ke hisse se zyada nahi ho sakta. Supplier ka hissa (bill '
                        . Money::format($amount) . ' minus kiraya ' . Money::format($freight) . ') = '
                        . Money::format($supplierBill) . ' aur aap ' . Money::format($paid)
                        . ' daal rahe hain. Kiraya driver ke khaate me jata hai, wo "Kiraya do" se dena hai.'
                    : 'Diya hua paisa bill se zyada nahi ho sakta. Bill (maal + kiraya + mazdoori + deegar) = '
                        . Money::format($amount) . ' aur aap ' . Money::format($paid)
                        . ' daal rahe hain. Pehle qeemat ya charges theek karo, warna di hui raqam kam karo.',
            ]);
        }

        return [
            'project_id'          => $v['project_id'] ?? null,
            'vendor_id'           => $v['vendor_id'] ?? null,
            // Kiraya kis dale wale ko gaya. Alag column hai, is liye material
            // vs sub-contractor ki taqseem (jo vendor_id par lagti hai) is se
            // nahi hilti. NULL = koi driver nahi.
            'driver_id'           => $v['driver_id'] ?? null,
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
