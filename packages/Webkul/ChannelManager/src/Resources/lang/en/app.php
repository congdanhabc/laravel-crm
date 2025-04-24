<?php

return [
    /**
     * General Module Titles & Resources
     */
    'title' => 'Channel Manager',
    'channel' => 'Channel',
    'channels' => 'Channels',
    'channel_resource' => 'channel', // Lowercase resource name for messages like "delete [channel]?"

    /**
     * Admin Menu Title (if different from general title)
     */
    'admin' => [
        'menu' => [
            'title' => 'Channel Manager', // Text appearing in the admin sidebar
        ],
    ],

    /**
     * Index Page
     */
    'index' => [
        'title' => 'Channels', // Title specifically for the listing page
        'add-channel-btn-title' => 'Add Channel', // Button text
    ],

    /**
     * Create Page
     */
    'create' => [
        'title' => 'Create Channel', // Page title
        'breadcrumb' => 'Create', // Breadcrumb part
        'save-btn-title' => 'Save Channel', // Can override the default 'Save' if needed

        // Accordion/Section Titles
        'general' => 'General Information',
        'credentials' => 'Credentials & Connection',
        'config' => 'Configuration',
        'additional' => 'Additional Information',

        // Field Labels & Placeholders
        'name' => 'Channel Name',
        'name_placeholder' => 'Enter the name for this channel',
        'type' => 'Channel Type',
        'default' => 'Choose Type',
        'type_facebook' => 'Facebook Messenger',
        'type_channex' => 'Channex.io',

        'facebook' => [
            'page_id' => 'Facebook Page ID',
            'page_access_token' => 'Facebook Page Access Token',
            'app_secret' => 'Facebook App Secret',
            'connect_btn' => 'Connect',
        ],
        'channex' => [

        ]
    ],

    /**
     * Edit Page (Often shares keys with Create, but can have specific ones)
     */
    'edit' => [
        'title' => 'Edit Channel', // Page title
        'breadcrumb' => 'Edit', // Breadcrumb part
        // You can add specific edit keys here if needed
    ],

    /**
     * Controller Action Messages (Success, Error, Warnings)
     */
    'create-success' => 'Channel created successfully.',
    'update-success' => 'Channel updated successfully.',
    'delete-success' => 'Channel deleted successfully.',
    'delete-failed' => 'Failed to delete channel.', // Example specific error
    // 'mass_delete_success' => 'Selected channels deleted successfully.', // Covered by admin::app generally
    // 'mass_update_success' => 'Selected channels updated successfully.', // Covered by admin::app generally

    /**
     * DataGrid Specific Translations
     */
    'datagrid' => [
        // Column Headers (use keys from admin::app if possible for consistency)
        'id' => 'ID', // Use admin::app.datagrid.id
        'name' => 'Channel Name',
        'type' => 'Type',
        'status' => 'Status', // Use admin::app.datagrid.status
        'created_at' => 'Created At', // Use admin::app.datagrid.created_at

        // Status Texts (for boolean/closure rendering)
        'active' => 'Active',
        'inactive' => 'Inactive',

        // Filter Options (Example for 'type' column)
        'type_messenger' => 'Facebook Messenger',
        'type_channex' => 'Channex.io',

        // Mass Actions
        'update_status' => 'Update Status', // Label for mass action dropdown

        // Other DataGrid texts if needed
        'channel_resource' => 'channel', // Lowercase resource name for messages like "delete [channel]?" - Reuse general one if same
        'creator' => 'Created By', // Example for joined column header
    ],

    'validation' => [
        'invalid_fb_credentials' => 'Facebook verification failed. Please check your Page ID and Page Access Token.',
    ],

    /**
     * Webhook specific (Optional)
     */
    'webhook' => [
        'verification_failed' => 'Webhook verification failed.',
        'invalid_signature' => 'Invalid webhook signature.',
        // ...
    ],

    /**
     * Channex API specific (Optional)
     */
    'channex' => [
        'api_error' => 'Error communicating with Channex API.',
        'config_missing' => 'Channex API credentials not configured.',
        // ...
    ],
];
