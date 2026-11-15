<?php

namespace App\Http\Controllers;

use App\Models\DriverPayment;
use App\Models\Project;
use App\Models\Vendor;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Driver ko kiraya dena. Mazdoor ki wage payments ka theek wahi jorr:
 * banta hai = us driver ke chakkaron ka kul kiraya, diya = ye table,
 * baqi = dono ka farq (Vendor::driverPayablePaisa()).
 */
class DriverPaymentController extends Controller
{
    private function rules(): array
    {
        return [
            'project_id'     => ['nullable', 'exists:projects,id'],
            'date'           => ['required', 'date'],
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'in:cash,bank,cheque,online'],
            'override'       => ['nullable', 'boolean'],
            'notes'          => ['nullable', 'string'],
        ];
    }

    public function store(Request $request, Vendor $vendor)
    {
        if ($vendor->type !== 'driver') {
            return back()->with('error', $vendor->name . ' driver nahi hai, is liye kiraya is khaate me nahi ja sakta.');
        }

        $v      = $request->validate($this->rules());
        $amount = Money::toPaisa($v['amount']);
        $baqi   = $vendor->driverPayablePaisa();

        $this->guardOverpay($amount, $baqi, $vendor, $request->boolean('override'));

        $pay = DriverPayment::create([
            'driver_id'      => $vendor->id,
            'project_id'     => $v['project_id'] ?? null,
            'date'           => $v['date'],
            'amount_paisa'   => $amount,
            'payment_method' => $v['payment_method'] ?? 'cash',
            'notes'          => $v['notes'] ?? null,
        ]);

        return redirect()->route('vendors.show', $vendor)
            ->with('status', $vendor->name . ' ko ' . Money::format($amount) . ' kiraya de dia.')
            ->with('voucher_url', route('vouchers.driver-payment', $pay))
            ->with('voucher_label', '🖨 Kiraya Voucher');
    }

    public function edit(DriverPayment $driverPayment)
    {
        $driver = $driverPayment->driver()->withTrashed()->firstOrFail();

        return view('drivers.edit-payment', [
            'payment'  => $driverPayment,
            'driver'   => $driver,
            'projects' => Project::orderBy('name')->get(['id', 'name']),
            // Isi entry ko wapis jama kar ke baqi, warna form par likha number
            // us cap se mail nahi khata jo update() lagata hai.
            'capPaisa' => $driver->driverPayablePaisa() + (int) $driverPayment->amount_paisa,
        ]);
    }

    public function update(Request $request, DriverPayment $driverPayment)
    {
        $v      = $request->validate($this->rules());
        $amount = Money::toPaisa($v['amount']);
        $driver = $driverPayment->driver()->withTrashed()->firstOrFail();

        // Yahan wahi trap hai jo mazdoor ki wage payment me tha:
        // driverPayablePaisa() me is row ki raqam PEHLE SE ghat chuki hoti hai,
        // is liye usay jyun ka tyun cap banaya jaye to apni hi raqam par save
        // karna "zyada de diya" kehkar reject ho jata. Pehle is row ko wapis
        // jama karte hain, phir cap banate hain.
        $baqi = $driver->driverPayablePaisa() + (int) $driverPayment->amount_paisa;

        $this->guardOverpay($amount, $baqi, $driver, $request->boolean('override'), true);

        $driverPayment->update([
            'project_id'     => $v['project_id'] ?? null,
            'date'           => $v['date'],
            'amount_paisa'   => $amount,
            'payment_method' => $v['payment_method'] ?? 'cash',
            'notes'          => $v['notes'] ?? null,
        ]);

        return redirect()->route('vendors.show', $driver)
            ->with('status', 'Kiraya ki entry update ho gayi.')
            ->with('voucher_url', route('vouchers.driver-payment', $driverPayment))
            ->with('voucher_label', '🖨 Kiraya Voucher');
    }

    public function destroy(DriverPayment $driverPayment)
    {
        $driverPayment->delete();

        return back()->with('status', 'Kiraya ki entry hata di.');
    }

    private function guardOverpay(int $amount, int $baqi, Vendor $driver, bool $override, bool $editing = false): void
    {
        if ($amount <= $baqi || $override) {
            return;
        }

        throw ValidationException::withMessages([
            'amount' => $driver->name . ' ka baqi kiraya sirf ' . Money::format($baqi) . ' hai'
                . ($editing ? ' (is entry ko wapis jama kar ke)' : '')
                . '. Zyada dena hai to "override" check karo.',
        ]);
    }
}
