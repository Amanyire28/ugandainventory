import { getStoreItem, putStoreData, clearAllOfflineData } from './db';

// Helper to hash password using PBKDF2 (Web Crypto API)
async function hashPassword(password, saltHex) {
    const encoder = new TextEncoder();
    const passwordBuffer = encoder.encode(password);
    
    // Convert salt from hex back to bytes
    const saltBuffer = new Uint8Array(saltHex.match(/.{1,2}/g).map(byte => parseInt(byte, 16)));
    
    const baseKey = await window.crypto.subtle.importKey(
        'raw',
        passwordBuffer,
        'PBKDF2',
        false,
        ['deriveBits', 'deriveKey']
    );
    
    const key = await window.crypto.subtle.deriveKey(
        {
            name: 'PBKDF2',
            salt: saltBuffer,
            iterations: 100000,
            hash: 'SHA-256'
        },
        baseKey,
        { name: 'AES-GCM', length: 256 },
        true,
        ['encrypt', 'decrypt']
    );
    
    const exportedKey = await window.crypto.subtle.exportKey('raw', key);
    const hashArray = Array.from(new Uint8Array(exportedKey));
    return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
}

// Generate a random salt as a hex string
function generateSalt() {
    const saltBytes = window.crypto.getRandomValues(new Uint8Array(16));
    return Array.from(saltBytes).map(b => b.toString(16).padStart(2, '0')).join('');
}

export async function loginUser(email, password, deviceName, deviceUuid, appVersion) {
    const isOnline = window.navigator.onLine;
    const cleanEmail = email.trim().toLowerCase();

    if (isOnline) {
        try {
            // Online Login - authenticate with Laravel server
            const response = await axios.post('/api/offline/login', {
                email: cleanEmail,
                password,
                device_name: deviceName,
                device_uuid: deviceUuid,
                app_version: appVersion
            });

            if (response.data.success) {
                const data = response.data;
                const salt = generateSalt();
                const localHash = await hashPassword(password, salt);

                // Store credentials locally
                const cachedUser = {
                    email: cleanEmail,
                    salt,
                    password_hash: localHash,
                    token: data.token,
                    user_id: data.user.id,
                    business_id: data.user.business_id,
                    role: data.user.role,
                    permissions: data.user.permissions,
                    profile: data.user,
                };

                await putStoreData('cached_user', cachedUser);

                // Save token in axios headers
                window.axios.defaults.headers.common['Authorization'] = `Bearer ${data.token}`;
                localStorage.setItem('pwa_token', data.token);
                localStorage.setItem('pwa_user_email', cleanEmail);
                localStorage.setItem('pwa_device_uuid', deviceUuid);
                localStorage.setItem('pwa_device_name', deviceName);

                // Fetch initial data dump for offline use
                await refreshOfflineData();

                return { success: true, user: data.user, token: data.token };
            }
        } catch (e) {
            console.error('Online login failed, falling back to offline check if possible', e);
            if (e.response && e.response.status === 401) {
                return { success: false, message: 'Invalid credentials' };
            }
        }
    }

    // Offline Login - verify locally
    const cachedUser = await getStoreItem('cached_user', cleanEmail);
    if (!cachedUser) {
        return { 
            success: false, 
            message: isOnline ? 'Network error during login.' : 'This device has never logged in with this account. Internet required for first login.' 
        };
    }

    const calculatedHash = await hashPassword(password, cachedUser.salt);
    if (calculatedHash === cachedUser.password_hash) {
        // Authenticated!
        window.axios.defaults.headers.common['Authorization'] = `Bearer ${cachedUser.token}`;
        localStorage.setItem('pwa_token', cachedUser.token);
        localStorage.setItem('pwa_user_email', email);
        localStorage.setItem('pwa_device_uuid', deviceUuid);
        localStorage.setItem('pwa_device_name', deviceName);

        return { success: true, user: cachedUser.profile, token: cachedUser.token, offline: true };
    }

    return { success: false, message: 'Invalid credentials.' };
}

export async function logoutUser() {
    const token = localStorage.getItem('pwa_token');
    if (token && window.navigator.onLine) {
        try {
            await axios.post('/api/offline/logout', {}, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
        } catch (e) {
            console.error('Failed to logout online:', e);
        }
    }

    // Clear local storage and IndexedDB (crucial security requirement)
    localStorage.removeItem('pwa_token');
    localStorage.removeItem('pwa_user_email');
    localStorage.removeItem('pwa_last_sync');
    await clearAllOfflineData();
}

// Download initial data dump from server
export async function refreshOfflineData(lastSyncTime = null) {
    try {
        const url = lastSyncTime ? `/api/offline/sync/download?last_sync=${encodeURIComponent(lastSyncTime)}` : '/api/offline/sync/download';
        const response = await axios.get(url);
        if (response.data.success) {
            const data = response.data.data;
            
            // Put data in stores (incremental sync updates existing, leaves others)
            await putStoreData('products', data.products);
            await putStoreData('categories', data.categories);
            await putStoreData('customers', data.customers);
            await putStoreData('units', data.units);
            
            // Store business settings & profile
            await putStoreData('settings', { key: 'business_info', value: data.settings });
            await putStoreData('settings', { key: 'profile_info', value: data.profile });

            localStorage.setItem('pwa_last_sync', response.data.last_sync);
            console.log('Offline PWA data refreshed successfully');
        }
    } catch (e) {
        console.error('Failed to refresh offline PWA data:', e);
    }
}
