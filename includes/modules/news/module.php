<?php
defined('ABSPATH') || exit;

require_once __DIR__ . '/class-brehl-news-module.php';

Brehl_Module_Registry::register(
    'news',
    array(
        'label'   => __('Unternehmensnews', 'brehl-intranet'),
        'enabled' => true,
        'class'   => Brehl_News_Module::class,
    )
);
