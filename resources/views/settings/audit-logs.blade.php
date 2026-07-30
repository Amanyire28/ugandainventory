@extends('layouts.app')

@section('title', 'System Audit Logs')

@section('page-title')
    <a href="{{ route('settings.index') }}" class="text-indigo-600 hover:text-indigo-900 mr-2">
        <i class="fas fa-arrow-left"></i>
    </a>
    <i class="fas fa-history text-indigo-600 mr-2"></i>System Audit Logs
@endsection

@section('content')
@php
    $getAuditDetails = function($log) {
        $action = $log->action;
        $old = $log->old_values;
        $new = $log->new_values;
        
        $ref = '-';
        $oldVal = '-';
        $newVal = '-';
        $amount = '-';
        $method = '-';

        // 1. Resolve Reference (Sale Number, Invoice Number, Product Name/SKU, etc.)
        if (is_array($new)) {
            $ref = $new['sale_number'] ?? $new['invoice_number'] ?? $new['transfer_number'] ?? $new['name'] ?? $new['sku'] ?? '-';
        } elseif (is_array($old)) {
            $ref = $old['sale_number'] ?? $old['invoice_number'] ?? $old['transfer_number'] ?? $old['name'] ?? $old['sku'] ?? '-';
        }

        // 2. Resolve Amount & Payment Method for transactions
        if (str_contains($action, 'sale') || str_contains($action, 'invoice') || str_contains($action, 'payment')) {
            $valArray = is_array($new) ? $new : (is_array($old) ? $old : []);
            if (isset($valArray['total'])) {
                $amount = 'UGX ' . number_format($valArray['total'], 0);
            } elseif (isset($valArray['amount'])) {
                $amount = 'UGX ' . number_format($valArray['amount'], 0);
            }
            if (isset($valArray['payment_method'])) {
                $method = ucfirst($valArray['payment_method']);
            }
        }

        // 3. Resolve Old & New Values for adjustments (like price, stock, status)
        if (is_array($old) && is_array($new)) {
            // Find what changed
            $changedKeys = array_keys(array_diff_assoc($new, $old));
            // Filter out system keys
            $ignored = ['business_id', 'location_id', 'user_id', 'id', 'created_at', 'updated_at', 'role_id', 'slug', 'image', 'is_system_role', 'password', 'remember_token', 'email_verified_at', 'business_category_id'];
            $key = collect($changedKeys)->reject(fn($k) => in_array($k, $ignored))->first();
            
            if ($key) {
                $o = $old[$key] ?? '-';
                $n = $new[$key] ?? '-';
                
                // Format price / stock numbers
                if (is_numeric($o) && is_numeric($n)) {
                    if (str_contains($key, 'price') || str_contains($key, 'amount') || str_contains($key, 'total')) {
                        $oldVal = 'UGX ' . number_format($o, 0);
                        $newVal = 'UGX ' . number_format($n, 0);
                    } elseif (str_contains($key, 'quantity') || str_contains($key, 'qty') || str_contains($key, 'stock')) {
                        $oldVal = number_format($o, 0) . ' pcs';
                        $newVal = number_format($n, 0) . ' pcs';
                    } else {
                        $oldVal = $o;
                        $newVal = $n;
                    }
                } else {
                    $oldVal = is_array($o) ? json_encode($o) : $o;
                    $newVal = is_array($n) ? json_encode($n) : $n;
                }
            }
        } elseif (is_array($new)) {
            // Creation
            $oldVal = '-';
            if (isset($new['status'])) {
                $newVal = ucfirst($new['status']);
            } elseif (isset($new['is_active'])) {
                $newVal = $new['is_active'] ? 'Active' : 'Inactive';
            } elseif (isset($new['selling_price'])) {
                $newVal = 'UGX ' . number_format($new['selling_price'], 0);
            } else {
                $newVal = 'Created';
            }
        }

        return compact('ref', 'oldVal', 'newVal', 'amount', 'method');
    };
@endphp

<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Audit Trail</h2>
            <p class="text-gray-600 text-sm mt-0.5">Track deletes, price adjustments, stock reconciliation, and other critical transaction updates.</p>
        </div>
    </div>

    <!-- Audit Log Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference / Item</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Old Value</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">New Value</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($logs as $log)
                    @php
                        $details = $getAuditDetails($log);
                    @endphp
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                            {{ $log->timestamp->format('Y-m-d H:i:s') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-semibold">
                            {{ $log->user ? $log->user->name : 'System' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 text-xs font-bold rounded-full 
                                @if (str_contains($log->action, 'delete') || str_contains($log->action, 'void'))
                                    bg-red-100 text-red-800
                                @elseif (str_contains($log->action, 'create'))
                                    bg-green-100 text-green-800
                                @elseif (str_contains($log->action, 'adjustment') || str_contains($log->action, 'reconciliation'))
                                    bg-amber-100 text-amber-800
                                @else
                                    bg-indigo-100 text-indigo-800
                                @endif">
                                {{ strtoupper(str_replace('_', ' ', $log->action)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-semibold font-mono">
                            {{ $details['ref'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                            {{ $details['oldVal'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold font-mono">
                            {{ $details['newVal'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-emerald-700 font-bold font-mono">
                            {{ $details['amount'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">
                            @if($details['method'] !== '-')
                                <span class="px-2 py-0.5 bg-gray-100 rounded text-gray-800 text-xs font-semibold">
                                    {{ $details['method'] }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-gray-400">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-history text-4xl text-gray-300 mb-2"></i>
                                <p class="text-sm font-medium">No audit logs found.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $logs->links() }}
    </div>
</div>
@endsection
