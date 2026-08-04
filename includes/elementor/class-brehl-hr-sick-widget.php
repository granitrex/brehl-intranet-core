<?php
defined('ABSPATH') || exit;
final class Brehl_HR_Sick_Widget extends My_Brehl_Widget_Base {
    public function get_name(): string { return 'brehl_hr_sick'; }
    public function get_title(): string { return __('My Brehl – Personal: Krankmeldungen', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-heart-o'; }
    public function get_categories(): array { return array('brehl-personal'); }
    protected function register_controls(): void {}
    protected function render(): void { if (class_exists('Brehl_Sick_Leave_Module')) echo Brehl_Sick_Leave_Module::instance()->management_panel(); }
}
