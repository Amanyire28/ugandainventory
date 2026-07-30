@extends('layouts.app')

@section('title', 'System Audit Logs')

@section('page-title')
    <a href="{{ route('settings.index') }}" class="text-indigo-600 hover:text-indigo-900 mr-2">
        <i class="fas fa-arrow-left"></i>
    </a>
    <i class="fas fa-history text-indigo-600 mr-2"></i>System Audit Logs
@endsection

@section('content')
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
                        <td class="px-6 py-4 text-xs text-gray-600 max-w-md">
                            @if($log->old_values)
                                <pre class="bg-gray-50 p-2 rounded block whitespace-pre-wrap break-all max-h-32 overflow-y-auto font-mono text-[10px] leading-relaxed border border-gray-100">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-600 max-w-md">
                            @if($log->new_values)
                                <pre class="bg-gray-50 p-2 rounded block whitespace-pre-wrap break-all max-h-32 overflow-y-auto font-mono text-[10px] leading-relaxed border border-gray-100">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
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
