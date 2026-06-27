const DB_NAME = 'hrm_offline_db';
const DB_VERSION = 1;
const STORE_NAME = 'sync_queue';

let dbInstance = null;

function getDB() {
    if (dbInstance) return Promise.resolve(dbInstance);

    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = event => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
            }
        };

        request.onsuccess = event => {
            dbInstance = event.target.result;
            resolve(dbInstance);
        };

        request.onerror = event => {
            reject(event.target.error);
        };
    });
}

export const offlineStore = {
    /**
     * Add a mutation to the offline sync queue.
     */
    async addMutation(action, model, payload, xid = null) {
        const db = await getDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readwrite');
            const store = tx.objectStore(STORE_NAME);

            const mutation = {
                action,
                model,
                payload,
                xid,
                timestamp: new Date().toISOString()
            };

            const request = store.add(mutation);
            request.onsuccess = () => resolve(true);
            request.onerror = () => reject(request.error);
        });
    },

    /**
     * Get all queued mutations.
     */
    async getMutations() {
        const db = await getDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readonly');
            const store = tx.objectStore(STORE_NAME);
            const request = store.getAll();

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    },

    /**
     * Clear all processed mutations.
     */
    async clearMutations() {
        const db = await getDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readwrite');
            const store = tx.objectStore(STORE_NAME);
            const request = store.clear();

            request.onsuccess = () => resolve(true);
            request.onerror = () => reject(request.error);
        });
    }
};
