<?php
defined('ABSPATH') || exit;
final class Brehl_Vehicle_Damage_Management_Widget extends My_Brehl_Widget_Base {
    public function get_name(): string { return 'brehl_vehicle_damage_management'; }
    public function get_title(): string { return __('My Brehl – Fuhrpark: Schadenverwaltung','brehl-intranet'); }
    public function get_icon(): string { return 'eicon-warning'; }
    public function get_categories(): array { return array('brehl-fuhrpark'); }
    public function get_style_depends(): array { return array('brehl-intranet','my-brehl-system','brehl-intranet-vehicle-damage'); }
    protected function register_controls(): void {}
    protected function render(): void { if(class_exists('Brehl_Vehicle_Damage_Module')) echo Brehl_Vehicle_Damage_Module::instance()->management_panel(); }
}
