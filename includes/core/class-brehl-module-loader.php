<?php
defined('ABSPATH') || exit;

/**
 * Loads each module in isolation. A missing optional module cannot break
 * authentication or the basic intranet interface.
 */
final class Brehl_Module_Loader {
    private const MODULES = array(
        'dashboard',
        'news',
        'documents',
        'vacation',
        'sick-leave',
        'time',
        'employees',
        'notifications',
        'vehicles',
        'workwear',
    );

    public static function load(): void {
        foreach (self::MODULES as $module) {
            $file = BREHL_INTR_DIR . 'includes/modules/' . $module . '/module.php';

            if (is_readable($file)) {
                require_once $file;
            }
        }

        foreach (Brehl_Module_Registry::all() as $module) {
            $class = isset($module['class']) ? (string) $module['class'] : '';

            if (!empty($module['enabled']) && '' !== $class && class_exists($class)) {
                if (method_exists($class, 'instance')) {
                    $class::instance();
                } else {
                    new $class();
                }
            }
        }
    }
}
