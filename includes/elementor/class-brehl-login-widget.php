<?php

defined('ABSPATH') || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

final class Brehl_Login_Widget extends Widget_Base {
    public function get_name(): string {
        return 'brehl-login';
    }

    public function get_title(): string {
        return __('Brehl Login', 'brehl-intranet');
    }

    public function get_icon(): string {
        return 'eicon-lock-user';
    }

    public function get_categories(): array {
        return array('brehl-intranet');
    }

    public function get_style_depends(): array {
        return array('brehl-intranet');
    }

    protected function register_controls(): void {
        $this->start_controls_section(
            'content_section',
            array('label' => __('Inhalt', 'brehl-intranet'))
        );

        $this->add_control('logo', array(
            'label' => __('Logo', 'brehl-intranet'),
            'type' => Controls_Manager::MEDIA,
        ));

        $this->add_control('title', array(
            'label' => __('Überschrift', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => __('Willkommen zurück', 'brehl-intranet'),
            'label_block' => true,
        ));

        $this->add_control('intro', array(
            'label' => __('Einleitung', 'brehl-intranet'),
            'type' => Controls_Manager::TEXTAREA,
            'default' => __('Bitte melden Sie sich mit Ihrer Personalnummer an.', 'brehl-intranet'),
        ));

        $this->add_control('personnel_label', array(
            'label' => __('Bezeichnung Personalnummer', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => __('Personalnummer', 'brehl-intranet'),
        ));

        $this->add_control('password_label', array(
            'label' => __('Bezeichnung Passwort', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => __('Passwort', 'brehl-intranet'),
        ));

        $this->add_control('remember_label', array(
            'label' => __('Bezeichnung angemeldet bleiben', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => __('Angemeldet bleiben', 'brehl-intranet'),
        ));

        $this->add_control('button_label', array(
            'label' => __('Button-Text', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => __('Anmelden', 'brehl-intranet'),
        ));

        $this->add_control('show_language', array(
            'label' => __('Sprachauswahl anzeigen', 'brehl-intranet'),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => __('Ja', 'brehl-intranet'),
            'label_off' => __('Nein', 'brehl-intranet'),
            'return_value' => 'yes',
            'default' => 'yes',
        ));

        $this->end_controls_section();

        $this->start_controls_section(
            'shell_style',
            array(
                'label' => __('Hintergrund', 'brehl-intranet'),
                'tab' => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_group_control(Group_Control_Background::get_type(), array(
            'name' => 'shell_background',
            'types' => array('classic', 'gradient'),
            'selector' => '{{WRAPPER}} .brehl-login-shell',
        ));

        $this->add_responsive_control('shell_min_height', array(
            'label' => __('Mindesthöhe', 'brehl-intranet'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => array('px', 'vh'),
            'range' => array(
                'px' => array('min' => 400, 'max' => 1400),
                'vh' => array('min' => 40, 'max' => 100),
            ),
            'default' => array('unit' => 'vh', 'size' => 100),
            'selectors' => array(
                '{{WRAPPER}} .brehl-login-shell' => 'min-height: {{SIZE}}{{UNIT}};',
            ),
        ));

        $this->end_controls_section();

        $this->start_controls_section(
            'card_style',
            array(
                'label' => __('Login-Fenster', 'brehl-intranet'),
                'tab' => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control('card_background', array(
            'label' => __('Hintergrundfarbe', 'brehl-intranet'),
            'type' => Controls_Manager::COLOR,
            'default' => '#ffffff',
            'selectors' => array(
                '{{WRAPPER}} .brehl-login-card' => 'background-color: {{VALUE}};',
            ),
        ));

        $this->add_responsive_control('card_width', array(
            'label' => __('Breite', 'brehl-intranet'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => array('px', '%'),
            'range' => array(
                'px' => array('min' => 280, 'max' => 800),
                '%' => array('min' => 30, 'max' => 100),
            ),
            'default' => array('unit' => 'px', 'size' => 430),
            'selectors' => array(
                '{{WRAPPER}} .brehl-login-card' => 'width: min(100%, {{SIZE}}{{UNIT}});',
            ),
        ));

        $this->add_responsive_control('card_padding', array(
            'label' => __('Innenabstand', 'brehl-intranet'),
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => array('px', 'em', '%'),
            'selectors' => array(
                '{{WRAPPER}} .brehl-login-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ));

        $this->add_control('card_radius', array(
            'label' => __('Eckenradius', 'brehl-intranet'),
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => array('px', '%'),
            'selectors' => array(
                '{{WRAPPER}} .brehl-login-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ));

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), array(
            'name' => 'card_shadow',
            'selector' => '{{WRAPPER}} .brehl-login-card',
        ));

        $this->end_controls_section();

        $this->start_controls_section(
            'typography_style',
            array(
                'label' => __('Texte', 'brehl-intranet'),
                'tab' => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control('title_color', array(
            'label' => __('Überschrift-Farbe', 'brehl-intranet'),
            'type' => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .brehl-login-card h1' => 'color: {{VALUE}};',
            ),
        ));

        $this->add_group_control(Group_Control_Typography::get_type(), array(
            'name' => 'title_typography',
            'selector' => '{{WRAPPER}} .brehl-login-card h1',
        ));

        $this->add_control('text_color', array(
            'label' => __('Textfarbe', 'brehl-intranet'),
            'type' => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .brehl-login-card > p, {{WRAPPER}} .brehl-login-card label' => 'color: {{VALUE}};',
            ),
        ));

        $this->end_controls_section();

        $this->start_controls_section(
            'button_style',
            array(
                'label' => __('Anmeldebutton', 'brehl-intranet'),
                'tab' => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control('button_background', array(
            'label' => __('Hintergrundfarbe', 'brehl-intranet'),
            'type' => Controls_Manager::COLOR,
            'default' => '#d4202f',
            'selectors' => array(
                '{{WRAPPER}} .brehl-login-card button' => 'background-color: {{VALUE}};',
            ),
        ));

        $this->add_control('button_color', array(
            'label' => __('Textfarbe', 'brehl-intranet'),
            'type' => Controls_Manager::COLOR,
            'default' => '#ffffff',
            'selectors' => array(
                '{{WRAPPER}} .brehl-login-card button' => 'color: {{VALUE}};',
            ),
        ));

        $this->add_control('button_radius', array(
            'label' => __('Eckenradius', 'brehl-intranet'),
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => array('px', '%'),
            'selectors' => array(
                '{{WRAPPER}} .brehl-login-card button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ));

        $this->end_controls_section();

        $this->start_controls_section(
            'input_style',
            array(
                'label' => __('Eingabefelder', 'brehl-intranet'),
                'tab' => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control('input_background', array(
            'label' => __('Hintergrundfarbe', 'brehl-intranet'),
            'type' => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .brehl-login-card input[type="text"], {{WRAPPER}} .brehl-login-card input[type="password"]' => 'background-color: {{VALUE}};',
            ),
        ));

        $this->add_control('input_border_color', array(
            'label' => __('Rahmenfarbe', 'brehl-intranet'),
            'type' => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .brehl-login-card input[type="text"], {{WRAPPER}} .brehl-login-card input[type="password"]' => 'border-color: {{VALUE}};',
            ),
        ));

        $this->add_control('input_radius', array(
            'label' => __('Eckenradius', 'brehl-intranet'),
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => array('px', '%'),
            'selectors' => array(
                '{{WRAPPER}} .brehl-login-card input[type="text"], {{WRAPPER}} .brehl-login-card input[type="password"]' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ));

        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();

        echo Brehl_Intranet::instance()->login_shortcode(array(
            'title' => $settings['title'] ?? '',
            'intro' => $settings['intro'] ?? '',
            'personnel_label' => $settings['personnel_label'] ?? '',
            'password_label' => $settings['password_label'] ?? '',
            'remember_label' => $settings['remember_label'] ?? '',
            'button_label' => $settings['button_label'] ?? '',
            'show_language' => $settings['show_language'] ?? 'no',
            'logo_url' => isset($settings['logo']['url']) ? $settings['logo']['url'] : '',
        ));
    }
}
