<?php
defined('ABSPATH') || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Widget_Base;

final class Brehl_Userbar_Widget extends Widget_Base {
    public function get_name(): string { return 'brehl-userbar'; }
    public function get_title(): string { return __('My Brehl Benutzerleiste', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-user-circle-o'; }
    public function get_categories(): array { return array('brehl-intranet'); }
    public function get_style_depends(): array { return array('brehl-intranet'); }
    public function get_script_depends(): array { return array('brehl-intranet-news'); }

    protected function register_controls(): void {
        $this->start_controls_section('content', array('label' => __('Inhalt', 'brehl-intranet')));
        $this->add_control('search_placeholder', array(
            'label' => __('Suchtext', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => __('News, Dokumente oder Personen durchsuchen …', 'brehl-intranet'),
            'label_block' => true,
        ));
        $this->add_control('show_search', array('label' => __('Suche anzeigen', 'brehl-intranet'), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes'));
        $this->add_control('show_language', array('label' => __('Sprache anzeigen', 'brehl-intranet'), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes'));
        $this->add_control('show_notifications', array('label' => __('Benachrichtigungen anzeigen', 'brehl-intranet'), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes'));
        $this->end_controls_section();

        $this->start_controls_section('style', array('label' => __('Design', 'brehl-intranet'), 'tab' => Controls_Manager::TAB_STYLE));
        $this->add_control('background', array('label' => __('Hintergrund', 'brehl-intranet'), 'type' => Controls_Manager::COLOR, 'default' => '#FFFFFF', 'selectors' => array('{{WRAPPER}} .brehl-userbar' => 'background: {{VALUE}};')));
        $this->add_control('accent', array('label' => __('Akzentfarbe', 'brehl-intranet'), 'type' => Controls_Manager::COLOR, 'default' => '#EC008C', 'selectors' => array('{{WRAPPER}} .brehl-userbar__avatar' => 'background: {{VALUE}};', '{{WRAPPER}} .brehl-userbar__badge' => 'background: {{VALUE}};')));
        $this->add_responsive_control('padding', array('label' => __('Innenabstand', 'brehl-intranet'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array('px'), 'selectors' => array('{{WRAPPER}} .brehl-userbar' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};')));
        $this->add_control('radius', array('label' => __('Eckenradius', 'brehl-intranet'), 'type' => Controls_Manager::SLIDER, 'range' => array('px' => array('min' => 0, 'max' => 40)), 'default' => array('unit' => 'px', 'size' => 16), 'selectors' => array('{{WRAPPER}} .brehl-userbar' => 'border-radius: {{SIZE}}{{UNIT}};')));
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), array('name' => 'shadow', 'selector' => '{{WRAPPER}} .brehl-userbar'));
        $this->end_controls_section();
    }

    private function unread_news(int $user_id): array {
        $read = array_map('intval', (array) get_user_meta($user_id, '_my_brehl_read_news', true));
        $query = new WP_Query(array(
            'post_type' => 'brehl_news',
            'post_status' => 'publish',
            'posts_per_page' => 6,
            'post__not_in' => $read ?: array(0),
            'orderby' => array('meta_value_num' => 'DESC', 'date' => 'DESC'),
            'meta_key' => '_brehl_news_important',
            'meta_query' => array(
                'relation' => 'OR',
                array('key' => '_brehl_news_expiry', 'compare' => 'NOT EXISTS'),
                array('key' => '_brehl_news_expiry', 'value' => '', 'compare' => '='),
                array('key' => '_brehl_news_expiry', 'value' => current_time('Y-m-d'), 'compare' => '>=', 'type' => 'DATE'),
            ),
        ));
        $items = array();
        foreach ($query->posts as $post) {
            $terms = get_the_terms($post->ID, 'brehl_news_category');
            $items[] = array(
                'id' => (int) $post->ID,
                'title' => get_the_title($post),
                'date' => human_time_diff(get_post_time('U', true, $post), current_time('timestamp')),
                'category' => (!is_wp_error($terms) && $terms) ? $terms[0]->name : __('Unternehmen', 'brehl-intranet'),
                'important' => (bool) get_post_meta($post->ID, '_brehl_news_important', true),
            );
        }
        wp_reset_postdata();
        return $items;
    }

    protected function render(): void {
        if (!is_user_logged_in()) return;
        $s = $this->get_settings_for_display();
        $user = wp_get_current_user();
        $initials = mb_strtoupper(mb_substr($user->first_name ?: $user->display_name, 0, 1));
        $language = get_user_meta($user->ID, 'brehl_language', true) ?: 'de';
        $notifications = class_exists('Brehl_Notifications_Module')
            ? Brehl_Notifications_Module::instance()->unread_items((int) $user->ID, 8)
            : $this->unread_news((int) $user->ID);
        ?>
        <div class="brehl-userbar">
            <?php if ('yes' === ($s['show_search'] ?? '')) : ?>
                <form class="brehl-userbar__search" action="<?php echo esc_url(home_url('/')); ?>" method="get">
                    <span aria-hidden="true">⌕</span>
                    <input type="search" name="s" placeholder="<?php echo esc_attr($s['search_placeholder']); ?>">
                </form>
            <?php endif; ?>

            <div class="brehl-userbar__actions">
                <?php if ('yes' === ($s['show_notifications'] ?? '')) : ?>
                    <div class="my-brehl-notifications" data-my-brehl-notifications>
                        <button class="brehl-userbar__icon" type="button" aria-label="<?php esc_attr_e('Benachrichtigungen öffnen', 'brehl-intranet'); ?>" aria-expanded="false" data-my-brehl-notifications-toggle>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                            <?php if ($notifications) : ?><span class="brehl-userbar__badge" data-notification-count><?php echo esc_html((string) count($notifications)); ?></span><?php endif; ?>
                        </button>
                        <div class="my-brehl-notifications__panel" hidden data-my-brehl-notifications-panel>
                            <div class="my-brehl-notifications__head"><div><strong><?php esc_html_e('Benachrichtigungen', 'brehl-intranet'); ?></strong><span><?php esc_html_e('Neue Meldungen in My Brehl', 'brehl-intranet'); ?></span></div></div>
                            <div class="my-brehl-notifications__list">
                                <?php if ($notifications) : foreach ($notifications as $item) : ?>
                                    <?php if ('system' === ($item['kind'] ?? 'news')) : ?>
                                    <a class="my-brehl-notification my-brehl-notification--<?php echo esc_attr($item['type']); ?><?php echo $item['important'] ? ' is-important' : ''; ?>" href="<?php echo esc_url($item['action_url']); ?>">
                                        <span class="my-brehl-notification__dot"></span>
                                        <span class="my-brehl-notification__body"><small><?php echo esc_html($item['category']); ?> · <?php echo esc_html($item['date']); ?> <?php esc_html_e('zuvor', 'brehl-intranet'); ?></small><strong><?php echo esc_html($item['title']); ?></strong></span>
                                        <span aria-hidden="true">→</span>
                                    </a>
                                    <?php else : ?>
                                    <button type="button" class="my-brehl-notification<?php echo $item['important'] ? ' is-important' : ''; ?>" data-my-brehl-notification-news="<?php echo esc_attr((string) $item['id']); ?>">
                                        <span class="my-brehl-notification__dot"></span>
                                        <span class="my-brehl-notification__body"><small><?php echo esc_html($item['category']); ?> · <?php echo esc_html($item['date']); ?> <?php esc_html_e('zuvor', 'brehl-intranet'); ?></small><strong><?php echo esc_html($item['title']); ?></strong></span>
                                        <span aria-hidden="true">→</span>
                                    </button>
                                    <?php endif; ?>
                                <?php endforeach; else : ?>
                                    <div class="my-brehl-notifications__empty"><span>✓</span><strong><?php esc_html_e('Alles gelesen', 'brehl-intranet'); ?></strong><p><?php esc_html_e('Aktuell gibt es keine neuen Meldungen.', 'brehl-intranet'); ?></p></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ('yes' === ($s['show_language'] ?? '')) : ?><span class="brehl-userbar__language"><?php echo esc_html(strtoupper($language)); ?></span><?php endif; ?>
                <a class="brehl-userbar__profile" href="<?php echo esc_url(home_url('/mein-profil/')); ?>"><span class="brehl-userbar__avatar"><?php echo esc_html($initials); ?></span><span class="brehl-userbar__name"><?php echo esc_html($user->display_name); ?></span></a>
                <a class="brehl-userbar__logout" href="<?php echo esc_url(wp_logout_url(home_url('/login/'))); ?>" aria-label="<?php esc_attr_e('Abmelden','brehl-intranet'); ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h5M14 8l4 4-4 4m4-4H8"/></svg><span><?php esc_html_e('Abmelden','brehl-intranet'); ?></span></a>
            </div>
        </div>
        <?php
    }
}
