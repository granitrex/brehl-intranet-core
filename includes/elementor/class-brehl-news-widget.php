<?php

defined('ABSPATH') || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

final class Brehl_News_Widget extends My_Brehl_Widget_Base {
    public function get_name(): string {
        return 'brehl_news';
    }

    public function get_title(): string {
        return __('My Brehl – Unternehmensnews', 'brehl-intranet');
    }

    public function get_icon(): string {
        return 'eicon-posts-grid';
    }

    public function get_script_depends(): array {
        return array('brehl-intranet-news');
    }

    protected function register_controls(): void {
        $this->start_controls_section('content', array(
            'label' => __('Inhalt', 'brehl-intranet'),
        ));
        $this->add_control('eyebrow', array(
            'label' => __('Überzeile', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => __('Aktuelles aus My Brehl', 'brehl-intranet'),
        ));
        $this->add_control('title', array(
            'label' => __('Überschrift', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => __('Unternehmensnews', 'brehl-intranet'),
        ));
        $this->add_control('limit', array(
            'label' => __('Anzahl', 'brehl-intranet'),
            'type' => Controls_Manager::NUMBER,
            'default' => 6,
            'min' => 1,
            'max' => 24,
        ));
        $this->add_control('category', array(
            'label' => __('Kategorie', 'brehl-intranet'),
            'type' => Controls_Manager::SELECT,
            'options' => $this->category_options(),
            'default' => '',
        ));
        $this->add_control('show_filters', $this->switcher(__('Suche und Filter', 'brehl-intranet'), 'yes'));
        $this->add_control('show_all_url', array(
            'label' => __('Link „Alle anzeigen“', 'brehl-intranet'),
            'type' => Controls_Manager::URL,
            'placeholder' => 'https://…',
            'options' => array('url', 'is_external', 'nofollow'),
        ));
        $this->end_controls_section();

        $this->start_controls_section('card_content', array(
            'label' => __('Karteninhalt', 'brehl-intranet'),
        ));
        $this->add_control('show_image', $this->switcher(__('Beitragsbild', 'brehl-intranet'), 'yes'));
        $this->add_control('show_excerpt', $this->switcher(__('Kurztext', 'brehl-intranet'), 'yes'));
        $this->add_control('show_date', $this->switcher(__('Datum', 'brehl-intranet'), 'yes'));
        $this->add_control('show_author', $this->switcher(__('Autor', 'brehl-intranet'), 'yes'));
        $this->add_control('show_reading_time', $this->switcher(__('Lesedauer', 'brehl-intranet'), ''));
        $this->add_control('show_comments', $this->switcher(__('Kommentaranzahl', 'brehl-intranet'), 'yes'));
        $this->add_control('show_badges', $this->switcher(__('„Wichtig“- und „Neu“-Badges', 'brehl-intranet'), 'yes'));
        $this->add_control('show_read_more', $this->switcher(__('„Mehr lesen“-Hinweis', 'brehl-intranet'), 'yes'));
        $this->add_control('read_more_label', array(
            'label' => __('Beschriftung', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => __('Mehr lesen', 'brehl-intranet'),
            'condition' => array('show_read_more' => 'yes'),
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
            'default' => '2',
            'tablet_default' => '2',
            'mobile_default' => '1',
            'selectors' => array(
                '{{WRAPPER}} .brehl-news-grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));',
            ),
        ));
        $this->add_responsive_control('gap', array(
            'label' => __('Kartenabstand', 'brehl-intranet'),
            'type' => Controls_Manager::SLIDER,
            'range' => array('px' => array('min' => 0, 'max' => 60)),
            'default' => array('size' => 20),
            'selectors' => array('{{WRAPPER}} .brehl-news-grid' => 'gap: {{SIZE}}{{UNIT}};'),
        ));
        $this->add_responsive_control('image_height', array(
            'label' => __('Bildhöhe', 'brehl-intranet'),
            'type' => Controls_Manager::SLIDER,
            'range' => array('px' => array('min' => 120, 'max' => 500)),
            'default' => array('size' => 210),
            'condition' => array('show_image' => 'yes'),
            'selectors' => array('{{WRAPPER}} .brehl-news-media' => 'height: {{SIZE}}{{UNIT}};'),
        ));
        $this->end_controls_section();

        $this->start_controls_section('card_style', array(
            'label' => __('News-Karte', 'brehl-intranet'),
            'tab' => Controls_Manager::TAB_STYLE,
        ));
        $this->add_control('card_background', array(
            'label' => __('Hintergrund', 'brehl-intranet'),
            'type' => Controls_Manager::COLOR,
            'selectors' => array('{{WRAPPER}} .brehl-news-card' => 'background-color: {{VALUE}};'),
        ));
        $this->add_control('card_border_color', array(
            'label' => __('Rahmenfarbe', 'brehl-intranet'),
            'type' => Controls_Manager::COLOR,
            'selectors' => array('{{WRAPPER}} .brehl-news-card' => 'border-color: {{VALUE}};'),
        ));
        $this->add_responsive_control('card_radius', array(
            'label' => __('Eckenradius', 'brehl-intranet'),
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => array('px', '%'),
            'selectors' => array(
                '{{WRAPPER}} .brehl-news-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ));
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), array(
            'name' => 'card_shadow',
            'selector' => '{{WRAPPER}} .brehl-news-card',
        ));
        $this->add_responsive_control('card_padding', array(
            'label' => __('Text-Innenabstand', 'brehl-intranet'),
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => array('px', 'em'),
            'selectors' => array(
                '{{WRAPPER}} .brehl-news-body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ));
        $this->end_controls_section();

        $this->start_controls_section('typography', array(
            'label' => __('Typografie und Farben', 'brehl-intranet'),
            'tab' => Controls_Manager::TAB_STYLE,
        ));
        $this->add_group_control(Group_Control_Typography::get_type(), array(
            'name' => 'title_typography',
            'label' => __('Titel', 'brehl-intranet'),
            'selector' => '{{WRAPPER}} .brehl-news-body h3',
        ));
        $this->add_control('title_color', array(
            'label' => __('Titelfarbe', 'brehl-intranet'),
            'type' => Controls_Manager::COLOR,
            'selectors' => array('{{WRAPPER}} .brehl-news-body h3' => 'color: {{VALUE}};'),
        ));
        $this->add_group_control(Group_Control_Typography::get_type(), array(
            'name' => 'excerpt_typography',
            'label' => __('Kurztext', 'brehl-intranet'),
            'selector' => '{{WRAPPER}} .brehl-news-body p',
        ));
        $this->add_control('accent_color', array(
            'label' => __('Akzentfarbe', 'brehl-intranet'),
            'type' => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .brehl-news-eyebrow, {{WRAPPER}} .brehl-news-all, {{WRAPPER}} .brehl-news-meta span:first-child, {{WRAPPER}} .brehl-news-more' => 'color: {{VALUE}};',
                '{{WRAPPER}} .brehl-news-badge, {{WRAPPER}} .brehl-news-unread' => 'background-color: {{VALUE}};',
            ),
        ));
        $this->end_controls_section();
    }

    protected function render(): void {
        if (!is_user_logged_in() || !class_exists('Brehl_News_Module')) {
            return;
        }

        $settings = $this->get_settings_for_display();
        $link = is_array($settings['show_all_url'] ?? null) ? $settings['show_all_url'] : array();
        $options = array(
            'eyebrow' => (string) ($settings['eyebrow'] ?? ''),
            'show_image' => 'yes' === ($settings['show_image'] ?? 'yes'),
            'show_excerpt' => 'yes' === ($settings['show_excerpt'] ?? 'yes'),
            'show_date' => 'yes' === ($settings['show_date'] ?? 'yes'),
            'show_author' => 'yes' === ($settings['show_author'] ?? 'yes'),
            'show_reading_time' => 'yes' === ($settings['show_reading_time'] ?? ''),
            'show_comments' => 'yes' === ($settings['show_comments'] ?? 'yes'),
            'show_badges' => 'yes' === ($settings['show_badges'] ?? 'yes'),
            'show_read_more' => 'yes' === ($settings['show_read_more'] ?? 'yes'),
            'read_more_label' => (string) ($settings['read_more_label'] ?? __('Mehr lesen', 'brehl-intranet')),
            'show_all_attributes' => $this->link_attributes($link),
        );

        echo Brehl_News_Module::instance()->render_feed(
            (int) ($settings['limit'] ?? 6),
            (string) ($settings['title'] ?? __('Unternehmensnews', 'brehl-intranet')),
            (string) ($settings['category'] ?? ''),
            (string) ($link['url'] ?? ''),
            'yes' === ($settings['show_filters'] ?? 'yes'),
            $options
        ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    private function switcher(string $label, string $default): array {
        return array(
            'label' => $label,
            'type' => Controls_Manager::SWITCHER,
            'label_on' => __('Ja', 'brehl-intranet'),
            'label_off' => __('Nein', 'brehl-intranet'),
            'return_value' => 'yes',
            'default' => $default,
        );
    }

    private function category_options(): array {
        $options = array('' => __('Alle Kategorien', 'brehl-intranet'));
        $terms = get_terms(array(
            'taxonomy' => 'brehl_news_category',
            'hide_empty' => false,
        ));
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $options[$term->slug] = $term->name;
            }
        }
        return $options;
    }
}
