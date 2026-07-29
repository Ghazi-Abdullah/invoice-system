<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupportTicketController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['store']);
    }

    public function index(Request $request)
    {
        // ✅ أضفنا withCount('replies')
        $query = SupportTicket::with(['user', 'replies'])->withCount('replies');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $tickets = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'data' => $tickets->items(),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page'    => $tickets->lastPage(),
                'per_page'     => $tickets->perPage(),
                'total'        => $tickets->total(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255',
            'subject'  => 'required|string|max:255',
            'message'  => 'required|string',
            'priority' => 'nullable|in:low,medium,high',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors'  => $validator->errors()
            ], 422);
        }

        $ticket = SupportTicket::create([
            'ticket_number' => 'TKT-' . strtoupper(uniqid()),
            'user_id'       => auth('sanctum')->id(),
            'name'          => $request->name,
            'email'         => $request->email,
            'subject'       => $request->subject,
            'message'       => $request->message,
            'priority'      => $request->priority ?? 'medium',
            'status'        => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء التذكرة بنجاح',
            'ticket'  => $ticket
        ], 201);
    }

    public function show($id)
    {
        $ticket = SupportTicket::with(['replies.user'])->findOrFail($id);
        return response()->json($ticket);
    }

    public function reply(Request $request, $id)
    {
        $request->validate(['message' => 'required|string']);

        $ticket = SupportTicket::findOrFail($id);

        $reply = SupportTicketReply::create([
            'ticket_id'      => $id,
            'user_id'        => auth('sanctum')->id(),
            'message'        => $request->message,
            'is_admin_reply' => auth('sanctum')->user()?->isAdmin() ?? false,
        ]);

        if ($ticket->status === 'closed') {
            $ticket->status = 'in_progress';
            $ticket->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الرد بنجاح',
            'reply'   => $reply,
            'replies' => $ticket->replies()->with('user')->get(),
            'status'  => $ticket->status
        ]);
    }

    public function close($id)
    {
        $ticket = SupportTicket::findOrFail($id);
        $ticket->status = 'closed';
        $ticket->save();

        return response()->json([
            'success' => true,
            'message' => 'تم إغلاق التذكرة',
            'ticket'  => $ticket
        ]);
    }

    public function destroy($id)
    {
        $ticket = SupportTicket::findOrFail($id);
        $ticket->replies()->delete();
        $ticket->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف التذكرة'
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:open,in_progress,closed']);

        $ticket = SupportTicket::findOrFail($id);
        $oldStatus = $ticket->status;
        $ticket->status = $request->status;
        $ticket->save();

        if ($request->status === 'closed' && $oldStatus !== 'closed') {
            SupportTicketReply::create([
                'ticket_id'      => $id,
                'user_id'        => auth('sanctum')->id(),
                'message'        => 'تم إغلاق التذكرة من قبل الإدارة.',
                'is_admin_reply' => true,
            ]);
        }

        if ($oldStatus === 'closed' && in_array($request->status, ['open', 'in_progress'])) {
            SupportTicketReply::create([
                'ticket_id'      => $id,
                'user_id'        => auth('sanctum')->id(),
                'message'        => 'تم إعادة فتح التذكرة.',
                'is_admin_reply' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الحالة',
            'data'    => $ticket->load(['replies.user']),
        ]);
    }
}
