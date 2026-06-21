<x-app-layout>
    <x-slot name="title">Closeout · {{ $project->name }}</x-slot>
    <x-slot name="header">Project Closeout · {{ $project->name }}</x-slot>

    <div class="mb-4 flex items-center gap-2">
        <a href="{{ route('reports') }}" class="text-sm font-medium text-emerald-700 hover:underline">← Reports</a>
        <span class="flex-1"></span>
        <a href="{{ route('reports.closeout', ['project'=>$project->id,'export'=>'pdf']) }}" class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">Export PDF</a>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat label="Contract Value" :value="\App\Support\Money::format($project->contract_value_paisa)" />
        <x-stat label="Total Cost" :value="\App\Support\Money::format($f->totalAccruedCostPaisa())" color="text-amber-600" />
        <x-stat label="Final Profit" :value="\App\Support\Money::format($f->projectedProfitPaisa())" :color="$f->projectedProfitPaisa() < 0 ? 'text-red-600' : 'text-emerald-600'" />
    </div>

    <div class="mt-6">
        <x-card title="Estimate vs Actual" class="!p-0">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Category</th><th class="px-4 py-3 text-right">Estimated</th><th class="px-4 py-3 text-right">Actual</th><th class="px-4 py-3 text-right">Variance</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($est as $cat => $e)
                        @php $a = $act[$cat] ?? 0; $var = $a - $e; @endphp
                        @if($e || $a)
                            <tr class="{{ $var > 0 && $e > 0 ? 'bg-red-50' : '' }}"><td class="px-4 py-2.5 capitalize">{{ $cat }}</td><td class="px-4 py-2.5 text-right">@money($e)</td><td class="px-4 py-2.5 text-right">@money($a)</td><td class="px-4 py-2.5 text-right {{ $var > 0 ? 'text-red-600' : 'text-emerald-600' }}">@money($var)</td></tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </x-card>
    </div>
</x-app-layout>
