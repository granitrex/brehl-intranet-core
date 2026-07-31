<?php
defined('ABSPATH') || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

final class My_Brehl_Section_Title_Widget extends Widget_Base {
    public function get_name(): string { return 'my-brehl-section-title'; }
    public function get_title(): string { return __('My Brehl – Abschnittstitel', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-heading'; }
    public function get_categories(): array { return array('brehl-intranet'); }
    public function get_style_depends(): array { return array('brehl-intranet'); }

    protected function register_controls(): void {
        $this->start_controls_section('content', array('label' => __('Inhalt', 'brehl-intranet')));
        $this->add_control('eyebrow', array('label'=>__('Kleine Überschrift','brehl-intranet'),'type'=>Controls_Manager::TEXT,'default'=>__('AKTUELLES AUS MY BREHL','brehl-intranet'),'label_block'=>true));
        $this->add_control('title', array('label'=>__('Überschrift','brehl-intranet'),'type'=>Controls_Manager::TEXT,'default'=>__('Unternehmensnews','brehl-intranet'),'label_block'=>true));
        $this->add_control('link_text', array('label'=>__('Linktext','brehl-intranet'),'type'=>Controls_Manager::TEXT,'default'=>__('Alle anzeigen','brehl-intranet')));
        $this->add_control('link', array('label'=>__('Link','brehl-intranet'),'type'=>Controls_Manager::URL,'placeholder'=>'https://'));
        $this->end_controls_section();
        $this->start_controls_section('style', array('label'=>__('Design','brehl-intranet'),'tab'=>Controls_Manager::TAB_STYLE));
        $this->add_control('eyebrow_color', array('label'=>__('Akzentfarbe','brehl-intranet'),'type'=>Controls_Manager::COLOR,'default'=>'#EC008C','selectors'=>array('{{WRAPPER}} .my-brehl-section-title__eyebrow'=>'color: {{VALUE}};','{{WRAPPER}} .my-brehl-section-title__link'=>'color: {{VALUE}};')));
        $this->add_control('title_color', array('label'=>__('Titelfarbe','brehl-intranet'),'type'=>Controls_Manager::COLOR,'default'=>'#17235A','selectors'=>array('{{WRAPPER}} .my-brehl-section-title__title'=>'color: {{VALUE}};')));
        $this->add_group_control(Group_Control_Typography::get_type(), array('name'=>'title_typography','selector'=>'{{WRAPPER}} .my-brehl-section-title__title'));
        $this->end_controls_section();
    }

    protected function render(): void {
        $s=$this->get_settings_for_display();
        $url = !empty($s['link']['url']) ? $s['link']['url'] : '';
        echo '<div class="my-brehl-section-title">';
        echo '<div><div class="my-brehl-section-title__eyebrow">'.esc_html($s['eyebrow']).'</div><h2 class="my-brehl-section-title__title">'.esc_html($s['title']).'</h2></div>';
        if ($url && !empty($s['link_text'])) echo '<a class="my-brehl-section-title__link" href="'.esc_url($url).'">'.esc_html($s['link_text']).' <span aria-hidden="true">→</span></a>';
        echo '</div>';
    }
}
