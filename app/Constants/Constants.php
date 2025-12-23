<?php

namespace App\Constants;

class Constants
{
    // ========== Pagination ==========
    const MAX_PER_PAGE = 100;
    const DEFAULT_PER_PAGE = 10;
    const MIN_PER_PAGE = 1;

    // ========== Client Permissions ==========
    const VIEW_CLIENTS = 'view_clients';
    const CREATE_CLIENT = 'create_client';
    const EDIT_CLIENT = 'edit_client';
    const DELETE_CLIENT = 'delete_client';

    // ========== Invoice Permissions ==========
    const VIEW_INVOICES = 'view_invoices';
    const CREATE_INVOICE = 'create_invoice';
    const EDIT_INVOICE = 'edit_invoice';
    const DELETE_INVOICE = 'delete_invoice';

    // ========== User Permissions ==========
    const VIEW_USERS = 'view_users';
    const CREATE_USER = 'create_user';
    const EDIT_USER = 'edit_user';
    const DELETE_USER = 'delete_user';

    // ========== Report Permissions ==========
    const VIEW_REPORTS = 'view_reports';
    const VIEW_SALES_REPORT = 'view_sales_report';
    const EXPORT_REPORTS = 'export_reports';

    // ========== Admin Permissions ==========
    const VIEW_ADMIN_GROUPS = 'view_admin_groups';
    const MANAGE_ADMIN_GROUPS = 'manage_admin_groups';
    const MANAGE_PERMISSIONS = 'manage_permissions';

    // ========== Status ==========
    const ACTIVE = 1;
    const INACTIVE = 0;

    // ========== Invoice Statuses ==========
    const INVOICE_STATUS_DRAFT = 'draft';
    const INVOICE_STATUS_SENT = 'sent';
    const INVOICE_STATUS_PAID = 'paid';
    const INVOICE_STATUS_OVERDUE = 'overdue';
    const INVOICE_STATUS_CANCELLED = 'cancelled';

    // ========== Payment Terms ==========
    const PAYMENT_TERM_NET_30 = 'net_30';
    const PAYMENT_TERM_NET_60 = 'net_60';
    const PAYMENT_TERM_IMMEDIATE = 'immediate';

    // ========== Currency ==========
    const CURRENCY_USD = 'USD';
    const CURRENCY_SAR = 'SAR';
    const CURRENCY_EUR = 'EUR';

    // ========== Activity Types ==========
    const ACTIVITY_TYPE_CREATE = 'CREATE';
    const ACTIVITY_TYPE_UPDATE = 'UPDATE';
    const ACTIVITY_TYPE_DELETE = 'DELETE';
    const ACTIVITY_TYPE_LOGIN = 'LOGIN';
    const ACTIVITY_TYPE_LOGOUT = 'LOGOUT';

    // ========== Admin Groups ==========
    const SUPER_ADMIN_GROUP_ID = 1;
    const ADMIN_GROUP_ID = 2;
    const ACCOUNTANT_GROUP_ID = 3;
    const SALES_GROUP_ID = 4;
    const CLIENT_GROUP_ID = 5;

    // ========== Response Messages ==========
    const MSG_SUCCESS = 'success';
    const MSG_ERROR = 'error';
    const MSG_NOT_FOUND = 'not_found';
    const MSG_UNAUTHORIZED = 'unauthorized';
    const MSG_FORBIDDEN = 'forbidden';
    const MSG_VALIDATION_ERROR = 'validation_error';

    // ========== HTTP Status Codes ==========
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_INTERNAL_SERVER_ERROR = 500;
    const HTTP_UNPROCESSABLE_ENTITY = 422;
}
