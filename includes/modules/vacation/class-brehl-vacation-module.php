<?php

defined('ABSPATH') || exit;

final class Brehl_Vacation_Module {
    private static $instance = null;
    private const DB_VERSION = '1.0';

    public static function instance(): self {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'maybe_install'));
        add_action('init', array($this, 'register_shortcodes'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_post_brehl_submit_vacation', array($this, 'handle_submission'));
        add_action('admin_post_brehl_update_vacation', array($this, 'handle_status_update'));
        add_action('admin_post_brehl_save_vacation_balance', array($this, 'handle_balance_update'));
    }

    public static function install(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = $wpdb->prefix . 'brehl_vacation_requests';
        $charset = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            vacation_type VARCHAR(30) NOT NULL DEFAULT 'urlaub',
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            day_part VARCHAR(20) NOT NULL DEFAULT 'full',
            requested_days DECIMAL(5,1) NOT NULL DEFAULT 0.0,
            employee_note TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'eingereicht',
            admin_note TEXT NULL,
            decided_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
            decided_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY start_date (start_date),
            KEY end_date (end_date)
        ) {$charset};");
        update_option('brehl_vacation_db_version', self::DB_VERSION);
    }

    public function maybe_install(): void {
        if (self::DB_VERSION !== get_option('brehl_vacation_db_version')) self::install();
    }

    public function register_shortcodes(): void {
        add_shortcode('my_brehl_urlaub', array($this, 'shortcode'));
        add_shortcode('my_brehl_urlaub_kpi', array($this, 'kpi_shortcode'));
        add_shortcode('my_brehl_urlaub_uebersicht', array($this, 'overview_shortcode'));
        add_shortcode('my_brehl_urlaub_antrag', array($this, 'request_shortcode'));
        add_shortcode('my_brehl_urlaub_status', array($this, 'status_shortcode'));
    }

    private function table(): string { global $wpdb; return $wpdb->prefix . 'brehl_vacation_requests'; }
    private function can_manage(): bool { return current_user_can('my_brehl_manage_vacation') || current_user_can('manage_options'); }

    public function admin_menu(): void {
        add_submenu_page('my-brehl-system', 'Urlaubsverwaltung', 'Urlaub', 'my_brehl_manage_system', 'my-brehl-vacation', array($this, 'admin_page'));
    }

    private function enqueue(): void {
        wp_enqueue_style('brehl-intranet');
        wp_enqueue_style('my-brehl-system');
        wp_enqueue_script('brehl-intranet-vacation');
    }

    public function shortcode(): string {
        if (!is_user_logged_in()) return '';
        return '<div class="mbs-vacation">' . $this->overview_shortcode() . $this->request_shortcode() . $this->status_shortcode() . '</div>';
    }

    public function overview_shortcode(): string {
        if (!is_user_logged_in()) return '';
        $this->enqueue();
        $year = (int) wp_date('Y');
        $balance = $this->balance(get_current_user_id(), $year);
        ob_start(); ?>
        <section class="mbs-vacation-kpis">
            <div class="mbs-kpi"><span>Urlaubsanspruch <?php echo esc_html($year); ?></span><strong><?php echo esc_html($this->format_days($balance['total'])); ?></strong><small>Tage</small></div>
            <div class="mbs-kpi"><span>Genehmigt</span><strong><?php echo esc_html($this->format_days($balance['approved'])); ?></strong><small>Tage</small></div>
            <div class="mbs-kpi"><span>Beantragt</span><strong><?php echo esc_html($this->format_days($balance['pending'])); ?></strong><small>Tage</small></div>
            <div class="mbs-kpi"><span>Verfügbar</span><strong><?php echo esc_html($this->format_days($balance['available'])); ?></strong><small>Tage</small></div>
        </section>
        <?php return (string) ob_get_clean();
    }

    public function request_shortcode(): string {
        if (!is_user_logged_in()) return '';
        $this->enqueue();
        $result = sanitize_key($_GET['vacation'] ?? '');
        ob_start(); ?>
        <section class="mbs-vacation-form mbs-card">
                <div class="mbs-card-head"><div><span class="mbs-kicker">PERSONAL</span><h3>Urlaubsantrag stellen</h3></div></div>
                <?php if ('saved' === $result) : ?><div class="mbs-form-message is-success">Ihr Urlaubsantrag wurde übermittelt.</div><?php endif; ?>
                <?php if ('error' === $result) : ?><div class="mbs-form-message is-error">Der Antrag konnte nicht gespeichert werden. Bitte prüfen Sie die Angaben.</div><?php endif; ?>
                <?php if ('overlap' === $result) : ?><div class="mbs-form-message is-error">Für diesen Zeitraum besteht bereits ein offener oder genehmigter Antrag.</div><?php endif; ?>
                <form class="mbs-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="brehl_submit_vacation">
                    <?php wp_nonce_field('brehl_submit_vacation', 'brehl_vacation_nonce'); ?>
                    <div class="mbs-form-grid">
                        <label><span>Urlaubsart *</span><select name="vacation_type" required><option value="urlaub">Erholungsurlaub</option><option value="sonderurlaub">Sonderurlaub</option><option value="unbezahlt">Unbezahlter Urlaub</option></select></label>
                        <label><span>Umfang *</span><select name="day_part"><option value="full">Ganze Tage</option><option value="half_morning">Halber Tag – vormittags</option><option value="half_afternoon">Halber Tag – nachmittags</option></select></label>
                        <?php echo $this->date_field('start_date', __('Von', 'brehl-intranet')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php echo $this->date_field('end_date', __('Bis', 'brehl-intranet')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <label class="mbs-form-full"><span>Bemerkung</span><textarea name="employee_note" rows="4" placeholder="Optional, z. B. Vertretung oder Hinweis"></textarea></label>
                    </div>
                    <p class="mbs-form-hint">Samstage und Sonntage werden nicht als Urlaubstage gezählt. Halbe Tage können nur für einen einzelnen Tag beantragt werden.</p>
                    <button type="submit" class="mbs-primary-button">Urlaubsantrag absenden</button>
                </form>
        </section>
        <?php return (string) ob_get_clean();
    }

    public function status_shortcode(): string {
        if (!is_user_logged_in()) return '';
        $this->enqueue();
        global $wpdb;
        $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table()} WHERE user_id=%d ORDER BY created_at DESC LIMIT 30", get_current_user_id()));
        ob_start(); ?>
        <section class="mbs-vacation-status mbs-card">
                <div class="mbs-card-head"><h3>Meine Urlaubsanträge</h3></div>
                <div class="mbs-list">
                    <?php if (!$items) : ?><p class="mbs-empty">Sie haben noch keinen Urlaubsantrag gestellt.</p><?php endif; ?>
                    <?php foreach ($items as $item) : ?>
                    <article class="mbs-vacation-item">
                        <div><strong><?php echo esc_html($this->type_label($item->vacation_type)); ?></strong><p><?php echo esc_html($this->period_label($item)); ?> · <?php echo esc_html($this->format_days((float)$item->requested_days)); ?> Tag(e)</p><?php if ($item->admin_note) : ?><small>Rückmeldung: <?php echo esc_html($item->admin_note); ?></small><?php endif; ?></div>
                        <span class="mbs-status mbs-status-<?php echo esc_attr($item->status); ?>"><?php echo esc_html($this->status_label($item->status)); ?></span>
                    </article>
                    <?php endforeach; ?>
                </div>
        </section>
        <?php return (string) ob_get_clean();
    }

    private function date_field(string $name, string $label): string {
        $min = wp_date('Y-m-d');
        ob_start(); ?>
        <label class="mbs-date-field"><span><?php echo esc_html($label); ?> *</span><span class="mbs-date-picker" data-min="<?php echo esc_attr($min); ?>"><input class="mbs-date-picker__display" type="text" readonly placeholder="TT.MM.JJJJ" aria-label="<?php echo esc_attr($label); ?>" required><input class="mbs-date-picker__value" type="hidden" name="<?php echo esc_attr($name); ?>"><button class="mbs-date-picker__button" type="button" aria-label="<?php echo esc_attr(sprintf(__('%s im Kalender auswählen', 'brehl-intranet'), $label)); ?>">▦</button><span class="mbs-calendar" hidden></span></span></label>
        <?php return (string) ob_get_clean();
    }

    public function kpi_shortcode(): string {
        if (!is_user_logged_in()) return '';
        $this->enqueue();
        $b = $this->balance(get_current_user_id(), (int)wp_date('Y'));
        return '<div class="mbs-kpi mbs-vacation-single-kpi"><span>Resturlaub</span><strong>' . esc_html($this->format_days($b['available'])) . '</strong><small>Tage verfügbar</small></div>';
    }

    public function handle_submission(): void {
        if (!is_user_logged_in()) auth_redirect();
        if (!current_user_can('my_brehl_submit_vacation') && !$this->can_manage()) wp_die(esc_html__('Keine Berechtigung.', 'brehl-intranet'), 403);
        if (!isset($_POST['brehl_vacation_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['brehl_vacation_nonce'])), 'brehl_submit_vacation')) $this->redirect('error');
        $uid = get_current_user_id();
        $start = sanitize_text_field(wp_unslash($_POST['start_date'] ?? ''));
        $end = sanitize_text_field(wp_unslash($_POST['end_date'] ?? ''));
        $type = sanitize_key($_POST['vacation_type'] ?? 'urlaub');
        $part = sanitize_key($_POST['day_part'] ?? 'full');
        if (!in_array($type, array('urlaub','sonderurlaub','unbezahlt'), true) || !in_array($part, array('full','half_morning','half_afternoon'), true) || !$this->valid_date($start) || !$this->valid_date($end) || $end < $start || ('full' !== $part && $start !== $end)) $this->redirect('error');
        $days = 'full' === $part ? $this->working_days($start, $end) : 0.5;
        if ($days <= 0) $this->redirect('error');
        global $wpdb;
        $overlap = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table()} WHERE user_id=%d AND status IN ('eingereicht','genehmigt') AND start_date<=%s AND end_date>=%s", $uid, $end, $start));
        if ($overlap) $this->redirect('overlap');
        $now = current_time('mysql');
        $ok = $wpdb->insert($this->table(), array('user_id'=>$uid,'vacation_type'=>$type,'start_date'=>$start,'end_date'=>$end,'day_part'=>$part,'requested_days'=>$days,'employee_note'=>sanitize_textarea_field(wp_unslash($_POST['employee_note'] ?? '')),'status'=>'eingereicht','created_at'=>$now,'updated_at'=>$now), array('%d','%s','%s','%s','%s','%f','%s','%s','%s','%s'));
        if (!$ok) $this->redirect('error');
        $id = (int)$wpdb->insert_id;
        $this->notify_managers($id, $uid, $start, $end);
        $this->log_activity($uid, $uid, 'Urlaubsantrag eingereicht', $id);
        $this->redirect('saved');
    }

    public function handle_status_update(): void {
        if (!$this->can_manage()) wp_die('Keine Berechtigung.');
        $id = absint($_POST['request_id'] ?? 0); check_admin_referer('brehl_update_vacation_' . $id);
        $status = sanitize_key($_POST['status'] ?? 'eingereicht');
        if (!in_array($status, array('eingereicht','genehmigt','abgelehnt'), true)) $status = 'eingereicht';
        global $wpdb; $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table()} WHERE id=%d", $id));
        if (!$item) wp_die('Antrag nicht gefunden.');
        $now = current_time('mysql');
        $wpdb->update($this->table(), array('status'=>$status,'admin_note'=>sanitize_textarea_field(wp_unslash($_POST['admin_note'] ?? '')),'decided_by'=>get_current_user_id(),'decided_at'=>$status==='eingereicht'?null:$now,'updated_at'=>$now), array('id'=>$id));
        $this->notify_user((int)$item->user_id, $status, $item);
        $this->log_activity((int)$item->user_id, get_current_user_id(), 'Urlaubsantrag ' . $this->status_label($status), $id);
        $redirect = isset($_POST['redirect_to']) ? wp_validate_redirect(esc_url_raw(wp_unslash($_POST['redirect_to'])), '') : '';
        if ($redirect) {
            wp_safe_redirect(add_query_arg('vacation_management', 'updated', $redirect));
        } else {
            wp_safe_redirect(admin_url('admin.php?page=my-brehl-vacation&updated=1'));
        }
        exit;
    }

    public function management_panel(): string {
        if (!$this->can_manage()) return '';
        $this->enqueue();
        global $wpdb;
        $archive = !empty($_GET['vacation_archive']);
        $condition = $archive ? "status IN ('genehmigt','abgelehnt')" : "status='eingereicht'";
        $per_page = 20;
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table()} WHERE {$condition}");
        $page = min(max(1, absint($_GET['vacation_page'] ?? 1)), max(1, (int) ceil($total / $per_page)));
        $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table()} WHERE {$condition} ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, ($page - 1) * $per_page));
        $result = sanitize_key($_GET['vacation_management'] ?? '');
        $toggle = $archive ? remove_query_arg(array('vacation_archive','vacation_page')) : add_query_arg('vacation_archive','1',remove_query_arg('vacation_page'));
        ob_start(); ?>
        <section class="mbs-workwear-management mbs-absence-management"><div class="mbs-card">
            <div class="mbs-card-head"><div><span class="mbs-kicker"><?php esc_html_e('Anträge','brehl-intranet'); ?></span><h3><?php echo $archive ? esc_html__('Urlaubsarchiv','brehl-intranet') : esc_html__('Urlaubsverwaltung','brehl-intranet'); ?></h3></div></div>
            <div class="mbs-workwear-toolbar"><span><?php echo esc_html(sprintf(_n('%d Vorgang','%d Vorgänge',$total,'brehl-intranet'),$total)); ?></span><a class="mbs-workwear-archive-link" href="<?php echo esc_url($toggle); ?>"><?php echo $archive ? esc_html__('Aktuelle Anträge','brehl-intranet') : esc_html__('Archiv anzeigen','brehl-intranet'); ?></a></div>
            <?php if ('updated' === $result) : ?><div class="brehl-hr__notice"><?php esc_html_e('Der Urlaubsantrag wurde aktualisiert.', 'brehl-intranet'); ?></div><?php endif; ?>
            <div class="mbs-workwear-management__list">
                <?php if (!$items) : ?><p class="mbs-empty"><?php echo $archive ? esc_html__('Das Urlaubsarchiv ist leer.','brehl-intranet') : esc_html__('Es liegen keine offenen Urlaubsanträge vor.','brehl-intranet'); ?></p><?php endif; ?>
                <?php foreach ($items as $item) : $user = get_userdata((int) $item->user_id); ?>
                    <details class="mbs-workwear-case mbs-absence-case"><summary><div><strong><?php echo esc_html($user ? $user->display_name : __('Unbekannter Mitarbeiter','brehl-intranet')); ?></strong><small><?php echo esc_html($this->type_label($item->vacation_type).' · '.$this->period_label($item).' · '.$this->format_days((float)$item->requested_days).' Tag(e)'); ?></small></div><?php if (!$archive) : ?><span class="mbs-workwear-new-badge"><?php esc_html_e('NEU','brehl-intranet'); ?></span><?php endif; ?><span class="mbs-status mbs-status-<?php echo esc_attr($item->status); ?>"><?php echo esc_html($this->status_label($item->status)); ?></span></summary><div class="mbs-workwear-case__body">
                        <?php if ($item->employee_note) : ?><p class="mbs-workwear-note"><strong><?php esc_html_e('Hinweis:','brehl-intranet'); ?></strong> <?php echo esc_html($item->employee_note); ?></p><?php endif; ?>
                        <?php if (!$archive) : ?><form class="mbs-workwear-management__form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="brehl_update_vacation">
                            <input type="hidden" name="request_id" value="<?php echo esc_attr((string) $item->id); ?>">
                            <input type="hidden" name="redirect_to" value="<?php echo esc_url(remove_query_arg(array('vacation_management', 'people_result', 'employee_id'))); ?>">
                            <?php wp_nonce_field('brehl_update_vacation_' . $item->id); ?>
                            <fieldset class="mbs-workwear-status-actions"><legend><?php esc_html_e('Status direkt ändern','brehl-intranet'); ?></legend><?php foreach (array('eingereicht','genehmigt','abgelehnt') as $status) : ?><button class="<?php echo $item->status===$status?'is-current':''; ?>" type="submit" name="status" value="<?php echo esc_attr($status); ?>"><?php echo esc_html($this->status_label($status)); ?></button><?php endforeach; ?></fieldset>
                            <label class="mbs-form-full"><span><?php esc_html_e('Rückmeldung an den Mitarbeiter', 'brehl-intranet'); ?></span><textarea name="admin_note" rows="2" placeholder="<?php echo esc_attr__('Optionaler Hinweis', 'brehl-intranet'); ?>"><?php echo esc_textarea($item->admin_note); ?></textarea></label>
                            <p class="mbs-workwear-save-hint"><?php esc_html_e('Der angeklickte Status wird zusammen mit der Rückmeldung gespeichert.','brehl-intranet'); ?></p>
                        </form><?php elseif ($item->admin_note) : ?><p class="mbs-workwear-note"><strong><?php esc_html_e('Rückmeldung:','brehl-intranet'); ?></strong> <?php echo esc_html($item->admin_note); ?></p><?php endif; ?></div></details>
                <?php endforeach; ?>
            </div><?php echo $this->pagination_html($page,$total,$per_page,'vacation_page'); ?>
        </div></section>
        <?php return (string) ob_get_clean();
    }

    public function handle_balance_update(): void {
        if (!$this->can_manage()) wp_die('Keine Berechtigung.');
        check_admin_referer('brehl_save_vacation_balance');
        $uid = absint($_POST['user_id'] ?? 0); $year = absint($_POST['year'] ?? wp_date('Y'));
        $user = $uid ? get_userdata($uid) : null;
        if (!$user || !in_array(Brehl_Roles::EMPLOYEE_ROLE, (array) $user->roles, true) || $year < 2020 || $year > 2100) wp_die('Ungültige Angaben.');
        update_user_meta($uid, 'brehl_vacation_entitlement_' . $year, max(0, (float)str_replace(',', '.', (string)($_POST['entitlement'] ?? 0))));
        update_user_meta($uid, 'brehl_vacation_carryover_' . $year, (float)str_replace(',', '.', (string)($_POST['carryover'] ?? 0)));
        $redirect = isset($_POST['redirect_to']) ? wp_validate_redirect(esc_url_raw(wp_unslash($_POST['redirect_to'])), '') : '';
        wp_safe_redirect($redirect ? add_query_arg('vacation_management', 'balance_saved', $redirect) : admin_url('admin.php?page=my-brehl-vacation&balance_saved=1&year=' . $year)); exit;
    }

    public function admin_page(): void {
        if (!$this->can_manage()) wp_die('Keine Berechtigung.');
        global $wpdb;
        $year = absint($_GET['year'] ?? wp_date('Y'));
        $status = sanitize_key($_GET['status'] ?? '');
        $where = $status && in_array($status, array('eingereicht','genehmigt','abgelehnt'), true) ? $wpdb->prepare(' WHERE status=%s', $status) : '';
        $items = $wpdb->get_results("SELECT * FROM {$this->table()}{$where} ORDER BY FIELD(status,'eingereicht','genehmigt','abgelehnt'), start_date ASC LIMIT 300");
        $users = get_users(array('orderby'=>'display_name'));
        ?>
        <div class="wrap"><h1>Urlaubsverwaltung</h1><p>Urlaubsansprüche pflegen und Anträge genehmigen oder ablehnen.</p>
        <h2>Urlaubskonten <?php echo esc_html($year); ?></h2>
        <table class="widefat striped"><thead><tr><th>Mitarbeiter</th><th>Anspruch</th><th>Übertrag</th><th>Genehmigt</th><th>Verfügbar</th><th>Speichern</th></tr></thead><tbody>
        <?php foreach ($users as $user) : $b=$this->balance($user->ID,$year); ?>
        <tr><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="brehl_save_vacation_balance"><input type="hidden" name="user_id" value="<?php echo esc_attr($user->ID); ?>"><input type="hidden" name="year" value="<?php echo esc_attr($year); ?>"><?php wp_nonce_field('brehl_save_vacation_balance'); ?><td><strong><?php echo esc_html($user->display_name); ?></strong></td><td><input name="entitlement" type="number" step="0.5" min="0" value="<?php echo esc_attr($b['entitlement']); ?>" style="width:85px"></td><td><input name="carryover" type="number" step="0.5" value="<?php echo esc_attr($b['carryover']); ?>" style="width:85px"></td><td><?php echo esc_html($this->format_days($b['approved'])); ?></td><td><strong><?php echo esc_html($this->format_days($b['available'])); ?></strong></td><td><button class="button">Speichern</button></td></form></tr>
        <?php endforeach; ?></tbody></table>
        <h2 style="margin-top:30px">Anträge</h2><p class="subsubsub"><a href="<?php echo esc_url(admin_url('admin.php?page=my-brehl-vacation')); ?>">Alle</a> | <a href="<?php echo esc_url(admin_url('admin.php?page=my-brehl-vacation&status=eingereicht')); ?>">Eingereicht</a> | <a href="<?php echo esc_url(admin_url('admin.php?page=my-brehl-vacation&status=genehmigt')); ?>">Genehmigt</a> | <a href="<?php echo esc_url(admin_url('admin.php?page=my-brehl-vacation&status=abgelehnt')); ?>">Abgelehnt</a></p><div style="clear:both"></div>
        <?php if (!$items) echo '<p>Keine Anträge vorhanden.</p>'; foreach ($items as $item) : $user=get_userdata($item->user_id); ?>
        <div class="postbox" style="padding:18px;margin-top:15px;max-width:1100px"><div style="display:flex;justify-content:space-between;gap:20px"><div><h2 style="margin:0 0 8px"><?php echo esc_html($user?$user->display_name:'Unbekannt'); ?> · <?php echo esc_html($this->type_label($item->vacation_type)); ?></h2><p><strong>Zeitraum:</strong> <?php echo esc_html($this->period_label($item)); ?> · <strong><?php echo esc_html($this->format_days((float)$item->requested_days)); ?> Tag(e)</strong><br><strong>Bemerkung:</strong> <?php echo esc_html($item->employee_note ?: '–'); ?></p></div><span><?php echo esc_html($this->status_label($item->status)); ?></span></div>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:grid;grid-template-columns:200px 1fr auto;gap:12px;align-items:end"><input type="hidden" name="action" value="brehl_update_vacation"><input type="hidden" name="request_id" value="<?php echo esc_attr($item->id); ?>"><?php wp_nonce_field('brehl_update_vacation_' . $item->id); ?><label><strong>Status</strong><select name="status" style="width:100%"><option value="eingereicht" <?php selected($item->status,'eingereicht'); ?>>Eingereicht</option><option value="genehmigt" <?php selected($item->status,'genehmigt'); ?>>Genehmigt</option><option value="abgelehnt" <?php selected($item->status,'abgelehnt'); ?>>Abgelehnt</option></select></label><label><strong>Rückmeldung</strong><textarea name="admin_note" rows="2" style="width:100%"><?php echo esc_textarea($item->admin_note); ?></textarea></label><button class="button button-primary">Aktualisieren</button></form></div>
        <?php endforeach; ?></div><?php
    }

    private function balance(int $uid, int $year): array {
        global $wpdb;
        $entitlement = get_user_meta($uid, 'brehl_vacation_entitlement_' . $year, true);
        $entitlement = '' === $entitlement ? 30.0 : (float)$entitlement;
        $carryover = (float)get_user_meta($uid, 'brehl_vacation_carryover_' . $year, true);
        $approved = (float)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(requested_days),0) FROM {$this->table()} WHERE user_id=%d AND status='genehmigt' AND vacation_type='urlaub' AND YEAR(start_date)=%d", $uid, $year));
        $pending = (float)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(requested_days),0) FROM {$this->table()} WHERE user_id=%d AND status='eingereicht' AND vacation_type='urlaub' AND YEAR(start_date)=%d", $uid, $year));
        return array('entitlement'=>$entitlement,'carryover'=>$carryover,'total'=>$entitlement+$carryover,'approved'=>$approved,'pending'=>$pending,'available'=>$entitlement+$carryover-$approved-$pending);
    }

    private function working_days(string $start, string $end): float { $a=new DateTimeImmutable($start); $b=(new DateTimeImmutable($end))->modify('+1 day'); $days=0; for($d=$a;$d<$b;$d=$d->modify('+1 day')) if((int)$d->format('N')<=5)$days++; return (float)$days; }
    private function valid_date(string $date): bool { $d=DateTime::createFromFormat('Y-m-d',$date); return $d && $d->format('Y-m-d')===$date; }
    private function format_days(float $n): string { return rtrim(rtrim(number_format($n,1,',','.'),'0'),','); }
    private function type_label(string $s): string { return array('urlaub'=>'Erholungsurlaub','sonderurlaub'=>'Sonderurlaub','unbezahlt'=>'Unbezahlter Urlaub')[$s] ?? 'Urlaub'; }
    private function status_label(string $s): string { return array('eingereicht'=>'Eingereicht','genehmigt'=>'Genehmigt','abgelehnt'=>'Abgelehnt')[$s] ?? 'Eingereicht'; }
    private function pagination_html(int $page,int $total,int $per_page,string $key): string { $pages=(int)ceil($total/$per_page); if($pages<2)return ''; $placeholder=999999999; $base=str_replace((string)$placeholder,'%#%',esc_url_raw(add_query_arg($key,$placeholder))); $links=paginate_links(array('base'=>$base,'format'=>'','current'=>min($page,$pages),'total'=>$pages,'type'=>'list','prev_text'=>__('Zurück','brehl-intranet'),'next_text'=>__('Weiter','brehl-intranet'))); return $links?'<nav class="mbs-workwear-pagination" aria-label="'.esc_attr__('Archivseiten','brehl-intranet').'">'.$links.'</nav>':''; }
    private function period_label($item): string { $label=wp_date('d.m.Y',strtotime($item->start_date)); if($item->end_date!==$item->start_date)$label.=' – '.wp_date('d.m.Y',strtotime($item->end_date)); if('half_morning'===$item->day_part)$label.=' (vormittags)'; if('half_afternoon'===$item->day_part)$label.=' (nachmittags)'; return $label; }
    private function redirect(string $result): void { $url=wp_get_referer() ?: home_url('/dashboard/'); wp_safe_redirect(add_query_arg('vacation',$result,$url)); exit; }

    private function notify_managers(int $id,int $uid,string $start,string $end): void { global $wpdb; $u=get_userdata($uid); $admins=get_users(array('role__in'=>array('administrator','personalverwaltung'),'fields'=>'ID')); foreach(array_unique(array_map('intval',$admins)) as $aid)$wpdb->insert($wpdb->prefix.'my_brehl_notifications',array('user_id'=>$aid,'title'=>'Neuer Urlaubsantrag','message'=>($u?$u->display_name:'Ein Mitarbeiter').' beantragt Urlaub vom '.wp_date('d.m.Y',strtotime($start)).' bis '.wp_date('d.m.Y',strtotime($end)).'.','type'=>'info','link_url'=>home_url('/personalverwaltung/'),'is_read'=>0,'created_at'=>current_time('mysql'))); }
    private function notify_user(int $uid,string $status,$item): void { global $wpdb; $wpdb->insert($wpdb->prefix.'my_brehl_notifications',array('user_id'=>$uid,'title'=>'Urlaubsantrag aktualisiert','message'=>'Ihr Antrag für '.$this->period_label($item).' wurde '.$this->status_label($status).'.','type'=>$status==='genehmigt'?'success':($status==='abgelehnt'?'warning':'info'),'link_url'=>home_url('/urlaub/'),'is_read'=>0,'created_at'=>current_time('mysql'))); }
    private function log_activity(int $uid,int $actor,string $action,int $id): void { global $wpdb; $wpdb->insert($wpdb->prefix.'my_brehl_activity',array('user_id'=>$uid,'actor_id'=>$actor,'module'=>'urlaub','action'=>$action,'object_type'=>'urlaubsantrag','object_id'=>$id,'details'=>'','created_at'=>current_time('mysql'))); }
}
