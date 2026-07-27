<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupportTicketController extends Controller
{
    /**
     * Constructor — استثني store من middleware auth
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['store']);
    }

    /**
     * عرض كل التذاكر (للأدمن)
     */
    public function index(Request $request)
    {
        $query = SupportTicket::with(['user', 'replies']);

        // فلترة حسب الحالة
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // فلترة حسب الأولوية
        if ($request->has('priority') && $request->priority !== '') {
            $query->where('priority', $request->priority);
        }

        // بحث
        if ($request->has('search') && $request->search !== '') {
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
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ]
        ]);
    }

    /**
     * إنشاء تذكرة جديدة (Public — بدون تسجيل)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'nullable|in:low,medium,high',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $ticket = SupportTicket::create([
            'ticket_number' => 'TKT-' . strtoupper(uniqid()),
            'user_id' => auth('sanctum')->id(), // null if guest
            'name' => $request->name,
            'email' => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
            'priority' => $request->priority ?? 'medium',
            'status' => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء التذكرة بنجاح',
            'ticket' => $ticket
        ], 201);
    }

    /**
     * عرض تفاصيل تذكرة
     */
    public function show($id)
    {
        $ticket = SupportTicket::with(['replies.user'])->findOrFail($id);
        return response()->json($ticket);
    }

    /**
     * إضافة رد على تذكرة
     */
    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $ticket = SupportTicket::findOrFail($id);

        $reply = SupportTicketReply::create([
            'ticket_id' => $id,
            'user_id' => auth('sanctum')->id(),
            'message' => $request->message,
            'is_admin_reply' => auth('sanctum')->user()?->isAdmin() ?? false,
        ]);

        // تحديث الحالة إذا كانت مغلقة
        if ($ticket->status === 'closed') {
            $ticket->status = 'in_progress';
            $ticket->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الرد بنجاح',
            'reply' => $reply,
            'replies' => $ticket->replies()->with('user')->get(),
            'status' => $ticket->status
        ]);
    }

    /**
     * إغلاق تذكرة
     */
    public function close($id)
    {
        $ticket = SupportTicket::findOrFail($id);
        $ticket->status = 'closed';
        $ticket->save();

        return response()->json([
            'success' => true,
            'message' => 'تم إغلاق التذكرة',
            'ticket' => $ticket
        ]);
    }

    /**
     * حذف تذكرة
     */
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
}
