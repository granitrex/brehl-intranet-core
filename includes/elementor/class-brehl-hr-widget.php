<?php

defined('ABSPATH') || exit;

final class Brehl_HR_Widget extends My_Brehl_Widget_Base {
    public function get_name(): string {
        return 'brehl_hr_management';
    }

    public function get_title(): string {
        return __('My Brehl – Personalverwaltung', 'brehl-intranet');
    }

    public function get_icon(): string {
        return 'eicon-lock-user';
    }

    public function get_script_depends(): array {
        return array('brehl-intranet-hr');
    }

    protected function register_controls(): void {}

    protected function render(): void {
        if (!class_exists('Brehl_Employees_Module')) {
            return;
        }
        echo Brehl_Employees_Module::instance()->shortcode(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
