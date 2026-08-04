<?php

defined('ABSPATH') || exit;

final class My_Brehl_Logout_Widget extends \Elementor\Widget_Base {
    public function get_name(): string { return 'my_brehl_logout'; }
    public function get_title(): string { return __('My Brehl – Abmelden', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-sign-out'; }
    public function get_categories(): array { return array('brehl-intranet'); }
    public function get_style_depends(): array { return array('brehl-intranet'); }

    protected function register_controls(): void {
        $this->start_controls_section('content', array('label' => __('Inhalt', 'brehl-intranet')));
        $this->add_control('label', array('label'=>__('Beschriftung','brehl-intranet'),'type'=>\Elementor\Controls_Manager::TEXT,'default'=>__('Abmelden','brehl-intranet')));
        $this->add_control('show_icon', array('label'=>__('Symbol anzeigen','brehl-intranet'),'type'=>\Elementor\Controls_Manager::SWITCHER,'return_value'=>'yes','default'=>'yes'));
        $this->add_control('redirect_url', array('label'=>__('Ziel nach Abmeldung','brehl-intranet'),'type'=>\Elementor\Controls_Manager::URL,'placeholder'=>home_url('/login/')));
        $this->end_controls_section();
        $this->start_controls_section('style', array('label'=>__('Gestaltung','brehl-intranet'),'tab'=>\Elementor\Controls_Manager::TAB_STYLE));
        $this->add_control('text_color', array('label'=>__('Textfarbe','brehl-intranet'),'type'=>\Elementor\Controls_Manager::COLOR,'default'=>'#17245D','selectors'=>array('{{WRAPPER}} .my-brehl-logout'=>'color: {{VALUE}};')));
        $this->add_control('background', array('label'=>__('Hintergrund','brehl-intranet'),'type'=>\Elementor\Controls_Manager::COLOR,'default'=>'#FFFFFF','selectors'=>array('{{WRAPPER}} .my-brehl-logout'=>'background: {{VALUE}};')));
        $this->end_controls_section();
    }

    protected function render(): void {
        if (!is_user_logged_in()) return;
        $settings=$this->get_settings_for_display();
        $redirect=!empty($settings['redirect_url']['url'])?$settings['redirect_url']['url']:home_url('/login/');
        ?><a class="my-brehl-logout" href="<?php echo esc_url(wp_logout_url($redirect)); ?>"><?php if('yes'===($settings['show_icon']??'')): ?><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h5M14 8l4 4-4 4m4-4H8"/></svg><?php endif; ?><span><?php echo esc_html($settings['label']??__('Abmelden','brehl-intranet')); ?></span></a><?php
    }
}
