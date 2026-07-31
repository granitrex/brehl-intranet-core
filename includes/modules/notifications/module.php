<?php
defined('ABSPATH') || exit;

require_once __DIR__ . '/class-brehl-notifications-module.php';

Brehl_Module_Registry::register(
    'notifications',
    array(
        'label'   => __('Benachrichtigungen', 'brehl-intranet'),
        'enabled' => true,
        'class'   => Brehl_Notifications_Module::class,
    )
);
