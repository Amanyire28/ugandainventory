@extends('layouts.app')

@section('title', 'Stock Taking')

@section('page-title')
    <i class="fas fa-list-check text-indigo-600 mr-2"></i>Stock Verification & Reconciliation
@endsection

@section('content')
<div class="space-y-6">

    @if(session('success'))
    <div class="p-4 bg-green-50 border-l-4 border-green-500 rounded-lg text-green-800 flex items-center space-x-2">
        <i class="fas fa-check-circle text-green-500"></i>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-lg text-red-800 flex items-center space-x-2">
        <i class="fas fa-exclamation-circle text-red-500"></i>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    <!-- Create New Session Button -->
    <div class="flex justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <div>
            <h2 class="text-lg font-bold text-gray-800">Physical Stock Count Sessions</h2>
            <p class="text-xs text-gray-500">Start a counting sheet to record physical shelf counts and identify discrepancies.</p>
        </div>
        <form method="POST" action="{{ route('stock-taking.create-session') }}" class="inline">
            @csrf
            <button type="submit" class="flex items-center space-x-2 px-6 py-2.5 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 shadow-sm transition">
                <i class="fas fa-plus"></i>
                <span>Start Stock Taking</span>
            </button>
        </form>
    </div>

    <!-- Active Sessions -->
    @php
        $activeSessions = $sessions->where('status', 'active');
    @endphp

    @if($activeSessions->count() > 0)
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 shadow-sm">
        <h3 class="text-lg font-bold text-yellow-900 mb-4 flex items-center">
            <i class="fas fa-spinner fa-spin text-yellow-600 mr-2"></i>Active Counting Sheets
        </h3>
        <div class="space-y-3">
            @foreach($activeSessions as $session)
            <div class="flex items-center justify-between bg-white p-4 rounded-lg border border-yellow-200 shadow-sm hover:border-yellow-300 transition">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="font-bold text-gray-900">Session #{{ $session->id }}</span>
                        <span class="text-xs px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded font-semibold">Active</span>
                    </div>
                    <p class="text-xs text-gray-600 mt-1">Initiated: {{ $session->session_date->format('M d, Y \a\t H:i A') }} by {{ $session->initiator->name }}</p>
                    @if($session->notes)
                    <p class="text-xs text-gray-700 mt-1.5 italic bg-gray-50 p-2 rounded">"{{ $session->notes }}"</p>
                    @endif
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('stock-taking.session', $session->id) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold text-sm transition">
                        <i class="fas fa-pencil mr-1"></i>Continue Count
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Past Sessions History -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
        <h3 class="text-xl font-bold text-gray-800 mb-6">
            <i class="fas fa-history text-indigo-600 mr-2"></i>Verification & Session History
        </h3>

        @if($sessions->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b-2 border-gray-200 bg-gray-50">
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Session ID</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Started</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Linked Accounting Period</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Initiated By</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Items Counted</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Discrepancy Summary</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($sessions as $session)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-4 font-bold text-indigo-600">
                            #{{ $session->id }}
                        </td>
                        <td class="px-4 py-4">
                            <span class="font-semibold text-gray-900">{{ $session->session_date->format('M d, Y') }}</span>
                            <div class="text-xs text-gray-400">{{ $session->session_date->format('h:i A') }}</div>
                        </td>
                        <td class="px-4 py-4">
                            <span class="px-2.5 py-1 text-xs font-bold rounded-full uppercase tracking-wider
                                @if($session->status === 'active')
                                    bg-yellow-100 text-yellow-800
                                @elseif($session->status === 'closed')
                                    bg-green-100 text-green-800
                                @else
                                    bg-gray-100 text-gray-800
                                @endif">
                                {{ $session->status }}
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            @if($session->period_month)
                                <span class="text-purple-700 font-bold bg-purple-50 px-2 py-1 rounded border border-purple-100">
                                    <i class="fas fa-lock mr-1"></i>{{ Carbon\Carbon::parse($session->period_month)->format('F Y') }}
                                </span>
                            @elseif($session->status === 'closed')
                                <a href="{{ route('inventory.periods') }}" class="text-amber-700 font-bold bg-amber-50 px-2 py-1 rounded border border-amber-100 hover:bg-amber-100 inline-block">
                                    <i class="fas fa-exclamation-triangle mr-1 text-amber-500"></i>Reconcile Month Close
                                </a>
                            @else
                                <span class="text-gray-400 italic">Session still active</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-gray-600">
                            {{ $session->initiator->name }}
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="px-2.5 py-1 bg-indigo-50 border border-indigo-100 text-indigo-800 rounded-lg font-bold">
                                {{ $session->adjustments->count() }} products
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            @php
                                $variances = $session->adjustments->where('variance', '!=', 0);
                                $positiveVar = $session->adjustments->where('variance', '>', 0)->sum('variance');
                                $negativeVar = abs($session->adjustments->where('variance', '<', 0)->sum('variance'));
                            @endphp
                            <div class="space-y-1 text-xs">
                                @if($variances->count() > 0)
                                    @if($positiveVar > 0)
                                        <div class="flex items-center text-green-700 font-bold">
                                            <i class="fas fa-arrow-up mr-1 text-green-500"></i> Stock Gain: +{{ number_format($positiveVar, 1) }} units
                                        </div>
                                    @endif
                                    @if($negativeVar > 0)
                                        <div class="flex items-center text-red-700 font-bold">
                                            <i class="fas fa-arrow-down mr-1 text-red-500"></i> Stock Loss: -{{ number_format($negativeVar, 1) }} units
                                        </div>
                                    @endif
                                @else
                                    <span class="text-gray-400 flex items-center">
                                        <i class="fas fa-check-circle text-green-500 mr-1"></i> Exact match (no variances)
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <a href="{{ route('stock-taking.session', $session->id) }}" class="text-indigo-600 hover:text-indigo-800 font-bold flex items-center justify-center space-x-1 hover:underline">
                                <i class="fas fa-eye"></i>
                                <span>Details</span>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $sessions->links() }}
        </div>
        @else
        <div class="text-center py-12 text-gray-500">
            <i class="fas fa-clipboard-list text-gray-300 text-5xl mb-4"></i>
            <p class="text-lg font-semibold">No stock taking sessions recorded yet</p>
            <p class="text-sm text-gray-400 mt-1">Start your first stock-take sheet using the button at the top.</p>
        </div>
        @endif
    </div>

</div>
@endsection
