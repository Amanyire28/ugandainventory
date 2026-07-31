import { getSyncQueueCount } from './sync';

let activeRegistration = null;
let updatePendingActivation = false;
let checkInterval = null;

// Helper to check if the application is currently busy (active sale, printing, counting)
function isApplicationBusy() {
    // 1. POS active cart check
    if (window.cart && window.cart.length > 0) {
        return true;
    }
    // 2. POS processing check
    if (window.isProcessingSale) {
        return true;
    }
    // 3. Receipt modal open check
    const receiptModal = document.getElementById('receiptModal');
    if (receiptModal && !receiptModal.classList.contains('hidden')) {
        return true;
    }
    // 4. Void recent sales modal open check
    const voidModal = document.getElementById('f12VoidModal');
    if (voidModal && !voidModal.classList.contains('hidden')) {
        return true;
    }
    return false;
}

// Check if there are pending offline transactions
async function getPendingCounts() {
    const db = await import('./db');
    const queue = await db.getStoreData('sync_queue');
    const sales = queue.filter(item => item.type === 'sale').length;
    const customers = queue.filter(item => item.type === 'customer').length;
    const stockTakes = queue.filter(item => item.type === 'stock_take').length;
    return {
        total: queue.length,
        sales,
        customers,
        stockTakes
    };
}

