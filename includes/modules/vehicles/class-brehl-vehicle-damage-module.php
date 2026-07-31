<?php

defined('ABSPATH') || exit;

final class Brehl_Vehicle_Damage_Module {
    private static $instance = null;
    private const DB_VERSION = '1.0';

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'maybe_install'));
        add_action('init', array($this, 'register_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_post_brehl_submit_vehicle_damage', array($this, 'handle_submission'));
        add_action('admin_post_brehl_update_vehicle_damage', array($this, 'handle_status_update'));
    }

    public static function install(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table = $wpdb->prefix . 'brehl_vehicle_damages';
        $charset = $wpdb->get_charset_collate();

        dbDelta("CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            vehicle VARCHAR(190) NOT NULL,
            license_plate VARCHAR(40) NULL,
            incident_date DATE NOT NULL,
            incident_time TIME NULL,
            location VARCHAR(190) NULL,
            description TEXT NOT NULL,
            third_party_involved TINYINT(1) NOT NULL DEFAULT 0,
            police_involved TINYINT(1) NOT NULL DEFAULT 0,
            drivable TINYINT(1) NOT NULL DEFAULT 1,
            attachment_ids LONGTEXT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'neu',
            admin_note TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY incident_date (incident_date)
        ) {$charset};");

        update_option('brehl_vehicle_damage_db_version', self::DB_VERSION);
    }

    public function maybe_install(): void {
        if (self::DB_VERSION !== get_option('brehl_vehicle_damage_db_version')) {
            self::install();
        }
    }

    public function register_shortcode(): void {
        add_shortcode('my_brehl_fahrzeugschaden', array($this, 'shortcode'));
    }

    private function can_manage(): bool {
        return current_user_can('my_brehl_manage_system') || current_user_can('manage_options');
    }

    private function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'brehl_vehicle_damages';
    }

    public function admin_menu(): void {
        add_submenu_page(
            'my-brehl-system',
            __('Fahrzeugschäden', 'brehl-intranet'),
            __('Fahrzeugschäden', 'brehl-intranet'),
            'my_brehl_manage_system',
            'my-brehl-vehicle-damages',
            array($this, 'admin_page')
        );
    }

    public function shortcode(): string {
        if (!is_user_logged_in()) {
            return '';
        }

        wp_enqueue_style('brehl-intranet');
        wp_enqueue_style('my-brehl-system');

        global $wpdb;
        $user_id = get_current_user_id();
        $table = $this->table();
        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC LIMIT 10",
                $user_id
            )
        );

        $saved = isset($_GET['vehicle_damage']) && 'saved' === sanitize_key(wp_unslash($_GET['vehicle_damage']));
        $error = isset($_GET['vehicle_damage']) && 'error' === sanitize_key(wp_unslash($_GET['vehicle_damage']));

        ob_start();
        ?>
        <section class="mbs-vehicle-damage">
            <div class="mbs-card">
                <div class="mbs-card-head">
                    <div>
                        <span class="mbs-kicker">FUHRPARK</span>
                        <h3><?php esc_html_e('Fahrzeugschaden melden', 'brehl-intranet'); ?></h3>
                    </div>
                </div>

                <?php if ($saved) : ?>
                    <div class="mbs-form-message is-success"><?php esc_html_e('Die Schadenmeldung wurde erfolgreich übermittelt.', 'brehl-intranet'); ?></div>
                <?php elseif ($error) : ?>
                    <div class="mbs-form-message is-error"><?php esc_html_e('Die Schadenmeldung konnte nicht gespeichert werden. Bitte prüfen Sie Ihre Angaben.', 'brehl-intranet'); ?></div>
                <?php endif; ?>

                <form class="mbs-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="brehl_submit_vehicle_damage">
                    <?php wp_nonce_field('brehl_submit_vehicle_damage', 'brehl_vehicle_damage_nonce'); ?>

                    <div class="mbs-form-grid">
                        <label>
                            <span><?php esc_html_e('Fahrzeug / Modell', 'brehl-intranet'); ?> *</span>
                            <input type="text" name="vehicle" required placeholder="z. B. Mercedes Vito">
                        </label>
                        <label>
                            <span><?php esc_html_e('Kennzeichen', 'brehl-intranet'); ?></span>
                            <input type="text" name="license_plate" placeholder="FD-AB 123">
                        </label>
                        <label>
                            <span><?php esc_html_e('Datum des Schadens', 'brehl-intranet'); ?> *</span>
                            <input type="date" name="incident_date" required max="<?php echo esc_attr(wp_date('Y-m-d')); ?>">
                        </label>
                        <label>
                            <span><?php esc_html_e('Uhrzeit', 'brehl-intranet'); ?></span>
                            <input type="time" name="incident_time">
                        </label>
                        <label class="mbs-form-full">
                            <span><?php esc_html_e('Ort', 'brehl-intranet'); ?></span>
                            <input type="text" name="location" placeholder="Ort, Straße oder Baustelle">
                        </label>
                        <label class="mbs-form-full">
                            <span><?php esc_html_e('Schadensbeschreibung', 'brehl-intranet'); ?> *</span>
                            <textarea name="description" rows="6" required placeholder="Bitte beschreiben Sie kurz, wie der Schaden entstanden ist und was beschädigt wurde."></textarea>
                        </label>
                        <label class="mbs-form-full">
                            <span><?php esc_html_e('Fotos hochladen', 'brehl-intranet'); ?></span>
                            <input type="file" name="damage_photos[]" accept="image/jpeg,image/png,image/webp" multiple>
                            <small><?php esc_html_e('Bis zu 5 Bilder, jeweils maximal 8 MB.', 'brehl-intranet'); ?></small>
                        </label>
                    </div>

                    <div class="mbs-check-grid">
                        <label><input type="checkbox" name="third_party_involved" value="1"> <span><?php esc_html_e('Weitere Person oder weiteres Fahrzeug beteiligt', 'brehl-intranet'); ?></span></label>
                        <label><input type="checkbox" name="police_involved" value="1"> <span><?php esc_html_e('Polizei wurde verständigt', 'brehl-intranet'); ?></span></label>
                        <label><input type="checkbox" name="not_drivable" value="1"> <span><?php esc_html_e('Fahrzeug ist nicht mehr fahrbereit', 'brehl-intranet'); ?></span></label>
                    </div>

                    <button type="submit" class="mbs-primary-button"><?php esc_html_e('Schadenmeldung absenden', 'brehl-intranet'); ?></button>
                </form>
            </div>

            <div class="mbs-card">
                <div class="mbs-card-head"><h3><?php esc_html_e('Meine Meldungen', 'brehl-intranet'); ?></h3></div>
                <div class="mbs-list">
                    <?php if (!$items) : ?>
                        <p class="mbs-empty"><?php esc_html_e('Sie haben noch keine Fahrzeugschäden gemeldet.', 'brehl-intranet'); ?></p>
                    <?php endif; ?>
                    <?php foreach ($items as $item) : ?>
                        <article class="mbs-damage-item">
                            <div>
                                <strong><?php echo esc_html($item->vehicle); ?><?php echo $item->license_plate ? ' · ' . esc_html($item->license_plate) : ''; ?></strong>
                                <p><?php echo esc_html(wp_trim_words($item->description, 22)); ?></p>
                                <small><?php echo esc_html(wp_date('d.m.Y', strtotime($item->incident_date))); ?> · <?php echo esc_html(wp_date('d.m.Y H:i', strtotime($item->created_at))); ?></small>
                            </div>
                            <span class="mbs-status mbs-status-<?php echo esc_attr($item->status); ?>"><?php echo esc_html($this->status_label($item->status)); ?></span>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    public function handle_submission(): void {
        if (!is_user_logged_in()) {
            auth_redirect();
        }

        if (
            !isset($_POST['brehl_vehicle_damage_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['brehl_vehicle_damage_nonce'])), 'brehl_submit_vehicle_damage')
        ) {
            $this->redirect_frontend('error');
        }

        $vehicle = sanitize_text_field(wp_unslash($_POST['vehicle'] ?? ''));
        $incident_date = sanitize_text_field(wp_unslash($_POST['incident_date'] ?? ''));
        $description = sanitize_textarea_field(wp_unslash($_POST['description'] ?? ''));

        if ('' === $vehicle || '' === $incident_date || '' === $description || !$this->valid_date($incident_date)) {
            $this->redirect_frontend('error');
        }

        $attachments = $this->handle_uploads();

        global $wpdb;
        $now = current_time('mysql');
        $inserted = $wpdb->insert(
            $this->table(),
            array(
                'user_id' => get_current_user_id(),
                'vehicle' => $vehicle,
                'license_plate' => sanitize_text_field(wp_unslash($_POST['license_plate'] ?? '')),
                'incident_date' => $incident_date,
                'incident_time' => sanitize_text_field(wp_unslash($_POST['incident_time'] ?? '')) ?: null,
                'location' => sanitize_text_field(wp_unslash($_POST['location'] ?? '')),
                'description' => $description,
                'third_party_involved' => isset($_POST['third_party_involved']) ? 1 : 0,
                'police_involved' => isset($_POST['police_involved']) ? 1 : 0,
                'drivable' => isset($_POST['not_drivable']) ? 0 : 1,
                'attachment_ids' => wp_json_encode($attachments),
                'status' => 'neu',
                'created_at' => $now,
                'updated_at' => $now,
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s')
        );

        if (!$inserted) {
            $this->redirect_frontend('error');
        }

        $damage_id = (int) $wpdb->insert_id;
        $this->create_admin_notification($damage_id, $vehicle);
        $this->log_activity(get_current_user_id(), get_current_user_id(), 'Fahrzeugschaden gemeldet: ' . $vehicle, $damage_id);
        $this->redirect_frontend('saved');
    }

    private function handle_uploads(): array {
        if (empty($_FILES['damage_photos']['name']) || !is_array($_FILES['damage_photos']['name'])) {
            return array();
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $ids = array();
        $files = $_FILES['damage_photos'];
        $limit = min(5, count($files['name']));
        $allowed = array('image/jpeg', 'image/png', 'image/webp');

        for ($i = 0; $i < $limit; $i++) {
            if (UPLOAD_ERR_NO_FILE === (int) $files['error'][$i]) {
                continue;
            }
            if (UPLOAD_ERR_OK !== (int) $files['error'][$i] || (int) $files['size'][$i] > 8 * MB_IN_BYTES) {
                continue;
            }

            $file = array(
                'name' => sanitize_file_name($files['name'][$i]),
                'type' => sanitize_mime_type($files['type'][$i]),
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            );

            $checked = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
            if (empty($checked['type']) || !in_array($checked['type'], $allowed, true)) {
                continue;
            }

            $_FILES['brehl_single_damage_photo'] = $file;
            $attachment_id = media_handle_upload('brehl_single_damage_photo', 0);
            unset($_FILES['brehl_single_damage_photo']);

            if (!is_wp_error($attachment_id)) {
                $ids[] = (int) $attachment_id;
            }
        }

        return $ids;
    }

    public function handle_status_update(): void {
        if (!$this->can_manage()) {
            wp_die(esc_html__('Keine Berechtigung.', 'brehl-intranet'));
        }

        $id = absint($_POST['damage_id'] ?? 0);
        check_admin_referer('brehl_update_vehicle_damage_' . $id);

        $allowed = array('neu', 'in_pruefung', 'beauftragt', 'erledigt', 'abgelehnt');
        $status = sanitize_key($_POST['status'] ?? 'neu');
        if (!in_array($status, $allowed, true)) {
            $status = 'neu';
        }

        global $wpdb;
        $table = $this->table();
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
        if (!$item) {
            wp_safe_redirect(admin_url('admin.php?page=my-brehl-vehicle-damages'));
            exit;
        }

        $note = sanitize_textarea_field(wp_unslash($_POST['admin_note'] ?? ''));
        $wpdb->update(
            $this->table(),
            array('status' => $status, 'admin_note' => $note, 'updated_at' => current_time('mysql')),
            array('id' => $id),
            array('%s', '%s', '%s'),
            array('%d')
        );

        $this->create_user_notification((int) $item->user_id, $id, $status, $item->vehicle);
        $this->log_activity((int) $item->user_id, get_current_user_id(), 'Status der Schadenmeldung geändert: ' . $this->status_label($status), $id);

        wp_safe_redirect(admin_url('admin.php?page=my-brehl-vehicle-damages&updated=1'));
        exit;
    }

    public function admin_page(): void {
        if (!$this->can_manage()) {
            wp_die(esc_html__('Keine Berechtigung.', 'brehl-intranet'));
        }

        global $wpdb;
        $status_filter = sanitize_key($_GET['status'] ?? '');
        $allowed = array('neu', 'in_pruefung', 'beauftragt', 'erledigt', 'abgelehnt');
        $where = '';
        if (in_array($status_filter, $allowed, true)) {
            $where = $wpdb->prepare(' WHERE status = %s', $status_filter);
        }
        $table = $this->table();
        $items = $wpdb->get_results("SELECT * FROM {$table}{$where} ORDER BY created_at DESC LIMIT 200");
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Fahrzeugschäden', 'brehl-intranet'); ?></h1>
            <p><?php esc_html_e('Hier werden alle über das Mitarbeiterportal eingereichten Schadenmeldungen verwaltet.', 'brehl-intranet'); ?></p>

            <p class="subsubsub">
                <a href="<?php echo esc_url(admin_url('admin.php?page=my-brehl-vehicle-damages')); ?>" class="<?php echo '' === $status_filter ? 'current' : ''; ?>">Alle</a> |
                <?php foreach ($allowed as $index => $status) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=my-brehl-vehicle-damages&status=' . $status)); ?>" class="<?php echo $status_filter === $status ? 'current' : ''; ?>"><?php echo esc_html($this->status_label($status)); ?></a><?php echo $index < count($allowed) - 1 ? ' | ' : ''; ?>
                <?php endforeach; ?>
            </p>
            <div style="clear:both"></div>

            <?php if (!$items) : ?>
                <div class="notice notice-info"><p><?php esc_html_e('Keine Schadenmeldungen vorhanden.', 'brehl-intranet'); ?></p></div>
            <?php endif; ?>

            <?php foreach ($items as $item) :
                $user = get_userdata($item->user_id);
                $attachments = json_decode((string) $item->attachment_ids, true);
                ?>
                <div class="postbox" style="padding:20px;margin-top:18px;max-width:1150px">
                    <div style="display:flex;justify-content:space-between;gap:20px;align-items:flex-start;flex-wrap:wrap">
                        <div>
                            <h2 style="margin-top:0"><?php echo esc_html($item->vehicle); ?><?php echo $item->license_plate ? ' · ' . esc_html($item->license_plate) : ''; ?></h2>
                            <p><strong>Mitarbeiter:</strong> <?php echo esc_html($user ? $user->display_name : 'Unbekannt'); ?><br>
                            <strong>Schadendatum:</strong> <?php echo esc_html(wp_date('d.m.Y', strtotime($item->incident_date))); ?><?php echo $item->incident_time ? ' · ' . esc_html(substr($item->incident_time, 0, 5)) . ' Uhr' : ''; ?><br>
                            <strong>Ort:</strong> <?php echo esc_html($item->location ?: '–'); ?><br>
                            <strong>Fahrbereit:</strong> <?php echo $item->drivable ? 'Ja' : 'Nein'; ?> · <strong>Polizei:</strong> <?php echo $item->police_involved ? 'Ja' : 'Nein'; ?> · <strong>Dritte beteiligt:</strong> <?php echo $item->third_party_involved ? 'Ja' : 'Nein'; ?></p>
                        </div>
                        <span style="padding:7px 12px;border-radius:999px;background:#f1f1f1;font-weight:600"><?php echo esc_html($this->status_label($item->status)); ?></span>
                    </div>
                    <p><strong>Beschreibung:</strong><br><?php echo nl2br(esc_html($item->description)); ?></p>

                    <?php if (is_array($attachments) && $attachments) : ?>
                        <div style="display:flex;gap:10px;flex-wrap:wrap;margin:15px 0">
                            <?php foreach ($attachments as $attachment_id) :
                                $url = wp_get_attachment_image_url((int) $attachment_id, 'medium');
                                $full = wp_get_attachment_url((int) $attachment_id);
                                if (!$url || !$full) continue;
                                ?>
                                <a href="<?php echo esc_url($full); ?>" target="_blank" rel="noopener"><img src="<?php echo esc_url($url); ?>" alt="" style="width:150px;height:110px;object-fit:cover;border-radius:6px"></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:grid;grid-template-columns:minmax(180px,240px) 1fr auto;gap:12px;align-items:end">
                        <input type="hidden" name="action" value="brehl_update_vehicle_damage">
                        <input type="hidden" name="damage_id" value="<?php echo esc_attr($item->id); ?>">
                        <?php wp_nonce_field('brehl_update_vehicle_damage_' . $item->id); ?>
                        <label><strong>Status</strong><br><select name="status" style="width:100%">
                            <?php foreach ($allowed as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($item->status, $status); ?>><?php echo esc_html($this->status_label($status)); ?></option><?php endforeach; ?>
                        </select></label>
                        <label><strong>Interne Notiz / Rückmeldung</strong><br><textarea name="admin_note" rows="2" style="width:100%"><?php echo esc_textarea($item->admin_note); ?></textarea></label>
                        <button class="button button-primary" type="submit">Aktualisieren</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function status_label(string $status): string {
        $labels = array(
            'neu' => __('Neu', 'brehl-intranet'),
            'in_pruefung' => __('In Prüfung', 'brehl-intranet'),
            'beauftragt' => __('Reparatur beauftragt', 'brehl-intranet'),
            'erledigt' => __('Erledigt', 'brehl-intranet'),
            'abgelehnt' => __('Abgelehnt', 'brehl-intranet'),
        );
        return $labels[$status] ?? $labels['neu'];
    }

    private function valid_date(string $date): bool {
        $parsed = DateTime::createFromFormat('Y-m-d', $date);
        return $parsed && $parsed->format('Y-m-d') === $date && $date <= wp_date('Y-m-d');
    }

    private function redirect_frontend(string $result): void {
        $redirect = wp_get_referer();
        if (!$redirect) {
            $redirect = home_url('/dashboard/');
        }
        wp_safe_redirect(add_query_arg('vehicle_damage', $result, $redirect));
        exit;
    }

    private function create_admin_notification(int $damage_id, string $vehicle): void {
        global $wpdb;
        $admins = get_users(array('role__in' => array('administrator', 'personalverwaltung'), 'fields' => 'ID'));
        foreach (array_unique(array_map('intval', $admins)) as $admin_id) {
            $wpdb->insert(
                $wpdb->prefix . 'my_brehl_notifications',
                array(
                    'user_id' => $admin_id,
                    'title' => 'Neue Fahrzeugschadenmeldung',
                    'message' => 'Für ' . $vehicle . ' wurde eine neue Schadenmeldung eingereicht.',
                    'type' => 'warning',
                    'link_url' => admin_url('admin.php?page=my-brehl-vehicle-damages'),
                    'is_read' => 0,
                    'created_at' => current_time('mysql'),
                )
            );
        }
    }

    private function create_user_notification(int $user_id, int $damage_id, string $status, string $vehicle): void {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'my_brehl_notifications',
            array(
                'user_id' => $user_id,
                'title' => 'Fahrzeugschaden aktualisiert',
                'message' => 'Der Status Ihrer Meldung für ' . $vehicle . ' lautet jetzt: ' . $this->status_label($status) . '.',
                'type' => 'info',
                'link_url' => '',
                'is_read' => 0,
                'created_at' => current_time('mysql'),
            )
        );
    }

    private function log_activity(int $user_id, int $actor_id, string $action, int $object_id): void {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'my_brehl_activity',
            array(
                'user_id' => $user_id,
                'actor_id' => $actor_id,
                'module' => 'fahrzeuge',
                'action' => $action,
                'object_type' => 'fahrzeugschaden',
                'object_id' => $object_id,
                'details' => '',
                'created_at' => current_time('mysql'),
            )
        );
    }
}
