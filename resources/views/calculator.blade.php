<x-app-layout>
    <x-slot name="title">Estimate Calculator</x-slot>
    <x-slot name="header">Estimate Calculator (تخمینہ کیلکولیٹر)</x-slot>

    <p class="mb-4 text-sm text-gray-500">Theka lene se pehle yahan se kharch ka andaza nikalo — maal, mazdoor, diesel sab daalo. Ye sirf calculator hai (save nahi hota). Print kar sakte ho.</p>

    <div x-data="calc()" class="space-y-6">
        {{-- Contract target --}}
        <x-card title="Contract / Quote (ٹھیکہ مالیت)">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Covered Area (sq.ft)</label>
                    <input type="number" x-model.number="area" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="e.g. 1500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Rate / sq.ft (₨)</label>
                    <input type="number" x-model.number="rate" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="e.g. 2200">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Contract Value (auto = area × rate, ya manual)</label>
                    <div class="relative mt-1">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">₨</span>
                        <input type="number" x-model.number="contractManual" :placeholder="fmt(area*rate)" class="block w-full rounded-md border-gray-300 pl-8 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                </div>
            </div>
        </x-card>

        {{-- Material --}}
        <x-card title="Material (مٹیریل)" class="!p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-3 py-2">Item (چیز)</th><th class="px-3 py-2 w-24">Qty</th><th class="px-3 py-2 w-24">Unit</th><th class="px-3 py-2 w-32">Rate (₨)</th><th class="px-3 py-2 w-36 text-right">Amount</th><th class="w-10"></th></tr></thead>
                    <tbody>
                        <template x-for="(r,i) in material" :key="i">
                            <tr class="border-b border-gray-100">
                                <td class="px-3 py-2"><input x-model="r.name" placeholder="Cement, Sarya…" class="w-full rounded border-gray-300 text-sm"></td>
                                <td class="px-3 py-2"><input type="number" x-model.number="r.qty" class="w-full rounded border-gray-300 text-sm"></td>
                                <td class="px-3 py-2"><input x-model="r.unit" placeholder="bag" class="w-full rounded border-gray-300 text-sm"></td>
                                <td class="px-3 py-2"><input type="number" x-model.number="r.rate" class="w-full rounded border-gray-300 text-sm"></td>
                                <td class="px-3 py-2 text-right font-medium" x-text="fmt((r.qty||0)*(r.rate||0))"></td>
                                <td class="px-3 py-2 text-center"><button type="button" @click="material.splice(i,1)" class="text-red-400 hover:text-red-600">✕</button></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between gap-3 border-t border-gray-100 p-3">
                <div class="flex items-center gap-3">
                    <button type="button" @click="material.push({name:'',qty:0,unit:'',rate:0})" class="rounded-md bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700 hover:bg-emerald-100">+ Row (لائن)</button>
                    <label class="flex items-center gap-1 text-xs text-gray-500">Wastage % <input type="number" x-model.number="wastage" class="w-16 rounded border-gray-300 text-sm"></label>
                </div>
                <div class="text-sm">Material total: <span class="font-bold text-amber-600" x-text="fmt(materialTotal)"></span></div>
            </div>
        </x-card>

        {{-- Labour --}}
        <x-card title="Labour / Mazdoor (مزدوری)" class="!p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-3 py-2">Role (کام)</th><th class="px-3 py-2 w-24"># Workers</th><th class="px-3 py-2 w-24">Days</th><th class="px-3 py-2 w-32">Daily Wage (₨)</th><th class="px-3 py-2 w-36 text-right">Amount</th><th class="w-10"></th></tr></thead>
                    <tbody>
                        <template x-for="(r,i) in labour" :key="i">
                            <tr class="border-b border-gray-100">
                                <td class="px-3 py-2"><input x-model="r.name" placeholder="Mistri, Mazdoor…" class="w-full rounded border-gray-300 text-sm"></td>
                                <td class="px-3 py-2"><input type="number" x-model.number="r.count" class="w-full rounded border-gray-300 text-sm"></td>
                                <td class="px-3 py-2"><input type="number" x-model.number="r.days" class="w-full rounded border-gray-300 text-sm"></td>
                                <td class="px-3 py-2"><input type="number" x-model.number="r.wage" class="w-full rounded border-gray-300 text-sm"></td>
                                <td class="px-3 py-2 text-right font-medium" x-text="fmt((r.count||0)*(r.days||0)*(r.wage||0))"></td>
                                <td class="px-3 py-2 text-center"><button type="button" @click="labour.splice(i,1)" class="text-red-400 hover:text-red-600">✕</button></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between border-t border-gray-100 p-3">
                <button type="button" @click="labour.push({name:'',count:1,days:1,wage:0})" class="rounded-md bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700 hover:bg-emerald-100">+ Row (لائن)</button>
                <div class="text-sm">Labour total: <span class="font-bold text-indigo-600" x-text="fmt(labourTotal)"></span></div>
            </div>
        </x-card>

        {{-- Other --}}
        <x-card title="Other — diesel, transport, equipment (دیگر اخراجات)" class="!p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-3 py-2">Item (چیز)</th><th class="px-3 py-2 w-24">Qty</th><th class="px-3 py-2 w-32">Rate (₨)</th><th class="px-3 py-2 w-36 text-right">Amount</th><th class="w-10"></th></tr></thead>
                    <tbody>
                        <template x-for="(r,i) in other" :key="i">
                            <tr class="border-b border-gray-100">
                                <td class="px-3 py-2"><input x-model="r.name" placeholder="Diesel, Crane…" class="w-full rounded border-gray-300 text-sm"></td>
                                <td class="px-3 py-2"><input type="number" x-model.number="r.qty" class="w-full rounded border-gray-300 text-sm"></td>
                                <td class="px-3 py-2"><input type="number" x-model.number="r.rate" class="w-full rounded border-gray-300 text-sm"></td>
                                <td class="px-3 py-2 text-right font-medium" x-text="fmt((r.qty||0)*(r.rate||0))"></td>
                                <td class="px-3 py-2 text-center"><button type="button" @click="other.splice(i,1)" class="text-red-400 hover:text-red-600">✕</button></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between border-t border-gray-100 p-3">
                <button type="button" @click="other.push({name:'',qty:1,rate:0})" class="rounded-md bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700 hover:bg-emerald-100">+ Row (لائن)</button>
                <div class="text-sm">Other total: <span class="font-bold text-sky-600" x-text="fmt(otherTotal)"></span></div>
            </div>
        </x-card>

        {{-- Summary --}}
        <div class="sticky bottom-0 rounded-xl bg-gray-900 p-5 text-white shadow-lg">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div><div class="text-xs text-gray-400">Total Expense (کل خرچ)</div><div class="text-xl font-bold text-amber-400" x-text="fmt(grandTotal)"></div></div>
                <div><div class="text-xs text-gray-400">Contract / Quote</div><div class="text-xl font-bold text-sky-300" x-text="fmt(contractValue)"></div></div>
                <div><div class="text-xs text-gray-400">Profit (منافع)</div><div class="text-xl font-bold" :class="profit<0?'text-red-400':'text-emerald-400'" x-text="fmt(profit)"></div></div>
                <div><div class="text-xs text-gray-400">Margin %</div><div class="text-xl font-bold" :class="profit<0?'text-red-400':'text-emerald-400'" x-text="(contractValue>0 ? (profit/contractValue*100).toFixed(1) : '0') + '%'"></div></div>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-4 border-t border-gray-700 pt-3 text-xs text-gray-300">
                <span x-show="area>0">Cost / sq.ft: <span class="font-semibold text-white" x-text="fmt(area>0 ? grandTotal/area : 0)"></span></span>
                <span class="flex items-center gap-1">Desired profit %: <input type="number" x-model.number="targetMargin" class="w-16 rounded border-0 bg-gray-700 px-2 py-1 text-white"></span>
                <span x-show="targetMargin>0">Suggested quote: <span class="font-semibold text-emerald-300" x-text="fmt(grandTotal/(1-targetMargin/100))"></span></span>
                <span class="flex-1"></span>
                <button type="button" @click="window.print()" class="rounded-md bg-white px-4 py-1.5 font-medium text-gray-800 hover:bg-gray-100">🖨 Print</button>
            </div>
        </div>
    </div>

    @push('head')
    <script>
        function calc(){
            return {
                area: 1500, rate: 2200, contractManual: null, wastage: 5, targetMargin: 0,
                material: [{name:'Cement', qty:600, unit:'bag', rate:1300},{name:'Sarya / Steel', qty:6000, unit:'kg', rate:290},{name:'Bricks', qty:40000, unit:'nos', rate:22}],
                labour: [{name:'Mistri', count:2, days:120, wage:1800},{name:'Mazdoor', count:4, days:120, wage:1100}],
                other: [{name:'Diesel / Generator', qty:1, rate:60000},{name:'Transport', qty:1, rate:80000}],
                fmt(n){ return '₨ ' + Math.round(Number(n)||0).toLocaleString('en-PK'); },
                get materialTotal(){ return this.material.reduce((s,r)=>s+(r.qty||0)*(r.rate||0),0) * (1 + (this.wastage||0)/100); },
                get labourTotal(){ return this.labour.reduce((s,r)=>s+(r.count||0)*(r.days||0)*(r.wage||0),0); },
                get otherTotal(){ return this.other.reduce((s,r)=>s+(r.qty||0)*(r.rate||0),0); },
                get grandTotal(){ return this.materialTotal + this.labourTotal + this.otherTotal; },
                get contractValue(){ return this.contractManual || (this.area*this.rate) || 0; },
                get profit(){ return this.contractValue - this.grandTotal; },
            }
        }
    </script>
    @endpush
</x-app-layout>
