<?php
defined('ABSPATH') || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

final class My_Brehl_Avatar_Widget extends Widget_Base {
    public function get_name(): string { return 'my-brehl-avatar'; }
    public function get_title(): string { return __('My Brehl – Benutzeravatar', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-person'; }
    public function get_categories(): array { return array('brehl-intranet'); }
    public function get_style_depends(): array { return array('brehl-intranet'); }

    protected function register_controls(): void {
        $this->start_controls_section('content', array('label'=>__('Inhalt','brehl-intranet')));
        $this->add_control('show_name', array('label'=>__('Name anzeigen','brehl-intranet'),'type'=>Controls_Manager::SWITCHER,'return_value'=>'yes','default'=>'yes'));
        $this->add_control('show_role', array('label'=>__('Rolle anzeigen','brehl-intranet'),'type'=>Controls_Manager::SWITCHER,'return_value'=>'yes','default'=>''));
        $this->end_controls_section();
        $this->start_controls_section('style', array('label'=>__('Design','brehl-intranet'),'tab'=>Controls_Manager::TAB_STYLE));
        $this->add_control('size', array('label'=>__('Avatargröße','brehl-intranet'),'type'=>Controls_Manager::SLIDER,'range'=>array('px'=>array('min'=>28,'max'=>120)),'default'=>array('size'=>48,'unit'=>'px'),'selectors'=>array('{{WRAPPER}} .my-brehl-avatar__image'=>'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};')));
        $this->add_control('accent', array('label'=>__('Hintergrundfarbe','brehl-intranet'),'type'=>Controls_Manager::COLOR,'default'=>'#EC008C','selectors'=>array('{{WRAPPER}} .my-brehl-avatar__image'=>'background: {{VALUE}};')));
        $this->add_control('text_color', array('label'=>__('Textfarbe','brehl-intranet'),'type'=>Controls_Manager::COLOR,'default'=>'#17235A','selectors'=>array('{{WRAPPER}} .my-brehl-avatar__name'=>'color: {{VALUE}};')));
        $this->add_group_control(Group_Control_Typography::get_type(), array('name'=>'name_typography','selector'=>'{{WRAPPER}} .my-brehl-avatar__name'));
        $this->end_controls_section();
    }

    protected function render(): void {
        if (!is_user_logged_in()) return;
        $s=$this->get_settings_for_display(); $u=wp_get_current_user();
        $name=$u->first_name ?: $u->display_name; $initial=mb_strtoupper(mb_substr($name,0,1));
        $role = !empty($u->roles) ? translate_user_role(wp_roles()->roles[$u->roles[0]]['name'] ?? $u->roles[0]) : '';
        echo '<div class="my-brehl-avatar"><span class="my-brehl-avatar__image">'.esc_html($initial).'</span>';
        if ('yes'===($s['show_name']??'')) { echo '<span class="my-brehl-avatar__content"><strong class="my-brehl-avatar__name">'.esc_html($name).'</strong>'; if ('yes'===($s['show_role']??'') && $role) echo '<span class="my-brehl-avatar__role">'.esc_html($role).'</span>'; echo '</span>'; }
        echo '</div>';
    }
}
