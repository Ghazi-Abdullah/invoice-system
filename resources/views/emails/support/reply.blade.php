{{-- resources/views/emails/support/reply.blade.php --}}
<!DOCTYPE html>
<html dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.support.email.reply_subject', ['ticket' => $ticket->ticket_number]) }}</title>
    <style>
        @media only screen and (max-width: 600px) {
            .container {
                width: 100% !important;
            }

            .content {
                padding: 20px !important;
            }

            .button {
                width: 100% !important;
                display: block !important;
                text-align: center !important;
            }
        }
    </style>
</head>

<body style="margin:0; padding:0; background-color:#f4f4f4; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#f4f4f4;">
        <tr>
            <td align="center" style="padding:40px 0;">

                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" class="container" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 6px rgba(0,0,0,0.1);">

                    {{-- Header --}}
                    <tr>
                        <td style="background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding:30px; text-align:center;">
                            <h1 style="color:#ffffff; margin:0; font-size:24px; font-weight:600;">
                                {{ __('messages.support.email.ticket_label') }} {{ $ticket->ticket_number }}
                            </h1>
                            <p style="color:#e0e0e0; margin:10px 0 0 0; font-size:14px;">
                                {{ $ticket->subject }}
                            </p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td class="content" style="padding:40px 30px; direction: {{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}; text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};">

                            <p style="color:#333333; font-size:16px; line-height:1.6; margin:0 0 20px 0;">
                                @if($fromAdmin)
                                <span style="color:#667eea; font-weight:bold;">{{ __('messages.support.email.new_reply_from_support') }}</span>
                                @else
                                <span style="color:#764ba2; font-weight:bold;">{{ __('messages.support.email.new_reply_from_customer') }}</span>
                                @endif
                            </p>

                            {{-- Message Box --}}
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#f8f9fa; border-radius:8px; {{ app()->getLocale() === 'ar' ? 'border-right:4px solid ' . ($fromAdmin ? '#667eea' : '#764ba2') : 'border-left:4px solid ' . ($fromAdmin ? '#667eea' : '#764ba2') }};">
                                <tr>
                                    <td style="padding:20px;">
                                        <p style="color:#555555; font-size:15px; line-height:1.8; margin:0; white-space:pre-wrap;">{{ $replyMessage }}</p>
                                    </td>
                                </tr>
                            </table>

                            {{-- Ticket Info --}}
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top:25px; border-top:1px solid #eeeeee; padding-top:20px;">
                                <tr>
                                    <td style="padding-bottom:10px;">
                                        <span style="color:#888888; font-size:13px;">{{ __('messages.support.email.ticket_number') }}:</span>
                                        <span style="color:#333333; font-size:14px; font-weight:600;">{{ $ticket->ticket_number }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-bottom:10px;">
                                        <span style="color:#888888; font-size:13px;">{{ __('messages.support.email.status') }}:</span>
                                        @php
                                        $statusColor = match($ticket->status) {
                                        'closed' => '#dc3545',
                                        'in_progress' => '#ffc107',
                                        default => '#28a745',
                                        };
                                        $statusText = match($ticket->status) {
                                        'open' => __('messages.support.status.open'),
                                        'in_progress' => __('messages.support.status.in_progress'),
                                        'closed' => __('messages.support.status.closed'),
                                        default => $ticket->status,
                                        };
                                        @endphp
                                        <span style="display:inline-block; background-color:{{ $statusColor }}; color:#ffffff; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600;">
                                            {{ $statusText }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <span style="color:#888888; font-size:13px;">{{ __('messages.support.email.priority') }}:</span>
                                        @php
                                        $priorityColor = match($ticket->priority) {
                                        'high' => '#dc3545',
                                        'medium' => '#ffc107',
                                        default => '#28a745',
                                        };
                                        $priorityText = match($ticket->priority) {
                                        'high' => __('messages.support.priority.high'),
                                        'medium' => __('messages.support.priority.medium'),
                                        'low' => __('messages.support.priority.low'),
                                        default => $ticket->priority,
                                        };
                                        @endphp
                                        <span style="color:{{ $priorityColor }}; font-size:14px; font-weight:600;">
                                            {{ $priorityText }}
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            {{-- CTA Button --}}
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top:30px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ config('app.frontend_url') }}/support/track?ticket={{ $ticket->ticket_number }}"
                                            class="button"
                                            style="display:inline-block; padding:14px 35px; background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:#ffffff; text-decoration:none; border-radius:8px; font-size:16px; font-weight:600; box-shadow:0 4px 12px rgba(102,126,234,0.4);">
                                            {{ __('messages.support.email.view_ticket') }}
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="color:#999999; font-size:12px; text-align:center; margin-top:20px; line-height:1.5;">
                                {{ __('messages.support.email.cant_click') }}<br>
                                <a href="{{ config('app.frontend_url') }}/support/track?ticket={{ $ticket->ticket_number }}" style="color:#667eea; word-break:break-all;">
                                    {{ config('app.frontend_url') }}/support/track?ticket={{ $ticket->ticket_number }}
                                </a>
                            </p>

                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color:#f8f9fa; padding:20px; text-align:center; border-top:1px solid #eeeeee;">
                            <p style="color:#999999; font-size:12px; margin:0;">
                                {{ __('messages.support.email.auto_message') }}
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>