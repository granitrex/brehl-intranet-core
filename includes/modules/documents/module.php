<?php
defined('ABSPATH') || exit;

require_once __DIR__ . '/class-brehl-documents-module.php';

Brehl_Module_Registry::register(
    'documents',
    array(
        'label'   => __('Dokumente', 'brehl-intranet'),
        'enabled' => true,
        'class'   => Brehl_Documents_Module::class,
    )
);
