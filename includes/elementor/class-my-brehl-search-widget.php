<?php

defined('ABSPATH') || exit;

class My_Brehl_Search_Widget extends \Elementor\Widget_Base {
    public function get_name() { return 'my_brehl_search'; }
    public function get_title() { return __('My Brehl – Suche', 'brehl-intranet'); }
    public function get_icon() { return 'eicon-search'; }
    public function get_categories() { return array('brehl-intranet'); }
    public function get_style_depends() { return array('brehl-intranet'); }

    protected function register_controls() {
        $this->start_controls_section('content', array('label' => __('Inhalt', 'brehl-intranet')));
        $this->add_control('placeholder', array(
            'label' => __('Platzhalter', 'brehl-intranet'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Nach Personen, Dokumenten oder News suchen …', 'brehl-intranet'),
        ));
        $this->add_control('search_url', array(
            'label' => __('Zielseite der Suche', 'brehl-intranet'),
            'type' => \Elementor\Controls_Manager::URL,
            'placeholder' => home_url('/suche/'),
            'description' => __('Leer lassen, um die normale WordPress-Suche zu verwenden.', 'brehl-intranet'),
        ));
        $this->add_control('show_shortcut', array(
            'label' => __('Tastenkürzel anzeigen', 'brehl-intranet'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'label_on' => __('Ja', 'brehl-intranet'),
            'label_off' => __('Nein', 'brehl-intranet'),
            'return_value' => 'yes',
            'default' => 'yes',
        ));
        $this->end_controls_section();

        $this->start_controls_section('style', array('label' => __('Gestaltung', 'brehl-intranet'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE));
        $this->add_control('background', array('label' => __('Hintergrund', 'brehl-intranet'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#F6F7FB', 'selectors' => array('{{WRAPPER}} .my-brehl-search' => 'background: {{VALUE}};')));
        $this->add_control('text_color', array('label' => __('Textfarbe', 'brehl-intranet'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#667085', 'selectors' => array('{{WRAPPER}} .my-brehl-search input' => 'color: {{VALUE}};', '{{WRAPPER}} .my-brehl-search input::placeholder' => 'color: {{VALUE}};')));
        $this->add_responsive_control('height', array('label' => __('Höhe', 'brehl-intranet'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => array('px' => array('min' => 42, 'max' => 90)), 'default' => array('size' => 58), 'selectors' => array('{{WRAPPER}} .my-brehl-search' => 'min-height: {{SIZE}}{{UNIT}};')));
        $this->add_responsive_control('radius', array('label' => __('Eckenradius', 'brehl-intranet'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => array('px' => array('min' => 0, 'max' => 40)), 'default' => array('size' => 14), 'selectors' => array('{{WRAPPER}} .my-brehl-search' => 'border-radius: {{SIZE}}{{UNIT}};')));
        $this->end_controls_section();
    }

    protected function render() {
        $s = $this->get_settings_for_display();
        $action = !empty($s['search_url']['url']) ? $s['search_url']['url'] : home_url('/');
        ?>
        <form class="my-brehl-search" action="<?php echo esc_url($action); ?>" method="get" role="search">
            <span class="my-brehl-search__icon" aria-hidden="true">⌕</span>
            <input type="search" name="s" placeholder="<?php echo esc_attr($s['placeholder']); ?>" aria-label="<?php echo esc_attr__('Suche', 'brehl-intranet'); ?>">
            <?php if ('yes' === $s['show_shortcut']) : ?><span class="my-brehl-search__shortcut">⌘ K</span><?php endif; ?>
        </form>
        <?php
    }
}
