<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SupportTicket\ReplySupportTicketRequest;
use App\Http\Requests\Admin\SupportTicket\StoreSupportTicketRequest;
use App\Http\Requests\Admin\SupportTicket\UpdateSupportTicketStatusRequest;
use App\Http\Requests\Admin\SupportTicket\AssignSupportTicketRequest;
    use App\Http\Requests\Admin\SupportTicket\TrackSupportTicketRequest;
use App\Models\SupportTicket;
use App\Repository\Admin\SupportTicket\SupportTicketInterface;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    use ResponseTrait;

    protected SupportTicketInterface $supportTicketRepository;

    public function __construct(SupportTicketInterface $supportTicketRepository)
    {
        $this->supportTicketRepository = $supportTicketRepository;
    }

    public function index(Request $request)
    {
        $result = $this->supportTicketRepository->index($request);

        return $result['status']
            ? $this->successResponse($result['message'], $result['data'])
            : $this->failureResponse($result['message']);
    }

    public function show($id)
    {
        $result = $this->supportTicketRepository->show($id);

        return $result['status']
            ? $this->successResponse($result['message'], $result['data'])
            : $this->failureResponse($result['message'], null, 404);
    }

    public function store(StoreSupportTicketRequest $request)
    {
        $result = $this->supportTicketRepository->store($request);

        return $result['status']
            ? $this->successResponse($result['message'], $result['data'], 201)
            : $this->failureResponse($result['message']);
    }

    public function reply(ReplySupportTicketRequest $request, SupportTicket $ticket)
    {
        $result = $this->supportTicketRepository->reply($request, $ticket);

        return $result['status']
            ? $this->successResponse($result['message'], $result['data'])
            : $this->failureResponse($result['message']);
    }

    public function updateStatus(UpdateSupportTicketStatusRequest $request, SupportTicket $ticket)
    {
        $result = $this->supportTicketRepository->updateStatus($request, $ticket);

        return $result['status']
            ? $this->successResponse($result['message'], $result['data'])
            : $this->failureResponse($result['message']);
    }

    // ✅ close() أصبحت تستدعي نفس منطق updateStatus بدل تكرار الكود —
    //    هذا يحل التضارب القديم بين "إغلاق سريع" و"تغيير الحالة لـ closed"
    public function close(SupportTicket $ticket)
    {
        $result = $this->supportTicketRepository->updateStatus(
            new Request(['status' => 'closed']),
            $ticket
        );

        return $result['status']
            ? $this->successResponse($result['message'], $result['data'])
            : $this->failureResponse($result['message']);
    }

    public function destroy(SupportTicket $ticket)
    {
        $result = $this->supportTicketRepository->destroy($ticket);

        return $result['status']
            ? $this->successResponse($result['message'], $result['data'])
            : $this->failureResponse($result['message']);
    }



    public function track(TrackSupportTicketRequest $request)
    {
        $result = $this->supportTicketRepository->track($request);
        return $result['status']
            ? $this->successResponse($result['message'], $result['data'])
            : $this->failureResponse($result['message'], null, 404);
    }

    public function assign(AssignSupportTicketRequest $request, SupportTicket $ticket)
    {
        $result = $this->supportTicketRepository->assign($request, $ticket);
        return $result['status']
            ? $this->successResponse($result['message'], $result['data'])
            : $this->failureResponse($result['message']);
    }
}
