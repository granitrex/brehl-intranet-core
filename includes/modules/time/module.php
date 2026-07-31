<?php
defined('ABSPATH') || exit;

Brehl_Module_Registry::register(
    'time',
    array(
        'label'   => __('Zeiterfassung', 'brehl-intranet'),
        'enabled' => true,
        'class'   => '',
    )
);
