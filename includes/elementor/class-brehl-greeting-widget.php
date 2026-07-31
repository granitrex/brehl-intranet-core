<?php
defined('ABSPATH') || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

final class Brehl_Greeting_Widget extends Widget_Base {
    public function get_name(): string { return 'brehl-greeting'; }
    public function get_title(): string { return __('My Brehl – Begrüßung', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-banner'; }
    public function get_categories(): array { return array('brehl-intranet'); }
    public function get_style_depends(): array { return array('brehl-intranet'); }

    protected function register_controls(): void {
        $this->start_controls_section('content', array('label' => __('Inhalt', 'brehl-intranet')));

        $this->add_control('eyebrow', array(
            'label' => __('Kleine Überschrift', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => __('MY BREHL', 'brehl-intranet'),
            'label_block' => true,
        ));
        $this->add_control('morning', array('label' => __('Morgens', 'brehl-intranet'), 'type' => Controls_Manager::TEXT, 'default' => __('Guten Morgen', 'brehl-intranet')));
        $this->add_control('afternoon', array('label' => __('Tagsüber', 'brehl-intranet'), 'type' => Controls_Manager::TEXT, 'default' => __('Guten Tag', 'brehl-intranet')));
        $this->add_control('evening', array('label' => __('Abends', 'brehl-intranet'), 'type' => Controls_Manager::TEXT, 'default' => __('Guten Abend', 'brehl-intranet')));
        $this->add_control('subtitle', array(
            'label' => __('Unterzeile', 'brehl-intranet'),
            'type' => Controls_Manager::TEXTAREA,
            'default' => __('Schön, dass Sie wieder da sind.', 'brehl-intranet'),
            'rows' => 2,
        ));
        $this->add_control('show_wave', array(
            'label' => __('Winke-Icon anzeigen', 'brehl-intranet'),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => 'yes',
        ));
        $this->add_control('show_date', array(
            'label' => __('Datum anzeigen', 'brehl-intranet'),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => 'yes',
        ));
        $this->add_control('date_format', array(
            'label' => __('Datumsformat', 'brehl-intranet'),
            'type' => Controls_Manager::SELECT,
            'default' => 'long',
            'options' => array(
                'long' => __('Donnerstag, 30. Juli 2026', 'brehl-intranet'),
                'medium' => __('30. Juli 2026', 'brehl-intranet'),
                'short' => __('30.07.2026', 'brehl-intranet'),
            ),
            'condition' => array('show_date' => 'yes'),
        ));
        $this->end_controls_section();

        $this->start_controls_section('box_style', array('label' => __('Karte', 'brehl-intranet'), 'tab' => Controls_Manager::TAB_STYLE));
        $this->add_group_control(Group_Control_Background::get_type(), array(
            'name' => 'background',
            'types' => array('classic', 'gradient'),
            'fields_options' => array('background' => array('default' => 'classic'), 'color' => array('default' => '#FFFFFF')),
            'selector' => '{{WRAPPER}} .brehl-greeting',
        ));
        $this->add_group_control(Group_Control_Border::get_type(), array('name' => 'border', 'selector' => '{{WRAPPER}} .brehl-greeting'));
        $this->add_responsive_control('padding', array(
            'label' => __('Innenabstand', 'brehl-intranet'),
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => array('px', 'em'),
            'default' => array('top' => 28, 'right' => 28, 'bottom' => 28, 'left' => 28, 'unit' => 'px', 'isLinked' => true),
            'selectors' => array('{{WRAPPER}} .brehl-greeting' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'),
        ));
        $this->add_responsive_control('min_height', array(
            'label' => __('Mindesthöhe', 'brehl-intranet'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => array('px'),
            'range' => array('px' => array('min' => 120, 'max' => 500)),
            'selectors' => array('{{WRAPPER}} .brehl-greeting' => 'min-height: {{SIZE}}{{UNIT}};'),
        ));
        $this->add_control('radius', array(
            'label' => __('Eckenradius', 'brehl-intranet'),
            'type' => Controls_Manager::SLIDER,
            'range' => array('px' => array('min' => 0, 'max' => 60)),
            'default' => array('unit' => 'px', 'size' => 24),
            'selectors' => array('{{WRAPPER}} .brehl-greeting' => 'border-radius: {{SIZE}}{{UNIT}};'),
        ));
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), array('name' => 'shadow', 'selector' => '{{WRAPPER}} .brehl-greeting'));
        $this->add_responsive_control('alignment', array(
            'label' => __('Ausrichtung', 'brehl-intranet'),
            'type' => Controls_Manager::CHOOSE,
            'options' => array(
                'left' => array('title' => __('Links', 'brehl-intranet'), 'icon' => 'eicon-text-align-left'),
                'center' => array('title' => __('Zentriert', 'brehl-intranet'), 'icon' => 'eicon-text-align-center'),
                'right' => array('title' => __('Rechts', 'brehl-intranet'), 'icon' => 'eicon-text-align-right'),
            ),
            'default' => 'left',
            'selectors' => array('{{WRAPPER}} .brehl-greeting' => 'text-align: {{VALUE}};', '{{WRAPPER}} .brehl-greeting__date' => 'justify-content: {{VALUE}};'),
            'selectors_dictionary' => array('left' => 'flex-start', 'center' => 'center', 'right' => 'flex-end'),
        ));
        $this->end_controls_section();

        $this->start_controls_section('text_style', array('label' => __('Texte', 'brehl-intranet'), 'tab' => Controls_Manager::TAB_STYLE));
        $this->add_control('eyebrow_color', array('label' => __('Kleine Überschrift', 'brehl-intranet'), 'type' => Controls_Manager::COLOR, 'default' => '#EC008C', 'selectors' => array('{{WRAPPER}} .brehl-greeting__eyebrow' => 'color: {{VALUE}};')));
        $this->add_group_control(Group_Control_Typography::get_type(), array('name' => 'eyebrow_typography', 'selector' => '{{WRAPPER}} .brehl-greeting__eyebrow'));
        $this->add_control('title_color', array('label' => __('Begrüßung', 'brehl-intranet'), 'type' => Controls_Manager::COLOR, 'default' => '#17235A', 'selectors' => array('{{WRAPPER}} .brehl-greeting__title' => 'color: {{VALUE}};')));
        $this->add_group_control(Group_Control_Typography::get_type(), array('name' => 'title_typography', 'selector' => '{{WRAPPER}} .brehl-greeting__title'));
        $this->add_control('subtitle_color', array('label' => __('Unterzeile', 'brehl-intranet'), 'type' => Controls_Manager::COLOR, 'default' => '#687386', 'selectors' => array('{{WRAPPER}} .brehl-greeting__subtitle' => 'color: {{VALUE}};')));
        $this->add_group_control(Group_Control_Typography::get_type(), array('name' => 'subtitle_typography', 'selector' => '{{WRAPPER}} .brehl-greeting__subtitle'));
        $this->add_control('date_color', array('label' => __('Datum', 'brehl-intranet'), 'type' => Controls_Manager::COLOR, 'default' => '#687386', 'selectors' => array('{{WRAPPER}} .brehl-greeting__date' => 'color: {{VALUE}};')));
        $this->add_group_control(Group_Control_Typography::get_type(), array('name' => 'date_typography', 'selector' => '{{WRAPPER}} .brehl-greeting__date'));
        $this->add_control('accent', array('label' => __('Akzentfarbe', 'brehl-intranet'), 'type' => Controls_Manager::COLOR, 'default' => '#EC008C', 'selectors' => array('{{WRAPPER}} .brehl-greeting__date::before' => 'background: {{VALUE}};', '{{WRAPPER}} .brehl-greeting::after' => 'background: {{VALUE}}1A;')));
        $this->end_controls_section();
    }

    protected function render(): void {
        if (!is_user_logged_in()) {
            return;
        }

        $s = $this->get_settings_for_display();
        $hour = (int) current_time('G');
        $greeting = $hour < 12 ? $s['morning'] : ($hour < 18 ? $s['afternoon'] : $s['evening']);
        $user = wp_get_current_user();
        $name = trim((string) $user->first_name) ?: $user->display_name;

        switch ($s['date_format'] ?? 'long') {
            case 'short':
                $date = wp_date('d.m.Y');
                break;
            case 'medium':
                $date = wp_date('j. F Y');
                break;
            default:
                $date = wp_date('l, j. F Y');
        }
        ?>
        <section class="brehl-greeting" aria-label="<?php echo esc_attr__('Persönliche Begrüßung', 'brehl-intranet'); ?>">
            <div class="brehl-greeting__content">
                <?php if (!empty($s['eyebrow'])) : ?>
                    <p class="brehl-greeting__eyebrow"><?php echo esc_html($s['eyebrow']); ?></p>
                <?php endif; ?>
                <h2 class="brehl-greeting__title">
                    <?php echo esc_html($greeting . ', ' . $name); ?>
                    <?php if ('yes' === ($s['show_wave'] ?? '')) : ?><span class="brehl-greeting__wave" aria-hidden="true">👋</span><?php endif; ?>
                </h2>
                <?php if (!empty($s['subtitle'])) : ?><p class="brehl-greeting__subtitle"><?php echo esc_html($s['subtitle']); ?></p><?php endif; ?>
                <?php if ('yes' === ($s['show_date'] ?? '')) : ?><p class="brehl-greeting__date"><?php echo esc_html($date); ?></p><?php endif; ?>
            </div>
        </section>
        <?php
    }
}
