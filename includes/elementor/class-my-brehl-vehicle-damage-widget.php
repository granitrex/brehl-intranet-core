<?php
defined('ABSPATH') || exit;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

final class My_Brehl_Vehicle_Damage_Widget extends Widget_Base {
    public function get_name(): string { return 'my-brehl-vehicle-damage'; }
    public function get_title(): string { return __('My Brehl – Fahrzeugschaden', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-car'; }
    public function get_categories(): array { return array('brehl-intranet'); }
    public function get_style_depends(): array { return array('brehl-intranet', 'my-brehl-system'); }

    protected function register_controls(): void {
        $this->start_controls_section('content', array('label' => __('Inhalt', 'brehl-intranet')));
        $this->add_control('notice', array(
            'type' => Controls_Manager::RAW_HTML,
            'raw' => __('Zeigt das vollständige Formular zur Schadenmeldung und die persönlichen Meldungen des Mitarbeiters.', 'brehl-intranet'),
            'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
        ));
        $this->end_controls_section();
    }

    protected function render(): void {
        if (!is_user_logged_in()) { return; }
        echo do_shortcode('[my_brehl_fahrzeugschaden]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
