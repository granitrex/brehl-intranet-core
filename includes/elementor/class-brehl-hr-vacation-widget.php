<?php
defined('ABSPATH') || exit;
final class Brehl_HR_Vacation_Widget extends My_Brehl_Widget_Base {
    public function get_name(): string { return 'brehl_hr_vacation'; }
    public function get_title(): string { return __('My Brehl – Personal: Urlaubsverwaltung', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-calendar'; }
    public function get_categories(): array { return array('brehl-personal'); }
    protected function register_controls(): void {}
    protected function render(): void { if (class_exists('Brehl_Vacation_Module')) echo Brehl_Vacation_Module::instance()->management_panel(); }
}
