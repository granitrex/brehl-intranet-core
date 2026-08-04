<?php
defined('ABSPATH') || exit;
final class Brehl_Vehicle_Service_Request_Widget extends My_Brehl_Widget_Base {
    public function get_name(): string { return 'brehl_vehicle_service_request'; }
    public function get_title(): string { return __('My Brehl – Fuhrpark: Serviceanfrage', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-tools'; }
    public function get_categories(): array { return array('brehl-fuhrpark'); }
    public function get_style_depends(): array { return array('brehl-intranet','my-brehl-system','brehl-intranet-vehicle-damage'); }
    protected function register_controls(): void {}
    protected function render(): void { if (class_exists('Brehl_Vehicle_Service_Module')) echo Brehl_Vehicle_Service_Module::instance()->request_panel(); }
}
