<?php

defined('ABSPATH') || exit;

/**
 * Single data source for dashboard KPIs.
 */
final class Brehl_KPI_Service {
    private static function table_exists(string $table): bool {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
    }

    private static function format_number(float $value): string {
        return rtrim(rtrim(number_format($value, 1, ',', '.'), '0'), ',');
    }

    private static function vacation_value(string $type, int $user_id): string {
        global $wpdb;
        $year = (int) wp_date('Y');
        $table = $wpdb->prefix . 'brehl_vacation_requests';
        if (!$user_id || !self::table_exists($table)) {
            return '0';
        }

        $raw = get_user_meta($user_id, 'brehl_vacation_entitlement_' . $year, true);
        $entitlement = '' === $raw ? 30.0 : (float) $raw;
        $carryover = (float) get_user_meta($user_id, 'brehl_vacation_carryover_' . $year, true);
        $approved = (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(requested_days),0) FROM {$table} WHERE user_id=%d AND status='genehmigt' AND vacation_type='urlaub' AND YEAR(start_date)=%d", $user_id, $year));
        $pending = (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(requested_days),0) FROM {$table} WHERE user_id=%d AND status='eingereicht' AND vacation_type='urlaub' AND YEAR(start_date)=%d", $user_id, $year));

        if ('vacation_pending' === $type) {
            return self::format_number($pending);
        }
        if ('vacation_approved' === $type) {
            return self::format_number($approved);
        }
        return self::format_number($entitlement + $carryover - $approved - $pending);
    }

    private static function unread_news_count(int $user_id): int {
        if (!$user_id || !post_type_exists('brehl_news')) {
            return 0;
        }
        $query = new WP_Query(array(
            'post_type' => 'brehl_news',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ));
        $read = array_map('intval', (array) get_user_meta($user_id, '_my_brehl_read_news', true));
        return count(array_diff(array_map('intval', $query->posts), $read));
    }

    public static function value(string $source, string $manual = '', ?int $user_id = null): string {
        global $wpdb;
        $user_id = null === $user_id ? get_current_user_id() : $user_id;

        if ('manual' === $source) {
            return $manual;
        }
        if (0 === strpos($source, 'vacation_')) {
            return self::vacation_value($source, $user_id);
        }
        if ('unread_news' === $source) {
            return (string) self::unread_news_count($user_id);
        }
        if ('employee_count' === $source) {
            $counts = count_users();
            return (string) (int) ($counts['total_users'] ?? 0);
        }
        if (!$user_id) {
            return '0';
        }
        if ('unread_notifications' === $source && class_exists('Brehl_Notifications_Module')) {
            return (string) count(Brehl_Notifications_Module::instance()->unread_items($user_id, 99));
        }

        $queries = array(
            'unread_notifications' => array($wpdb->prefix . 'my_brehl_notifications', "SELECT COUNT(*) FROM %s WHERE (user_id=0 OR user_id=%d) AND is_read=0"),
            'open_tasks' => array($wpdb->prefix . 'my_brehl_tasks', "SELECT COUNT(*) FROM %s WHERE user_id=%d AND status='offen'"),
            'open_vehicle_damages' => array($wpdb->prefix . 'brehl_vehicle_damages', "SELECT COUNT(*) FROM %s WHERE user_id=%d AND status NOT IN ('erledigt','abgelehnt')"),
        );

        if (!isset($queries[$source])) {
            return '0';
        }

        [$table, $sql] = $queries[$source];
        if (!self::table_exists($table)) {
            return '0';
        }
        $sql = sprintf($sql, $table);
        return (string) (int) $wpdb->get_var($wpdb->prepare($sql, $user_id));
    }
}
