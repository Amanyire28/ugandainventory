import { getSyncQueueCount, processSyncQueue } from './sync';

let bannerElement = null;

export function initOfflineUI() {
    createBannerElement();
    updateUIStatus();

    window.addEventListener('online', () => {
        updateUIStatus();
        processSyncQueue();
    });
    window.addEventListener('offline', () => {
        updateUIStatus();
    });

    window.addEventListener('sync-status-changed', (e) => {
        updateUIStatus(e.detail);
    });

    window.addEventListener('sync-queue-updated', () => {
        updateUIStatus();
    });
}

function createBannerElement() {
    if (document.getElementById('pwa-offline-banner')) return;

    bannerElement = document.createElement('header');
    bannerElement.id = 'pwa-offline-banner';
    bannerElement.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 99999;
        text-align: center;
        font-family: 'Inter', system-ui, sans-serif;
        font-size: 14px;
        font-weight: 600;
        padding: 10px 20px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: none;
        align-items: center;
        justify-content: center;
        gap: 8px;
    `;
    document.body.prepend(bannerElement);

    // Add CSS body padding adjustment when banner is visible
    const style = document.createElement('style');
    style.innerHTML = `
        body.pwa-banner-visible {
            margin-top: 40px !important;
        }
        #pwa-offline-banner .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff;
            border-bottom-color: transparent;
            border-radius: 50%;
            display: inline-block;
            box-sizing: border-box;
            animation: rotation 1s linear infinite;
        }
        @keyframes rotation {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
}

export async function updateUIStatus(syncState = null) {
    if (!bannerElement) createBannerElement();

    const isOnline = window.navigator.onLine;
    const pendingCount = await getSyncQueueCount();

    if (!isOnline) {
        // Device is offline
        bannerElement.style.display = 'flex';
        bannerElement.style.backgroundColor = '#ef4444'; // Tailwind Red 500
        bannerElement.style.color = '#ffffff';
        bannerElement.innerHTML = `
            <i class="fa-solid fa-circle-nodes"></i>
            <span>🔴 Offline Mode — You are working offline. <strong>${pendingCount}</strong> pending transaction${pendingCount !== 1 ? 's' : ''} queued.</span>
        `;
        document.body.classList.add('pwa-banner-visible');
    } else if (syncState === 'syncing') {
        // Currently synchronizing
        bannerElement.style.display = 'flex';
        bannerElement.style.backgroundColor = '#3b82f6'; // Tailwind Blue 500
        bannerElement.style.color = '#ffffff';
        bannerElement.innerHTML = `
            <span class="spinner"></span>
            <span>Syncing offline data... Please keep this page open.</span>
        `;
        document.body.classList.add('pwa-banner-visible');
    } else if (syncState === 'has-conflicts') {
        // Sync completed but has unresolved conflicts
        bannerElement.style.display = 'flex';
        bannerElement.style.backgroundColor = '#f59e0b'; // Tailwind Amber 500
        bannerElement.style.color = '#ffffff';
        bannerElement.innerHTML = `
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>⚠️ Sync completed with stock or customer conflicts. Please contact the manager.</span>
        `;
        document.body.classList.add('pwa-banner-visible');
    } else if (pendingCount > 0) {
        // Online but has queued items waiting to sync
        bannerElement.style.display = 'flex';
        bannerElement.style.backgroundColor = '#6366f1'; // Indigo 500
        bannerElement.style.color = '#ffffff';
        bannerElement.innerHTML = `
            <i class="fa-solid fa-cloud-arrow-up"></i>
            <span>Ready to Sync — <strong>${pendingCount}</strong> offline transaction${pendingCount !== 1 ? 's' : ''} to upload. <button onclick="window.processSyncQueue()" style="background: rgba(255,255,255,0.2); border: none; padding: 2px 8px; border-radius: 4px; color: white; cursor: pointer; margin-left: 10px;">Sync Now</button></span>
        `;
        document.body.classList.add('pwa-banner-visible');
        
        // Expose function globally for button
        window.processSyncQueue = processSyncQueue;
    } else if (syncState === 'synced') {
        // Just finished syncing
        bannerElement.style.display = 'flex';
        bannerElement.style.backgroundColor = '#10b981'; // Tailwind Green 500
        bannerElement.style.color = '#ffffff';
        bannerElement.innerHTML = `
            <i class="fa-solid fa-circle-check"></i>
            <span>✓ All data synchronized and up to date!</span>
        `;
        document.body.classList.add('pwa-banner-visible');

        // Hide after 4 seconds
        setTimeout(() => {
            if (window.navigator.onLine && bannerElement.style.backgroundColor === 'rgb(16, 185, 129)') {
                bannerElement.style.display = 'none';
                document.body.classList.remove('pwa-banner-visible');
            }
        }, 4000);
    } else {
        // Connected & fully synced
        bannerElement.style.display = 'none';
        document.body.classList.remove('pwa-banner-visible');
    }
}
