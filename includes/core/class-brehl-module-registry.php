<?php
defined('ABSPATH') || exit;

/**
 * Lightweight registry for independent intranet modules.
 */
final class Brehl_Module_Registry {
    private static array $modules = array();

    public static function register(string $key, array $module): void {
        self::$modules[sanitize_key($key)] = wp_parse_args(
            $module,
            array(
                'label'   => $key,
                'enabled' => true,
                'class'   => '',
            )
        );
    }

    public static function all(): array {
        return self::$modules;
    }

    public static function is_enabled(string $key): bool {
        $key = sanitize_key($key);
        return isset(self::$modules[$key]) && !empty(self::$modules[$key]['enabled']);
    }
}
