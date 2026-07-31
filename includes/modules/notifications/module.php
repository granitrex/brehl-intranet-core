<?php
defined('ABSPATH') || exit;

Brehl_Module_Registry::register(
    'notifications',
    array(
        'label'   => __('Benachrichtigungen', 'brehl-intranet'),
        'enabled' => true,
        'class'   => '',
    )
);
