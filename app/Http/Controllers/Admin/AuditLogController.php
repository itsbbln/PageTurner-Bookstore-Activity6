<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $q = Audit::query()->latest();

        if ($request->filled('user_id')) {
            $q->where('user_id', $request->integer('user_id'));
        }
        if ($request->filled('event')) {
            $q->where('event', $request->string('event'));
        }
        if ($request->filled('auditable_type')) {
            $q->where('auditable_type', $request->string('auditable_type'));
        }
        if ($request->filled('date_from')) {
            $q->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $q->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $audits = $q->paginate(25)->appends($request->query());

        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $eventOptions = ['created', 'updated', 'deleted', 'restored'];
        $typeOptions = Audit::query()->select('auditable_type')->distinct()->orderBy('auditable_type')->pluck('auditable_type');

        return view('admin.audit.index', compact('audits', 'users', 'eventOptions', 'typeOptions'));
    }

    public function show(Audit $audit)
    {
        return view('admin.audit.show', [
            'audit' => $audit,
        ]);
    }
}

