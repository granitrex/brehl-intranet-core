<?php
defined('ABSPATH') || exit;

Brehl_Module_Registry::register(
    'employees',
    array(
        'label'   => __('Mitarbeiter', 'brehl-intranet'),
        'enabled' => true,
        'class'   => '',
    )
);
