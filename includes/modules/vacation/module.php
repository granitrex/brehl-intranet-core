<?php
defined('ABSPATH') || exit;
require_once __DIR__ . '/class-brehl-vacation-module.php';
Brehl_Module_Registry::register('vacation', array('label'=>__('Urlaub','brehl-intranet'),'enabled'=>true,'class'=>'Brehl_Vacation_Module'));
