<?php

defined('ABSPATH') || exit;

/**
 * Central role and capability definition for My Brehl.
 *
 * WordPress administrators keep full backend access. HR and employees receive
 * only explicit intranet capabilities and are handled in the frontend portal.
 */
final class Brehl_Roles {
    public const HR_ROLE = 'personalverwaltung';
    public const EMPLOYEE_ROLE = 'brehl_employee';

    private const HR_CAPABILITIES = array(
        'my_brehl_manage_system',
        'my_brehl_manage_people',
        'my_brehl_manage_news',
        'my_brehl_manage_vacation',
        'my_brehl_manage_sick_leave',
        'my_brehl_manage_vehicle_damage',
        'my_brehl_manage_documents',
        'my_brehl_send_notifications',
        'my_brehl_view_activity_log',
    );

    private const EMPLOYEE_CAPABILITIES = array(
        'my_brehl_access_portal',
        'my_brehl_comment',
        'my_brehl_download_documents',
        'my_brehl_submit_vacation',
        'my_brehl_submit_sick_leave',
        'my_brehl_submit_vehicle_damage',
    );

    public static function sync(): void {
        add_role(self::HR_ROLE, __('Personalverwaltung', 'brehl-intranet'), array('read' => true));
        add_role(self::EMPLOYEE_ROLE, __('Mitarbeiter', 'brehl-intranet'), array('read' => true));

        self::migrate_legacy_roles();
        self::sync_role(self::HR_ROLE, self::HR_CAPABILITIES);
        self::sync_role(self::EMPLOYEE_ROLE, self::EMPLOYEE_CAPABILITIES);

        $administrator = get_role('administrator');
        if ($administrator) {
            foreach (array_merge(self::HR_CAPABILITIES, self::EMPLOYEE_CAPABILITIES) as $capability) {
                if (!$administrator->has_cap($capability)) {
                    $administrator->add_cap($capability);
                }
            }
        }
    }

    public static function is_hr(?WP_User $user = null): bool {
        $user = $user ?: wp_get_current_user();
        return $user->exists() && in_array(self::HR_ROLE, (array) $user->roles, true);
    }

    public static function is_employee(?WP_User $user = null): bool {
        $user = $user ?: wp_get_current_user();
        return $user->exists() && in_array(self::EMPLOYEE_ROLE, (array) $user->roles, true);
    }

    private static function sync_role(string $role_name, array $capabilities): void {
        $role = get_role($role_name);
        if (!$role) {
            return;
        }
        if (!$role->has_cap('read')) {
            $role->add_cap('read');
        }
        foreach ($capabilities as $capability) {
            if (!$role->has_cap($capability)) {
                $role->add_cap($capability);
            }
        }

        // These capabilities would expose the regular WordPress content or
        // administration interfaces and must remain administrator-only.
        foreach (array(
            'manage_options',
            'activate_plugins',
            'install_plugins',
            'edit_themes',
            'edit_users',
            'promote_users',
            'delete_users',
            'edit_posts',
            'publish_posts',
            'delete_posts',
            'edit_pages',
            'publish_pages',
            'delete_pages',
        ) as $capability) {
            if ($role->has_cap($capability)) {
                $role->remove_cap($capability);
            }
        }
    }

    /**
     * Earlier plugin versions created equivalent roles under different slugs.
     * Move their users first, then remove only roles with an exact known label.
     */
    private static function migrate_legacy_roles(): void {
        if ('1' === get_option('brehl_role_migration_version')) {
            return;
        }

        global $wp_roles;
        if (!$wp_roles instanceof WP_Roles) {
            $wp_roles = wp_roles();
        }

        $labels = array(
            self::HR_ROLE => array('personalverwaltung', 'brehl personalverwaltung', 'my brehl personalverwaltung'),
            self::EMPLOYEE_ROLE => array('mitarbeiter', 'brehl mitarbeiter', 'my brehl mitarbeiter'),
        );

        foreach ($wp_roles->roles as $legacy_slug => $definition) {
            foreach ($labels as $canonical_slug => $known_labels) {
                if ($legacy_slug === $canonical_slug) {
                    continue;
                }
                $label = sanitize_title((string) ($definition['name'] ?? ''));
                $normalized_labels = array_map('sanitize_title', $known_labels);
                if (!in_array($label, $normalized_labels, true)) {
                    continue;
                }

                $users = get_users(array(
                    'role' => $legacy_slug,
                    'fields' => 'all',
                ));
                foreach ($users as $user) {
                    $user->add_role($canonical_slug);
                    $user->remove_role($legacy_slug);
                }
                remove_role($legacy_slug);
            }
        }

        update_option('brehl_role_migration_version', '1', false);
    }
}
