<?php
defined('ABSPATH') || exit;
class Brehl_News_Widget extends \Elementor\Widget_Base {
    public function get_name(): string { return 'brehl_news'; }
    public function get_title(): string { return __('My Brehl – Unternehmensnews', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-posts-grid'; }
    public function get_categories(): array { return array('brehl-intranet'); }
    public function get_style_depends(): array { return array('brehl-intranet'); }
    public function get_script_depends(): array { return array('brehl-intranet-news'); }
    protected function register_controls(): void {
        $this->start_controls_section('content', array('label' => __('Inhalt', 'brehl-intranet')));
        $this->add_control('title', array('label' => __('Überschrift', 'brehl-intranet'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Unternehmensnews', 'brehl-intranet')));
        $this->add_control('limit', array('label' => __('Anzahl', 'brehl-intranet'), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 6, 'min' => 1, 'max' => 24));
        $this->add_control('category', array('label' => __('Kategorie-Slug (optional)', 'brehl-intranet'), 'type' => \Elementor\Controls_Manager::TEXT, 'placeholder' => 'sicherheit'));
        $this->add_control('show_filters', array('label' => __('Suche und Filter anzeigen', 'brehl-intranet'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'label_on' => __('Ja', 'brehl-intranet'), 'label_off' => __('Nein', 'brehl-intranet'), 'return_value' => 'yes', 'default' => 'yes'));
        $this->add_control('show_all_url', array('label' => __('URL „Alle anzeigen“', 'brehl-intranet'), 'type' => \Elementor\Controls_Manager::URL, 'placeholder' => 'https://…', 'options' => array('url')));
        $this->end_controls_section();
    }
    protected function render(): void {
        if (!is_user_logged_in() || !class_exists('Brehl_News_Module')) return;
        $settings = $this->get_settings_for_display(); $url = isset($settings['show_all_url']['url']) ? (string) $settings['show_all_url']['url'] : '';
        echo Brehl_News_Module::instance()->render_feed((int) ($settings['limit'] ?? 6), (string) ($settings['title'] ?? 'Unternehmensnews'), (string) ($settings['category'] ?? ''), $url, 'yes' === ($settings['show_filters'] ?? 'yes')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
