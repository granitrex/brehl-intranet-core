<?php

defined('ABSPATH') || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

final class Brehl_Documents_Widget extends My_Brehl_Widget_Base {
    public function get_name(): string {
        return 'brehl_documents';
    }

    public function get_title(): string {
        return __('My Brehl – Dokumente', 'brehl-intranet');
    }

    public function get_icon(): string {
        return 'eicon-document-file';
    }

    public function get_script_depends(): array {
        return array('brehl-intranet-documents');
    }

    protected function register_controls(): void {
        $this->start_controls_section('content', array('label' => __('Inhalt', 'brehl-intranet')));
        $this->add_control('limit', array(
            'label' => __('Anzahl', 'brehl-intranet'),
            'type' => Controls_Manager::NUMBER,
            'default' => 12,
            'min' => 1,
            'max' => 50,
        ));
        $this->add_control('category', array(
            'label' => __('Kategorie', 'brehl-intranet'),
            'type' => Controls_Manager::SELECT,
            'options' => $this->category_options(),
            'default' => '',
        ));
        $this->add_control('show_search', array(
            'label' => __('Suche und Filter', 'brehl-intranet'),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => __('Ja', 'brehl-intranet'),
            'label_off' => __('Nein', 'brehl-intranet'),
            'return_value' => 'yes',
            'default' => 'yes',
        ));
        $this->end_controls_section();

        $this->start_controls_section('layout', array(
            'label' => __('Layout', 'brehl-intranet'),
            'tab' => Controls_Manager::TAB_STYLE,
        ));
        $this->add_responsive_control('columns', array(
            'label' => __('Spalten', 'brehl-intranet'),
            'type' => Controls_Manager::SELECT,
            'options' => array('1' => '1', '2' => '2', '3' => '3', '4' => '4'),
            'default' => '3',
            'tablet_default' => '2',
            'mobile_default' => '1',
            'selectors' => array(
                '{{WRAPPER}} .brehl-documents__grid' => 'grid-template-columns:repeat({{VALUE}},minmax(0,1fr));',
            ),
        ));
        $this->add_responsive_control('gap', array(
            'label' => __('Abstand', 'brehl-intranet'),
            'type' => Controls_Manager::SLIDER,
            'range' => array('px' => array('min' => 0, 'max' => 60)),
            'default' => array('size' => 18),
            'selectors' => array('{{WRAPPER}} .brehl-documents__grid' => 'gap:{{SIZE}}{{UNIT}};'),
        ));
        $this->end_controls_section();

        $this->start_controls_section('card', array(
            'label' => __('Dokumentkarte', 'brehl-intranet'),
            'tab' => Controls_Manager::TAB_STYLE,
        ));
        $this->add_control('background', array(
            'label' => __('Hintergrund', 'brehl-intranet'),
            'type' => Controls_Manager::COLOR,
            'selectors' => array('{{WRAPPER}} .brehl-document-card' => 'background:{{VALUE}};'),
        ));
        $this->add_control('accent', array(
            'label' => __('Akzentfarbe', 'brehl-intranet'),
            'type' => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .brehl-document-card__icon, {{WRAPPER}} .brehl-document-card__footer a, {{WRAPPER}} .brehl-document-card__meta span' => 'color:{{VALUE}};',
                '{{WRAPPER}} .brehl-document-card__meta strong' => 'background:{{VALUE}};',
            ),
        ));
        $this->add_responsive_control('padding', array(
            'label' => __('Innenabstand', 'brehl-intranet'),
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => array('px', 'em'),
            'selectors' => array(
                '{{WRAPPER}} .brehl-document-card' => 'padding:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ));
        $this->add_responsive_control('radius', array(
            'label' => __('Eckenradius', 'brehl-intranet'),
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => array('px', '%'),
            'selectors' => array(
                '{{WRAPPER}} .brehl-document-card' => 'border-radius:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ));
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), array(
            'name' => 'shadow',
            'selector' => '{{WRAPPER}} .brehl-document-card',
        ));
        $this->add_group_control(Group_Control_Typography::get_type(), array(
            'name' => 'title_typography',
            'label' => __('Titel', 'brehl-intranet'),
            'selector' => '{{WRAPPER}} .brehl-document-card h3',
        ));
        $this->end_controls_section();
    }

    protected function render(): void {
        if (!is_user_logged_in() || !class_exists('Brehl_Documents_Module')) {
            return;
        }
        $settings = $this->get_settings_for_display();
        echo Brehl_Documents_Module::instance()->render_library(
            (int) ($settings['limit'] ?? 12),
            (string) ($settings['category'] ?? ''),
            'yes' === ($settings['show_search'] ?? 'yes')
        ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    private function category_options(): array {
        $options = array('' => __('Alle Kategorien', 'brehl-intranet'));
        $terms = get_terms(array('taxonomy' => 'brehl_document_category', 'hide_empty' => false));
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $options[$term->slug] = $term->name;
            }
        }
        return $options;
    }
}
