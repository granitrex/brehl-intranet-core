<?php
defined('ABSPATH') || exit;

require_once __DIR__ . '/class-brehl-vehicle-damage-module.php';

Brehl_Module_Registry::register(
    'vehicles',
    array(
        'label'   => __('Fahrzeuge', 'brehl-intranet'),
        'enabled' => true,
        'class'   => 'Brehl_Vehicle_Damage_Module',
    )
);
