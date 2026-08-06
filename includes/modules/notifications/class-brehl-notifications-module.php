<?php

defined('ABSPATH') || exit;

final class Brehl_Notifications_Module {
    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** @return array<int,array<string,mixed>> */
    public function unread_items(int $user_id, int $limit = 8): array {
        if (!$user_id) {
            return array();
        }
        $items = array_merge($this->system_items($user_id, $limit), $this->news_items($user_id, $limit));
        usort($items, static fn(array $a, array $b): int => $b['timestamp'] <=> $a['timestamp']);
        return array_slice($items, 0, max(1, $limit));
    }

    /** @return array<int,array<string,mixed>> */
    private function system_items(int $user_id, int $limit): array {
        global $wpdb;
        $table = $wpdb->prefix . 'my_brehl_notifications';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
            return array();
        }
        $read_global = array_map('intval', (array) get_user_meta($user_id, '_my_brehl_read_global_notifications', true));
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE (user_id=%d AND is_read=0) OR user_id=0 ORDER BY created_at DESC LIMIT %d",
            $user_id,
            max(10, $limit * 3)
        ));
        $items = array();
        foreach ($rows as $row) {
            if (0 === (int) $row->user_id && in_array((int) $row->id, $read_global, true)) {
                continue;
            }
            $items[] = array(
                'kind' => 'system',
                'id' => (int) $row->id,
                'title' => (string) $row->title,
                'message' => (string) $row->message,
                'category' => $this->type_label((string) $row->type),
                'type' => sanitize_key((string) $row->type),
                'important' => in_array((string) $row->type, array('warning', 'danger', 'important'), true),
                'timestamp' => strtotime((string) $row->created_at),
                'date' => human_time_diff(strtotime((string) $row->created_at), current_time('timestamp')),
                'action_url' => wp_nonce_url(add_query_arg(array('action'=>'my_brehl_mark_notification','notification_id'=>(int)$row->id,'redirect_to'=>$this->destination($row,$user_id)),admin_url('admin-post.php')), 'my_brehl_mark_notification_' . (int) $row->id),
            );
        }
        return $items;
    }

    /** @return array<int,array<string,mixed>> */
    private function news_items(int $user_id, int $limit): array {
        $read = array_map('intval', (array) get_user_meta($user_id, '_my_brehl_read_news', true));
        $query = new WP_Query(array(
            'post_type' => 'brehl_news',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
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
                'kind' => 'news',
                'id' => (int) $post->ID,
                'title' => get_the_title($post),
                'message' => '',
                'category' => (!is_wp_error($terms) && $terms) ? $terms[0]->name : __('Unternehmensnews', 'brehl-intranet'),
                'type' => 'news',
                'important' => (bool) get_post_meta($post->ID, '_brehl_news_important', true),
                'timestamp' => (int) get_post_time('U', true, $post),
                'date' => human_time_diff(get_post_time('U', true, $post), current_time('timestamp')),
                'action_url' => '',
            );
        }
        wp_reset_postdata();
        return $items;
    }

    private function type_label(string $type): string {
        $labels = array(
            'success' => __('Erfolg', 'brehl-intranet'),
            'warning' => __('Hinweis', 'brehl-intranet'),
            'danger' => __('Wichtig', 'brehl-intranet'),
            'important' => __('Wichtig', 'brehl-intranet'),
            'info' => __('Information', 'brehl-intranet'),
        );
        return $labels[$type] ?? __('Benachrichtigung', 'brehl-intranet');
    }

    private function destination(object $row,int $user_id): string {
        $title=mb_strtolower((string)$row->title); $user=get_userdata($user_id);
        $manager=$user && (user_can($user,'manage_options')||in_array(Brehl_Roles::HR_ROLE,(array)$user->roles,true));
        if(str_contains($title,'bekleidung')) return home_url($manager?'/arbeistkleidungverwaltung/':'/arbeistkleidung/');
        if(str_contains($title,'urlaub')) return home_url($manager?'/personalverwaltung/':'/urlaub/');
        if(str_contains($title,'krank')) return home_url($manager?'/personalverwaltung/':'/krankmeldung/');
        if(str_contains($title,'service')||str_contains($title,'fahrzeug')||str_contains($title,'schaden')) return home_url($manager?'/fuhrparkverwaltung/':'/fuhrpark/');
        if(!empty($row->link_url)) return (string)$row->link_url;
        return home_url('/dashboard/');
    }
}
