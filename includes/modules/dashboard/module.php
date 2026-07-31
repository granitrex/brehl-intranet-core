<?php
defined('ABSPATH') || exit;

Brehl_Module_Registry::register(
    'dashboard',
    array(
        'label'   => __('Dashboard', 'brehl-intranet'),
        'enabled' => true,
        'class'   => '',
    )
);
