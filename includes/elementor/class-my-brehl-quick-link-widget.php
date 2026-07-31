<?php
defined('ABSPATH') || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Widget_Base;

final class My_Brehl_Quick_Link_Widget extends Widget_Base {
    public function get_name(): string { return 'my-brehl-quick-link'; }
    public function get_title(): string { return __('My Brehl – Schnellzugriff', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-button'; }
    public function get_categories(): array { return array('brehl-intranet'); }
    public function get_style_depends(): array { return array('brehl-intranet'); }

    protected function register_controls(): void {
        $this->start_controls_section('content', array('label'=>__('Inhalt','brehl-intranet')));
        $this->add_control('icon', array('label'=>__('Icon','brehl-intranet'),'type'=>Controls_Manager::ICONS,'default'=>array('value'=>'fas fa-file-alt','library'=>'fa-solid')));
        $this->add_control('title', array('label'=>__('Titel','brehl-intranet'),'type'=>Controls_Manager::TEXT,'default'=>__('Dokumente','brehl-intranet')));
        $this->add_control('text', array('label'=>__('Beschreibung','brehl-intranet'),'type'=>Controls_Manager::TEXT,'default'=>__('Wichtige Dateien öffnen','brehl-intranet')));
        $this->add_control('link', array('label'=>__('Link','brehl-intranet'),'type'=>Controls_Manager::URL,'placeholder'=>'https://'));
        $this->end_controls_section();
        $this->start_controls_section('style', array('label'=>__('Design','brehl-intranet'),'tab'=>Controls_Manager::TAB_STYLE));
        $this->add_control('accent', array('label'=>__('Akzentfarbe','brehl-intranet'),'type'=>Controls_Manager::COLOR,'default'=>'#EC008C','selectors'=>array('{{WRAPPER}} .my-brehl-quick-link__icon'=>'color: {{VALUE}}; background: color-mix(in srgb, {{VALUE}} 12%, white);')));
        $this->add_control('background', array('label'=>__('Hintergrund','brehl-intranet'),'type'=>Controls_Manager::COLOR,'default'=>'#FFFFFF','selectors'=>array('{{WRAPPER}} .my-brehl-quick-link'=>'background: {{VALUE}};')));
        $this->add_control('radius', array('label'=>__('Eckenradius','brehl-intranet'),'type'=>Controls_Manager::SLIDER,'range'=>array('px'=>array('min'=>0,'max'=>40)),'default'=>array('size'=>20,'unit'=>'px'),'selectors'=>array('{{WRAPPER}} .my-brehl-quick-link'=>'border-radius: {{SIZE}}{{UNIT}};')));
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), array('name'=>'shadow','selector'=>'{{WRAPPER}} .my-brehl-quick-link'));
        $this->add_group_control(Group_Control_Typography::get_type(), array('name'=>'title_typography','selector'=>'{{WRAPPER}} .my-brehl-quick-link__title'));
        $this->end_controls_section();
    }

    protected function render(): void {
        $s=$this->get_settings_for_display();
        $tag = !empty($s['link']['url']) ? 'a' : 'div';
        $href = 'a' === $tag ? ' href="'.esc_url($s['link']['url']).'"' : '';
        echo '<'.$tag.' class="my-brehl-quick-link"'.$href.'>';
        echo '<span class="my-brehl-quick-link__icon">'; Icons_Manager::render_icon($s['icon'], array('aria-hidden'=>'true')); echo '</span>';
        echo '<span class="my-brehl-quick-link__content"><strong class="my-brehl-quick-link__title">'.esc_html($s['title']).'</strong><span class="my-brehl-quick-link__text">'.esc_html($s['text']).'</span></span><span class="my-brehl-quick-link__arrow">→</span>';
        echo '</'.$tag.'>';
    }
}
