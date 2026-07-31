import { getDB, getStoreData, putStoreData, deleteStoreItem } from './db';
import { refreshOfflineData } from './auth';

let isSyncing = false;

export async function addToSyncQueue(type, payload, offlineUuid) {
    const db = await getDB();
    const item = {
        type,
        payload,
        offline_uuid: offlineUuid,
        timestamp: new Date().toISOString(),
        status: 'pending'
    };
    await db.put('sync_queue', item);
    
    // Dispatch event to update UI badge/banner
    window.dispatchEvent(new CustomEvent('sync-queue-updated'));
}

export async function getSyncQueueCount() {
    const db = await getDB();
    const items = await db.getAll('sync_queue');
    // Count only those that are not resolved conflicts
    return items.filter(item => item.status !== 'conflict').length;
}

export async function processSyncQueue() {
    if (isSyncing || !window.navigator.onLine) return;
    
    const token = localStorage.getItem('pwa_token');
    if (!token) return;

    isSyncing = true;
    window.dispatchEvent(new CustomEvent('sync-status-changed', { detail: 'syncing' }));

    try {
        const db = await getDB();
        let queue = await db.getAll('sync_queue');
        if (queue.length === 0) {
            isSyncing = false;
            window.dispatchEvent(new CustomEvent('sync-status-changed', { detail: 'synced' }));
            return;
        }

        const deviceUuid = localStorage.getItem('pwa_device_uuid') || 'unknown-device';
        const config = {
            headers: { 'Authorization': `Bearer ${token}` }
        };

        // ==========================================
        // 1. SYNC CUSTOMERS FIRST
        // ==========================================
        const customerItems = queue.filter(item => item.type === 'customer' && item.status === 'pending');
        if (customerItems.length > 0) {
            const customerPayloads = customerItems.map(item => ({
                ...item.payload,
                device_id: deviceUuid
            }));

            try {
                const response = await axios.post('/api/offline/sync/customers', {
                    customers: customerPayloads
                }, config);

                if (response.data.success) {
                    const mapping = response.data.mapping; // offline_uuid -> server_id
                    
                    // Remove customers from sync queue
                    for (const item of customerItems) {
                        await db.delete('sync_queue', item.id);
                    }

                    // Update sales referencing these customer UUIDs in the queue
                    queue = await db.getAll('sync_queue');
                    for (const item of queue) {
                        if (item.type === 'sale' && item.payload.customer_offline_uuid) {
                            const newId = mapping[item.payload.customer_offline_uuid];
                            if (newId) {
                                item.payload.customer_id = newId;
                                delete item.payload.customer_offline_uuid;
                                await db.put('sync_queue', item);
                            }
                        }
                    }
                }
            } catch (err) {
                console.error('Failed to sync offline customers:', err);
                // Keep customers in queue to retry
            }
        }

        // Fetch refreshed queue
        queue = await db.getAll('sync_queue');

        // ==========================================
        // 2. SYNC SALES
        // ==========================================
        const saleItems = queue.filter(item => item.type === 'sale' && item.status === 'pending');
        if (saleItems.length > 0) {
            const salePayloads = saleItems.map(item => item.payload);

            try {
                const response = await axios.post('/api/offline/sync/sales', {
                    sales: salePayloads,
                    device_id: deviceUuid
                }, config);

                if (response.data.success) {
                    // All sales synced successfully!
                    for (const item of saleItems) {
                        await db.delete('sync_queue', item.id);
                    }
                }
            } catch (err) {
                if (err.response && err.response.status === 409) {
                    // Conflict detected! Some or all sales had issues (stock, customer, etc.)
                    const results = err.response.data.results;
                    
                    for (const item of saleItems) {
                        const result = results[item.offline_uuid];
                        if (result) {
                            if (result.status === 'synced') {
                                await db.delete('sync_queue', item.id);
                            } else if (result.status === 'conflict') {
                                // Save to local conflicts database
                                item.status = 'conflict';
                                item.error_message = result.conflicts.map(c => c.message).join(', ');
                                await db.put('sync_queue', item); // Mark as conflict in queue
                                
                                await db.put('conflicts', {
                                    offline_uuid: item.offline_uuid,
                                    type: 'sale',
                                    payload: item.payload,
                                    conflicts: result.conflicts,
                                    timestamp: new Date().toISOString(),
                                    resolved: false
                                });
                            }
                        }
                    }
                    window.dispatchEvent(new CustomEvent('sync-conflict-detected', { detail: results }));
                } else {
                    console.error('Failed to sync offline sales:', err);
                }
            }
        }

        // Fetch refreshed queue
        queue = await db.getAll('sync_queue');

        // ==========================================
        // 3. SYNC STOCK TAKES
        // ==========================================
        const stockItems = queue.filter(item => item.type === 'stock_take' && item.status === 'pending');
        if (stockItems.length > 0) {
            const stockPayloads = stockItems.map(item => item.payload);

            try {
                const response = await axios.post('/api/offline/sync/stock-takes', {
                    sessions: stockPayloads,
                    device_id: deviceUuid
                }, config);

                if (response.data.success) {
                    const syncedUuids = response.data.synced_sessions;
                    for (const item of stockItems) {
                        if (syncedUuids.includes(item.offline_uuid)) {
                            await db.delete('sync_queue', item.id);
                        }
                    }
                }
            } catch (err) {
                console.error('Failed to sync offline stock takes:', err);
            }
        }

        // ==========================================
        // 4. INCREMENTAL DOWNLOAD REFRESH
        // ==========================================
        const lastSyncTime = localStorage.getItem('pwa_last_sync');
        await refreshOfflineData(lastSyncTime);

        isSyncing = false;
        
        // Notify complete
        const remaining = await getSyncQueueCount();
        if (remaining === 0) {
            window.dispatchEvent(new CustomEvent('sync-status-changed', { detail: 'synced' }));
        } else {
            window.dispatchEvent(new CustomEvent('sync-status-changed', { detail: 'has-conflicts' }));
        }
        
    } catch (e) {
        console.error('Sync process failed:', e);
        isSyncing = false;
        window.dispatchEvent(new CustomEvent('sync-status-changed', { detail: 'failed' }));
    }
}

// Auto-trigger sync on network reconnect
window.addEventListener('online', () => {
    processSyncQueue();
});
