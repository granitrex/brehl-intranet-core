<?php

defined('ABSPATH') || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Widget_Base;

/**
 * Shared base class for all new My Brehl Elementor widgets.
 *
 * It keeps common metadata, card controls and safe link rendering in one place.
 * Existing widgets remain compatible and can migrate to this class step by step.
 */
abstract class My_Brehl_Widget_Base extends Widget_Base {
    public function get_categories(): array {
        return array('brehl-intranet');
    }

    public function get_style_depends(): array {
        return array('brehl-intranet');
    }

    protected function register_card_style_controls(string $selector, string $section_id = 'style_card'): void {
        $this->start_controls_section($section_id, array(
            'label' => __('Karte', 'brehl-intranet'),
            'tab' => Controls_Manager::TAB_STYLE,
        ));

        $this->add_group_control(Group_Control_Background::get_type(), array(
            'name' => $section_id . '_background',
            'types' => array('classic', 'gradient'),
            'selector' => $selector,
        ));
        $this->add_group_control(Group_Control_Border::get_type(), array(
            'name' => $section_id . '_border',
            'selector' => $selector,
        ));
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), array(
            'name' => $section_id . '_shadow',
            'selector' => $selector,
        ));
        $this->add_responsive_control($section_id . '_padding', array(
            'label' => __('Innenabstand', 'brehl-intranet'),
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => array('px', 'em', '%'),
            'selectors' => array($selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'),
        ));
        $this->add_responsive_control($section_id . '_radius', array(
            'label' => __('Eckenradius', 'brehl-intranet'),
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => array('px', '%'),
            'selectors' => array($selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'),
        ));
        $this->end_controls_section();
    }

    protected function link_attributes(array $link): string {
        if (empty($link['url'])) {
            return '';
        }

        $attributes = ' href="' . esc_url($link['url']) . '"';
        if (!empty($link['is_external'])) {
            $attributes .= ' target="_blank"';
        }

        $rel = array();
        if (!empty($link['nofollow'])) {
            $rel[] = 'nofollow';
        }
        if (!empty($link['is_external'])) {
            $rel[] = 'noopener';
        }
        if ($rel) {
            $attributes .= ' rel="' . esc_attr(implode(' ', $rel)) . '"';
        }

        return $attributes;
    }
}
