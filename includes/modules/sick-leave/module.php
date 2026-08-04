<?php
defined('ABSPATH') || exit;

require_once __DIR__ . '/class-brehl-sick-leave-module.php';

Brehl_Module_Registry::register('sick-leave', array(
    'label' => __('Krankmeldungen', 'brehl-intranet'),
    'enabled' => true,
    'class' => Brehl_Sick_Leave_Module::class,
));
