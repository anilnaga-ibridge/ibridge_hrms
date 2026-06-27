<?php

namespace App\Http\Controllers\Api\EnterpriseTasks;

use App\Http\Controllers\Controller;
use App\Services\EnterpriseTasks\OfflineSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SyncController extends Controller
{
    protected OfflineSyncService $syncService;

    public function __construct(OfflineSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Push offline mutations to the server.
     */
    public function push(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mutations' => 'required|array',
            'mutations.*.action' => 'required|in:create,update,delete',
            'mutations.*.model' => 'required|string',
            'mutations.*.payload' => 'required|array',
            'mutations.*.timestamp' => 'required|string',
            'mutations.*.xid' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $companyId = company()->id;
        $userId = auth('api')->id();

        $results = $this->syncService->processPush($companyId, $userId, $request->mutations);

        return response()->json([
            'results' => $results
        ]);
    }

    /**
     * Pull updates since the client's last sync timestamp.
     */
    public function pull(Request $request)
    {
        $companyId = company()->id;
        $since = $request->query('since');

        $changes = $this->syncService->pullChanges($companyId, $since);

        return response()->json($changes);
    }
}
