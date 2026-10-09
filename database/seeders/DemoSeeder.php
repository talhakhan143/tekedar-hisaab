<?php

namespace Database\Seeders;

use App\Models\ClientPayment;
use App\Models\Estimate;
use App\Models\GeneralOverhead;
use App\Models\MaterialPurchase;
use App\Models\OtherExpense;
use App\Models\Project;
use App\Models\Vendor;
use App\Models\WagePayment;
use App\Models\WorkEntry;
use App\Models\Worker;
use App\Models\WorkerAdvance;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * One full realistic theka, exactly per the acceptance test:
 * 1500 sqft full-finished @ ₨2,200/sqft.
 * 3 client payments, 10 material purchases (2 on udhaar),
 * 4 workers + attendance + 2 advances, 6 other expenses.
 */
class DemoSeeder extends Seeder
{
    /** rupees -> paisa */
    private function p(float $rupees): int
    {
        return (int) round($rupees * 100);
    }

    public function run(): void
    {
        // Everything below uses plain create(), so running this twice would
        // duplicate the whole demo set. Bail out if a project already exists.
        if (Project::query()->exists()) {
            $this->command?->warn('DemoSeeder skipped: demo data already present.');

            return;
        }

        $start = Carbon::create(2026, 3, 1);

        // ---------- Project ----------
        $area = 1500;
        $rate = $this->p(2200);
        $project = Project::create([
            'name'                 => 'DHA Phase 6 — Kothi (Full Finish)',
            'client_name'          => 'Malik Asadullah',
            'client_phone'         => '0300-1234567',
            'client_address'       => 'House 142, St 9, DHA Phase 6, Lahore',
            'contract_type'        => 'full_finished',
            'pricing_mode'         => 'per_sqft',
            'covered_area_sqft'    => $area,
            'rate_per_sqft_paisa'  => $rate,
            'contract_value_paisa' => $area * $rate, // 3,300,000
            'completion_percent'   => 55,
            'start_date'           => $start,
            'expected_end_date'    => (clone $start)->addMonths(8),
            'status'               => 'active',
            'notes'                => 'Grey + complete finishing. Client retains 7% till DLP.',
        ]);

        // ---------- Estimates (budget BEFORE work) ----------
        $estimates = [
            ['material', 'Cement (bags)', 'bag', 900, 1300, 0],
            ['material', 'Steel / Sarya', 'kg', 9000, 285, 2],
            ['material', 'Bricks', 'nos', 60000, 22, 3],
            ['material', 'Sand + Crush', 'cft', 4000, 95, 5],
            ['material', 'Tiles & Marble', 'sqft', 2200, 380, 4],
            ['material', 'Paint & Finishing', 'job', 1, 320000, 0],
            ['labour',   'Mistri + Mazdoor labour', 'job', 1, 850000, 0],
            ['subcontractor', 'Electrical + Plumbing', 'job', 1, 420000, 0],
            ['transport','Material transport', 'job', 1, 60000, 0],
            ['equipment','Mixer + scaffolding rent', 'job', 1, 70000, 0],
            ['overhead', 'Site office + permits', 'job', 1, 45000, 0],
            ['misc',     'Chai paani + misc', 'job', 1, 40000, 0],
        ];
        foreach ($estimates as [$cat, $item, $unit, $qty, $rateR, $wastage]) {
            $qtyInflated = $cat === 'material' ? $qty * (1 + $wastage / 100) : $qty;
            Estimate::create([
                'project_id'          => $project->id,
                'category'            => $cat,
                'item_name'           => $item,
                'unit'                => $unit,
                'qty_estimated'       => $qty,
                'rate_per_unit_paisa' => $this->p($rateR),
                'amount_paisa'        => $this->p($qtyInflated * $rateR),
                'wastage_percent'     => $wastage,
            ]);
        }

        // ---------- Client payments (3) ----------
        $payments = [
            // [date, gross, is_mobilization]
            [(clone $start),                 500000, true],   // peshgi / advance
            [(clone $start)->addMonths(2),  1000000, false],
            [(clone $start)->addMonths(3),  1200000, false],
        ];
        foreach ($payments as [$date, $grossR, $isMob]) {
            $gross = $this->p($grossR);
            ClientPayment::create([
                'project_id'           => $project->id,
                'date'                 => $date,
                'gross_amount_paisa'   => $gross,
                'retention_held_paisa' => 0,
                'net_received_paisa'   => $gross,
                'payment_method'       => $isMob ? 'bank' : 'cheque',
                'reference'            => $isMob ? 'MOB-ADV' : 'RB-' . $date->format('mY'),
                'is_mobilization'      => $isMob,
                'notes'                => $isMob ? 'Mobilization advance' : 'Running bill',
            ]);
        }

        // ---------- Vendors ----------
        $alFalah   = Vendor::create(['name' => 'Al-Falah Building Material', 'type' => 'supplier', 'phone' => '0321-1111111']);
        $steelKing = Vendor::create(['name' => 'Steel King Traders', 'type' => 'supplier', 'phone' => '0321-2222222']);
        $tileWorld = Vendor::create(['name' => 'Tile World', 'type' => 'supplier', 'phone' => '0321-3333333']);
        $sparkSub  = Vendor::create(['name' => 'Spark Electrical & Plumbing', 'type' => 'subcontractor', 'phone' => '0321-4444444']);

        // ---------- Material purchases (10; 2 on udhaar) ----------
        // [date offset months, vendor, item, qty, unit, rate, paidR(null=full)]
        $purchases = [
            [1, $alFalah,   'Cement (bags)', 200, 'bag', 1320, null],
            [1, $steelKing, 'Steel / Sarya', 1200, 'kg', 292, 200000],   // udhaar (partial)
            [1, $alFalah,   'Bricks', 8000, 'nos', 22, null],
            [2, $alFalah,   'Sand', 600, 'cft', 70, null],
            [2, $alFalah,   'Crush', 400, 'cft', 120, null],
            [2, $alFalah,   'Cement (bags)', 100, 'bag', 1330, null],
            [3, $tileWorld, 'Floor Tiles', 300, 'sqft', 360, null],
            [3, $tileWorld, 'Marble', 100, 'sqft', 850, 40000],          // udhaar (partial)
            [3, $alFalah,   'Paint + putty', 1, 'job', 120000, null],
            [3, $sparkSub,  'Electrical + Plumbing (sub)', 1, 'job', 410000, null],
        ];
        foreach ($purchases as [$m, $vendor, $item, $qty, $unit, $rateR, $paidR]) {
            $amount = $this->p($qty * $rateR);
            $paid = $paidR === null ? $amount : $this->p($paidR);
            MaterialPurchase::create([
                'project_id'          => $project->id,
                'vendor_id'           => $vendor->id,
                'date'                => (clone $start)->addMonths($m),
                'item_name'           => $item,
                'qty'                 => $qty,
                'unit'                => $unit,
                'rate_per_unit_paisa' => $this->p($rateR),
                'amount_paisa'        => $amount,
                'amount_paid_paisa'   => $paid,
                'balance_due_paisa'   => $amount - $paid,
            ]);
        }

        // ---------- Workers (4) ----------
        $mistri  = Worker::create(['name' => 'Ustad Ramzan',  'role' => 'mistri',      'wage_type' => 'daily', 'default_wage_paisa' => $this->p(1800), 'phone' => '0345-1111111']);
        $mazdoor = Worker::create(['name' => 'Akram Mazdoor', 'role' => 'mazdoor',     'wage_type' => 'daily', 'default_wage_paisa' => $this->p(1100), 'phone' => '0345-2222222']);
        $elec    = Worker::create(['name' => 'Bilal Electric','role' => 'electrician', 'wage_type' => 'daily', 'default_wage_paisa' => $this->p(2000), 'phone' => '0345-3333333']);
        $painter = Worker::create(['name' => 'Saleem Painter','role' => 'painter',     'wage_type' => 'daily', 'default_wage_paisa' => $this->p(1700), 'phone' => '0345-4444444']);

        // Attendance — [worker, month offset, day-count, days_present each].
        // Months 1,2,3 from start(Mar) = Apr, May, Jun. Month-3 rows give "this month" data.
        $attendance = [
            [$mistri,  1, 26, 1],
            [$mistri,  2, 26, 1],
            [$mistri,  3, 17, 1],
            [$mistri,  3, 1, 0.5],   // half day
            [$mazdoor, 1, 28, 1],
            [$mazdoor, 2, 28, 1],
            [$mazdoor, 3, 18, 1],
            [$elec,    2, 20, 1],
            [$elec,    3, 12, 1],
            [$painter, 3, 22, 1],
        ];
        foreach ($attendance as [$worker, $m, $count, $days]) {
            for ($i = 0; $i < $count; $i++) {
                WorkEntry::create([
                    'worker_id'           => $worker->id,
                    'project_id'          => $project->id,
                    'date'                => (clone $start)->addMonths($m)->addDays($i % 27),
                    'days_present'        => $days,
                    'computed_wage_paisa' => (int) round($worker->default_wage_paisa * $days),
                ]);
            }
        }

        // ---------- Worker advances (2) ----------
        WorkerAdvance::create(['worker_id' => $mistri->id, 'project_id' => $project->id, 'date' => (clone $start)->addMonths(2), 'type' => 'advance_given', 'amount_paisa' => $this->p(10000), 'notes' => 'Peshgi for Eid']);
        WorkerAdvance::create(['worker_id' => $mazdoor->id, 'project_id' => $project->id, 'date' => (clone $start)->addMonths(2)->addDays(5), 'type' => 'advance_given', 'amount_paisa' => $this->p(5000), 'notes' => 'Peshgi']);

        // A couple of wage payments
        WagePayment::create(['worker_id' => $mistri->id, 'project_id' => $project->id, 'date' => (clone $start)->addMonths(2)->endOfMonth(), 'amount_paisa' => $this->p(40000), 'period_label' => 'Month 2']);
        WagePayment::create(['worker_id' => $mazdoor->id, 'project_id' => $project->id, 'date' => (clone $start)->addMonths(2)->endOfMonth(), 'amount_paisa' => $this->p(25000), 'period_label' => 'Month 2']);

        // ---------- Other expenses (6) ----------
        $expenses = [
            ['transport',     'Material delivery trucks', 35000, 'Local transporter'],
            ['fuel',          'Generator diesel', 18000, 'PSO Pump'],
            ['equipment_rental', 'Concrete mixer rent', 22000, 'Tool Rental Co'],
            ['food_chai',     'Site chai/food', 14000, 'Local dhaba'],
            ['permits_govt',  'Map approval + permits', 30000, 'LDA'],
            ['bank_charges',  'Cheque/bank charges', 3500, 'Bank'],
        ];
        foreach ($expenses as $i => [$cat, $desc, $amtR, $paidTo]) {
            OtherExpense::create([
                'project_id'   => $project->id,
                'date'         => (clone $start)->addMonths(2 + ($i % 2)),
                'category'     => $cat,
                'description'  => $desc,
                'amount_paisa' => $this->p($amtR),
                'paid_to'      => $paidTo,
            ]);
        }


        // ---------- General overheads (monthly, not per-project) ----------
        GeneralOverhead::create(['month' => '2026-05', 'category' => 'office_rent', 'amount_paisa' => $this->p(45000)]);
        GeneralOverhead::create(['month' => '2026-05', 'category' => 'salary_draw', 'amount_paisa' => $this->p(120000)]);
        GeneralOverhead::create(['month' => '2026-06', 'category' => 'office_rent', 'amount_paisa' => $this->p(45000)]);
        GeneralOverhead::create(['month' => '2026-06', 'category' => 'marketing',   'amount_paisa' => $this->p(15000)]);
    }
}
