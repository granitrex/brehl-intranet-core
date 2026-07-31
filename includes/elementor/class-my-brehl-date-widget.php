<?php
defined('ABSPATH') || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

final class My_Brehl_Date_Widget extends Widget_Base {
    public function get_name(): string { return 'my-brehl-date'; }
    public function get_title(): string { return __('My Brehl – Datum', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-calendar'; }
    public function get_categories(): array { return array('brehl-intranet'); }
    public function get_style_depends(): array { return array('brehl-intranet'); }

    protected function register_controls(): void {
        $this->start_controls_section('content', array('label' => __('Inhalt', 'brehl-intranet')));
        $this->add_control('format', array(
            'label' => __('Datumsformat', 'brehl-intranet'),
            'type' => Controls_Manager::SELECT,
            'default' => 'long',
            'options' => array('long' => __('Mittwoch, 29. Juli 2026', 'brehl-intranet'), 'short' => __('29.07.2026', 'brehl-intranet'), 'custom' => __('Eigenes Format', 'brehl-intranet')),
        ));
        $this->add_control('custom_format', array(
            'label' => __('PHP-Datumsformat', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => 'd.m.Y',
            'condition' => array('format' => 'custom'),
        ));
        $this->add_control('prefix', array('label' => __('Text davor', 'brehl-intranet'), 'type' => Controls_Manager::TEXT, 'default' => ''));
        $this->end_controls_section();

        $this->start_controls_section('style', array('label' => __('Design', 'brehl-intranet'), 'tab' => Controls_Manager::TAB_STYLE));
        $this->add_control('color', array('label' => __('Farbe', 'brehl-intranet'), 'type' => Controls_Manager::COLOR, 'default' => '#687386', 'selectors' => array('{{WRAPPER}} .my-brehl-date' => 'color: {{VALUE}};')));
        $this->add_group_control(Group_Control_Typography::get_type(), array('name' => 'typography', 'selector' => '{{WRAPPER}} .my-brehl-date'));
        $this->add_control('align', array('label' => __('Ausrichtung', 'brehl-intranet'), 'type' => Controls_Manager::CHOOSE, 'options' => array('left'=>array('title'=>__('Links','brehl-intranet'),'icon'=>'eicon-text-align-left'),'center'=>array('title'=>__('Mitte','brehl-intranet'),'icon'=>'eicon-text-align-center'),'right'=>array('title'=>__('Rechts','brehl-intranet'),'icon'=>'eicon-text-align-right')), 'default'=>'left', 'selectors'=>array('{{WRAPPER}} .my-brehl-date'=>'text-align: {{VALUE}};')));
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $format = $s['format'] ?? 'long';
        if ('short' === $format) $value = wp_date('d.m.Y');
        elseif ('custom' === $format) $value = wp_date(sanitize_text_field($s['custom_format'] ?? 'd.m.Y'));
        else $value = wp_date('l, j. F Y');
        echo '<div class="my-brehl-date">' . esc_html(($s['prefix'] ?? '') . $value) . '</div>';
    }
}
