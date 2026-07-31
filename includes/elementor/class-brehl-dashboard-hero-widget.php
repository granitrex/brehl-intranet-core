<?php

defined('ABSPATH') || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

final class Brehl_Dashboard_Hero_Widget extends Widget_Base {
    public function get_name(): string { return 'my-brehl-hero'; }
    public function get_title(): string { return __('My Brehl – Hero & Kennzahlen', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-banner'; }
    public function get_categories(): array { return array('brehl-intranet'); }
    public function get_style_depends(): array { return array('brehl-intranet'); }

    protected function register_controls(): void {
        $this->start_controls_section('content', array('label' => __('Begrüßung', 'brehl-intranet')));

        $this->add_control('eyebrow', array(
            'label' => __('Kleine Überschrift', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => __('MY BREHL', 'brehl-intranet'),
        ));
        $this->add_control('morning', array(
            'label' => __('Morgens', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => __('Guten Morgen', 'brehl-intranet'),
        ));
        $this->add_control('afternoon', array(
            'label' => __('Tagsüber', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => __('Guten Tag', 'brehl-intranet'),
        ));
        $this->add_control('evening', array(
            'label' => __('Abends', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => __('Guten Abend', 'brehl-intranet'),
        ));
        $this->add_control('subtitle', array(
            'label' => __('Unterzeile', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => __('Schön, dass Sie da sind.', 'brehl-intranet'),
            'label_block' => true,
        ));
        $this->add_control('show_wave', array(
            'label' => __('Hand-Emoji anzeigen', 'brehl-intranet'),
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
        $this->end_controls_section();

        $this->start_controls_section('metrics_section', array('label' => __('Kennzahlen', 'brehl-intranet')));
        $repeater = new Repeater();
        $repeater->add_control('source', array(
            'label' => __('Wertquelle', 'brehl-intranet'),
            'type' => Controls_Manager::SELECT,
            'default' => 'manual',
            'options' => array(
                'manual' => __('Manuell', 'brehl-intranet'),
                'news' => __('Veröffentlichte News', 'brehl-intranet'),
                'users' => __('Mitarbeiterkonten', 'brehl-intranet'),
            ),
        ));
        $repeater->add_control('value', array(
            'label' => __('Manueller Wert', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => '0',
            'condition' => array('source' => 'manual'),
        ));
        $repeater->add_control('label', array(
            'label' => __('Bezeichnung', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => __('Neue News', 'brehl-intranet'),
        ));
        $repeater->add_control('icon', array(
            'label' => __('Icon', 'brehl-intranet'),
            'type' => Controls_Manager::ICONS,
            'default' => array('value' => 'fas fa-newspaper', 'library' => 'fa-solid'),
        ));
        $repeater->add_control('accent', array(
            'label' => __('Akzentfarbe', 'brehl-intranet'),
            'type' => Controls_Manager::COLOR,
            'default' => '#EC008C',
        ));
        $repeater->add_control('link', array(
            'label' => __('Link', 'brehl-intranet'),
            'type' => Controls_Manager::URL,
            'placeholder' => 'https://',
            'dynamic' => array('active' => true),
        ));
        $this->add_control('metrics', array(
            'label' => __('Karten', 'brehl-intranet'),
            'type' => Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'title_field' => '{{{ label }}}',
            'default' => array(
                array('source' => 'news', 'value' => '0', 'label' => __('Neue News', 'brehl-intranet'), 'accent' => '#EC008C', 'icon' => array('value' => 'fas fa-newspaper', 'library' => 'fa-solid')),
                array('source' => 'manual', 'value' => '—', 'label' => __('Neue Dokumente', 'brehl-intranet'), 'accent' => '#17235A', 'icon' => array('value' => 'fas fa-file-alt', 'library' => 'fa-solid')),
                array('source' => 'manual', 'value' => '—', 'label' => __('Resturlaub', 'brehl-intranet'), 'accent' => '#159A68', 'icon' => array('value' => 'fas fa-umbrella-beach', 'library' => 'fa-solid')),
                array('source' => 'users', 'value' => '0', 'label' => __('Mitarbeiter', 'brehl-intranet'), 'accent' => '#F59E0B', 'icon' => array('value' => 'fas fa-users', 'library' => 'fa-solid')),
            ),
        ));
        $this->end_controls_section();

        $this->start_controls_section('hero_style', array(
            'label' => __('Hero-Design', 'brehl-intranet'),
            'tab' => Controls_Manager::TAB_STYLE,
        ));
        $this->add_group_control(Group_Control_Background::get_type(), array(
            'name' => 'background',
            'types' => array('classic', 'gradient'),
            'fields_options' => array('background' => array('default' => 'classic'), 'color' => array('default' => '#FFF7FC')),
            'selector' => '{{WRAPPER}} .my-brehl-hero',
        ));
        $this->add_control('eyebrow_color', array(
            'label' => __('Akzenttext', 'brehl-intranet'),
            'type' => Controls_Manager::COLOR,
            'default' => '#EC008C',
            'selectors' => array('{{WRAPPER}} .my-brehl-hero__eyebrow' => 'color: {{VALUE}};'),
        ));
        $this->add_control('title_color', array(
            'label' => __('Überschrift', 'brehl-intranet'),
            'type' => Controls_Manager::COLOR,
            'default' => '#17235A',
            'selectors' => array('{{WRAPPER}} .my-brehl-hero__title' => 'color: {{VALUE}};'),
        ));
        $this->add_control('text_color', array(
            'label' => __('Text und Datum', 'brehl-intranet'),
            'type' => Controls_Manager::COLOR,
            'default' => '#687386',
            'selectors' => array(
                '{{WRAPPER}} .my-brehl-hero__subtitle' => 'color: {{VALUE}};',
                '{{WRAPPER}} .my-brehl-hero__date' => 'color: {{VALUE}};',
            ),
        ));
        $this->add_group_control(Group_Control_Typography::get_type(), array(
            'name' => 'title_typography',
            'label' => __('Überschrift Typografie', 'brehl-intranet'),
            'selector' => '{{WRAPPER}} .my-brehl-hero__title',
        ));
        $this->add_responsive_control('padding', array(
            'label' => __('Innenabstand', 'brehl-intranet'),
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => array('px'),
            'default' => array('top'=>38,'right'=>38,'bottom'=>38,'left'=>38,'unit'=>'px','isLinked'=>true),
            'mobile_default' => array('top'=>24,'right'=>20,'bottom'=>24,'left'=>20,'unit'=>'px','isLinked'=>false),
            'selectors' => array('{{WRAPPER}} .my-brehl-hero' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'),
        ));
        $this->add_control('radius', array(
            'label' => __('Eckenradius', 'brehl-intranet'),
            'type' => Controls_Manager::SLIDER,
            'range' => array('px' => array('min'=>0,'max'=>50)),
            'default' => array('unit'=>'px','size'=>26),
            'selectors' => array('{{WRAPPER}} .my-brehl-hero' => 'border-radius: {{SIZE}}{{UNIT}};'),
        ));
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), array(
            'name' => 'hero_shadow',
            'selector' => '{{WRAPPER}} .my-brehl-hero',
        ));
        $this->end_controls_section();

        $this->start_controls_section('card_style', array(
            'label' => __('Karten-Design', 'brehl-intranet'),
            'tab' => Controls_Manager::TAB_STYLE,
        ));
        $this->add_control('card_background', array(
            'label' => __('Hintergrund', 'brehl-intranet'),
            'type' => Controls_Manager::COLOR,
            'default' => '#FFFFFF',
            'selectors' => array('{{WRAPPER}} .my-brehl-hero__metric' => 'background-color: {{VALUE}};'),
        ));
        $this->add_control('card_text_color', array(
            'label' => __('Textfarbe', 'brehl-intranet'),
            'type' => Controls_Manager::COLOR,
            'default' => '#17235A',
            'selectors' => array(
                '{{WRAPPER}} .my-brehl-hero__metric strong' => 'color: {{VALUE}};',
                '{{WRAPPER}} .my-brehl-hero__metric-label' => 'color: {{VALUE}};',
            ),
        ));
        $this->add_responsive_control('card_min_height', array(
            'label' => __('Mindesthöhe', 'brehl-intranet'),
            'type' => Controls_Manager::SLIDER,
            'range' => array('px' => array('min'=>100,'max'=>240)),
            'default' => array('unit'=>'px','size'=>150),
            'selectors' => array('{{WRAPPER}} .my-brehl-hero__metric' => 'min-height: {{SIZE}}{{UNIT}};'),
        ));
        $this->add_control('card_radius', array(
            'label' => __('Eckenradius Karten', 'brehl-intranet'),
            'type' => Controls_Manager::SLIDER,
            'range' => array('px' => array('min'=>0,'max'=>40)),
            'default' => array('unit'=>'px','size'=>20),
            'selectors' => array('{{WRAPPER}} .my-brehl-hero__metric' => 'border-radius: {{SIZE}}{{UNIT}};'),
        ));
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), array(
            'name' => 'card_shadow',
            'selector' => '{{WRAPPER}} .my-brehl-hero__metric',
        ));
        $this->end_controls_section();
    }

    private function metric_value(array $metric): string {
        $source = isset($metric['source']) ? sanitize_key($metric['source']) : 'manual';
        if ('news' === $source) {
            $count = wp_count_posts('brehl_news');
            return isset($count->publish) ? (string) (int) $count->publish : '0';
        }
        if ('users' === $source) {
            $counts = count_users();
            return isset($counts['total_users']) ? (string) (int) $counts['total_users'] : '0';
        }
        return isset($metric['value']) && '' !== (string) $metric['value'] ? (string) $metric['value'] : '—';
    }

    protected function render(): void {
        if (!is_user_logged_in()) {
            return;
        }

        $s = $this->get_settings_for_display();
        $user = wp_get_current_user();
        $name = $user->first_name ?: $user->display_name;
        $hour = (int) current_time('G');
        $greeting = $hour < 12 ? $s['morning'] : ($hour < 18 ? $s['afternoon'] : $s['evening']);
        ?>
        <section class="my-brehl-hero">
            <div class="my-brehl-hero__intro">
                <?php if (!empty($s['eyebrow'])) : ?>
                    <p class="my-brehl-hero__eyebrow"><?php echo esc_html($s['eyebrow']); ?></p>
                <?php endif; ?>
                <h1 class="my-brehl-hero__title">
                    <?php echo esc_html($greeting . ', ' . $name); ?>
                    <?php if ('yes' === ($s['show_wave'] ?? '')) : ?><span class="my-brehl-hero__wave" aria-hidden="true">👋</span><?php endif; ?>
                </h1>
                <?php if (!empty($s['subtitle'])) : ?>
                    <p class="my-brehl-hero__subtitle"><?php echo esc_html($s['subtitle']); ?></p>
                <?php endif; ?>
                <?php if ('yes' === ($s['show_date'] ?? '')) : ?>
                    <p class="my-brehl-hero__date"><?php echo esc_html(wp_date('l, j. F Y')); ?></p>
                <?php endif; ?>
            </div>

            <?php if (!empty($s['metrics']) && is_array($s['metrics'])) : ?>
                <div class="my-brehl-hero__metrics">
                    <?php foreach ($s['metrics'] as $index => $metric) :
                        $tag = !empty($metric['link']['url']) ? 'a' : 'div';
                        $attrs = '';
                        if ('a' === $tag) {
                            $this->add_link_attributes('metric_link_' . $index, $metric['link']);
                            $attrs = $this->get_render_attribute_string('metric_link_' . $index);
                        }
                        ?>
                        <<?php echo esc_attr($tag); ?> class="my-brehl-hero__metric" style="--my-brehl-metric-accent:<?php echo esc_attr($metric['accent'] ?: '#EC008C'); ?>" <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                            <span class="my-brehl-hero__metric-accent"></span>
                            <?php if (!empty($metric['icon']['value'])) : ?>
                                <span class="my-brehl-hero__metric-icon"><?php Icons_Manager::render_icon($metric['icon'], array('aria-hidden' => 'true')); ?></span>
                            <?php endif; ?>
                            <strong><?php echo esc_html($this->metric_value($metric)); ?></strong>
                            <span class="my-brehl-hero__metric-label"><?php echo esc_html($metric['label']); ?></span>
                        </<?php echo esc_attr($tag); ?>>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php
    }
}
