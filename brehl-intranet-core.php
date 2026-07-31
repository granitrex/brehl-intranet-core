<?php
/**
 * Plugin Name: My Brehl Core
 * Description: Technische Grundlage und flexible Elementor-Bausteine für das Mitarbeiterportal my.brehl.de.
 * Version: 3.2.1
 * Author: Brehl GmbH
 * Text Domain: brehl-intranet
 */

defined('ABSPATH') || exit;

define('BREHL_INTR_VERSION', '3.2.1');
define('BREHL_INTR_FILE', __FILE__);
define('BREHL_INTR_DIR', plugin_dir_path(__FILE__));
define('BREHL_INTR_URL', plugin_dir_url(__FILE__));

require_once BREHL_INTR_DIR . 'includes/class-brehl-intranet.php';

register_activation_hook(__FILE__, static function (): void {
    Brehl_Intranet::activate();
    require_once BREHL_INTR_DIR . 'includes/system/class-my-brehl-system.php';
    My_Brehl_System::activate();
    Brehl_Vehicle_Damage_Module::install();
    Brehl_Vacation_Module::install();
});

add_action('plugins_loaded', static function () {
    Brehl_Intranet::instance();
});


register_deactivation_hook(
    __FILE__,
    static function (): void {
        flush_rewrite_rules();
    }
);
