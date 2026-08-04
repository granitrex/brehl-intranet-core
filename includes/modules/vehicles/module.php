<?php
defined('ABSPATH') || exit;

require_once __DIR__ . '/class-brehl-vehicle-damage-module.php';
require_once __DIR__ . '/class-brehl-vehicle-service-module.php';

Brehl_Module_Registry::register(
    'vehicles',
    array(
        'label'   => __('Fahrzeuge', 'brehl-intranet'),
        'enabled' => true,
        'class'   => 'Brehl_Vehicle_Damage_Module',
    )
);

Brehl_Module_Registry::register(
    'vehicle-service',
    array('label' => __('Fahrzeugservice', 'brehl-intranet'), 'enabled' => true, 'class' => 'Brehl_Vehicle_Service_Module')
);
