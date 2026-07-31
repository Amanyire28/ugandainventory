import { openDB } from 'idb';

const DB_NAME = 'dukaflow_offline_db';
const DB_VERSION = 1;

let dbPromise = null;

export function getDB() {
    if (!dbPromise) {
        dbPromise = openDB(DB_NAME, DB_VERSION, {
            upgrade(db) {
                // Stores for cached operational data
                db.createObjectStore('products', { keyPath: 'id' });
                db.createObjectStore('categories', { keyPath: 'id' });
                db.createObjectStore('customers', { keyPath: 'id' });
                db.createObjectStore('units', { autoIncrement: true });
                db.createObjectStore('settings', { keyPath: 'key' });
                db.createObjectStore('cached_user', { keyPath: 'email' });

                // Queues for pending sync operations
                db.createObjectStore('sync_queue', { keyPath: 'id', autoIncrement: true });
                db.createObjectStore('conflicts', { keyPath: 'id', autoIncrement: true });
            },
        });
    }
    return dbPromise;
}

export async function getStoreData(storeName) {
    const db = await getDB();
    return db.getAll(storeName);
}

export async function putStoreData(storeName, data) {
    const db = await getDB();
    const tx = db.transaction(storeName, 'readwrite');
    if (Array.isArray(data)) {
        for (const item of data) {
            await tx.store.put(item);
        }
    } else {
        await tx.store.put(data);
    }
    await tx.done;
}

export async function getStoreItem(storeName, key) {
    const db = await getDB();
    return db.get(storeName, key);
}

export async function deleteStoreItem(storeName, key) {
    const db = await getDB();
    return db.delete(storeName, key);
}

export async function clearAllOfflineData() {
    const db = await getDB();
    const stores = ['products', 'categories', 'customers', 'units', 'settings', 'cached_user', 'sync_queue', 'conflicts'];
    for (const store of stores) {
        try {
            const tx = db.transaction(store, 'readwrite');
            await tx.store.clear();
            await tx.done;
        } catch (e) {
            console.error(`Failed to clear store ${store}:`, e);
        }
    }
}
