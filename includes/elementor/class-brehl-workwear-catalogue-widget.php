<?php
defined('ABSPATH') || exit;
final class Brehl_Workwear_Catalogue_Widget extends My_Brehl_Widget_Base {
    public function get_name(): string { return 'brehl_workwear_catalogue'; }
    public function get_title(): string { return __('My Brehl – Arbeitskleidung: Artikel verwalten', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-product-images'; }
    public function get_categories(): array { return array('brehl-workwear'); }
    protected function register_controls(): void {}
    protected function render(): void { if(class_exists('Brehl_Workwear_Module')) echo Brehl_Workwear_Module::instance()->catalogue_panel(); }
}
