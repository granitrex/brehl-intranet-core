<?php
defined('ABSPATH') || exit;
final class Brehl_Vehicle_Form_Widget extends My_Brehl_Widget_Base {
    public function get_name(): string { return 'brehl_vehicle_form'; }
    public function get_title(): string { return __('My Brehl – Fuhrpark: Fahrzeugformular', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-form-horizontal'; }
    public function get_categories(): array { return array('brehl-fuhrpark'); }
    protected function register_controls(): void {}
    protected function render(): void { if (class_exists('Brehl_Vehicle_Damage_Module')) echo Brehl_Vehicle_Damage_Module::instance()->vehicle_form_panel(); }
}
