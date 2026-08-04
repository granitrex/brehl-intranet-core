<?php
defined('ABSPATH') || exit;
final class Brehl_Fleet_Metrics_Widget extends My_Brehl_Widget_Base {
    public function get_name(): string { return 'brehl_fleet_metrics'; }
    public function get_title(): string { return __('My Brehl – Fuhrpark: Kennzahlen','brehl-intranet'); }
    public function get_icon(): string { return 'eicon-counter'; }
    public function get_categories(): array { return array('brehl-fuhrpark'); }
    public function get_style_depends(): array { return array('brehl-intranet','my-brehl-system','brehl-intranet-vehicle-damage'); }
    protected function register_controls(): void {}
    protected function render(): void { if(class_exists('Brehl_Vehicle_Damage_Module')) echo Brehl_Vehicle_Damage_Module::instance()->metrics_panel(); }
}
