<?php

defined('ABSPATH') || exit;

final class My_Brehl_System {
    private static $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'register_shortcodes'));
        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_post_my_brehl_add_task', array($this, 'handle_add_task'));
        add_action('admin_post_my_brehl_toggle_task', array($this, 'handle_toggle_task'));
        add_action('admin_post_my_brehl_add_notification', array($this, 'handle_add_notification'));
        add_action('admin_post_my_brehl_mark_notification', array($this, 'handle_mark_notification'));
        add_action('admin_post_my_brehl_mark_all_notifications', array($this, 'handle_mark_all_notifications'));
        add_action('wp_ajax_my_brehl_global_search', array($this, 'ajax_global_search'));
    }

    public static function activate(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();

        $notifications = $wpdb->prefix . 'my_brehl_notifications';
        $tasks = $wpdb->prefix . 'my_brehl_tasks';
        $activity = $wpdb->prefix . 'my_brehl_activity';

        dbDelta("CREATE TABLE {$notifications} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            title VARCHAR(190) NOT NULL,
            message TEXT NOT NULL,
            type VARCHAR(40) NOT NULL DEFAULT 'info',
            link_url TEXT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY is_read (is_read)
        ) {$charset};");

        dbDelta("CREATE TABLE {$tasks} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
            title VARCHAR(190) NOT NULL,
            description TEXT NULL,
            priority VARCHAR(20) NOT NULL DEFAULT 'normal',
            due_date DATE NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'offen',
            created_at DATETIME NOT NULL,
            completed_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY due_date (due_date)
        ) {$charset};");

        dbDelta("CREATE TABLE {$activity} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            actor_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            module VARCHAR(80) NOT NULL DEFAULT 'grundsystem',
            action VARCHAR(190) NOT NULL,
            object_type VARCHAR(80) NULL,
            object_id BIGINT UNSIGNED NULL,
            details LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY actor_id (actor_id),
            KEY module (module)
        ) {$charset};");

        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('my_brehl_manage_system');
        }
        $hr = get_role('personalverwaltung');
        if ($hr) {
            $hr->add_cap('my_brehl_manage_system');
        }
        update_option('my_brehl_system_db_version', '1.0');
    }

    public function register_assets(): void {
        wp_register_style('my-brehl-system', BREHL_INTR_URL . 'assets/css/my-brehl-system.css', array('brehl-intranet'), BREHL_INTR_VERSION);
        wp_register_script('my-brehl-system', BREHL_INTR_URL . 'assets/js/my-brehl-system.js', array(), BREHL_INTR_VERSION, true);
        wp_localize_script('my-brehl-system', 'MyBrehlSystem', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('my_brehl_global_search'),
        ));
    }

    public function register_shortcodes(): void {
        add_shortcode('my_brehl_personal_dashboard', array($this, 'dashboard_shortcode'));
        add_shortcode('my_brehl_dashboard', array($this, 'dashboard_shortcode'));
        add_shortcode('my_brehl_benachrichtigungen', array($this, 'notifications_shortcode'));
        add_shortcode('my_brehl_aufgaben', array($this, 'tasks_shortcode'));
        add_shortcode('my_brehl_aktivitaeten', array($this, 'activity_shortcode'));
        add_shortcode('my_brehl_globale_suche', array($this, 'search_shortcode'));
    }

    private function enqueue(): void {
        wp_enqueue_style('brehl-intranet');
        wp_enqueue_style('my-brehl-system');
        wp_enqueue_script('my-brehl-system');
    }

    private function can_manage(): bool {
        return current_user_can('my_brehl_manage_system') || current_user_can('manage_options');
    }

    public function admin_menu(): void {
        add_menu_page('My Brehl', 'My Brehl', 'my_brehl_manage_system', 'my-brehl-system', array($this, 'admin_page'), 'dashicons-building', 26);
    }

    public function admin_page(): void {
        if (!$this->can_manage()) {
            wp_die(esc_html__('Keine Berechtigung.', 'brehl-intranet'));
        }
        $users = get_users(array('orderby' => 'display_name'));
        ?>
        <div class="wrap">
            <h1>My Brehl – Grundsystem</h1>
            <p>Hier können Aufgaben und Benachrichtigungen angelegt werden. Die Frontend-Bausteine werden über Shortcodes oder Elementor eingefügt.</p>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:24px;max-width:1100px">
                <div class="postbox" style="padding:20px">
                    <h2>Aufgabe anlegen</h2>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="my_brehl_add_task">
                        <?php wp_nonce_field('my_brehl_add_task'); ?>
                        <p><label>Mitarbeiter<br><select name="user_id" required style="width:100%"><option value="">Bitte wählen</option><?php foreach ($users as $user) : ?><option value="<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($user->display_name); ?></option><?php endforeach; ?></select></label></p>
                        <p><label>Titel<br><input type="text" name="title" required style="width:100%"></label></p>
                        <p><label>Beschreibung<br><textarea name="description" rows="4" style="width:100%"></textarea></label></p>
                        <p><label>Priorität<br><select name="priority"><option value="normal">Normal</option><option value="hoch">Hoch</option><option value="dringend">Dringend</option></select></label></p>
                        <p><label>Fällig am<br><input type="date" name="due_date"></label></p>
                        <p><button class="button button-primary">Aufgabe speichern</button></p>
                    </form>
                </div>
                <div class="postbox" style="padding:20px">
                    <h2>Benachrichtigung senden</h2>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="my_brehl_add_notification">
                        <?php wp_nonce_field('my_brehl_add_notification'); ?>
                        <p><label>Empfänger<br><select name="user_id" required style="width:100%"><option value="0">Alle Mitarbeiter</option><?php foreach ($users as $user) : ?><option value="<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($user->display_name); ?></option><?php endforeach; ?></select></label></p>
                        <p><label>Titel<br><input type="text" name="title" required style="width:100%"></label></p>
                        <p><label>Nachricht<br><textarea name="message" rows="4" required style="width:100%"></textarea></label></p>
                        <p><label>Art<br><select name="type"><option value="info">Information</option><option value="success">Erfolg</option><option value="warning">Hinweis</option><option value="danger">Dringend</option></select></label></p>
                        <p><label>Link (optional)<br><input type="url" name="link_url" style="width:100%"></label></p>
                        <p><button class="button button-primary">Benachrichtigung senden</button></p>
                    </form>
                </div>
            </div>
            <h2>Verfügbare Shortcodes</h2>
            <code>[my_brehl_personal_dashboard]</code><br><code>[my_brehl_benachrichtigungen]</code><br><code>[my_brehl_aufgaben]</code><br><code>[my_brehl_aktivitaeten]</code><br><code>[my_brehl_globale_suche]</code>
        </div>
        <?php
    }

    public function handle_add_task(): void {
        if (!$this->can_manage()) wp_die('Keine Berechtigung.');
        check_admin_referer('my_brehl_add_task');
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'my_brehl_tasks', array(
            'user_id' => absint($_POST['user_id'] ?? 0),
            'created_by' => get_current_user_id(),
            'title' => sanitize_text_field(wp_unslash($_POST['title'] ?? '')),
            'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
            'priority' => sanitize_key($_POST['priority'] ?? 'normal'),
            'due_date' => sanitize_text_field($_POST['due_date'] ?? '') ?: null,
            'status' => 'offen',
            'created_at' => current_time('mysql'),
        ));
        $this->log_activity(absint($_POST['user_id'] ?? 0), 'Aufgabe erstellt: ' . sanitize_text_field(wp_unslash($_POST['title'] ?? '')), 'aufgabe', (int)$wpdb->insert_id);
        wp_safe_redirect(admin_url('admin.php?page=my-brehl-system&saved=task'));
        exit;
    }

    public function handle_add_notification(): void {
        if (!$this->can_manage()) wp_die('Keine Berechtigung.');
        check_admin_referer('my_brehl_add_notification');
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'my_brehl_notifications', array(
            'user_id' => absint($_POST['user_id'] ?? 0),
            'title' => sanitize_text_field(wp_unslash($_POST['title'] ?? '')),
            'message' => sanitize_textarea_field(wp_unslash($_POST['message'] ?? '')),
            'type' => sanitize_key($_POST['type'] ?? 'info'),
            'link_url' => esc_url_raw(wp_unslash($_POST['link_url'] ?? '')),
            'is_read' => 0,
            'created_at' => current_time('mysql'),
        ));
        $this->log_activity(absint($_POST['user_id'] ?? 0), 'Benachrichtigung versendet: ' . sanitize_text_field(wp_unslash($_POST['title'] ?? '')), 'benachrichtigung', (int)$wpdb->insert_id);
        wp_safe_redirect(admin_url('admin.php?page=my-brehl-system&saved=notification'));
        exit;
    }

    public function handle_toggle_task(): void {
        if (!is_user_logged_in()) auth_redirect();
        $id = absint($_GET['task_id'] ?? 0);
        check_admin_referer('my_brehl_toggle_task_' . $id);
        global $wpdb;
        $table = $wpdb->prefix . 'my_brehl_tasks';
        $task = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d", $id));
        if (!$task || ((int)$task->user_id !== get_current_user_id() && !$this->can_manage())) wp_die('Keine Berechtigung.');
        $new = 'erledigt' === $task->status ? 'offen' : 'erledigt';
        $wpdb->update($table, array('status' => $new, 'completed_at' => 'erledigt' === $new ? current_time('mysql') : null), array('id' => $id));
        $this->log_activity((int)$task->user_id, ('erledigt' === $new ? 'Aufgabe erledigt: ' : 'Aufgabe wieder geöffnet: ') . $task->title, 'aufgabe', $id);
        wp_safe_redirect(wp_get_referer() ?: home_url('/'));
        exit;
    }

    public function handle_mark_notification(): void {
        if (!is_user_logged_in()) auth_redirect();
        $id = absint($_GET['notification_id'] ?? 0);
        check_admin_referer('my_brehl_mark_notification_' . $id);
        global $wpdb;
        $table = $wpdb->prefix . 'my_brehl_notifications';
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d", $id));
        if (!$item || !in_array((int)$item->user_id, array(0, get_current_user_id()), true)) wp_die('Keine Berechtigung.');
        if (0 === (int) $item->user_id) {
            $read = array_map('intval', (array) get_user_meta(get_current_user_id(), '_my_brehl_read_global_notifications', true));
            $read[] = $id;
            update_user_meta(get_current_user_id(), '_my_brehl_read_global_notifications', array_values(array_unique($read)));
        } else {
            $wpdb->update($table, array('is_read' => 1), array('id' => $id));
        }
        $redirect=$this->notification_redirect($item);
        wp_safe_redirect($redirect);
        exit;
    }

    private function notification_redirect(object $item): string {
        $title=mb_strtolower((string)$item->title);
        $manager=current_user_can('manage_options')||Brehl_Roles::is_hr();
        if(str_contains($title,'bekleidung')) return home_url($manager?'/arbeistkleidungverwaltung/':'/arbeistkleidung/');
        if(str_contains($title,'urlaub')) return home_url($manager?'/personalverwaltung/':'/urlaub/');
        if(str_contains($title,'krank')) return home_url($manager?'/personalverwaltung/':'/krankmeldung/');
        if(str_contains($title,'service')||str_contains($title,'fahrzeug')||str_contains($title,'schaden')) return home_url($manager?'/fuhrparkverwaltung/':'/fuhrpark/');
        if(!empty($item->link_url)) return (string)$item->link_url;
        return home_url('/dashboard/');
    }

    public function handle_mark_all_notifications(): void {
        if(!is_user_logged_in()) auth_redirect();
        check_admin_referer('my_brehl_mark_all_notifications');
        global $wpdb; $uid=get_current_user_id(); $table=$wpdb->prefix.'my_brehl_notifications';
        $wpdb->update($table,array('is_read'=>1),array('user_id'=>$uid));
        $global_ids=array_map('intval',(array)$wpdb->get_col("SELECT id FROM {$table} WHERE user_id=0"));
        update_user_meta($uid,'_my_brehl_read_global_notifications',$global_ids);
        $news_ids=get_posts(array('post_type'=>'brehl_news','post_status'=>'publish','numberposts'=>-1,'fields'=>'ids'));
        update_user_meta($uid,'_my_brehl_read_news',array_map('intval',(array)$news_ids));
        wp_safe_redirect(wp_get_referer()?:home_url('/dashboard/')); exit;
    }

    private function log_activity(int $user_id, string $action, string $object_type = '', int $object_id = 0): void {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'my_brehl_activity', array(
            'user_id' => $user_id,
            'actor_id' => get_current_user_id(),
            'module' => 'grundsystem',
            'action' => $action,
            'object_type' => $object_type,
            'object_id' => $object_id ?: null,
            'details' => null,
            'created_at' => current_time('mysql'),
        ));
    }

    private function counts(): array {
        global $wpdb;
        $uid = get_current_user_id();
        $notifications = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}my_brehl_notifications WHERE is_read=0 AND (user_id=0 OR user_id=%d)", $uid));
        $tasks = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}my_brehl_tasks WHERE status='offen' AND user_id=%d", $uid));
        $employees = count_users();
        return array('notifications' => (int)$notifications, 'tasks' => (int)$tasks, 'employees' => (int)($employees['total_users'] ?? 0));
    }

    public function dashboard_shortcode($atts = array()): string {
        if (!is_user_logged_in()) return '';
        $this->enqueue();
        $atts = shortcode_atts(array(
            'urlaub_url' => home_url('/urlaub/'),
            'fahrzeug_url' => home_url('/fahrzeugschaden/'),
            'dokumente_url' => home_url('/dokumente/'),
            'news_url' => home_url('/news/'),
            'mitarbeiter_url' => home_url('/mitarbeiter/'),
            'ansprechpartner_url' => home_url('/ansprechpartner/'),
        ), is_array($atts) ? $atts : array(), 'my_brehl_dashboard');
        global $wpdb;
        $uid = get_current_user_id();
        $user = wp_get_current_user();
        $c = $this->counts();
        $year = (int) wp_date('Y');
        $entitlement = get_user_meta($uid, 'brehl_vacation_entitlement_' . $year, true);
        $entitlement = '' === $entitlement ? 30.0 : (float) $entitlement;
        $carry = (float) get_user_meta($uid, 'brehl_vacation_carryover_' . $year, true);
        $approved = (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(requested_days),0) FROM {$wpdb->prefix}brehl_vacation_requests WHERE user_id=%d AND status='genehmigt' AND vacation_type='urlaub' AND YEAR(start_date)=%d", $uid, $year));
        $pending = (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(requested_days),0) FROM {$wpdb->prefix}brehl_vacation_requests WHERE user_id=%d AND status='eingereicht' AND vacation_type='urlaub' AND YEAR(start_date)=%d", $uid, $year));
        $available = $entitlement + $carry - $approved - $pending;
        $next_vacation = $wpdb->get_var($wpdb->prepare("SELECT start_date FROM {$wpdb->prefix}brehl_vacation_requests WHERE user_id=%d AND status='genehmigt' AND end_date >= CURDATE() ORDER BY start_date ASC LIMIT 1", $uid));
        $open_damages = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}brehl_vehicle_damages WHERE user_id=%d AND status NOT IN ('erledigt','abgelehnt')", $uid));
        $hour = (int) wp_date('G');
        $greeting = $hour < 11 ? 'Guten Morgen' : ($hour < 17 ? 'Guten Tag' : 'Guten Abend');
        $days = array('Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag');
        $date_label = $days[(int) wp_date('w')] . ', ' . wp_date('d.m.Y');
        $links = array(
            array('icon'=>'🏖️','title'=>'Urlaub beantragen','text'=>'Antrag und Resturlaub','url'=>$atts['urlaub_url']),
            array('icon'=>'🚗','title'=>'Fahrzeugschaden','text'=>'Schaden schnell melden','url'=>$atts['fahrzeug_url']),
            array('icon'=>'📄','title'=>'Dokumente','text'=>'Formulare und Unterlagen','url'=>$atts['dokumente_url']),
            array('icon'=>'📰','title'=>'News','text'=>'Aktuelles aus dem Betrieb','url'=>$atts['news_url']),
            array('icon'=>'👥','title'=>'Mitarbeiter','text'=>'Kollegen finden','url'=>$atts['mitarbeiter_url']),
            array('icon'=>'☎️','title'=>'Ansprechpartner','text'=>'Direkter Kontakt','url'=>$atts['ansprechpartner_url']),
        );
        ob_start(); ?>
        <section class="mbs-dashboard mbs-dashboard-v3">
            <header class="mbs-v3-hero">
                <div class="mbs-v3-user">
                    <?php echo get_avatar($uid, 64, '', '', array('class'=>'mbs-v3-avatar')); ?>
                    <div><span class="mbs-kicker">MY BREHL</span><h2><?php echo esc_html($greeting . ', ' . ($user->first_name ?: $user->display_name)); ?></h2><p><?php echo esc_html($date_label); ?></p></div>
                </div>
                <div class="mbs-v3-bell" aria-label="Neue Benachrichtigungen">🔔<span><?php echo esc_html($c['notifications']); ?></span></div>
            </header>
            <div class="mbs-v3-kpis">
                <a href="<?php echo esc_url($atts['urlaub_url']); ?>" class="mbs-v3-kpi"><span class="mbs-v3-kpi-icon">🏖️</span><div><small>Resturlaub</small><strong><?php echo esc_html(number_format_i18n($available, $available == floor($available) ? 0 : 1)); ?> Tage</strong><em><?php echo $pending > 0 ? esc_html(number_format_i18n($pending, 1) . ' Tage beantragt') : 'Keine offenen Anträge'; ?></em></div></a>
                <a href="<?php echo esc_url($atts['fahrzeug_url']); ?>" class="mbs-v3-kpi"><span class="mbs-v3-kpi-icon">🚗</span><div><small>Fahrzeug</small><strong><?php echo esc_html($open_damages); ?> offene Meldung<?php echo 1 === $open_damages ? '' : 'en'; ?></strong><em>Schäden und Status prüfen</em></div></a>
                <div class="mbs-v3-kpi"><span class="mbs-v3-kpi-icon">✅</span><div><small>Aufgaben</small><strong><?php echo esc_html($c['tasks']); ?> offen</strong><em>Deine aktuellen Aufgaben</em></div></div>
                <div class="mbs-v3-kpi"><span class="mbs-v3-kpi-icon">📅</span><div><small>Nächster Urlaub</small><strong><?php echo $next_vacation ? esc_html(wp_date('d.m.Y', strtotime($next_vacation))) : 'Noch keiner'; ?></strong><em><?php echo $next_vacation ? 'Genehmigter Urlaubsbeginn' : 'Kein Termin eingetragen'; ?></em></div></div>
            </div>
            <div class="mbs-v3-section-head"><div><span>SCHNELLZUGRIFF</span><h3>Was möchtest du erledigen?</h3></div></div>
            <nav class="mbs-v3-quicklinks">
                <?php foreach ($links as $link) : ?><a href="<?php echo esc_url($link['url']); ?>"><span><?php echo esc_html($link['icon']); ?></span><div><strong><?php echo esc_html($link['title']); ?></strong><small><?php echo esc_html($link['text']); ?></small></div><b>›</b></a><?php endforeach; ?>
            </nav>
            <div class="mbs-v3-content-grid">
                <div><?php echo do_shortcode('[my_brehl_aufgaben limit="5"]'); ?></div>
                <div><?php echo do_shortcode('[my_brehl_benachrichtigungen limit="5"]'); ?></div>
            </div>
        </section>
        <?php return (string) ob_get_clean();
    }

    public function notifications_shortcode($atts): string {
        if (!is_user_logged_in()) return '';
        $this->enqueue();
        $atts = shortcode_atts(array('limit' => 10), $atts, 'my_brehl_benachrichtigungen');
        global $wpdb;
        $uid = get_current_user_id();
        $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}my_brehl_notifications WHERE user_id=0 OR user_id=%d ORDER BY created_at DESC LIMIT %d", $uid, absint($atts['limit'])));
        ob_start(); ?>
        <div class="mbs-card"><div class="mbs-card-head"><h3>Benachrichtigungen</h3></div><div class="mbs-list">
        <?php if (!$items) : ?><p class="mbs-empty">Keine Benachrichtigungen vorhanden.</p><?php endif; ?>
        <?php foreach ($items as $item) : $url = wp_nonce_url(admin_url('admin-post.php?action=my_brehl_mark_notification&notification_id=' . $item->id), 'my_brehl_mark_notification_' . $item->id); ?>
            <article class="mbs-notice mbs-<?php echo esc_attr($item->type); ?> <?php echo $item->is_read ? 'is-read' : ''; ?>"><div><strong><?php echo esc_html($item->title); ?></strong><p><?php echo esc_html($item->message); ?></p><small><?php echo esc_html(wp_date('d.m.Y H:i', strtotime($item->created_at))); ?></small></div><?php if (!$item->is_read) : ?><a href="<?php echo esc_url($url); ?>">Als gelesen markieren</a><?php endif; ?></article>
        <?php endforeach; ?></div></div>
        <?php return ob_get_clean();
    }

    public function tasks_shortcode($atts): string {
        if (!is_user_logged_in()) return '';
        $this->enqueue();
        $atts = shortcode_atts(array('limit' => 10), $atts, 'my_brehl_aufgaben');
        global $wpdb;
        $uid = get_current_user_id();
        $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}my_brehl_tasks WHERE user_id=%d ORDER BY FIELD(status,'offen','erledigt'), due_date IS NULL, due_date ASC, created_at DESC LIMIT %d", $uid, absint($atts['limit'])));
        ob_start(); ?>
        <div class="mbs-card"><div class="mbs-card-head"><h3>Meine Aufgaben</h3></div><div class="mbs-list">
        <?php if (!$items) : ?><p class="mbs-empty">Keine Aufgaben vorhanden.</p><?php endif; ?>
        <?php foreach ($items as $item) : $url = wp_nonce_url(admin_url('admin-post.php?action=my_brehl_toggle_task&task_id=' . $item->id), 'my_brehl_toggle_task_' . $item->id); ?>
            <article class="mbs-task <?php echo 'erledigt' === $item->status ? 'is-done' : ''; ?>"><a class="mbs-check" href="<?php echo esc_url($url); ?>" aria-label="Status ändern"></a><div><strong><?php echo esc_html($item->title); ?></strong><?php if ($item->description) : ?><p><?php echo esc_html($item->description); ?></p><?php endif; ?><div class="mbs-meta"><span class="mbs-priority mbs-priority-<?php echo esc_attr($item->priority); ?>"><?php echo esc_html(ucfirst($item->priority)); ?></span><?php if ($item->due_date) : ?><span>Fällig: <?php echo esc_html(wp_date('d.m.Y', strtotime($item->due_date))); ?></span><?php endif; ?></div></div></article>
        <?php endforeach; ?></div></div>
        <?php return ob_get_clean();
    }

    public function activity_shortcode($atts): string {
        if (!is_user_logged_in()) return '';
        $this->enqueue();
        $atts = shortcode_atts(array('limit' => 20), $atts, 'my_brehl_aktivitaeten');
        global $wpdb;
        $where = $this->can_manage() ? '1=1' : $wpdb->prepare('(user_id=%d OR actor_id=%d)', get_current_user_id(), get_current_user_id());
        $items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}my_brehl_activity WHERE {$where} ORDER BY created_at DESC LIMIT " . absint($atts['limit']));
        ob_start(); ?><div class="mbs-card"><div class="mbs-card-head"><h3>Aktivitäten</h3></div><div class="mbs-timeline">
        <?php if (!$items) : ?><p class="mbs-empty">Noch keine Aktivitäten vorhanden.</p><?php endif; ?>
        <?php foreach ($items as $item) : $actor = get_userdata($item->actor_id); ?><div class="mbs-timeline-item"><span></span><div><strong><?php echo esc_html($item->action); ?></strong><small><?php echo esc_html(wp_date('d.m.Y H:i', strtotime($item->created_at))); ?><?php echo $actor ? ' · ' . esc_html($actor->display_name) : ''; ?></small></div></div><?php endforeach; ?>
        </div></div><?php return ob_get_clean();
    }

    public function search_shortcode(): string {
        if (!is_user_logged_in()) return '';
        $this->enqueue();
        return '<div class="mbs-search"><input type="search" class="mbs-search-input" placeholder="Mitarbeiter, Dokumente oder Inhalte suchen …"><div class="mbs-search-results" hidden></div></div>';
    }

    public function ajax_global_search(): void {
        check_ajax_referer('my_brehl_global_search', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error();
        $q = sanitize_text_field(wp_unslash($_GET['q'] ?? ''));
        if (mb_strlen($q) < 2) wp_send_json_success(array());
        $results = array();
        $users = get_users(array('search' => '*' . $q . '*', 'search_columns' => array('display_name', 'user_email', 'user_login'), 'number' => 8));
        foreach ($users as $user) {
            $results[] = array('type' => 'Mitarbeiter', 'title' => $user->display_name, 'subtitle' => get_user_meta($user->ID, 'my_brehl_department', true), 'url' => '#');
        }
        $posts = get_posts(array('s' => $q, 'post_status' => 'publish', 'posts_per_page' => 8, 'post_type' => array('post', 'page', 'brehl_news')));
        foreach ($posts as $post) {
            $results[] = array('type' => 'Inhalt', 'title' => get_the_title($post), 'subtitle' => get_post_type_object($post->post_type)->labels->singular_name ?? '', 'url' => get_permalink($post));
        }
        wp_send_json_success(array_slice($results, 0, 12));
    }
}
