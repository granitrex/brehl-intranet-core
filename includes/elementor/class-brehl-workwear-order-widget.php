<?php
defined('ABSPATH') || exit;
final class Brehl_Workwear_Order_Widget extends My_Brehl_Widget_Base {
    public function get_name(): string { return 'brehl_workwear_order'; }
    public function get_title(): string { return __('My Brehl – Arbeitskleidung bestellen', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-form-horizontal'; }
    public function get_categories(): array { return array('brehl-workwear'); }
    protected function register_controls(): void {}
    protected function render(): void { if (class_exists('Brehl_Workwear_Module')) echo Brehl_Workwear_Module::instance()->order_panel(); }
}
