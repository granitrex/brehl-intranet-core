<?php

defined('ABSPATH') || exit;

class My_Brehl_Profile_Widget extends \Elementor\Widget_Base {
    public function get_name() { return 'my_brehl_profile'; }
    public function get_title() { return __('My Brehl – Benutzerprofil', 'brehl-intranet'); }
    public function get_icon() { return 'eicon-person'; }
    public function get_categories() { return array('brehl-intranet'); }
    public function get_style_depends() { return array('brehl-intranet'); }

    protected function register_controls() {
        $this->start_controls_section('content', array('label' => __('Inhalt', 'brehl-intranet')));
        $this->add_control('show_role', array('label' => __('Position anzeigen', 'brehl-intranet'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes'));
        $this->add_control('show_dropdown', array('label' => __('Aufklappmenü anzeigen', 'brehl-intranet'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes'));
        $this->add_control('profile_url', array('label' => __('Profilseite', 'brehl-intranet'), 'type' => \Elementor\Controls_Manager::URL, 'placeholder' => home_url('/mein-profil/')));
        $this->add_control('documents_url', array('label' => __('Meine Dokumente', 'brehl-intranet'), 'type' => \Elementor\Controls_Manager::URL, 'placeholder' => home_url('/dokumente/')));
        $this->end_controls_section();
        $this->start_controls_section('style', array('label' => __('Gestaltung', 'brehl-intranet'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE));
        $this->add_control('accent', array('label' => __('Akzentfarbe', 'brehl-intranet'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#EC008C', 'selectors' => array('{{WRAPPER}} .my-brehl-profile__avatar' => 'background: {{VALUE}};')));
        $this->add_control('name_color', array('label' => __('Namensfarbe', 'brehl-intranet'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#101B4D', 'selectors' => array('{{WRAPPER}} .my-brehl-profile__name' => 'color: {{VALUE}};')));
        $this->end_controls_section();
    }

    protected function render() {
        if (!is_user_logged_in()) { return; }
        $s = $this->get_settings_for_display();
        $user = wp_get_current_user();
        $name = $user->display_name ?: $user->user_login;
        $initials = '';
        foreach (preg_split('/\s+/', trim($name)) as $part) { if ($part !== '') { $initials .= mb_strtoupper(mb_substr($part, 0, 1)); } }
        $initials = mb_substr($initials ?: 'MB', 0, 2);
        $position = get_user_meta($user->ID, 'brehl_position', true);
        if (!$position) { $position = __('Mitarbeiter', 'brehl-intranet'); }
        $profile = !empty($s['profile_url']['url']) ? $s['profile_url']['url'] : home_url('/mein-profil/');
        $documents = !empty($s['documents_url']['url']) ? $s['documents_url']['url'] : home_url('/dokumente/');
        $logout = wp_logout_url(home_url('/login/'));
        ?>
        <div class="my-brehl-profile<?php echo 'yes' === $s['show_dropdown'] ? ' has-dropdown' : ''; ?>">
            <button class="my-brehl-profile__trigger" type="button" aria-expanded="false">
                <span class="my-brehl-profile__avatar"><?php echo esc_html($initials); ?></span>
                <span class="my-brehl-profile__text"><strong class="my-brehl-profile__name"><?php echo esc_html($name); ?></strong><?php if ('yes' === $s['show_role']) : ?><small><?php echo esc_html($position); ?></small><?php endif; ?></span>
                <?php if ('yes' === $s['show_dropdown']) : ?><span class="my-brehl-profile__chevron">⌄</span><?php endif; ?>
            </button>
            <?php if ('yes' === $s['show_dropdown']) : ?>
                <div class="my-brehl-profile__menu">
                    <a href="<?php echo esc_url($profile); ?>"><?php esc_html_e('Mein Profil', 'brehl-intranet'); ?></a>
                    <a href="<?php echo esc_url($documents); ?>"><?php esc_html_e('Meine Dokumente', 'brehl-intranet'); ?></a>
                    <a href="<?php echo esc_url($logout); ?>"><?php esc_html_e('Abmelden', 'brehl-intranet'); ?></a>
                </div>
            <?php endif; ?>
        </div>
        <script>(function(){const r=document.currentScript.previousElementSibling;if(!r||!r.classList.contains('has-dropdown'))return;const b=r.querySelector('.my-brehl-profile__trigger');b.addEventListener('click',function(e){e.stopPropagation();r.classList.toggle('is-open');b.setAttribute('aria-expanded',r.classList.contains('is-open')?'true':'false')});document.addEventListener('click',()=>r.classList.remove('is-open'));})();</script>
        <?php
    }
}
