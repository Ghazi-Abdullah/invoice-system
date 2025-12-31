<?php

namespace App\Constants;

class InvoiceStatus
{
    const DRAFT = 'draft';
    const SENT = 'sent';
    const PAID = 'paid';
    const OVERDUE = 'overdue';

    public static function all(): array
    {
        return [
            self::DRAFT,
            self::SENT,
            self::PAID,
            self::OVERDUE
        ];
    }

    public static function labels(): array
    {
        return [
            self::DRAFT => 'مسودة',
            self::SENT => 'مرسلة',
            self::PAID => 'مدفوعة',
            self::OVERDUE => 'متأخرة'
        ];
    }

    public static function getLabel(string $status): string
    {
        return self::labels()[$status] ?? $status;
    }

    public static function colors(): array
    {
        return [
            self::DRAFT => 'gray',
            self::SENT => 'blue',
            self::PAID => 'green',
            self::OVERDUE => 'red'
        ];
    }

    public static function getColor(string $status): string
    {
        return self::colors()[$status] ?? 'gray';
    }
}
