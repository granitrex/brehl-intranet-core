<?php
defined('ABSPATH') || exit;
final class Brehl_HR_Metrics_Widget extends My_Brehl_Widget_Base {
    public function get_name(): string { return 'brehl_hr_metrics'; }
    public function get_title(): string { return __('My Brehl – Personal: Kennzahlen', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-counter'; }
    protected function register_controls(): void {}
    protected function render(): void { if (class_exists('Brehl_Employees_Module')) echo Brehl_Employees_Module::instance()->metrics_widget(); }
}