// Trigger update check via Laravel Version API
export async function checkVersionUpdate() {
    if (!window.navigator.onLine) return;
    
    const token = localStorage.getItem('pwa_token');
    if (!token) return;

    try {
        const response = await axios.get('/api/offline/version', {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        
        if (response.data.success) {
            const serverVersion = response.data.version;
            const currentMetaVersion = document.querySelector('meta[name="app-version"]')?.getAttribute('content');
            
            console.log(`PWA Update Check: Server v${serverVersion} | Client v${currentMetaVersion}`);

            if (serverVersion !== currentMetaVersion) {
                // Version mismatch! Trigger Service Worker registration update
                if ('serviceWorker' in navigator) {
                    const reg = await navigator.serviceWorker.getRegistration();
                    if (reg) {
                        await reg.update();
                    }
                }
            }
        }
    } catch (e) {
        console.error('Failed to query PWA version endpoint:', e);
    }
}

// Display PWA Update Available Notification
function showUpdateNotification(reg) {
    if (document.getElementById('pwa-update-toast')) return;

    const toast = document.createElement('aside');
    toast.id = 'pwa-update-toast';
    toast.style.cssText = `
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: #1e1b4b; /* Deep Indigo */
        color: #ffffff;
        padding: 20px 24px;
        border-radius: 16px;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3), 0 10px 10px -5px rgba(0,0,0,0.2);
        z-index: 100000;
        font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
        max-width: 380px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        animation: slideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    `;

    toast.innerHTML = `
        <div style="margin-bottom: 12px; display: flex; align-items: flex-start; gap: 12px;">
            <div style="background: #4f46e5; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; shrink: 0;">
                <i class="fas fa-rocket text-yellow-300"></i>
            </div>
            <div>
                <h4 style="font-weight: 800; font-size: 15px; margin: 0 0 4px 0; color: #ffffff;">🆕 New Update Available</h4>
                <p style="margin: 0; font-size: 13px; color: #cbd5e1; line-height: 1.4;">A new version is ready. This includes bug fixes and performance improvements.</p>
            </div>
        </div>
        <div style="display: flex; gap: 8px; justify-content: flex-end;">
            <button id="pwa-later-btn" style="background: transparent; border: 1px solid rgba(255,255,255,0.2); color: #ffffff; font-weight: 600; font-size: 12px; padding: 8px 16px; border-radius: 8px; cursor: pointer; transition: all 0.2s;">Later</button>
            <button id="pwa-update-btn" style="background: #4f46e5; border: none; color: #ffffff; font-weight: 700; font-size: 12px; padding: 8px 16px; border-radius: 8px; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);">Update Now</button>
        </div>
        <style>
            @keyframes slideIn {
                from { transform: translateY(100px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            #pwa-later-btn:hover { background: rgba(255,255,255,0.1); }
            #pwa-update-btn:hover { background: #4338ca; transform: translateY(-1px); }
        </style>
    `;

    document.body.appendChild(toast);

    document.getElementById('pwa-later-btn').addEventListener('click', () => {
        toast.remove();
    });

    document.getElementById('pwa-update-btn').addEventListener('click', () => {
        toast.remove();
        handleUpdateTrigger(reg);
    });
}

// Trigger warning if pending operations exist
async function showSyncRequiredNotification(reg, pending) {
    if (document.getElementById('pwa-sync-update-toast')) return;

    const toast = document.createElement('aside');
    toast.id = 'pwa-sync-update-toast';
    toast.style.cssText = `
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: #1f2937; /* Dark Grey */
        color: #ffffff;
        padding: 20px 24px;
        border-radius: 16px;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3), 0 10px 10px -5px rgba(0,0,0,0.2);
        z-index: 100000;
        font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
        max-width: 400px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        animation: slideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    `;

    toast.innerHTML = `
        <div style="margin-bottom: 12px; display: flex; align-items: flex-start; gap: 12px;">
            <div style="background: #d97706; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; shrink: 0;">
                <i class="fas fa-wifi-slash text-yellow-100"></i>
            </div>
            <div>
                <h4 style="font-weight: 800; font-size: 15px; margin: 0 0 4px 0; color: #ffffff;">Update Available</h4>
                <p style="margin: 0; font-size: 13px; color: #cbd5e1; line-height: 1.4;">A new version is ready. The update will be installed automatically after all pending offline transactions have synchronized successfully.</p>
                <div style="margin-top: 8px; padding: 8px 12px; background: rgba(0,0,0,0.2); border-radius: 8px; font-size: 12px; font-family: monospace; color: #fbbf24;">
                    Pending Synchronization:<br>
                    • Sales: ${pending.sales}<br>
                    • Customers: ${pending.customers}<br>
                    • Stock Takes: ${pending.stockTakes}
                </div>
            </div>
        </div>
        <div style="display: flex; justify-content: flex-end;">
            <button id="pwa-sync-close-btn" style="background: rgba(255,255,255,0.1); border: none; color: #ffffff; font-weight: 600; font-size: 11px; padding: 6px 12px; border-radius: 6px; cursor: pointer;">Got It</button>
        </div>
    `;

    document.body.appendChild(toast);

    document.getElementById('pwa-sync-close-btn').addEventListener('click', () => {
        toast.remove();
    });
}

// Handle the update button click or automatic trigger
async function handleUpdateTrigger(reg) {
    activeRegistration = reg;
    const pending = await getPendingCounts();

    if (pending.total > 0) {
        // Pending data exists, defer update until sync complete
        updatePendingActivation = true;
        showSyncRequiredNotification(reg, pending);
        
        // Push sync engine upload
        if (window.navigator.onLine) {
            window.processSyncQueue();
        }
    } else {
        // Safe to activate immediately
        activateUpdate(reg);
    }
}

// Send skipWaiting command to service worker
function activateUpdate(reg) {
    const waitingWorker = reg.waiting;
    if (waitingWorker) {
        waitingWorker.postMessage({ type: 'SKIP_WAITING' });
    }
}

// Listen to version activations
export function listenForUpdates() {
    window.addEventListener('pwa-update-available', (e) => {
        const reg = e.detail;
        showUpdateNotification(reg);
    });

    // Check if the page was just updated to show a success toast
    if (localStorage.getItem('pwa_just_updated') === 'true') {
        localStorage.removeItem('pwa_just_updated');
        const activeVer = localStorage.getItem('pwa_active_version') || 'latest';
        
        // Show update success banner
        setTimeout(() => {
            const toast = document.createElement('aside');
            toast.className = 'fixed bottom-6 left-6 bg-green-500 text-white px-6 py-4 rounded-xl shadow-lg flex items-center space-x-3 z-50 animate-slideIn';
            toast.innerHTML = `
                <i class="fas fa-check-circle text-xl"></i>
                <div>
                    <p class="font-bold">✓ Application Updated Successfully</p>
                    <p class="text-xs text-green-100">You are now running Version ${activeVer}</p>
                </div>
            `;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 5000);
        }, 1000);
    }

    // Hook into sync completion
    window.addEventListener('sync-status-changed', async (e) => {
        if (e.detail === 'synced' && updatePendingActivation) {
            // Queue is now empty! Check if we can activate
            if (!isApplicationBusy()) {
                const reg = activeRegistration || (await navigator.serviceWorker.getRegistration());
                if (reg && reg.waiting) {
                    activateUpdate(reg);
                }
            }
        }
    });

    // Background interval check for updates (every 3 hours)
    if (window.navigator.onLine) {
        checkVersionUpdate();
        checkInterval = setInterval(checkVersionUpdate, 3 * 60 * 60 * 1000);
    }

    window.addEventListener('online', () => {
        checkVersionUpdate();
        if (!checkInterval) {
            checkInterval = setInterval(checkVersionUpdate, 3 * 60 * 60 * 1000);
        }
    });

    window.addEventListener('offline', () => {
        if (checkInterval) {
            clearInterval(checkInterval);
            checkInterval = null;
        }
    });
}

// Injects the Update Status Widget in the target container
export async function renderUpdateStatusWidget(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const currentVer = document.querySelector('meta[name="app-version"]')?.getAttribute('content') || '1.0.0';
    const pending = await getPendingCounts();
    const isOnline = window.navigator.onLine;

    let latestVer = currentVer;
    try {
        if (isOnline) {
            const token = localStorage.getItem('pwa_token');
            const response = await axios.get('/api/offline/version', {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            if (response.data.success) {
                latestVer = response.data.version;
            }
        }
    } catch (e) {}

    const widgetHtml = `
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 text-white font-sans">
            <h4 class="text-sm font-extrabold text-slate-400 mb-4 uppercase tracking-wider flex items-center gap-2">
                <i class="fas fa-server text-indigo-500"></i> PWA Update & Sync Dashboard
            </h4>
            <div class="grid grid-cols-2 gap-4 text-xs">
                <div class="bg-slate-950 p-3 rounded-lg border border-slate-800/60">
                    <span class="text-slate-400 block mb-1">Current Version</span>
                    <span class="text-sm font-bold text-white">${currentVer}</span>
                </div>
                <div class="bg-slate-950 p-3 rounded-lg border border-slate-800/60">
                    <span class="text-slate-400 block mb-1">Latest Version</span>
                    <span class="text-sm font-bold ${latestVer !== currentVer ? 'text-yellow-400' : 'text-emerald-400'}">${latestVer}</span>
                </div>
                <div class="bg-slate-950 p-3 rounded-lg border border-slate-800/60">
                    <span class="text-slate-400 block mb-1">Service Worker</span>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full inline-block mt-1 ${'serviceWorker' in navigator ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-red-950 text-red-400 border border-red-800'}">
                        ${'serviceWorker' in navigator ? 'Active' : 'Not Supported'}
                    </span>
                </div>
                <div class="bg-slate-950 p-3 rounded-lg border border-slate-800/60">
                    <span class="text-slate-400 block mb-1">Sync Status</span>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full inline-block mt-1 ${isOnline ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-red-950 text-red-400 border border-red-800'}">
                        ${isOnline ? 'Online' : 'Offline'}
                    </span>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-800/60 flex items-center justify-between text-xs text-slate-400">
                <span>Pending Sync Operations: <strong class="text-white">${pending.total}</strong></span>
                ${isOnline && pending.total > 0 ? `<button onclick="window.processSyncQueue()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-3 py-1.5 rounded-lg">Sync Now</button>` : ''}
            </div>
        </div>
    `;

    container.innerHTML = widgetHtml;
}
