<?php
defined('ABSPATH') || exit;

final class Brehl_Vehicle_Service_Module {
    private static ?self $instance = null;
    private const DB_VERSION = '1.0';

    public static function instance(): self { return self::$instance ??= new self(); }

    private function __construct() {
        add_action('init', array($this, 'maybe_install'));
        add_action('admin_post_brehl_submit_vehicle_service', array($this, 'handle_submission'));
    }

    public static function install(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = $wpdb->prefix . 'brehl_vehicle_service_requests';
        $charset = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            vehicle_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            license_plate VARCHAR(40) NOT NULL,
            current_mileage BIGINT UNSIGNED NOT NULL,
            service_types LONGTEXT NOT NULL,
            description TEXT NOT NULL,
            urgency VARCHAR(30) NOT NULL DEFAULT 'normal',
            warning_light TINYINT(1) NOT NULL DEFAULT 0,
            drivable TINYINT(1) NOT NULL DEFAULT 1,
            desired_date DATE NULL,
            odometer_photo_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(30) NOT NULL DEFAULT 'submitted',
            admin_note TEXT NULL,
            appointment_at DATETIME NULL,
            workshop VARCHAR(190) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id), KEY user_id (user_id), KEY vehicle_id (vehicle_id), KEY status (status)
        ) {$charset};");
        update_option('brehl_vehicle_service_db_version', self::DB_VERSION);
    }

    public function maybe_install(): void { if (self::DB_VERSION !== get_option('brehl_vehicle_service_db_version')) self::install(); }
    private function table(): string { global $wpdb; return $wpdb->prefix . 'brehl_vehicle_service_requests'; }
    private function vehicles_table(): string { global $wpdb; return $wpdb->prefix . 'brehl_vehicles'; }

    public function request_panel(): string {
        if (!is_user_logged_in() || !(current_user_can('my_brehl_submit_vehicle_service') || current_user_can('my_brehl_manage_system'))) return '';
        wp_enqueue_style('brehl-intranet'); wp_enqueue_style('my-brehl-system'); wp_enqueue_style('brehl-intranet-vehicle-damage');
        global $wpdb;
        $uid = get_current_user_id();
        $plate = (string) get_user_meta($uid, 'brehl_vehicle_license_plate', true);
        $vehicle = $plate ? $wpdb->get_row($wpdb->prepare("SELECT id,manufacturer,model,current_mileage FROM {$this->vehicles_table()} WHERE license_plate=%s", $plate)) : null;
        $result = sanitize_key($_GET['vehicle_service'] ?? '');
        $types = $this->service_types();
        ob_start(); ?>
        <section class="mbs-vehicle-damage mbs-vehicle-service"><div class="mbs-card">
            <div class="mbs-card-head"><div><span class="mbs-kicker"><?php esc_html_e('Fuhrpark', 'brehl-intranet'); ?></span><h3><?php esc_html_e('Serviceanfrage stellen', 'brehl-intranet'); ?></h3></div></div>
            <?php if ('saved' === $result) : ?><div class="mbs-form-message is-success"><?php esc_html_e('Die Serviceanfrage wurde übermittelt.', 'brehl-intranet'); ?></div><?php elseif ('error' === $result) : ?><div class="mbs-form-message is-error"><?php esc_html_e('Bitte prüfen Sie Ihre Angaben.', 'brehl-intranet'); ?></div><?php endif; ?>
            <form class="mbs-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="brehl_submit_vehicle_service"><input type="hidden" name="vehicle_id" value="<?php echo esc_attr((string) ($vehicle->id ?? 0)); ?>"><?php wp_nonce_field('brehl_submit_vehicle_service'); ?>
                <div class="mbs-form-grid">
                    <label><span><?php esc_html_e('Kennzeichen', 'brehl-intranet'); ?> *</span><input name="license_plate" required value="<?php echo esc_attr($plate); ?>" placeholder="FD-AB 123"></label>
                    <label><span><?php esc_html_e('Fahrzeug', 'brehl-intranet'); ?></span><input value="<?php echo esc_attr($vehicle ? trim($vehicle->manufacturer . ' ' . $vehicle->model) : ''); ?>" readonly placeholder="Wird anhand des Kennzeichens zugeordnet"></label>
                    <label><span><?php esc_html_e('Aktueller Kilometerstand', 'brehl-intranet'); ?> *</span><input name="current_mileage" type="number" min="0" step="1" required value="<?php echo esc_attr((string) ($vehicle->current_mileage ?? '')); ?>"></label>
                    <label><span><?php esc_html_e('Gewünschter Zeitraum', 'brehl-intranet'); ?></span><input name="desired_date" type="date" min="<?php echo esc_attr(wp_date('Y-m-d')); ?>"></label>
                    <label class="mbs-form-full"><span><?php esc_html_e('Tachofoto (optional)', 'brehl-intranet'); ?></span><input name="odometer_photo" type="file" accept="image/jpeg,image/png,image/webp"><small><?php esc_html_e('JPG, PNG oder WebP, maximal 8 MB.', 'brehl-intranet'); ?></small></label>
                </div>
                <fieldset class="mbs-service-types"><legend><?php esc_html_e('Benötigte Arbeiten', 'brehl-intranet'); ?> *</legend><div><?php foreach ($types as $key=>$label) : ?><label><input type="checkbox" name="service_types[]" value="<?php echo esc_attr($key); ?>"> <span><?php echo esc_html($label); ?></span></label><?php endforeach; ?></div></fieldset>
                <div class="mbs-form-grid">
                    <label><span><?php esc_html_e('Dringlichkeit', 'brehl-intranet'); ?></span><select name="urgency"><option value="normal">Normal</option><option value="soon">Zeitnah</option><option value="urgent">Dringend</option></select></label>
                    <label class="mbs-form-full"><span><?php esc_html_e('Beschreibung / Hinweise', 'brehl-intranet'); ?> *</span><textarea name="description" rows="5" required placeholder="Bitte beschreiben Sie Auffälligkeiten, Geräusche oder weitere Hinweise."></textarea></label>
                </div>
                <div class="mbs-check-grid"><label><input type="checkbox" name="warning_light" value="1"> <span><?php esc_html_e('Warnleuchte ist aktiv', 'brehl-intranet'); ?></span></label><label><input type="checkbox" name="not_drivable" value="1"> <span><?php esc_html_e('Fahrzeug ist nicht fahrbereit', 'brehl-intranet'); ?></span></label></div>
                <button class="mbs-primary-button" type="submit"><?php esc_html_e('Serviceanfrage absenden', 'brehl-intranet'); ?></button>
            </form>
        </div></section>
        <?php return (string) ob_get_clean();
    }

    public function status_panel(): string {
        if (!is_user_logged_in()) return '';
        wp_enqueue_style('brehl-intranet'); wp_enqueue_style('my-brehl-system');
        global $wpdb;
        $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table()} WHERE user_id=%d ORDER BY created_at DESC LIMIT 30", get_current_user_id()));
        ob_start(); ?>
        <section class="mbs-vehicle-service-status"><div class="mbs-card"><div class="mbs-card-head"><h3><?php esc_html_e('Meine Serviceanfragen', 'brehl-intranet'); ?></h3></div><div class="mbs-list">
            <?php if (!$items) : ?><p class="mbs-empty"><?php esc_html_e('Sie haben noch keine Serviceanfrage gestellt.', 'brehl-intranet'); ?></p><?php endif; ?>
            <?php foreach ($items as $item) : ?><article class="mbs-damage-item"><div><strong><?php echo esc_html($item->license_plate); ?> · <?php echo esc_html(implode(', ', array_map(fn($type)=>$this->service_types()[$type] ?? $type, (array) json_decode($item->service_types,true)))); ?></strong><p><?php echo esc_html(wp_trim_words($item->description,18)); ?></p><small><?php echo esc_html(number_format_i18n((int)$item->current_mileage)); ?> km · <?php echo esc_html(wp_date('d.m.Y',strtotime($item->created_at))); ?><?php echo $item->appointment_at ? ' · Termin: ' . esc_html(wp_date('d.m.Y H:i',strtotime($item->appointment_at))) : ''; ?></small></div><span class="mbs-status"><?php echo esc_html($this->status_label($item->status)); ?></span></article><?php endforeach; ?>
        </div></div></section>
        <?php return (string) ob_get_clean();
    }

    public function handle_submission(): void {
        if (!is_user_logged_in() || !(current_user_can('my_brehl_submit_vehicle_service') || current_user_can('my_brehl_manage_system'))) wp_die(esc_html__('Keine Berechtigung.', 'brehl-intranet'),403);
        check_admin_referer('brehl_submit_vehicle_service');
        $plate = mb_strtoupper(sanitize_text_field(wp_unslash($_POST['license_plate'] ?? '')));
        $mileage = absint($_POST['current_mileage'] ?? 0);
        $description = sanitize_textarea_field(wp_unslash($_POST['description'] ?? ''));
        $allowed = array_keys($this->service_types());
        $types = array_values(array_intersect($allowed,array_map('sanitize_key',(array)($_POST['service_types'] ?? array()))));
        if (!$plate || !$mileage || !$description || !$types) $this->redirect('error');
        $urgency = sanitize_key($_POST['urgency'] ?? 'normal'); if (!in_array($urgency,array('normal','soon','urgent'),true)) $urgency='normal';
        $photo = $this->upload_photo(); $now=current_time('mysql'); global $wpdb;
        $saved=$wpdb->insert($this->table(),array('user_id'=>get_current_user_id(),'vehicle_id'=>absint($_POST['vehicle_id'] ?? 0),'license_plate'=>$plate,'current_mileage'=>$mileage,'service_types'=>wp_json_encode($types),'description'=>$description,'urgency'=>$urgency,'warning_light'=>isset($_POST['warning_light'])?1:0,'drivable'=>isset($_POST['not_drivable'])?0:1,'desired_date'=>$this->optional_date($_POST['desired_date'] ?? ''),'odometer_photo_id'=>$photo,'status'=>'submitted','created_at'=>$now,'updated_at'=>$now));
        if (!$saved) $this->redirect('error');
        $wpdb->query($wpdb->prepare("UPDATE {$this->vehicles_table()} SET current_mileage=GREATEST(current_mileage,%d),updated_at=%s WHERE license_plate=%s",$mileage,$now,$plate));
        $this->notify_managers($plate); $this->redirect('saved');
    }

    private function upload_photo(): int {
        if (empty($_FILES['odometer_photo']['name'])) return 0;
        $file = $_FILES['odometer_photo'];
        if ((int) $file['error'] !== UPLOAD_ERR_OK || (int) $file['size'] > 8 * MB_IN_BYTES) return 0;
        $checked = wp_check_filetype_and_ext($file['tmp_name'], sanitize_file_name($file['name']));
        if (empty($checked['type']) || !in_array($checked['type'], array('image/jpeg','image/png','image/webp'), true)) return 0;
        require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
        $id = media_handle_upload('odometer_photo', 0);
        return is_wp_error($id) ? 0 : (int) $id;
    }
    private function optional_date($date): ?string { $date=sanitize_text_field(wp_unslash((string)$date)); if (!$date)return null; $parsed=DateTime::createFromFormat('Y-m-d',$date); return $parsed&&$parsed->format('Y-m-d')===$date?$date:null; }
    private function redirect(string $result): void { $url=wp_get_referer()?:home_url('/dashboard/'); wp_safe_redirect(add_query_arg('vehicle_service',$result,$url)); exit; }
    private function notify_managers(string $plate): void { global $wpdb; foreach (get_users(array('role__in'=>array('administrator','personalverwaltung'),'fields'=>'ID')) as $uid) $wpdb->insert($wpdb->prefix.'my_brehl_notifications',array('user_id'=>(int)$uid,'title'=>'Neue Serviceanfrage','message'=>'Für '.$plate.' wurde eine Serviceanfrage gestellt.','type'=>'info','link_url'=>'','is_read'=>0,'created_at'=>current_time('mysql'))); }
    private function status_label(string $status): string { return array('submitted'=>'Eingereicht','review'=>'In Prüfung','scheduled'=>'Termin vereinbart','workshop'=>'In Werkstatt','completed'=>'Abgeschlossen','rejected'=>'Abgelehnt')[$status]??'Eingereicht'; }
    private function service_types(): array { return array('inspection'=>'Inspektion','oil'=>'Ölwechsel','tuv'=>'TÜV / Hauptuntersuchung','tires_front'=>'Reifen vorne','tires_rear'=>'Reifen hinten','tires_all'=>'Reifen komplett','pads_front'=>'Bremsbeläge vorne','pads_rear'=>'Bremsbeläge hinten','discs_front'=>'Bremsscheiben vorne','discs_rear'=>'Bremsscheiben hinten','brakes'=>'Bremsanlage allgemein','lighting'=>'Beleuchtung','glass'=>'Scheiben / Spiegel','body'=>'Karosserie','engine'=>'Motor / Antrieb','electrical'=>'Elektrik / Warnleuchte','climate'=>'Klimaanlage / Heizung','other'=>'Sonstiges'); }
}
