<?php

defined('ABSPATH') || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

class My_Brehl_Logo_Widget extends Widget_Base {
    public function get_name(): string { return 'my-brehl-logo'; }
    public function get_title(): string { return __('My Brehl – Logo & Marke', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-site-logo'; }
    public function get_categories(): array { return array('brehl-intranet'); }
    public function get_style_depends(): array { return array('brehl-intranet'); }

    protected function register_controls(): void {
        $this->start_controls_section('content', array('label' => __('Inhalt', 'brehl-intranet')));
        $this->add_control('logo', array(
            'label' => __('Logo auswählen', 'brehl-intranet'),
            'type' => Controls_Manager::MEDIA,
            'dynamic' => array('active' => true),
            'default' => array('url' => ''),
        ));
        $this->add_control('brand_name', array(
            'label' => __('Markenname', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => 'My Brehl',
            'label_block' => true,
        ));
        $this->add_control('subtitle', array(
            'label' => __('Unterzeile', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => __('Mitarbeiterportal', 'brehl-intranet'),
            'label_block' => true,
        ));
        $this->add_control('show_text', array(
            'label' => __('Text neben dem Logo anzeigen', 'brehl-intranet'),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => 'yes',
        ));
        $this->add_control('link', array(
            'label' => __('Verlinkung', 'brehl-intranet'),
            'type' => Controls_Manager::URL,
            'default' => array('url' => home_url('/dashboard/')),
        ));
        $this->end_controls_section();

        $this->start_controls_section('style', array('label' => __('Gestaltung', 'brehl-intranet'), 'tab' => Controls_Manager::TAB_STYLE));
        $this->add_control('direction', array(
            'label' => __('Anordnung', 'brehl-intranet'),
            'type' => Controls_Manager::CHOOSE,
            'options' => array(
                'row' => array('title' => __('Nebeneinander', 'brehl-intranet'), 'icon' => 'eicon-h-align-left'),
                'column' => array('title' => __('Untereinander', 'brehl-intranet'), 'icon' => 'eicon-v-align-top'),
            ),
            'default' => 'row',
            'selectors' => array('{{WRAPPER}} .my-brehl-brand' => 'flex-direction: {{VALUE}};'),
        ));
        $this->add_responsive_control('logo_width', array(
            'label' => __('Logobreite', 'brehl-intranet'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => array('px', '%'),
            'range' => array('px' => array('min' => 20, 'max' => 400), '%' => array('min' => 5, 'max' => 100)),
            'default' => array('unit' => 'px', 'size' => 120),
            'selectors' => array('{{WRAPPER}} .my-brehl-brand__logo' => 'width: {{SIZE}}{{UNIT}};'),
        ));
        $this->add_control('title_color', array('label' => __('Titelfarbe', 'brehl-intranet'), 'type' => Controls_Manager::COLOR, 'default' => '#17235A', 'selectors' => array('{{WRAPPER}} .my-brehl-brand__title' => 'color: {{VALUE}};')));
        $this->add_control('subtitle_color', array('label' => __('Unterzeilenfarbe', 'brehl-intranet'), 'type' => Controls_Manager::COLOR, 'default' => '#687386', 'selectors' => array('{{WRAPPER}} .my-brehl-brand__subtitle' => 'color: {{VALUE}};')));
        $this->add_group_control(Group_Control_Typography::get_type(), array('name' => 'title_typography', 'selector' => '{{WRAPPER}} .my-brehl-brand__title'));
        $this->add_group_control(Group_Control_Typography::get_type(), array('name' => 'subtitle_typography', 'selector' => '{{WRAPPER}} .my-brehl-brand__subtitle'));
        $this->add_responsive_control('gap', array('label' => __('Abstand', 'brehl-intranet'), 'type' => Controls_Manager::SLIDER, 'range' => array('px' => array('min' => 0, 'max' => 60)), 'default' => array('unit' => 'px', 'size' => 14), 'selectors' => array('{{WRAPPER}} .my-brehl-brand' => 'gap: {{SIZE}}{{UNIT}};')));
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $url = !empty($s['link']['url']) ? $s['link']['url'] : '';
        $tag = $url ? 'a' : 'div';
        $attrs = $url ? ' href="' . esc_url($url) . '"' : '';
        if ($url && !empty($s['link']['is_external'])) { $attrs .= ' target="_blank"'; }
        if ($url && !empty($s['link']['nofollow'])) { $attrs .= ' rel="nofollow"'; }
        echo '<' . esc_attr($tag) . ' class="my-brehl-brand"' . $attrs . '>';
        if (!empty($s['logo']['url'])) {
            echo '<img class="my-brehl-brand__logo" src="' . esc_url($s['logo']['url']) . '" alt="' . esc_attr($s['brand_name']) . '">';
        }
        if ('yes' === ($s['show_text'] ?? '')) {
            echo '<span class="my-brehl-brand__copy">';
            echo '<strong class="my-brehl-brand__title">' . esc_html($s['brand_name']) . '</strong>';
            if (!empty($s['subtitle'])) { echo '<span class="my-brehl-brand__subtitle">' . esc_html($s['subtitle']) . '</span>'; }
            echo '</span>';
        }
        echo '</' . esc_attr($tag) . '>';
    }
}
