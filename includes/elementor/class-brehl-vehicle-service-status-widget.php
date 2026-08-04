<?php
defined('ABSPATH') || exit;
final class Brehl_Vehicle_Service_Status_Widget extends My_Brehl_Widget_Base {
    public function get_name(): string { return 'brehl_vehicle_service_status'; }
    public function get_title(): string { return __('My Brehl – Fuhrpark: Meine Serviceanfragen', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-history'; }
    public function get_categories(): array { return array('brehl-fuhrpark'); }
    public function get_style_depends(): array { return array('brehl-intranet','my-brehl-system'); }
    protected function register_controls(): void {}
    protected function render(): void { if (class_exists('Brehl_Vehicle_Service_Module')) echo Brehl_Vehicle_Service_Module::instance()->status_panel(); }
}
