import { offlineStore } from './offlineStore';
import axios from 'axios';

export const syncManager = {
    /**
     * Start listening for online/offline browser events.
     */
    init() {
        window.addEventListener('online', () => this.syncOfflineData());
        // Run sync check on startup
        if (navigator.onLine) {
            this.syncOfflineData();
        }
    },

    /**
     * Push queued offline mutations to the backend.
     */
    async syncOfflineData() {
        try {
            const mutations = await offlineStore.getMutations();
            if (mutations.length === 0) return;

            console.log(`[SyncManager] Found ${mutations.length} offline mutations. Syncing...`);

            const response = await axios.post('/enterprise-tasks/sync/push', {
                mutations: mutations
            });

            console.log('[SyncManager] Sync results:', response.data);

            // Clear IndexedDB store on successful sync
            await offlineStore.clearMutations();
            console.log('[SyncManager] Offline queue cleared.');
        } catch (e) {
            console.error('[SyncManager] Failed to sync offline data:', e);
        }
    }
};
