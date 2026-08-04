<?php

defined('ABSPATH') || exit;

require_once BREHL_INTR_DIR . 'includes/core/class-brehl-design-system.php';
require_once BREHL_INTR_DIR . 'includes/core/class-brehl-module-registry.php';
require_once BREHL_INTR_DIR . 'includes/core/class-brehl-module-loader.php';
require_once BREHL_INTR_DIR . 'includes/core/class-brehl-roles.php';
require_once BREHL_INTR_DIR . 'includes/system/class-my-brehl-system.php';
require_once BREHL_INTR_DIR . 'includes/elementor/class-brehl-elementor-widget-manager.php';

final class Brehl_Intranet {
    private static $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        Brehl_Roles::sync();
        Brehl_Module_Loader::load();
        My_Brehl_System::instance();
        add_action('init', array($this, 'register_shortcodes'));
        add_action('admin_post_nopriv_brehl_intranet_login', array($this, 'handle_frontend_login'));
        add_action('admin_post_brehl_intranet_login', array($this, 'handle_frontend_login'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        add_filter('authenticate', array($this, 'authenticate_personnel_number'), 15, 3);
        add_filter('authenticate', array($this, 'reject_inactive_user'), 99, 3);
        add_action('validate_password_reset', array('Brehl_Roles', 'validate_password_reset'), 10, 2);
        add_filter('login_redirect', array($this, 'login_redirect'), 10, 3);

        add_action('template_redirect', array($this, 'protect_frontend'));
        add_action('admin_init', array($this, 'block_employee_admin'));
        add_filter('show_admin_bar', array($this, 'control_admin_bar'));

        add_action('show_user_profile', array($this, 'profile_fields'));
        add_action('edit_user_profile', array($this, 'profile_fields'));
        add_action('personal_options_update', array($this, 'save_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'save_profile_fields'));

        add_filter('locale', array($this, 'user_locale'));

        $widget_manager = Brehl_Elementor_Widget_Manager::instance();
        add_action('elementor/elements/categories_registered', array($widget_manager, 'register_category'));
        add_action('elementor/widgets/register', array($widget_manager, 'register_widgets'));
    }

    public static function activate(): void {
        Brehl_Roles::sync();

        if (!get_option('brehl_intranet_login_slug')) {
            update_option('brehl_intranet_login_slug', 'login');
        }
        if (!get_option('brehl_intranet_dashboard_slug')) {
            update_option('brehl_intranet_dashboard_slug', 'dashboard');
        }

        // Register module content types before flushing rewrite rules.
        Brehl_Module_Loader::load();
        if (class_exists('Brehl_News_Module')) {
            Brehl_News_Module::instance()->register_content_types();
        }
        if (class_exists('Brehl_Documents_Module')) {
            Brehl_Documents_Module::instance()->register_content_types();
        }
        flush_rewrite_rules();
    }

    public function register_shortcodes(): void {
        add_shortcode('brehl_login', array($this, 'login_shortcode'));
        add_shortcode('brehl_logout', array($this, 'logout_shortcode'));
        add_shortcode('brehl_user_name', array($this, 'user_name_shortcode'));
    }

    public function enqueue_assets(): void {
        wp_register_style(
            'brehl-intranet',
            BREHL_INTR_URL . 'assets/css/brehl-intranet.css',
            array(),
            BREHL_INTR_VERSION
        );
        wp_add_inline_style('brehl-intranet', Brehl_Design_System::css_variables());
        wp_register_script(
            'brehl-intranet-news',
            BREHL_INTR_URL . 'assets/js/brehl-news.js',
            array(),
            BREHL_INTR_VERSION,
            true
        );
        wp_localize_script('brehl-intranet-news', 'MyBrehlNews', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('my_brehl_news'),
            'messages' => array(
                'error' => __('Es ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.', 'brehl-intranet'),
                'commentEmpty' => __('Bitte geben Sie einen Kommentar ein.', 'brehl-intranet'),
                'commentSaved' => __('Kommentar wurde veröffentlicht.', 'brehl-intranet'),
            ),
        ));
        wp_register_style(
            'brehl-intranet-vehicle-damage',
            BREHL_INTR_URL . 'assets/css/brehl-vehicle-damage.css',
            array('brehl-intranet', 'my-brehl-system'),
            BREHL_INTR_VERSION
        );
        wp_register_script(
            'brehl-intranet-documents',
            BREHL_INTR_URL . 'assets/js/brehl-documents.js',
            array(),
            BREHL_INTR_VERSION,
            true
        );
        wp_register_script(
            'brehl-intranet-vehicle-damage',
            BREHL_INTR_URL . 'assets/js/brehl-vehicle-damage.js',
            array(),
            BREHL_INTR_VERSION,
            true
        );
        wp_register_script(
            'brehl-intranet-hr',
            BREHL_INTR_URL . 'assets/js/brehl-hr.js',
            array(),
            BREHL_INTR_VERSION,
            true
        );
        wp_register_script(
            'brehl-intranet-vacation',
            BREHL_INTR_URL . 'assets/js/brehl-vacation.js',
            array(),
            BREHL_INTR_VERSION,
            true
        );
    }

    private function strings(string $lang): array {
        $all = array(
            'de' => array(
                'title' => 'Willkommen zurück',
                'intro' => 'Bitte melden Sie sich mit Ihrer Personalnummer an.',
                'personnel' => 'Personalnummer',
                'password' => 'Passwort',
                'remember' => 'Angemeldet bleiben',
                'login' => 'Anmelden',
                'error' => 'Personalnummer oder Passwort ist nicht korrekt.',
                'logout' => 'Abmelden',
            ),
            'en' => array(
                'title' => 'Welcome back',
                'intro' => 'Please sign in with your personnel number.',
                'personnel' => 'Personnel number',
                'password' => 'Password',
                'remember' => 'Keep me signed in',
                'login' => 'Sign in',
                'error' => 'The personnel number or password is incorrect.',
                'logout' => 'Sign out',
            ),
            'sq' => array(
                'title' => 'Mirë se vini përsëri',
                'intro' => 'Ju lutemi identifikohuni me numrin tuaj të personelit.',
                'personnel' => 'Numri i personelit',
                'password' => 'Fjalëkalimi',
                'remember' => 'Qëndro i identifikuar',
                'login' => 'Hyr',
                'error' => 'Numri i personelit ose fjalëkalimi nuk është i saktë.',
                'logout' => 'Dil',
            ),
        );

        return $all[$lang] ?? $all['de'];
    }

    private function current_language(): string {
        $allowed = array('de', 'en', 'sq');

        if (isset($_GET['lang'])) {
            $lang = sanitize_key(wp_unslash($_GET['lang']));
            if (in_array($lang, $allowed, true)) {
                setcookie('brehl_lang', $lang, time() + YEAR_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true);
                return $lang;
            }
        }

        if (is_user_logged_in()) {
            $saved = get_user_meta(get_current_user_id(), 'brehl_language', true);
            if (in_array($saved, $allowed, true)) {
                return $saved;
            }
        }

        if (isset($_COOKIE['brehl_lang'])) {
            $cookie = sanitize_key(wp_unslash($_COOKIE['brehl_lang']));
            if (in_array($cookie, $allowed, true)) {
                return $cookie;
            }
        }

        return 'de';
    }

    public function login_shortcode($atts = array()): string {
        wp_enqueue_style('brehl-intranet');

        if (is_user_logged_in()) {
            $dashboard = home_url('/' . trim(get_option('brehl_intranet_dashboard_slug', 'dashboard'), '/') . '/');
            return '<div class="brehl-login-card"><a class="brehl-button" href="' . esc_url($dashboard) . '">' .
                esc_html__('Zum Dashboard', 'brehl-intranet') . '</a></div>';
        }

        $lang = $this->current_language();
        $s = $this->strings($lang);

        $atts = shortcode_atts(
            array(
                'title' => '',
                'intro' => '',
                'personnel_label' => '',
                'password_label' => '',
                'remember_label' => '',
                'button_label' => '',
                'show_language' => 'yes',
                'logo_url' => '',
            ),
            is_array($atts) ? $atts : array(),
            'brehl_login'
        );

        $s['title'] = '' !== $atts['title'] ? sanitize_text_field($atts['title']) : $s['title'];
        $s['intro'] = '' !== $atts['intro'] ? sanitize_text_field($atts['intro']) : $s['intro'];
        $s['personnel'] = '' !== $atts['personnel_label'] ? sanitize_text_field($atts['personnel_label']) : $s['personnel'];
        $s['password'] = '' !== $atts['password_label'] ? sanitize_text_field($atts['password_label']) : $s['password'];
        $s['remember'] = '' !== $atts['remember_label'] ? sanitize_text_field($atts['remember_label']) : $s['remember'];
        $s['login'] = '' !== $atts['button_label'] ? sanitize_text_field($atts['button_label']) : $s['login'];

        $error = isset($_GET['login']) && 'failed' === sanitize_key(wp_unslash($_GET['login']));

        $redirect = home_url('/' . trim(get_option('brehl_intranet_dashboard_slug', 'dashboard'), '/') . '/');
        $action = admin_url('admin-post.php');

        ob_start();
        ?>
        <div class="brehl-login-shell">
            <?php if ('yes' === $atts['show_language']) : ?>
                <div class="brehl-language-switch" aria-label="Language">
                    <a href="<?php echo esc_url(add_query_arg('lang', 'de')); ?>" class="<?php echo 'de' === $lang ? 'active' : ''; ?>">DE</a>
                    <a href="<?php echo esc_url(add_query_arg('lang', 'en')); ?>" class="<?php echo 'en' === $lang ? 'active' : ''; ?>">EN</a>
                    <a href="<?php echo esc_url(add_query_arg('lang', 'sq')); ?>" class="<?php echo 'sq' === $lang ? 'active' : ''; ?>">SQ</a>
                </div>
            <?php endif; ?>

            <div class="brehl-login-card">
                <?php if ('' !== $atts['logo_url']) : ?>
                    <img class="brehl-login-logo" src="<?php echo esc_url($atts['logo_url']); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
                <?php endif; ?>
                <h1><?php echo esc_html($s['title']); ?></h1>
                <p><?php echo esc_html($s['intro']); ?></p>

                <?php if ($error) : ?>
                    <div class="brehl-login-error"><?php echo esc_html($s['error']); ?></div>
                <?php endif; ?>

                <form method="post" action="<?php echo esc_url($action); ?>">
                    <input type="hidden" name="action" value="brehl_intranet_login">
                    <?php wp_nonce_field('brehl_intranet_login', 'brehl_login_nonce'); ?>
                    <label for="brehl-user-login"><?php echo esc_html($s['personnel']); ?></label>
                    <input id="brehl-user-login" name="log" type="text" inputmode="numeric" autocomplete="username" required>

                    <label for="brehl-user-pass"><?php echo esc_html($s['password']); ?></label>
                    <input id="brehl-user-pass" name="pwd" type="password" autocomplete="current-password" required>

                    <label class="brehl-remember">
                        <input name="rememberme" type="checkbox" value="forever">
                        <span><?php echo esc_html($s['remember']); ?></span>
                    </label>

                    <input type="hidden" name="redirect_to" value="<?php echo esc_url($redirect); ?>">
                    <button type="submit"><?php echo esc_html($s['login']); ?></button>
                </form>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    public function handle_frontend_login(): void {
        $login_slug = trim(get_option('brehl_intranet_login_slug', 'login'), '/');
        $login_url = home_url('/' . $login_slug . '/');

        if (
            !isset($_POST['brehl_login_nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['brehl_login_nonce'])),
                'brehl_intranet_login'
            )
        ) {
            wp_safe_redirect(add_query_arg('login', 'failed', $login_url));
            exit;
        }

        $personnel_number = isset($_POST['log'])
            ? sanitize_text_field(wp_unslash($_POST['log']))
            : '';

        $password = isset($_POST['pwd'])
            ? (string) wp_unslash($_POST['pwd'])
            : '';

        $remember = isset($_POST['rememberme']) && 'forever' === $_POST['rememberme'];
        $language = $this->current_language();

        $failure_url = add_query_arg(
            array(
                'login' => 'failed',
                'lang' => $language,
            ),
            $login_url
        );

        if ('' === $personnel_number || '' === $password) {
            wp_safe_redirect($failure_url);
            exit;
        }

        $users = get_users(array(
            'meta_key' => 'brehl_personnel_number',
            'meta_value' => $personnel_number,
            'number' => 2,
            'count_total' => false,
            'fields' => 'all',
        ));

        if (1 !== count($users)) {
            wp_safe_redirect($failure_url);
            exit;
        }

        $user = wp_authenticate($users[0]->user_login, $password);

        if (is_wp_error($user)) {
            wp_safe_redirect($failure_url);
            exit;
        }

        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, $remember, is_ssl());
        do_action('wp_login', $user->user_login, $user);

        $dashboard_slug = trim(get_option('brehl_intranet_dashboard_slug', 'dashboard'), '/');
        $dashboard_url = home_url('/' . $dashboard_slug . '/');

        $requested_redirect = isset($_POST['redirect_to'])
            ? esc_url_raw(wp_unslash($_POST['redirect_to']))
            : $dashboard_url;

        wp_safe_redirect(wp_validate_redirect($requested_redirect, $dashboard_url));
        exit;
    }

    public function authenticate_personnel_number($user, string $username, string $password) {
        if ($user instanceof WP_User || $user instanceof WP_Error || '' === trim($username)) {
            return $user;
        }

        $personnel_number = sanitize_text_field($username);
        $users = get_users(array(
            'meta_key' => 'brehl_personnel_number',
            'meta_value' => $personnel_number,
            'number' => 2,
            'count_total' => false,
            'fields' => 'all',
        ));

        if (1 !== count($users)) {
            return $user;
        }

        return wp_authenticate_username_password(null, $users[0]->user_login, $password);
    }

    public function reject_inactive_user($user, string $username, string $password) {
        if (
            $user instanceof WP_User
            && Brehl_Roles::is_employee($user)
            && '0' === get_user_meta($user->ID, '_my_brehl_account_active', true)
        ) {
            return new WP_Error('my_brehl_account_inactive', __('Dieses Mitarbeiterkonto ist deaktiviert.', 'brehl-intranet'));
        }
        return $user;
    }

    public function login_redirect(string $redirect_to, string $requested_redirect_to, $user): string {
        if ($user instanceof WP_User && (Brehl_Roles::is_employee($user) || Brehl_Roles::is_hr($user))) {
            return home_url('/' . trim(get_option('brehl_intranet_dashboard_slug', 'dashboard'), '/') . '/');
        }
        return $redirect_to;
    }

    public function protect_frontend(): void {
        if (is_admin() || wp_doing_ajax()) {
            return;
        }

        if (is_user_logged_in()) {
            if ($this->is_management_page() && !current_user_can('my_brehl_manage_system') && !current_user_can('manage_options')) {
                $dashboard_slug = trim(get_option('brehl_intranet_dashboard_slug', 'dashboard'), '/');
                wp_safe_redirect(add_query_arg('access_denied', '1', home_url('/' . $dashboard_slug . '/')));
                exit;
            }
            return;
        }

        $login_slug = trim(get_option('brehl_intranet_login_slug', 'login'), '/');

        if (is_page($login_slug) || is_404() || $this->is_public_request()) {
            return;
        }

        wp_safe_redirect(home_url('/' . $login_slug . '/'));
        exit;
    }

    private function is_management_page(): bool {
        return is_page(array(
            'mitarbeiterverwaltung',
            'personalverwaltung',
            'fuhrparkverwaltung',
            'bekleidungsverwaltung',
        ));
    }

    private function is_public_request(): bool {
        global $pagenow;

        if ('wp-login.php' === $pagenow) {
            return true;
        }

        $path = isset($_SERVER['REQUEST_URI']) ? wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH) : '';
        return is_string($path) && (
            str_starts_with($path, '/wp-json/') ||
            str_starts_with($path, '/wp-cron.php')
        );
    }

    /**
     * The WordPress backend is reserved for administrators.
     * Frontend AJAX and scheduled WordPress requests remain available.
     */
    public function block_employee_admin(): void {
        if (
            !is_user_logged_in() ||
            current_user_can('manage_options') ||
            wp_doing_ajax() ||
            wp_doing_cron()
        ) {
            return;
        }

        global $pagenow;

        // Required frontend form endpoints must stay reachable.
        if (in_array($pagenow, array('admin-post.php', 'async-upload.php'), true)) {
            return;
        }

        wp_safe_redirect(
            home_url('/' . trim(get_option('brehl_intranet_dashboard_slug', 'dashboard'), '/') . '/')
        );
        exit;
    }

    /**
     * Employees see only the custom intranet interface.
     * Administrators keep the normal WordPress toolbar.
     */
    public function control_admin_bar(bool $show): bool {
        if (!is_user_logged_in()) {
            return $show;
        }

        return current_user_can('manage_options');
    }

    public function profile_fields(WP_User $user): void {
        if (!current_user_can('edit_user', $user->ID)) {
            return;
        }

        $personnel = get_user_meta($user->ID, 'brehl_personnel_number', true);
        $language = get_user_meta($user->ID, 'brehl_language', true) ?: 'de';
        ?>
        <h2><?php esc_html_e('Brehl Intranet', 'brehl-intranet'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="brehl_personnel_number"><?php esc_html_e('Personalnummer', 'brehl-intranet'); ?></label></th>
                <td>
                    <input type="text" name="brehl_personnel_number" id="brehl_personnel_number"
                           value="<?php echo esc_attr($personnel); ?>" class="regular-text">
                    <p class="description"><?php esc_html_e('Muss für jeden Mitarbeiter eindeutig sein.', 'brehl-intranet'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="brehl_language"><?php esc_html_e('Bevorzugte Sprache', 'brehl-intranet'); ?></label></th>
                <td>
                    <select name="brehl_language" id="brehl_language">
                        <option value="de" <?php selected($language, 'de'); ?>>Deutsch</option>
                        <option value="en" <?php selected($language, 'en'); ?>>English</option>
                        <option value="sq" <?php selected($language, 'sq'); ?>>Shqip</option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
        wp_nonce_field('brehl_profile_fields', 'brehl_profile_nonce');
    }

    public function save_profile_fields(int $user_id): void {
        if (
            !current_user_can('edit_user', $user_id) ||
            !isset($_POST['brehl_profile_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['brehl_profile_nonce'])), 'brehl_profile_fields')
        ) {
            return;
        }

        $personnel = isset($_POST['brehl_personnel_number'])
            ? sanitize_text_field(wp_unslash($_POST['brehl_personnel_number']))
            : '';

        if ('' !== $personnel) {
            $existing = get_users(array(
                'meta_key' => 'brehl_personnel_number',
                'meta_value' => $personnel,
                'exclude' => array($user_id),
                'number' => 1,
                'fields' => 'ids',
            ));

            if (empty($existing)) {
                update_user_meta($user_id, 'brehl_personnel_number', $personnel);
            }
        } else {
            delete_user_meta($user_id, 'brehl_personnel_number');
        }

        $language = isset($_POST['brehl_language'])
            ? sanitize_key(wp_unslash($_POST['brehl_language']))
            : 'de';

        if (in_array($language, array('de', 'en', 'sq'), true)) {
            update_user_meta($user_id, 'brehl_language', $language);
        }
    }

    public function user_locale(string $locale): string {
        $lang = $this->current_language();
        $map = array(
            'de' => 'de_DE',
            'en' => 'en_US',
            'sq' => 'sq',
        );
        return $map[$lang] ?? $locale;
    }

    public function logout_shortcode(): string {
        if (!is_user_logged_in()) {
            return '';
        }
        $s = $this->strings($this->current_language());
        $login = home_url('/' . trim(get_option('brehl_intranet_login_slug', 'login'), '/') . '/');
        return '<a class="brehl-logout-link" href="' . esc_url(wp_logout_url($login)) . '">' . esc_html($s['logout']) . '</a>';
    }

    public function user_name_shortcode(): string {
        if (!is_user_logged_in()) {
            return '';
        }
        $user = wp_get_current_user();
        return esc_html($user->display_name);
    }
}
