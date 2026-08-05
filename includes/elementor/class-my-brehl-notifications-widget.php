<?php

defined('ABSPATH') || exit;

class My_Brehl_Notifications_Widget extends \Elementor\Widget_Base {
    public function get_name() { return 'my_brehl_notifications'; }
    public function get_title() { return __('My Brehl – Benachrichtigungen', 'brehl-intranet'); }
    public function get_icon() { return 'eicon-alert'; }
    public function get_categories() { return array('brehl-intranet'); }
    public function get_style_depends() { return array('brehl-intranet'); }
    public function get_script_depends() { return array('brehl-intranet-news'); }

    protected function register_controls() {
        $this->start_controls_section('content', array('label' => __('Inhalt', 'brehl-intranet')));
        $this->add_control('limit', array('label' => __('Anzahl Meldungen', 'brehl-intranet'), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 5, 'min' => 1, 'max' => 10));
        $this->add_control('empty_text', array('label' => __('Text ohne Meldungen', 'brehl-intranet'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Keine neuen Benachrichtigungen.', 'brehl-intranet')));
        $this->end_controls_section();
        $this->start_controls_section('style', array('label' => __('Gestaltung', 'brehl-intranet'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE));
        $this->add_control('badge', array('label' => __('Badge-Farbe', 'brehl-intranet'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#EC008C', 'selectors' => array('{{WRAPPER}} .my-brehl-notifications__badge' => 'background: {{VALUE}};')));
        $this->end_controls_section();
    }

    protected function render() {
        if (!is_user_logged_in()) { return; }
        $s = $this->get_settings_for_display();
        $notifications = class_exists('Brehl_Notifications_Module')
            ? Brehl_Notifications_Module::instance()->unread_items(get_current_user_id(), max(1, (int) $s['limit']))
            : array();
        $count = count($notifications);
        ?>
        <div class="my-brehl-notifications">
            <button class="my-brehl-notifications__trigger" type="button" aria-expanded="false" aria-label="<?php esc_attr_e('Benachrichtigungen', 'brehl-intranet'); ?>">
                <svg class="my-brehl-notifications__bell" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 6.5-2.5 7.2-3 9h18c-.5-1.8-3-2.5-3-9Z"/><path d="M10 21h4"/></svg><?php if ($count > 0) : ?><span class="my-brehl-notifications__badge"><?php echo esc_html($count > 99 ? '99+' : $count); ?></span><?php endif; ?>
            </button>
            <div class="my-brehl-notifications__panel">
                <div class="my-brehl-notifications__head"><strong><?php esc_html_e('Benachrichtigungen', 'brehl-intranet'); ?></strong><?php if($notifications): ?><a class="my-brehl-notifications__read-all" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=my_brehl_mark_all_notifications'),'my_brehl_mark_all_notifications')); ?>"><?php esc_html_e('Alle gelesen','brehl-intranet'); ?></a><?php else: ?><span><?php echo esc_html($count); ?> <?php esc_html_e('neu', 'brehl-intranet'); ?></span><?php endif; ?></div>
                <?php if ($notifications) : ?>
                    <div class="my-brehl-notifications__list">
                    <?php foreach ($notifications as $item) : ?>
                        <?php if ('system' === $item['kind']) : ?>
                            <a class="my-brehl-notifications__item" href="<?php echo esc_url($item['action_url']); ?>"><span class="dot"></span><span><strong><?php echo esc_html($item['title']); ?></strong><small><?php echo esc_html($item['category'] . ' · ' . $item['date']); ?> <?php esc_html_e('zuvor', 'brehl-intranet'); ?></small></span></a>
                        <?php else : ?>
                            <button type="button" class="my-brehl-notifications__item" data-my-brehl-notification-news="<?php echo esc_attr((string) $item['id']); ?>"><span class="dot"></span><span><strong><?php echo esc_html($item['title']); ?></strong><small><?php echo esc_html($item['category'] . ' · ' . $item['date']); ?> <?php esc_html_e('zuvor', 'brehl-intranet'); ?></small></span></button>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </div>
                <?php else : ?><p class="my-brehl-notifications__empty"><?php echo esc_html($s['empty_text']); ?></p><?php endif; ?>
            </div>
        </div>
        <script>(function(){const r=document.currentScript.previousElementSibling,b=r.querySelector('.my-brehl-notifications__trigger');b.addEventListener('click',function(e){e.stopPropagation();r.classList.toggle('is-open');b.setAttribute('aria-expanded',r.classList.contains('is-open')?'true':'false')});document.addEventListener('click',()=>r.classList.remove('is-open'));})();</script>
        <?php
    }
}
