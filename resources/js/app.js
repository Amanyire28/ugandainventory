import './bootstrap';

import Alpine from 'alpinejs';
import { initOfflineUI } from './offline-first/ui';
import { loginUser, logoutUser, refreshOfflineData } from './offline-first/auth';
import { processSyncQueue } from './offline-first/sync';
import { listenForUpdates, checkVersionUpdate, renderUpdateStatusWidget } from './offline-first/update-manager';
import './offline-first/pos';

console.log('Tailwind CSS + Vite PWA Loaded!');

// Expose functions globally for layout script blocks
window.loginUser = loginUser;
window.logoutUser = logoutUser;
window.refreshOfflineData = refreshOfflineData;
window.processSyncQueue = processSyncQueue;
window.renderUpdateStatusWidget = renderUpdateStatusWidget;
window.checkVersionUpdate = checkVersionUpdate;

// Helper to get or create device UUID
function getDeviceUuid() {
    let uuid = localStorage.getItem('pwa_device_uuid');
    if (!uuid) {
        if (typeof crypto.randomUUID === 'function') {
            uuid = crypto.randomUUID();
        } else {
            uuid = 'dev-' + Math.random().toString(36).substring(2, 15) + '-' + Date.now();
        }
        localStorage.setItem('pwa_device_uuid', uuid);
    }
    return uuid;
}

// Helper to get or create device name
function getDeviceName() {
    let name = localStorage.getItem('pwa_device_name');
    if (!name) {
        name = 'Device-' + getDeviceUuid().substring(0, 6).toUpperCase();
        localStorage.setItem('pwa_device_name', name);
    }
    return name;
}

// Initialize PWA modules on DOM content load
document.addEventListener('DOMContentLoaded', () => {
    initOfflineUI();
    listenForUpdates();
    
    // Auto-sync queue if online
    if (window.navigator.onLine) {
        processSyncQueue();
    }

    // Intercept login form submissions for offline-first caching
    document.addEventListener('submit', async (e) => {
        const form = e.target;
        
        // Match both admin and regular user logins
        if (form.getAttribute('action') && form.getAttribute('action').includes('/login')) {
            const emailInput = form.querySelector('input[name="email"]');
            const passwordInput = form.querySelector('input[name="password"]');
            
            if (emailInput && passwordInput) {
                const email = emailInput.value.trim();
                const password = passwordInput.value;
                const deviceUuid = getDeviceUuid();
                const deviceName = getDeviceName();

                if (!window.navigator.onLine) {
                    // Offline login
                    e.preventDefault();
                    const result = await loginUser(email, password, deviceName, deviceUuid, '1.0.0');
                    if (result.success) {
                        window.location.href = '/pos';
                    } else {
                        alert(result.message || 'Offline login failed.');
                    }
                } else {
                    // Online login - run offline cache prep before submitting the standard session form
                    e.preventDefault();
                    
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
                    
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Authenticating and caching...';
                    }
                    
                    try {
                        await loginUser(email, password, deviceName, deviceUuid, '1.0.0');
                    } catch (err) {
                        console.error('PWA Login Cache Prep failed:', err);
                    }
                    
                    // Proceed with standard web login
                    form.submit();
                }
            }
        }
    });

    // Intercept logout link/form to clear cached credentials
    document.addEventListener('click', async (e) => {
        const logoutLink = e.target.closest('a[href*="/logout"]') || e.target.closest('form[action*="/logout"] button');
        if (logoutLink) {
            e.preventDefault();
            if (confirm('Are you sure you want to logout? This will clear all offline cached data from this device.')) {
                await logoutUser();
                
                // Submit logout form or redirect to welcome
                const form = document.querySelector('form[action*="/logout"]');
                if (form) {
                    form.submit();
                } else {
                    window.location.href = '/';
                }
            }
        }
    });
});

window.Alpine = Alpine;
Alpine.start();
