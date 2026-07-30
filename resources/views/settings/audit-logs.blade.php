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
    $formatLog = function($action, $values) {
        if (!$values) {
            return '-';
        }
        if (!is_array($values)) {
            return $values;
        }

        // 1. For sales/invoices/payments/transfers
        if (str_contains($action, 'sale') || str_contains($action, 'invoice') || str_contains($action, 'payment') || str_contains($action, 'transfer')) {
            $total = $values['total'] ?? $values['amount'] ?? $values['subtotal'] ?? null;
            $ref = $values['sale_number'] ?? $values['invoice_number'] ?? $values['transfer_number'] ?? $values['reference'] ?? null;
            $method = $values['payment_method'] ?? null;
            
            $parts = [];
            if ($total !== null) $parts[] = 'Total: UGX ' . number_format($total, 0);
            if ($method) $parts[] = '(' . ucfirst($method) . ')';
            if ($ref) $parts[] = '[' . $ref . ']';
            
            return count($parts) > 0 ? implode(' ', $parts) : '-';
        }

        // 2. For price adjustment
        if (str_contains($action, 'price') || str_contains($action, 'rate')) {
            $price = $values['selling_price'] ?? $values['cost_price'] ?? null;
            if ($price !== null) {
                return 'Price: UGX ' . number_format($price, 0);
            }
        }

        // 3. For stock adjustment/reconciliation/transfers (quantity)
        if (str_contains($action, 'stock') || str_contains($action, 'quantity') || str_contains($action, 'reconciliation')) {
            $qty = $values['quantity'] ?? $values['qty'] ?? null;
            if ($qty !== null) {
                return 'Qty: ' . number_format($qty, 0);
            }
        }

        // 4. Default fallback: show top 2 attributes as a single line
        $parts = [];
        $ignored = ['business_id', 'location_id', 'user_id', 'id', 'created_at', 'updated_at', 'role_id', 'slug', 'image', 'is_system_role', 'password', 'remember_token', 'email_verified_at', 'business_category_id'];
        foreach ($values as $k => $v) {
            if (in_array($k, $ignored) || is_null($v) || is_array($v) || is_object($v)) continue;
            if (is_numeric($v) && (str_contains($k, 'price') || str_contains($k, 'total') || str_contains($k, 'amount') || str_contains($k, 'discount') || str_contains($k, 'debit') || str_contains($k, 'credit') || str_contains($k, 'balance'))) {
                $v = 'UGX ' . number_format($v, 0);
            }
            $parts[] = ucwords(str_replace('_', ' ', $k)) . ': ' . $v;
            if (count($parts) >= 2) break;
        }
        return count($parts) > 0 ? implode(', ', $parts) : '-';
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target Model</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Old Values</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">New Values</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($logs as $log)
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
                                {{ strtoupper($log->action) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ class_basename($log->model) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-mono">
                            {{ $log->model_id ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700 font-mono whitespace-nowrap">
                            {{ $formatLog($log->action, $log->old_values) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 font-semibold font-mono whitespace-nowrap">
                            {{ $formatLog($log->action, $log->new_values) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-gray-400">
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
