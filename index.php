<?php
/*
Plugin Name: Yodo Custom Fields Trigger for FluentCRM
Description: Adds a custom trigger for FluentCRM with dropdown options for custom fields.
Version: 1.0
Text Domain: yodocustom
Author: Yodo Club Design
*/

// Ensure this file is accessed directly
if (!defined('ABSPATH')) {
    exit;
}

//use FluentCrm\App\Models\CustomContactField;
//add_action('admin_init', 'crm_custom_fields_test');

function crm_custom_fields_test() {
    // Ensure Fluent CRM is active
    if (!class_exists('FluentCrm\App\Models\CustomContactField')) {
        echo 'Fluent CRM is not active.';
        return;
    }

    // Fetch custom fields
    $customContactField = new CustomContactField();
    $globalFields = $customContactField->getGlobalFields();
    $customFields = isset($globalFields['fields']) ? $globalFields['fields'] : [];

    // Display custom fields for testing purposes
    // echo '<pre>';
    // print_r($customFields);
    // echo '</pre>';
}



add_action('fluent_crm/after_init', function () {
    require_once __DIR__ . '/Automation/CustomFieldsTrigger.php';
    new CRM_Custom_Fields\Automation\CustomFieldTrigger();
});
