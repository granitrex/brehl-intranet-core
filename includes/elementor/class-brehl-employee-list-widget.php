<?php
defined('ABSPATH') || exit;
final class Brehl_Employee_List_Widget extends My_Brehl_Widget_Base {
    public function get_name(): string { return 'brehl_employee_list'; }
    public function get_title(): string { return __('My Brehl – Personal: Mitarbeiterliste', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-post-list'; }
    protected function register_controls(): void {}
    protected function render(): void { if (class_exists('Brehl_Employees_Module')) echo Brehl_Employees_Module::instance()->employee_list_widget(); }
}
