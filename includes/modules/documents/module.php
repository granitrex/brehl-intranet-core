<?php
defined('ABSPATH') || exit;

Brehl_Module_Registry::register(
    'documents',
    array(
        'label'   => __('Dokumente', 'brehl-intranet'),
        'enabled' => true,
        'class'   => '',
    )
);
