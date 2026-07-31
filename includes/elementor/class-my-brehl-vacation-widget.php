<?php
defined('ABSPATH') || exit;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

final class My_Brehl_Vacation_Widget extends Widget_Base {
    public function get_name(): string { return 'my-brehl-vacation'; }
    public function get_title(): string { return __('My Brehl – Urlaub', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-calendar'; }
    public function get_categories(): array { return array('brehl-intranet'); }
    public function get_style_depends(): array { return array('brehl-intranet', 'my-brehl-system'); }

    protected function register_controls(): void {
        $this->start_controls_section('content', array('label' => __('Darstellung', 'brehl-intranet')));
        $this->add_control('view', array(
            'label' => __('Ansicht', 'brehl-intranet'),
            'type' => Controls_Manager::SELECT,
            'default' => 'kpi',
            'options' => array(
                'kpi' => __('Nur Resturlaub-Kachel', 'brehl-intranet'),
                'full' => __('Urlaubsantrag und Übersicht', 'brehl-intranet'),
            ),
        ));
        $this->end_controls_section();
    }

    protected function render(): void {
        if (!is_user_logged_in()) { return; }
        $settings = $this->get_settings_for_display();
        echo do_shortcode('full' === ($settings['view'] ?? 'kpi') ? '[my_brehl_urlaub]' : '[my_brehl_urlaub_kpi]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
