<?php
defined('ABSPATH') || exit;

require_once __DIR__ . '/class-brehl-employees-module.php';

Brehl_Module_Registry::register(
    'employees',
    array(
        'label'   => __('Mitarbeiter', 'brehl-intranet'),
        'enabled' => true,
        'class'   => Brehl_Employees_Module::class,
    )
);
