<?php
defined('ABSPATH') || exit;
final class Brehl_Employee_Form_Widget extends My_Brehl_Widget_Base {
    public function get_name(): string { return 'brehl_employee_form'; }
    public function get_title(): string { return __('My Brehl – Personal: Mitarbeiterformular', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-form-horizontal'; }
    public function get_script_depends(): array { return array('brehl-intranet-hr'); }
    protected function register_controls(): void {}
    protected function render(): void { if (class_exists('Brehl_Employees_Module')) echo Brehl_Employees_Module::instance()->employee_form_widget(); }
}
