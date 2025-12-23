<?php

return [
    // General Messages
    'success' => 'Operation completed successfully',
    'error' => 'An error occurred',
    'not_found' => 'Resource not found',
    'no_permission' => 'You do not have permission to perform this action',
    'validation_error' => 'Validation failed',
    'server_error' => 'Internal server error',

    // Auth Messages
    'login_success' => 'Logged in successfully',
    'login_failed' => 'Invalid credentials',
    'logout_success' => 'Logged out successfully',
    'token_refreshed' => 'Token refreshed successfully',
    'user_fetched' => 'User data retrieved successfully',
    'profile_fetched' => 'Profile retrieved successfully',
    'profile_updated' => 'Profile updated successfully',
    'password_changed' => 'Password changed successfully',
    'password_invalid' => 'Current password is incorrect',
    'inactive_account' => 'Your account is inactive',
    'not_admin' => 'This account cannot access the admin panel',

    // Invoice Messages
    'invoices_fetched' => 'Invoices retrieved successfully',
    'invoice_fetched' => 'Invoice retrieved successfully',
    'invoice_created' => 'Invoice created successfully',
    'invoice_updated' => 'Invoice updated successfully',
    'invoice_deleted' => 'Invoice deleted successfully',
    'invoice_sent' => 'Invoice sent successfully',
    'invoice_marked_paid' => 'Invoice marked as paid successfully',
    'invoice_duplicated' => 'Invoice duplicated successfully',
    'pdf_generated' => 'PDF generated successfully',
    'pdf_downloaded' => 'PDF downloaded successfully',
    'only_draft_sent' => 'Only draft invoices can be sent',

    // Client Messages
    'clients_fetched' => 'Clients retrieved successfully',
    'client_fetched' => 'Client retrieved successfully',
    'client_created' => 'Client created successfully',
    'client_updated' => 'Client updated successfully',
    'client_deleted' => 'Client deleted successfully',
    'client_stats_fetched' => 'Client statistics retrieved successfully',
    'client_invoices_fetched' => 'Client invoices retrieved successfully',

    // User Messages
    'users_fetched' => 'Users retrieved successfully',
    'user_fetched' => 'User retrieved successfully',
    'user_created' => 'User created successfully',
    'user_updated' => 'User updated successfully',
    'user_deleted' => 'User deleted successfully',
    'user_status_updated' => 'User status updated successfully',
    'staff_users_fetched' => 'Staff users retrieved successfully',
    'client_users_fetched' => 'Client users retrieved successfully',

    // Admin Group Messages
    'admin_groups_fetched' => 'Admin groups retrieved successfully',
    'admin_group_fetched' => 'Admin group retrieved successfully',
    'admin_group_created' => 'Admin group created successfully',
    'admin_group_updated' => 'Admin group updated successfully',
    'admin_group_deleted' => 'Admin group deleted successfully',
    'permissions_fetched' => 'Permissions retrieved successfully',
    'permissions_updated' => 'Permissions updated successfully',
    'available_permissions_fetched' => 'Available permissions retrieved successfully',
    'groups_with_permissions_fetched' => 'Groups with permissions retrieved successfully',
    'cannot_delete_system_group' => 'System groups cannot be deleted',
    'cannot_delete_group_with_users' => 'Cannot delete group with assigned users',

    // Report Messages
    'invoice_report_fetched' => 'Invoice report retrieved successfully',
    'client_report_fetched' => 'Client report retrieved successfully',
    'payment_report_fetched' => 'Payment report retrieved successfully',
    'tax_report_fetched' => 'Tax report retrieved successfully',
    'report_exported' => 'Report exported successfully',
    'recent_activity_fetched' => 'Recent activity retrieved successfully',
    'top_clients_fetched' => 'Top clients retrieved successfully',
    'monthly_revenue_fetched' => 'Monthly revenue retrieved successfully',

    // Dashboard Messages
    'dashboard_stats_fetched' => 'Dashboard statistics retrieved successfully',
    'recent_invoices_fetched' => 'Recent invoices retrieved successfully',
    'overdue_invoices_fetched' => 'Overdue invoices retrieved successfully',

    // Permission Messages
    'permissions_fetched' => 'Permissions retrieved successfully',
    'menus_fetched' => 'Menus retrieved successfully',

    // Validation Messages
    'required' => 'The :attribute field is required',
    'email' => 'The :attribute must be a valid email address',
    'unique' => 'The :attribute has already been taken',
    'exists' => 'The selected :attribute is invalid',
    'min' => 'The :attribute must be at least :min characters',
    'max' => 'The :attribute may not be greater than :max characters',
    'numeric' => 'The :attribute must be a number',
    'date' => 'The :attribute must be a valid date',
    'after_or_equal' => 'The :attribute must be a date after or equal to :date',
    'confirmed' => 'The :attribute confirmation does not match',
    'array' => 'The :attribute must be an array',
    'in' => 'The selected :attribute is invalid',
    'boolean' => 'The :attribute field must be true or false',
    'string' => 'The :attribute must be a string',

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'login_success' => 'Logged in successfully',
    'login_failed' => 'Invalid credentials',
    'logout_success' => 'Logged out successfully',
    'token_refreshed' => 'Token refreshed successfully',
    'user_fetched' => 'User data retrieved successfully',
    'profile_fetched' => 'Profile retrieved successfully',
    'profile_updated' => 'Profile updated successfully',
    'password_changed' => 'Password changed successfully',
    'password_invalid' => 'Current password is incorrect',
    'inactive_account' => 'Your account is inactive',
    'not_admin' => 'This account cannot access the admin panel',
    'unauthenticated' => 'Unauthenticated',
];
