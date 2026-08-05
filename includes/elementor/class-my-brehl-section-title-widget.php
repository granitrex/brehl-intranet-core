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
        $this->add_control('html_tag', array('label'=>__('HTML-Ebene','brehl-intranet'),'type'=>Controls_Manager::SELECT,'default'=>'h2','options'=>array('h1'=>'H1','h2'=>'H2','h3'=>'H3','h4'=>'H4')));
        $this->add_control('show_eyebrow', array('label'=>__('Kleine Überschrift anzeigen','brehl-intranet'),'type'=>Controls_Manager::SWITCHER,'return_value'=>'yes','default'=>'yes'));
        $this->add_control('link_text', array('label'=>__('Linktext','brehl-intranet'),'type'=>Controls_Manager::TEXT,'default'=>__('Alle anzeigen','brehl-intranet')));
        $this->add_control('link', array('label'=>__('Link','brehl-intranet'),'type'=>Controls_Manager::URL,'placeholder'=>'https://'));
        $this->end_controls_section();
        $this->start_controls_section('style', array('label'=>__('Design','brehl-intranet'),'tab'=>Controls_Manager::TAB_STYLE));
        $this->add_control('eyebrow_color', array('label'=>__('Akzentfarbe','brehl-intranet'),'type'=>Controls_Manager::COLOR,'default'=>'#EC008C','selectors'=>array('{{WRAPPER}} .my-brehl-section-title__eyebrow'=>'color: {{VALUE}};','{{WRAPPER}} .my-brehl-section-title__link'=>'color: {{VALUE}};')));
        $this->add_control('title_color', array('label'=>__('Titelfarbe','brehl-intranet'),'type'=>Controls_Manager::COLOR,'default'=>'#17235A','selectors'=>array('{{WRAPPER}} .my-brehl-section-title__title'=>'color: {{VALUE}};')));
        $this->add_group_control(Group_Control_Typography::get_type(), array('name'=>'title_typography','selector'=>'{{WRAPPER}} .my-brehl-section-title__title'));
        $this->add_responsive_control('alignment', array('label'=>__('Ausrichtung','brehl-intranet'),'type'=>Controls_Manager::CHOOSE,'options'=>array('left'=>array('title'=>__('Links','brehl-intranet'),'icon'=>'eicon-text-align-left'),'center'=>array('title'=>__('Zentriert','brehl-intranet'),'icon'=>'eicon-text-align-center'),'right'=>array('title'=>__('Rechts','brehl-intranet'),'icon'=>'eicon-text-align-right')),'default'=>'left','selectors'=>array('{{WRAPPER}} .my-brehl-section-title'=>'text-align:{{VALUE}};')));
        $this->end_controls_section();
    }

    protected function render(): void {
        $s=$this->get_settings_for_display();
        $url = !empty($s['link']['url']) ? $s['link']['url'] : '';
        echo '<div class="my-brehl-section-title">';
        $tag=in_array($s['html_tag']??'h2',array('h1','h2','h3','h4'),true)?$s['html_tag']:'h2';
        echo '<div>';
        if('yes'===($s['show_eyebrow']??'yes')&&!empty($s['eyebrow']))echo '<div class="my-brehl-section-title__eyebrow">'.esc_html($s['eyebrow']).'</div>';
        echo '<'.$tag.' class="my-brehl-section-title__title">'.esc_html($s['title']).'</'.$tag.'></div>';
        if ($url && !empty($s['link_text'])) echo '<a class="my-brehl-section-title__link" href="'.esc_url($url).'">'.esc_html($s['link_text']).' <span aria-hidden="true">→</span></a>';
        echo '</div>';
    }
}
