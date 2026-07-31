<?php
defined('ABSPATH') || exit;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

final class My_Brehl_Tasks_Widget extends Widget_Base {
    public function get_name(): string { return 'my-brehl-tasks'; }
    public function get_title(): string { return __('My Brehl – Meine Aufgaben', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-check-circle'; }
    public function get_categories(): array { return array('brehl-intranet'); }
    public function get_style_depends(): array { return array('brehl-intranet', 'my-brehl-system'); }

    protected function register_controls(): void {
        $this->start_controls_section('content', array('label' => __('Inhalt', 'brehl-intranet')));
        $this->add_control('limit', array('label' => __('Anzahl Aufgaben', 'brehl-intranet'), 'type' => Controls_Manager::NUMBER, 'default' => 5, 'min' => 1, 'max' => 20));
        $this->end_controls_section();
    }

    protected function render(): void {
        if (!is_user_logged_in()) { return; }
        $settings = $this->get_settings_for_display();
        $limit = max(1, min(20, absint($settings['limit'] ?? 5)));
        echo do_shortcode('[my_brehl_aufgaben limit="' . $limit . '"]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
