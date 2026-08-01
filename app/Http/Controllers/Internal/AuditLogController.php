<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query()->orderByDesc('_id');

        if ($entityType = $request->query('entity_type')) {
            $query->where('entity_type', $entityType);
        }
        if ($entityId = $request->query('entity_id')) {
            $query->where('entity_id', (int) $entityId);
        }
        if ($userId = $request->query('user_id')) {
            $query->where('user_id', (int) $userId);
        }

        return response()->json($query->simplePaginate($request->integer('per_page', 50)));
    }
}
