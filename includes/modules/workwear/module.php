<?php
defined('ABSPATH') || exit;

require_once __DIR__ . '/class-brehl-workwear-module.php';

Brehl_Module_Registry::register(
    'workwear',
    array(
        'label' => __('Arbeitsbekleidung', 'brehl-intranet'),
        'enabled' => true,
        'class' => Brehl_Workwear_Module::class,
    )
);
