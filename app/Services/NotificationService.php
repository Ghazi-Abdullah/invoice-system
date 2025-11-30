<?php
// app/Services/NotificationService.php
namespace App\Services;

class NotificationService
{
    public function success(string $message)
    {
        return [
            'type' => 'success',
            'message' => $message
        ];
    }

    public function error(string $message)
    {
        return [
            'type' => 'error',
            'message' => $message
        ];
    }

    public function warning(string $message)
    {
        return [
            'type' => 'warning',
            'message' => $message
        ];
    }

    public function info(string $message)
    {
        return [
            'type' => 'info',
            'message' => $message
        ];
    }
}
