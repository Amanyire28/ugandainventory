@extends('admin.layout')
@section('title', 'System Audit Logs')

@push('styles')
<style>
    .page-header {
        display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;
    }
    .page-title {
        font-size: 1.5rem; font-weight: 800; color: #1f2937; margin: 0; display: flex; align-items: center; gap: 0.5rem;
    }
    .filters-card {
        background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 2rem;
    }
    .table-card {
        background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow-x: auto; width: 100%;
    }
    table { width: 100%; min-width: 100%; border-collapse: collapse; table-layout: fixed; }
    th {
        background: #f8fafc; padding: 1rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; border-bottom: 1px solid #e2e8f0;
    }
    td { padding: 1rem 1.5rem; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 0.875rem; vertical-align: top; word-wrap: break-word; word-break: break-word; }
    tr:last-child td { border-bottom: none; }
    tr:hover { background: #f8fafc; }
    .badge {
        padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
    }
    .badge-admin { background: #fee2e2; color: #991b1b; }
    .badge-user { background: #e0e7ff; color: #3730a3; }
    .action-badge { background: #f1f5f9; color: #475569; font-family: monospace; }
    .details-pre {
        background: #f8fafc; padding: 0.5rem; border-radius: 6px; font-size: 0.75rem; color: #475569; margin: 0;
        white-space: pre-wrap; font-family: monospace; border: 1px solid #e2e8f0; max-height: 100px; overflow-y: auto;
    }
    .diff-table {
        width: 100%; border-collapse: collapse; font-size: 0.75rem; border-radius: 6px; overflow: hidden; border: 1px solid #e2e8f0;
    }
    .diff-table th {
        background: #f1f5f9; padding: 6px 10px; font-weight: 600; color: #475569; text-transform: none; letter-spacing: normal;
    }
    .diff-table td {
        padding: 6px 10px; border-bottom: 1px solid #e2e8f0; color: #334155; font-family: monospace;
        word-break: break-all;
    }
    .diff-table tr:last-child td { border-bottom: none; }
    .diff-value-old { background: #fee2e2; color: #991b1b; }
    .diff-value-new { background: #dcfce7; color: #166534; }
    .diff-value-none { color: #94a3b8; font-style: italic; }
    .details-toggle {
        cursor: pointer;
        padding: 0.5rem 0.75rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        color: #4f46e5;
        font-weight: 600;
        font-size: 0.75rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        user-select: none;
        transition: background 0.2s;
    }
    .details-toggle:hover { background: #f1f5f9; }
    
    .expandable-row {
        display: none;
        background: #fcfcfc;
    }
    .expandable-row.open {
        display: table-row;
    }
    .diff-container {
        padding: 1rem 1.5rem;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        box-shadow: inset 0 2px 4px 0 rgb(0 0 0 / 0.02);
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1 class="page-title"><i class="fas fa-history text-indigo-600"></i> System Audit Logs</h1>
</div>

<div class="filters-card">
    <form method="GET" action="{{ route('admin.audit-logs.index') }}" style="display: flex; gap: 1rem; align-items: flex-end;">
        <div style="flex: 1;">
            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Filter by Actor</label>
            <select name="actor" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                <option value="">All Actors</option>
                <option value="admin" {{ request('actor') == 'admin' ? 'selected' : '' }}>Administrators Only</option>
                <option value="user" {{ request('actor') == 'user' ? 'selected' : '' }}>Business Users Only</option>
            </select>
        </div>
        <div style="flex: 1;">
            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Filter by Action (Keyword)</label>
            <input type="text" name="action" value="{{ request('action') }}" placeholder="e.g. create_product" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px;">
        </div>
        <div>
            <button type="submit" style="padding: 0.5rem 1.5rem; background: #4f46e5; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
                <i class="fas fa-filter"></i> Filter
            </button>
            @if(request()->hasAny(['actor', 'action']))
                <a href="{{ route('admin.audit-logs.index') }}" style="padding: 0.5rem 1rem; color: #64748b; text-decoration: none; font-weight: 600; font-size: 0.875rem;">Clear</a>
            @endif
        </div>
    </form>
</div>

<div class="table-card">
    <table style="width: 100%; table-layout: fixed;">
        <thead>
            <tr>
                <th style="width: 15%">Timestamp</th>
                <th style="width: 25%">Actor</th>
                <th style="width: 15%">Action</th>
                <th style="width: 25%">Model Affected</th>
                <th style="width: 20%">Details / Changes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td style="white-space: nowrap;">
                        <span style="font-weight: 600;">{{ $log->timestamp ? $log->timestamp->format('M d, Y') : 'N/A' }}</span><br>
                        <span style="color: #94a3b8; font-size: 0.75rem;">{{ $log->timestamp ? $log->timestamp->format('g:i:s A') : '' }}</span>
                    </td>
                    <td>
                        @if($log->admin_id && $log->admin)
                            <div style="font-weight: 700;">{{ $log->admin->name }}</div>
                            <span class="badge badge-admin">Super Admin</span>
                        @elseif($log->user_id && $log->user)
                            <div style="font-weight: 700;">{{ $log->user->name }}</div>
                            <span class="badge badge-user">User</span>
                            <div style="font-size: 0.75rem; color: #64748b; margin-top: 2px;">
                                <i class="fas fa-store"></i> {{ $log->user->business->name ?? 'Unknown Business' }}
                            </div>
                        @else
                            <span style="color: #94a3b8; font-style: italic;">Unknown Actor</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge action-badge">{{ $log->action }}</span>
                    </td>
                    <td>
                        @if($log->model)
                            <div style="font-size: 0.75rem; color: #64748b;">{{ class_basename($log->model) }}</div>
                            <div style="font-weight: 600;">ID: {{ $log->model_id ?? 'N/A' }}</div>
                        @else
                            <span style="color: #94a3b8;">-</span>
                        @endif
                    </td>
                    <td>
                        @if(!empty($log->new_values) || !empty($log->old_values))
                            @php
                                $oldVals = is_array($log->old_values) ? $log->old_values : [];
                                $newVals = is_array($log->new_values) ? $log->new_values : [];
                                
                                $allKeys = [];
                                $ignored = ['id', 'created_at', 'updated_at', 'deleted_at', 'password', 'remember_token'];
                                
                                if (!empty($oldVals) && !empty($newVals)) {
                                    // Update action: only show what changed
                                    foreach ($newVals as $k => $v) {
                                        if (!in_array($k, $ignored) && (!array_key_exists($k, $oldVals) || $oldVals[$k] != $v)) {
                                            $allKeys[] = $k;
                                        }
                                    }
                                } else {
                                    // Create or Delete action: show all non-ignored fields
                                    $rawKeys = array_unique(array_merge(array_keys($oldVals), array_keys($newVals)));
                                    $allKeys = array_filter($rawKeys, fn($k) => !in_array($k, $ignored));
                                }
                                sort($allKeys);
                            @endphp
                            <button type="button" class="details-toggle" onclick="var r = document.getElementById('details-{{ $log->id }}'); r.style.display = (r.style.display === 'none' || r.style.display === '') ? 'block' : 'none';">
                                <i class="fas fa-list-ul"></i> View {{ count($allKeys) }} Changes
                            </button>
                            
                            <div id="details-{{ $log->id }}" style="display: none; margin-top: 0.5rem; background: #f8fafc; border-radius: 6px; padding: 0.5rem; border: 1px solid #e2e8f0; max-height: 250px; overflow-y: auto;">
                                <table style="width: 100%; border-collapse: collapse; font-size: 0.75rem; font-family: monospace;">
                                    @foreach($allKeys as $key)
                                        <tr>
                                            <td style="padding: 4px; font-weight: 600; color: #475569; width: 40%; vertical-align: top;">{{ $key }}</td>
                                            <td style="padding: 4px; vertical-align: top; word-break: break-all;">
                                                @php 
                                                    $hasOld = array_key_exists($key, $oldVals);
                                                    $hasNew = array_key_exists($key, $newVals);
                                                    $oVal = $hasOld ? (is_array($oldVals[$key]) ? json_encode($oldVals[$key]) : (string)$oldVals[$key]) : 'null';
                                                    $nVal = $hasNew ? (is_array($newVals[$key]) ? json_encode($newVals[$key]) : (string)$newVals[$key]) : 'null';
                                                @endphp

                                                @if(!empty($oldVals) && !empty($newVals))
                                                    <span class="diff-value-old" style="padding: 2px 4px; border-radius: 4px;">{{ $oVal }}</span>
                                                    <i class="fas fa-arrow-right" style="color: #94a3b8; font-size: 0.6rem; margin: 0 4px;"></i>
                                                    <span class="diff-value-new" style="padding: 2px 4px; border-radius: 4px;">{{ $nVal }}</span>
                                                @elseif(!empty($newVals))
                                                    <span class="diff-value-new" style="padding: 2px 4px; border-radius: 4px;">{{ $nVal }}</span>
                                                @elseif(!empty($oldVals))
                                                    <span class="diff-value-old" style="padding: 2px 4px; border-radius: 4px;">{{ $oVal }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        @else
                            <span style="color: #94a3b8; font-size: 0.75rem;">No detailed changes.</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 3rem; color: #94a3b8;">
                        <i class="fas fa-inbox text-4xl mb-3" style="color: #cbd5e1;"></i>
                        <p style="margin: 0; font-weight: 600;">No audit logs found matching your criteria.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
    @if($logs->hasPages())
        <div style="padding: 1rem 1.5rem; border-top: 1px solid #e2e8f0; background: #f8fafc;">
            {{ $logs->links() }}
        </div>
    @endif
</div>

@endsection
