<?php

defined('ABSPATH') || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
require_once BREHL_INTR_DIR . 'includes/core/class-brehl-kpi-service.php';

use Elementor\Widget_Base;

class My_Brehl_KPI_Widget extends My_Brehl_Widget_Base {
    public function get_name(): string { return 'my-brehl-kpi'; }
    public function get_title(): string { return __('My Brehl – KPI-Karte', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-counter'; }
    public function get_categories(): array { return array('brehl-intranet'); }
    public function get_style_depends(): array { return array('brehl-intranet'); }

    protected function register_controls(): void {
        $this->start_controls_section('content', array('label' => __('Inhalt', 'brehl-intranet')));
        $this->add_control('value_source', array(
            'label' => __('Datenquelle', 'brehl-intranet'),
            'type' => Controls_Manager::SELECT,
            'default' => 'manual',
            'options' => array(
                'manual' => __('Manueller Wert', 'brehl-intranet'),
                'vacation_available' => __('Urlaub: verfügbare Tage', 'brehl-intranet'),
                'vacation_pending' => __('Urlaub: beantragte Tage', 'brehl-intranet'),
                'vacation_approved' => __('Urlaub: genehmigte Tage', 'brehl-intranet'),
                'unread_notifications' => __('Ungelesene Benachrichtigungen', 'brehl-intranet'),
                'open_tasks' => __('Offene Aufgaben', 'brehl-intranet'),
                'open_vehicle_damages' => __('Offene Fahrzeugschäden', 'brehl-intranet'),
                'unread_news' => __('Ungelesene Unternehmensnews', 'brehl-intranet'),
                'employee_count' => __('Anzahl Mitarbeiter', 'brehl-intranet'),
            ),
        ));
        $this->add_control('manual_value', array(
            'label' => __('Manueller Wert', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => '24',
            'condition' => array('value_source' => 'manual'),
        ));
        $this->add_control('title', array(
            'label' => __('Titel', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => __('Resturlaub', 'brehl-intranet'),
            'label_block' => true,
        ));
        $this->add_control('suffix', array(
            'label' => __('Zusatz hinter dem Wert', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => __('Tage', 'brehl-intranet'),
            'placeholder' => __('z. B. Tage oder Neu', 'brehl-intranet'),
        ));
        $this->add_control('subtitle', array(
            'label' => __('Untertitel', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => '',
            'label_block' => true,
        ));
        $this->add_control('icon', array(
            'label' => __('Icon', 'brehl-intranet'),
            'type' => Controls_Manager::ICONS,
            'default' => array('value' => 'fas fa-umbrella-beach', 'library' => 'fa-solid'),
        ));
        $this->add_control('show_icon', array(
            'label' => __('Icon anzeigen', 'brehl-intranet'),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => __('Ja', 'brehl-intranet'),
            'label_off' => __('Nein', 'brehl-intranet'),
            'return_value' => 'yes',
            'default' => 'yes',
        ));
        $this->add_control('badge', array(
            'label' => __('Badge', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => '',
            'placeholder' => __('z. B. Neu', 'brehl-intranet'),
        ));
        $this->add_control('link', array(
            'label' => __('Verlinkung', 'brehl-intranet'),
            'type' => Controls_Manager::URL,
            'placeholder' => home_url('/urlaub/'),
            'dynamic' => array('active' => true),
        ));
        $this->end_controls_section();

        $this->start_controls_section('layout', array('label' => __('Anordnung', 'brehl-intranet')));
        $this->add_responsive_control('alignment', array(
            'label' => __('Ausrichtung', 'brehl-intranet'),
            'type' => Controls_Manager::CHOOSE,
            'options' => array(
                'left' => array('title' => __('Links', 'brehl-intranet'), 'icon' => 'eicon-text-align-left'),
                'center' => array('title' => __('Mittig', 'brehl-intranet'), 'icon' => 'eicon-text-align-center'),
                'right' => array('title' => __('Rechts', 'brehl-intranet'), 'icon' => 'eicon-text-align-right'),
            ),
            'default' => 'left',
            'selectors' => array('{{WRAPPER}} .my-brehl-kpi' => 'text-align: {{VALUE}};'),
        ));
        $this->add_responsive_control('minimum_height', array(
            'label' => __('Mindesthöhe', 'brehl-intranet'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => array('px'),
            'range' => array('px' => array('min' => 80, 'max' => 400)),
            'default' => array('unit' => 'px', 'size' => 170),
            'selectors' => array('{{WRAPPER}} .my-brehl-kpi' => 'min-height: {{SIZE}}{{UNIT}};'),
        ));
        $this->end_controls_section();

        $this->start_controls_section('style_card', array('label' => __('Karte', 'brehl-intranet'), 'tab' => Controls_Manager::TAB_STYLE));
        $this->start_controls_tabs('card_tabs');
        $this->start_controls_tab('card_normal', array('label' => __('Normal', 'brehl-intranet')));
        $this->add_group_control(Group_Control_Background::get_type(), array('name' => 'background', 'types' => array('classic', 'gradient'), 'selector' => '{{WRAPPER}} .my-brehl-kpi'));
        $this->add_group_control(Group_Control_Border::get_type(), array('name' => 'border', 'selector' => '{{WRAPPER}} .my-brehl-kpi'));
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), array('name' => 'shadow', 'selector' => '{{WRAPPER}} .my-brehl-kpi'));
        $this->end_controls_tab();
        $this->start_controls_tab('card_hover', array('label' => __('Hover', 'brehl-intranet')));
        $this->add_control('hover_background', array('label' => __('Hintergrundfarbe', 'brehl-intranet'), 'type' => Controls_Manager::COLOR, 'selectors' => array('{{WRAPPER}} .my-brehl-kpi:hover' => 'background-color: {{VALUE}};')));
        $this->add_control('hover_translate', array(
            'label' => __('Anheben', 'brehl-intranet'),
            'type' => Controls_Manager::SLIDER,
            'range' => array('px' => array('min' => 0, 'max' => 20)),
            'default' => array('unit' => 'px', 'size' => 4),
            'selectors' => array('{{WRAPPER}} .my-brehl-kpi:hover' => 'transform: translateY(-{{SIZE}}{{UNIT}});'),
        ));
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), array('name' => 'hover_shadow', 'selector' => '{{WRAPPER}} .my-brehl-kpi:hover'));
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->add_responsive_control('padding', array(
            'label' => __('Innenabstand', 'brehl-intranet'),
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => array('px', 'em', '%'),
            'default' => array('top' => 24, 'right' => 24, 'bottom' => 24, 'left' => 24, 'unit' => 'px', 'isLinked' => true),
            'selectors' => array('{{WRAPPER}} .my-brehl-kpi' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'),
        ));
        $this->add_control('radius', array(
            'label' => __('Eckenradius', 'brehl-intranet'),
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => array('px', '%'),
            'default' => array('top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20, 'unit' => 'px', 'isLinked' => true),
            'selectors' => array('{{WRAPPER}} .my-brehl-kpi' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'),
        ));
        $this->end_controls_section();

        $this->start_controls_section('style_icon', array('label' => __('Icon', 'brehl-intranet'), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array('show_icon' => 'yes')));
        $this->add_control('icon_color', array('label' => __('Iconfarbe', 'brehl-intranet'), 'type' => Controls_Manager::COLOR, 'default' => '#EC008C', 'selectors' => array('{{WRAPPER}} .my-brehl-kpi__icon' => 'color: {{VALUE}};')));
        $this->add_control('icon_background', array('label' => __('Icon-Hintergrund', 'brehl-intranet'), 'type' => Controls_Manager::COLOR, 'default' => '#FCE8F5', 'selectors' => array('{{WRAPPER}} .my-brehl-kpi__icon' => 'background-color: {{VALUE}};')));
        $this->add_responsive_control('icon_size', array('label' => __('Icongröße', 'brehl-intranet'), 'type' => Controls_Manager::SLIDER, 'range' => array('px' => array('min' => 12, 'max' => 80)), 'default' => array('unit' => 'px', 'size' => 24), 'selectors' => array('{{WRAPPER}} .my-brehl-kpi__icon i' => 'font-size: {{SIZE}}{{UNIT}};', '{{WRAPPER}} .my-brehl-kpi__icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};')));
        $this->add_responsive_control('icon_box_size', array('label' => __('Iconfläche', 'brehl-intranet'), 'type' => Controls_Manager::SLIDER, 'range' => array('px' => array('min' => 30, 'max' => 120)), 'default' => array('unit' => 'px', 'size' => 52), 'selectors' => array('{{WRAPPER}} .my-brehl-kpi__icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};')));
        $this->add_control('icon_radius', array('label' => __('Icon-Radius', 'brehl-intranet'), 'type' => Controls_Manager::SLIDER, 'range' => array('px' => array('min' => 0, 'max' => 60)), 'default' => array('unit' => 'px', 'size' => 14), 'selectors' => array('{{WRAPPER}} .my-brehl-kpi__icon' => 'border-radius: {{SIZE}}{{UNIT}};')));
        $this->end_controls_section();

        $this->start_controls_section('style_text', array('label' => __('Texte', 'brehl-intranet'), 'tab' => Controls_Manager::TAB_STYLE));
        $this->add_control('title_color', array('label' => __('Titelfarbe', 'brehl-intranet'), 'type' => Controls_Manager::COLOR, 'default' => '#687386', 'selectors' => array('{{WRAPPER}} .my-brehl-kpi__title' => 'color: {{VALUE}};')));
        $this->add_group_control(Group_Control_Typography::get_type(), array('name' => 'title_typography', 'selector' => '{{WRAPPER}} .my-brehl-kpi__title'));
        $this->add_control('value_color', array('label' => __('Wertfarbe', 'brehl-intranet'), 'type' => Controls_Manager::COLOR, 'default' => '#17235A', 'selectors' => array('{{WRAPPER}} .my-brehl-kpi__value' => 'color: {{VALUE}};')));
        $this->add_group_control(Group_Control_Typography::get_type(), array('name' => 'value_typography', 'selector' => '{{WRAPPER}} .my-brehl-kpi__value'));
        $this->add_control('suffix_color', array('label' => __('Zusatzfarbe', 'brehl-intranet'), 'type' => Controls_Manager::COLOR, 'default' => '#17235A', 'selectors' => array('{{WRAPPER}} .my-brehl-kpi__suffix' => 'color: {{VALUE}};')));
        $this->add_group_control(Group_Control_Typography::get_type(), array('name' => 'suffix_typography', 'selector' => '{{WRAPPER}} .my-brehl-kpi__suffix'));
        $this->add_control('subtitle_color', array('label' => __('Untertitelfarbe', 'brehl-intranet'), 'type' => Controls_Manager::COLOR, 'default' => '#8A94A6', 'selectors' => array('{{WRAPPER}} .my-brehl-kpi__subtitle' => 'color: {{VALUE}};')));
        $this->add_group_control(Group_Control_Typography::get_type(), array('name' => 'subtitle_typography', 'selector' => '{{WRAPPER}} .my-brehl-kpi__subtitle'));
        $this->end_controls_section();

        $this->start_controls_section('style_badge', array('label' => __('Badge', 'brehl-intranet'), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array('badge!' => '')));
        $this->add_control('badge_color', array('label' => __('Textfarbe', 'brehl-intranet'), 'type' => Controls_Manager::COLOR, 'default' => '#EC008C', 'selectors' => array('{{WRAPPER}} .my-brehl-kpi__badge' => 'color: {{VALUE}};')));
        $this->add_control('badge_background', array('label' => __('Hintergrund', 'brehl-intranet'), 'type' => Controls_Manager::COLOR, 'default' => '#FCE8F5', 'selectors' => array('{{WRAPPER}} .my-brehl-kpi__badge' => 'background-color: {{VALUE}};')));
        $this->add_group_control(Group_Control_Typography::get_type(), array('name' => 'badge_typography', 'selector' => '{{WRAPPER}} .my-brehl-kpi__badge'));
        $this->end_controls_section();
    }

    private function table_exists(string $table): bool {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
    }

    private function format_number(float $value): string {
        return rtrim(rtrim(number_format($value, 1, ',', '.'), '0'), ',');
    }

    private function vacation_value(string $type): string {
        global $wpdb;
        $uid = get_current_user_id();
        $year = (int) wp_date('Y');
        $table = $wpdb->prefix . 'brehl_vacation_requests';
        if (!$uid || !$this->table_exists($table)) { return '0'; }

        $entitlement_raw = get_user_meta($uid, 'brehl_vacation_entitlement_' . $year, true);
        $entitlement = '' === $entitlement_raw ? 30.0 : (float) $entitlement_raw;
        $carryover = (float) get_user_meta($uid, 'brehl_vacation_carryover_' . $year, true);
        $approved = (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(requested_days),0) FROM {$table} WHERE user_id=%d AND status='genehmigt' AND vacation_type='urlaub' AND YEAR(start_date)=%d", $uid, $year));
        $pending = (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(requested_days),0) FROM {$table} WHERE user_id=%d AND status='eingereicht' AND vacation_type='urlaub' AND YEAR(start_date)=%d", $uid, $year));

        if ('vacation_pending' === $type) { return $this->format_number($pending); }
        if ('vacation_approved' === $type) { return $this->format_number($approved); }
        return $this->format_number($entitlement + $carryover - $approved - $pending);
    }

    private function unread_news_count(): int {
        if (!is_user_logged_in() || !post_type_exists('brehl_news')) { return 0; }
        $query = new WP_Query(array('post_type' => 'brehl_news', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true));
        $read = array_map('intval', (array) get_user_meta(get_current_user_id(), 'my_brehl_read_news', true));
        return count(array_diff(array_map('intval', $query->posts), $read));
    }

    private function dynamic_value(string $source, string $manual): string {
        global $wpdb;
        $uid = get_current_user_id();
        if ('manual' === $source) { return $manual; }
        if (0 === strpos($source, 'vacation_')) { return $this->vacation_value($source); }
        if ('unread_news' === $source) { return (string) $this->unread_news_count(); }
        if ('employee_count' === $source) { return (string) count_users()['total_users']; }
        if (!$uid) { return '0'; }

        if ('unread_notifications' === $source) {
            $table = $wpdb->prefix . 'my_brehl_notifications';
            if (!$this->table_exists($table)) { return '0'; }
            return (string) (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE (user_id=0 OR user_id=%d) AND is_read=0", $uid));
        }
        if ('open_tasks' === $source) {
            $table = $wpdb->prefix . 'my_brehl_tasks';
            if (!$this->table_exists($table)) { return '0'; }
            return (string) (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE user_id=%d AND status='offen'", $uid));
        }
        if ('open_vehicle_damages' === $source) {
            $table = $wpdb->prefix . 'brehl_vehicle_damages';
            if (!$this->table_exists($table)) { return '0'; }
            return (string) (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE user_id=%d AND status NOT IN ('erledigt','abgelehnt')", $uid));
        }
        return '0';
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $value = Brehl_KPI_Service::value((string) $s['value_source'], (string) $s['manual_value']);
        $url = !empty($s['link']['url']) ? $s['link']['url'] : '';
        $tag = $url ? 'a' : 'div';
        $attrs = '';
        if ($url) {
            $attrs .= ' href="' . esc_url($url) . '"';
            if (!empty($s['link']['is_external'])) { $attrs .= ' target="_blank"'; }
            if (!empty($s['link']['nofollow'])) { $attrs .= ' rel="nofollow"'; }
        }

        echo '<' . esc_attr($tag) . ' class="my-brehl-kpi"' . $attrs . '>';
        echo '<div class="my-brehl-kpi__top">';
        if ('yes' === $s['show_icon'] && !empty($s['icon']['value'])) {
            echo '<span class="my-brehl-kpi__icon">';
            Icons_Manager::render_icon($s['icon'], array('aria-hidden' => 'true'));
            echo '</span>';
        }
        if (!empty($s['badge'])) { echo '<span class="my-brehl-kpi__badge">' . esc_html($s['badge']) . '</span>'; }
        echo '</div>';
        if (!empty($s['title'])) { echo '<span class="my-brehl-kpi__title">' . esc_html($s['title']) . '</span>'; }
        echo '<div class="my-brehl-kpi__number"><strong class="my-brehl-kpi__value">' . esc_html($value) . '</strong>';
        if (!empty($s['suffix'])) { echo '<span class="my-brehl-kpi__suffix">' . esc_html($s['suffix']) . '</span>'; }
        echo '</div>';
        if (!empty($s['subtitle'])) { echo '<span class="my-brehl-kpi__subtitle">' . esc_html($s['subtitle']) . '</span>'; }
        echo '</' . esc_attr($tag) . '>';
    }
}
