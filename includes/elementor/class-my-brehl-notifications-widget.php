<?php

defined('ABSPATH') || exit;

class My_Brehl_Notifications_Widget extends \Elementor\Widget_Base {
    public function get_name() { return 'my_brehl_notifications'; }
    public function get_title() { return __('My Brehl – Benachrichtigungen', 'brehl-intranet'); }
    public function get_icon() { return 'eicon-alert'; }
    public function get_categories() { return array('brehl-intranet'); }
    public function get_style_depends() { return array('brehl-intranet'); }

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
        $read = (array) get_user_meta(get_current_user_id(), 'my_brehl_read_news', true);
        $query = new WP_Query(array('post_type' => 'brehl_news', 'post_status' => 'publish', 'posts_per_page' => max(1, (int)$s['limit']), 'post__not_in' => array_map('intval', $read), 'orderby' => 'date', 'order' => 'DESC'));
        $count = (int) $query->found_posts;
        ?>
        <div class="my-brehl-notifications">
            <button class="my-brehl-notifications__trigger" type="button" aria-expanded="false" aria-label="<?php esc_attr_e('Benachrichtigungen', 'brehl-intranet'); ?>">
                <span aria-hidden="true">♢</span><?php if ($count > 0) : ?><span class="my-brehl-notifications__badge"><?php echo esc_html($count > 99 ? '99+' : $count); ?></span><?php endif; ?>
            </button>
            <div class="my-brehl-notifications__panel">
                <div class="my-brehl-notifications__head"><strong><?php esc_html_e('Benachrichtigungen', 'brehl-intranet'); ?></strong><span><?php echo esc_html($count); ?> <?php esc_html_e('neu', 'brehl-intranet'); ?></span></div>
                <?php if ($query->have_posts()) : ?>
                    <div class="my-brehl-notifications__list">
                    <?php while ($query->have_posts()) : $query->the_post(); ?>
                        <a class="my-brehl-notifications__item" href="<?php the_permalink(); ?>"><span class="dot"></span><span><strong><?php the_title(); ?></strong><small><?php echo esc_html(human_time_diff(get_the_time('U'), current_time('timestamp'))); ?> <?php esc_html_e('zuvor', 'brehl-intranet'); ?></small></span></a>
                    <?php endwhile; wp_reset_postdata(); ?>
                    </div>
                <?php else : ?><p class="my-brehl-notifications__empty"><?php echo esc_html($s['empty_text']); ?></p><?php endif; ?>
            </div>
        </div>
        <script>(function(){const r=document.currentScript.previousElementSibling,b=r.querySelector('.my-brehl-notifications__trigger');b.addEventListener('click',function(e){e.stopPropagation();r.classList.toggle('is-open');b.setAttribute('aria-expanded',r.classList.contains('is-open')?'true':'false')});document.addEventListener('click',()=>r.classList.remove('is-open'));})();</script>
        <?php
    }
}
