<?php

defined('ABSPATH') || exit;

final class Brehl_Vehicle_Damage_Module {
    private static $instance = null;
    private const DB_VERSION = '2.2';

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
        add_action('admin_post_brehl_save_vehicle', array($this, 'handle_vehicle_save'));
        add_action('admin_post_brehl_archive_vehicle', array($this, 'handle_archive_vehicle'));
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
            incident_type VARCHAR(40) NOT NULL DEFAULT 'single_vehicle',
            third_party_involved TINYINT(1) NOT NULL DEFAULT 0,
            opponent_name VARCHAR(190) NULL,
            opponent_address VARCHAR(255) NULL,
            opponent_phone VARCHAR(80) NULL,
            opponent_license_plate VARCHAR(40) NULL,
            opponent_insurer VARCHAR(190) NULL,
            opponent_policy_number VARCHAR(120) NULL,
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

        $vehicles = $wpdb->prefix . 'brehl_vehicles';
        dbDelta("CREATE TABLE {$vehicles} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            license_plate VARCHAR(40) NOT NULL,
            manufacturer VARCHAR(100) NOT NULL,
            model VARCHAR(120) NOT NULL,
            vehicle_type VARCHAR(60) NULL,
            first_registration DATE NULL,
            current_mileage BIGINT UNSIGNED NOT NULL DEFAULT 0,
            assigned_user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            next_inspection DATE NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'active',
            internal_number VARCHAR(80) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY license_plate (license_plate),
            KEY assigned_user_id (assigned_user_id),
            KEY status (status)
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

    private function vehicle_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'brehl_vehicles';
    }

    public function vehicle_list_panel(): string {
        if (!$this->can_manage()) return '';
        wp_enqueue_style('brehl-intranet');
        global $wpdb;
        $show_archive='1'===sanitize_text_field(wp_unslash($_GET['vehicle_archive']??''));
        $where=$show_archive?"status='archived'":"status<>'archived'";
        $items = $wpdb->get_results("SELECT * FROM {$this->vehicle_table()} WHERE {$where} ORDER BY license_plate ASC");
        ob_start(); ?>
        <section class="brehl-hr"><div class="brehl-hr__panel">
            <div class="brehl-hr__panel-head"><h3><?php echo $show_archive?esc_html__('Fahrzeugarchiv','brehl-intranet'):esc_html__('Fahrzeuge','brehl-intranet'); ?></h3><div class="brehl-list-head-actions"><?php if($show_archive): ?><a href="<?php echo esc_url(remove_query_arg(array('vehicle_archive','vehicle_id'))); ?>"><?php esc_html_e('Aktive anzeigen','brehl-intranet'); ?></a><?php else: ?><a href="<?php echo esc_url(add_query_arg('vehicle_archive','1',remove_query_arg('vehicle_id'))); ?>"><?php esc_html_e('Archiv anzeigen','brehl-intranet'); ?></a><a href="<?php echo esc_url(remove_query_arg('vehicle_id')); ?>"><?php esc_html_e('Neu anlegen', 'brehl-intranet'); ?></a><?php endif; ?></div></div>
            <div class="brehl-vehicle-list">
                <?php foreach ($items as $item) : $user = $item->assigned_user_id ? get_userdata((int) $item->assigned_user_id) : null; ?>
                    <article class="brehl-vehicle-row">
                        <span class="brehl-vehicle-row__icon">🚗</span>
                        <div><strong><?php echo esc_html($item->license_plate); ?></strong><small><?php echo esc_html(trim($item->manufacturer . ' ' . $item->model)); ?> · <?php echo esc_html($user ? $user->display_name : __('Nicht fest zugeordnet', 'brehl-intranet')); ?></small></div>
                        <span class="brehl-vehicle-row__mileage"><?php echo esc_html(number_format_i18n((int) $item->current_mileage)); ?> km</span>
                        <span class="brehl-vehicle-row__status brehl-vehicle-row__status--<?php echo esc_attr($item->status); ?>"><?php echo esc_html($this->vehicle_status_label($item->status)); ?></span>
                        <div class="brehl-row-actions"><?php if(!$show_archive): ?><a href="<?php echo esc_url(add_query_arg('vehicle_id', (int) $item->id) . '#brehl-vehicle-form'); ?>"><?php esc_html_e('Bearbeiten', 'brehl-intranet'); ?></a><?php endif; ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"<?php echo $show_archive?'':' onsubmit="return confirm(\'Fahrzeug wirklich archivieren? Die Mitarbeiterzuordnung wird entfernt.\')"'; ?>><input type="hidden" name="action" value="brehl_archive_vehicle"><input type="hidden" name="vehicle_id" value="<?php echo esc_attr((string)$item->id); ?>"><input type="hidden" name="archive" value="<?php echo $show_archive?'0':'1'; ?>"><?php wp_nonce_field('brehl_archive_vehicle_'.$item->id); ?><button type="submit"><?php echo $show_archive?esc_html__('Wiederherstellen','brehl-intranet'):esc_html__('Archivieren','brehl-intranet'); ?></button></form></div>
                    </article>
                <?php endforeach; ?>
                <?php if (!$items) : ?><p class="brehl-hr__empty"><?php echo $show_archive?esc_html__('Das Fahrzeugarchiv ist leer.','brehl-intranet'):esc_html__('Noch keine Fahrzeuge angelegt.', 'brehl-intranet'); ?></p><?php endif; ?>
            </div>
        </div></section>
        <?php return (string) ob_get_clean();
    }

    public function vehicle_form_panel(): string {
        if (!$this->can_manage()) return '';
        wp_enqueue_style('brehl-intranet');
        global $wpdb;
        $id = absint($_GET['vehicle_id'] ?? 0);
        $item = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->vehicle_table()} WHERE id=%d", $id)) : null;
        if (!$item) $id = 0;
        $employees = array_values(array_filter(get_users(array('role' => Brehl_Roles::EMPLOYEE_ROLE, 'orderby' => 'display_name', 'order' => 'ASC')), static fn($employee) => '1' !== get_user_meta($employee->ID,'_my_brehl_archived',true)));
        $value = static fn(string $field): string => $item ? (string) $item->{$field} : '';
        $result = sanitize_key($_GET['vehicle_result'] ?? '');
        ob_start(); ?>
        <section class="brehl-hr" id="brehl-vehicle-form">
            <?php if ($result) : ?><div class="brehl-hr__notice"><?php echo 'saved' === $result ? esc_html__('Das Fahrzeug wurde gespeichert.', 'brehl-intranet') : esc_html__('Das Fahrzeug konnte nicht gespeichert werden. Bitte Eingaben prüfen.', 'brehl-intranet'); ?></div><?php endif; ?>
            <div class="brehl-hr__panel"><h3><?php echo $item ? esc_html__('Fahrzeug bearbeiten', 'brehl-intranet') : esc_html__('Fahrzeug anlegen', 'brehl-intranet'); ?></h3>
                <form class="brehl-hr-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="brehl_save_vehicle"><input type="hidden" name="vehicle_id" value="<?php echo esc_attr((string) $id); ?>"><?php wp_nonce_field('brehl_save_vehicle_' . $id); ?>
                    <label><span><?php esc_html_e('Kennzeichen', 'brehl-intranet'); ?> *</span><input name="license_plate" value="<?php echo esc_attr($value('license_plate')); ?>" required placeholder="FD-AB 123"></label>
                    <label><span><?php esc_html_e('Interne Fahrzeugnummer', 'brehl-intranet'); ?></span><input name="internal_number" value="<?php echo esc_attr($value('internal_number')); ?>"></label>
                    <label><span><?php esc_html_e('Hersteller', 'brehl-intranet'); ?> *</span><input name="manufacturer" value="<?php echo esc_attr($value('manufacturer')); ?>" required placeholder="Mercedes-Benz"></label>
                    <label><span><?php esc_html_e('Modell', 'brehl-intranet'); ?> *</span><input name="model" value="<?php echo esc_attr($value('model')); ?>" required placeholder="Vito"></label>
                    <label><span><?php esc_html_e('Fahrzeugtyp', 'brehl-intranet'); ?></span><select name="vehicle_type"><option value=""><?php esc_html_e('Bitte auswählen', 'brehl-intranet'); ?></option><?php foreach (array('pkw'=>'PKW','transporter'=>'Transporter','lkw'=>'LKW','anhaenger'=>'Anhänger','maschine'=>'Baumaschine','sonstiges'=>'Sonstiges') as $key=>$label) : ?><option value="<?php echo esc_attr($key); ?>" <?php selected($value('vehicle_type'),$key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></label>
                    <label><span><?php esc_html_e('Erstzulassung', 'brehl-intranet'); ?></span><input name="first_registration" type="date" value="<?php echo esc_attr($value('first_registration')); ?>"></label>
                    <label><span><?php esc_html_e('Aktueller Kilometerstand', 'brehl-intranet'); ?> *</span><input name="current_mileage" type="number" min="0" step="1" value="<?php echo esc_attr($item ? $value('current_mileage') : '0'); ?>" required></label>
                    <label><span><?php esc_html_e('Nächster TÜV', 'brehl-intranet'); ?></span><input name="next_inspection" type="date" value="<?php echo esc_attr($value('next_inspection')); ?>"></label>
                    <label class="is-wide"><span><?php esc_html_e('Fest zugeordneter Mitarbeiter', 'brehl-intranet'); ?></span><select name="assigned_user_id"><option value="0"><?php esc_html_e('Keine feste Zuordnung', 'brehl-intranet'); ?></option><?php foreach ($employees as $employee) : ?><option value="<?php echo esc_attr((string) $employee->ID); ?>" <?php selected((int)$value('assigned_user_id'),(int)$employee->ID); ?>><?php echo esc_html($employee->display_name); ?></option><?php endforeach; ?></select><small><?php esc_html_e('Das Kennzeichen wird automatisch in das Mitarbeiterprofil übernommen.', 'brehl-intranet'); ?></small></label>
                    <label class="is-wide"><span><?php esc_html_e('Fahrzeugstatus', 'brehl-intranet'); ?></span><select name="status"><?php foreach (array('active'=>'Aktiv','workshop'=>'In Werkstatt','inactive'=>'Außer Betrieb','sold'=>'Verkauft') as $key=>$label) : ?><option value="<?php echo esc_attr($key); ?>" <?php selected($item ? $value('status') : 'active',$key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></label>
                    <div class="is-wide brehl-hr-form__actions"><button type="submit"><?php echo $item ? esc_html__('Änderungen speichern', 'brehl-intranet') : esc_html__('Fahrzeug anlegen', 'brehl-intranet'); ?></button><?php if ($item) : ?><a class="brehl-hr-form__cancel" href="<?php echo esc_url(remove_query_arg('vehicle_id') . '#brehl-vehicle-form'); ?>"><?php esc_html_e('Abbrechen', 'brehl-intranet'); ?></a><?php endif; ?></div>
                </form>
            </div>
        </section>
        <?php return (string) ob_get_clean();
    }

    public function metrics_panel(): string {
        if (!$this->can_manage()) return '';
        wp_enqueue_style('brehl-intranet'); wp_enqueue_style('my-brehl-system'); wp_enqueue_style('brehl-intranet-vehicle-damage');
        global $wpdb;
        $vehicles=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->vehicle_table()} WHERE status='active'");
        $damages=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->table()} WHERE status IN ('neu','in_pruefung','beauftragt')");
        $service_table=$wpdb->prefix.'brehl_vehicle_service_requests';
        $services=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$service_table} WHERE status IN ('submitted','review','scheduled','workshop')");
        $workshop=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->vehicle_table()} WHERE status='workshop'");
        ob_start(); ?><section class="mbs-fleet-metrics"><div><span><?php esc_html_e('Aktive Fahrzeuge','brehl-intranet'); ?></span><strong><?php echo esc_html((string)$vehicles); ?></strong></div><div><span><?php esc_html_e('Offene Schäden','brehl-intranet'); ?></span><strong><?php echo esc_html((string)$damages); ?></strong></div><div><span><?php esc_html_e('Offene Services','brehl-intranet'); ?></span><strong><?php echo esc_html((string)$services); ?></strong></div><div><span><?php esc_html_e('In Werkstatt','brehl-intranet'); ?></span><strong><?php echo esc_html((string)$workshop); ?></strong></div></section><?php return (string)ob_get_clean();
    }

    public function management_panel(): string {
        if (!$this->can_manage()) return '';
        wp_enqueue_style('brehl-intranet'); wp_enqueue_style('my-brehl-system'); wp_enqueue_style('brehl-intranet-vehicle-damage');
        global $wpdb;
        $items=$wpdb->get_results("SELECT d.*,u.display_name FROM {$this->table()} d LEFT JOIN {$wpdb->users} u ON u.ID=d.user_id ORDER BY FIELD(d.status,'neu','in_pruefung','beauftragt','erledigt','abgelehnt'),d.created_at DESC LIMIT 100");
        $allowed=array('neu','in_pruefung','beauftragt','erledigt','abgelehnt');
        $updated='saved'===sanitize_key($_GET['vehicle_damage_management']??'');
        ob_start(); ?><section class="mbs-damage-management"><div class="mbs-card"><div class="mbs-card-head"><div><span class="mbs-kicker"><?php esc_html_e('Fuhrpark','brehl-intranet'); ?></span><h3><?php esc_html_e('Schadensmeldungen verwalten','brehl-intranet'); ?></h3></div><span class="mbs-count"><?php echo esc_html(sprintf(_n('%d Vorgang','%d Vorgänge',count($items),'brehl-intranet'),count($items))); ?></span></div><?php if($updated): ?><div class="mbs-form-message is-success"><?php esc_html_e('Die Schadenmeldung wurde aktualisiert.','brehl-intranet'); ?></div><?php endif; ?><div class="mbs-damage-management__list"><?php if(!$items): ?><p class="mbs-empty"><?php esc_html_e('Keine Schadenmeldungen vorhanden.','brehl-intranet'); ?></p><?php endif; ?><?php foreach($items as $item): $attachments=(array)json_decode((string)$item->attachment_ids,true); ?><article class="mbs-damage-case"><header><div><strong><?php echo esc_html($item->vehicle); ?> · <?php echo esc_html($item->license_plate); ?></strong><span><?php echo esc_html($item->display_name?:__('Unbekannter Mitarbeiter','brehl-intranet')); ?> · <?php echo esc_html(wp_date('d.m.Y',strtotime($item->incident_date))); ?></span></div><span class="mbs-status mbs-status-<?php echo esc_attr($item->status); ?>"><?php echo esc_html($this->status_label($item->status)); ?></span></header><div class="mbs-damage-case__content"><p><strong><?php echo esc_html($this->incident_type_label($item->incident_type)); ?></strong><?php echo $item->location?' · '.esc_html($item->location):''; ?></p><p><?php echo nl2br(esc_html($item->description)); ?></p><div class="mbs-damage-case__facts"><span><?php echo $item->drivable?esc_html__('Fahrbereit','brehl-intranet'):esc_html__('Nicht fahrbereit','brehl-intranet'); ?></span><span><?php echo $item->police_involved?esc_html__('Polizei verständigt','brehl-intranet'):esc_html__('Keine Polizei','brehl-intranet'); ?></span></div><?php if($item->third_party_involved): ?><div class="mbs-damage-case__opponent"><strong><?php esc_html_e('Unfallgegner','brehl-intranet'); ?></strong><p><?php echo esc_html($item->opponent_name?:'–'); ?> · <?php echo esc_html($item->opponent_license_plate?:'–'); ?><br><?php echo esc_html($item->opponent_address?:''); ?><?php echo $item->opponent_phone?' · '.esc_html($item->opponent_phone):''; ?><br><?php echo esc_html($item->opponent_insurer?:__('Keine Versicherung angegeben','brehl-intranet')); ?><?php echo $item->opponent_policy_number?' · '.esc_html($item->opponent_policy_number):''; ?></p></div><?php endif; ?><?php if($attachments): ?><div class="mbs-damage-case__photos"><?php foreach($attachments as $attachment_id): $thumb=wp_get_attachment_image_url((int)$attachment_id,'thumbnail'); $full=wp_get_attachment_url((int)$attachment_id); if(!$thumb||!$full)continue; ?><a href="<?php echo esc_url($full); ?>" target="_blank" rel="noopener"><img src="<?php echo esc_url($thumb); ?>" alt=""></a><?php endforeach; ?></div><?php endif; ?></div><form class="mbs-damage-management__form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="brehl_update_vehicle_damage"><input type="hidden" name="damage_id" value="<?php echo esc_attr((string)$item->id); ?>"><input type="hidden" name="return_frontend" value="1"><?php wp_nonce_field('brehl_update_vehicle_damage_'.$item->id); ?><label><span><?php esc_html_e('Status','brehl-intranet'); ?></span><select name="status"><?php foreach($allowed as $status): ?><option value="<?php echo esc_attr($status); ?>" <?php selected($item->status,$status); ?>><?php echo esc_html($this->status_label($status)); ?></option><?php endforeach; ?></select></label><label><span><?php esc_html_e('Rückmeldung','brehl-intranet'); ?></span><textarea name="admin_note" rows="3"><?php echo esc_textarea((string)$item->admin_note); ?></textarea></label><button class="mbs-primary-button" type="submit"><?php esc_html_e('Speichern','brehl-intranet'); ?></button></form></article><?php endforeach; ?></div></div></section><?php return (string)ob_get_clean();
    }

    public function handle_vehicle_save(): void {
        if (!$this->can_manage()) wp_die(esc_html__('Keine Berechtigung.', 'brehl-intranet'), 403);
        $id = absint($_POST['vehicle_id'] ?? 0);
        check_admin_referer('brehl_save_vehicle_' . $id);
        global $wpdb;
        $old = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->vehicle_table()} WHERE id=%d", $id)) : null;
        if ($id && !$old) $this->redirect_vehicle('error');
        $plate = mb_strtoupper(sanitize_text_field(wp_unslash($_POST['license_plate'] ?? '')));
        $manufacturer = sanitize_text_field(wp_unslash($_POST['manufacturer'] ?? ''));
        $model = sanitize_text_field(wp_unslash($_POST['model'] ?? ''));
        $mileage = max(0, absint($_POST['current_mileage'] ?? 0));
        $assigned = absint($_POST['assigned_user_id'] ?? 0);
        $user = $assigned ? get_userdata($assigned) : null;
        if (!$plate || !$manufacturer || !$model || ($assigned && (!$user || !in_array(Brehl_Roles::EMPLOYEE_ROLE, (array)$user->roles, true)))) $this->redirect_vehicle('error');
        $duplicate = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$this->vehicle_table()} WHERE license_plate=%s AND id<>%d", $plate, $id));
        if ($duplicate) $this->redirect_vehicle('error');
        $status = sanitize_key($_POST['status'] ?? 'active');
        if (!in_array($status,array('active','workshop','inactive','sold'),true)) $status='active';
        $data = array('license_plate'=>$plate,'manufacturer'=>$manufacturer,'model'=>$model,'vehicle_type'=>sanitize_key($_POST['vehicle_type'] ?? ''),'first_registration'=>$this->optional_date($_POST['first_registration'] ?? ''),'current_mileage'=>$mileage,'assigned_user_id'=>$assigned,'next_inspection'=>$this->optional_date($_POST['next_inspection'] ?? ''),'status'=>$status,'internal_number'=>sanitize_text_field(wp_unslash($_POST['internal_number'] ?? '')),'updated_at'=>current_time('mysql'));
        if ($old) $saved = $wpdb->update($this->vehicle_table(),$data,array('id'=>$id)); else { $data['created_at']=current_time('mysql'); $saved=$wpdb->insert($this->vehicle_table(),$data); $id=(int)$wpdb->insert_id; }
        if (false === $saved) $this->redirect_vehicle('error');
        if ($old && $old->assigned_user_id && (int)$old->assigned_user_id !== $assigned && get_user_meta((int)$old->assigned_user_id,'brehl_vehicle_license_plate',true)===$old->license_plate) delete_user_meta((int)$old->assigned_user_id,'brehl_vehicle_license_plate');
        if ($assigned) {
            $wpdb->query($wpdb->prepare("UPDATE {$this->vehicle_table()} SET assigned_user_id=0,updated_at=%s WHERE assigned_user_id=%d AND id<>%d",current_time('mysql'),$assigned,$id));
            update_user_meta($assigned,'brehl_vehicle_license_plate',$plate);
        }
        $this->redirect_vehicle('saved');
    }

    public function handle_archive_vehicle(): void {
        if(!$this->can_manage()) wp_die(esc_html__('Keine Berechtigung.','brehl-intranet'),403);
        $id=absint($_POST['vehicle_id']??0); check_admin_referer('brehl_archive_vehicle_'.$id);
        global $wpdb; $vehicle=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->vehicle_table()} WHERE id=%d",$id)); if(!$vehicle) $this->redirect_vehicle('error');
        $archive='1'===sanitize_text_field(wp_unslash($_POST['archive']??'1')); $now=current_time('mysql');
        if($archive){
            $wpdb->update($this->vehicle_table(),array('status'=>'archived','assigned_user_id'=>0,'updated_at'=>$now),array('id'=>$id));
            if($vehicle->assigned_user_id&&get_user_meta((int)$vehicle->assigned_user_id,'brehl_vehicle_license_plate',true)===$vehicle->license_plate) delete_user_meta((int)$vehicle->assigned_user_id,'brehl_vehicle_license_plate');
        } else $wpdb->update($this->vehicle_table(),array('status'=>'active','updated_at'=>$now),array('id'=>$id));
        $url=wp_get_referer()?:home_url('/'); $url=$archive?remove_query_arg(array('vehicle_id','vehicle_archive'),$url):add_query_arg('vehicle_archive','1',remove_query_arg('vehicle_id',$url)); wp_safe_redirect($url); exit;
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
        wp_enqueue_style('brehl-intranet-vehicle-damage');
        wp_enqueue_script('brehl-intranet-vehicle-damage');

        global $wpdb;
        $user_id = get_current_user_id();
        $fixed_license_plate = (string) get_user_meta($user_id, 'brehl_vehicle_license_plate', true);
        $fixed_vehicle = $fixed_license_plate ? $wpdb->get_row($wpdb->prepare("SELECT manufacturer,model FROM {$this->vehicle_table()} WHERE license_plate=%s", $fixed_license_plate)) : null;
        $fixed_vehicle_label = $fixed_vehicle ? trim($fixed_vehicle->manufacturer . ' ' . $fixed_vehicle->model) : '';
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
                        <label class="mbs-form-full">
                            <span><?php esc_html_e('Schadenart', 'brehl-intranet'); ?> *</span>
                            <select name="incident_type" required data-brehl-incident-type>
                                <option value=""><?php esc_html_e('Bitte auswählen', 'brehl-intranet'); ?></option>
                                <option value="single_vehicle"><?php esc_html_e('Alleinunfall / ohne Unfallgegner', 'brehl-intranet'); ?></option>
                                <option value="collision"><?php esc_html_e('Unfall mit anderem Fahrzeug', 'brehl-intranet'); ?></option>
                                <option value="unknown_third_party"><?php esc_html_e('Parkschaden / unbekannter Verursacher', 'brehl-intranet'); ?></option>
                                <option value="wildlife"><?php esc_html_e('Wildunfall', 'brehl-intranet'); ?></option>
                                <option value="other"><?php esc_html_e('Sonstiger Fahrzeugschaden', 'brehl-intranet'); ?></option>
                            </select>
                            <small><?php esc_html_e('Bitte keine Schuldfrage bewerten – nur den tatsächlichen Ablauf auswählen.', 'brehl-intranet'); ?></small>
                        </label>
                        <label>
                            <span><?php esc_html_e('Fahrzeug / Modell', 'brehl-intranet'); ?> *</span>
                            <input type="text" name="vehicle" value="<?php echo esc_attr($fixed_vehicle_label); ?>" required placeholder="z. B. Mercedes Vito">
                        </label>
                        <label>
                            <span><?php esc_html_e('Kennzeichen', 'brehl-intranet'); ?> *</span>
                            <input type="text" name="license_plate" value="<?php echo esc_attr($fixed_license_plate); ?>" required placeholder="FD-AB 123" autocomplete="off">
                            <?php if ($fixed_license_plate) : ?><small><?php esc_html_e('Aus Ihrem Mitarbeiterprofil übernommen. Bei einem anderen Fahrzeug bitte anpassen.', 'brehl-intranet'); ?></small><?php endif; ?>
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
                        <label><input type="checkbox" name="police_involved" value="1"> <span><?php esc_html_e('Polizei wurde verständigt', 'brehl-intranet'); ?></span></label>
                        <label><input type="checkbox" name="not_drivable" value="1"> <span><?php esc_html_e('Fahrzeug ist nicht mehr fahrbereit', 'brehl-intranet'); ?></span></label>
                    </div>

                    <fieldset class="mbs-opponent" data-brehl-third-party-fields hidden>
                        <legend><?php esc_html_e('Daten des Unfallgegners', 'brehl-intranet'); ?></legend>
                        <p><?php esc_html_e('Bitte erfassen Sie die verfügbaren Angaben. Name und Kennzeichen sind erforderlich.', 'brehl-intranet'); ?></p>
                        <div class="mbs-form-grid">
                            <label><span><?php esc_html_e('Name des Unfallgegners', 'brehl-intranet'); ?> *</span><input name="opponent_name" data-brehl-third-party-required></label>
                            <label><span><?php esc_html_e('Kennzeichen des Gegners', 'brehl-intranet'); ?> *</span><input name="opponent_license_plate" placeholder="z. B. FD-XY 456" data-brehl-third-party-required></label>
                            <label class="mbs-form-full"><span><?php esc_html_e('Anschrift', 'brehl-intranet'); ?></span><input name="opponent_address" placeholder="Straße, Hausnummer, PLZ und Ort"></label>
                            <label><span><?php esc_html_e('Telefonnummer', 'brehl-intranet'); ?></span><input name="opponent_phone" type="tel"></label>
                            <label><span><?php esc_html_e('Versicherung', 'brehl-intranet'); ?></span><input name="opponent_insurer" placeholder="Name der Versicherung"></label>
                            <label class="mbs-form-full"><span><?php esc_html_e('Versicherungs- oder Schadennummer', 'brehl-intranet'); ?></span><input name="opponent_policy_number"></label>
                        </div>
                    </fieldset>

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
        $license_plate = mb_strtoupper(sanitize_text_field(wp_unslash($_POST['license_plate'] ?? '')));
        $incident_date = sanitize_text_field(wp_unslash($_POST['incident_date'] ?? ''));
        $description = sanitize_textarea_field(wp_unslash($_POST['description'] ?? ''));
        $incident_type = sanitize_key($_POST['incident_type'] ?? '');
        $allowed_incident_types = array('single_vehicle', 'collision', 'unknown_third_party', 'wildlife', 'other');
        $third_party = 'collision' === $incident_type;
        $opponent_name = sanitize_text_field(wp_unslash($_POST['opponent_name'] ?? ''));
        $opponent_plate = mb_strtoupper(sanitize_text_field(wp_unslash($_POST['opponent_license_plate'] ?? '')));

        if (!in_array($incident_type, $allowed_incident_types, true) || '' === $vehicle || '' === $license_plate || '' === $incident_date || '' === $description || !$this->valid_date($incident_date) || ($third_party && ('' === $opponent_name || '' === $opponent_plate))) {
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
                'license_plate' => $license_plate,
                'incident_date' => $incident_date,
                'incident_time' => sanitize_text_field(wp_unslash($_POST['incident_time'] ?? '')) ?: null,
                'location' => sanitize_text_field(wp_unslash($_POST['location'] ?? '')),
                'description' => $description,
                'incident_type' => $incident_type,
                'third_party_involved' => $third_party ? 1 : 0,
                'opponent_name' => $third_party ? $opponent_name : '',
                'opponent_address' => $third_party ? sanitize_text_field(wp_unslash($_POST['opponent_address'] ?? '')) : '',
                'opponent_phone' => $third_party ? sanitize_text_field(wp_unslash($_POST['opponent_phone'] ?? '')) : '',
                'opponent_license_plate' => $third_party ? $opponent_plate : '',
                'opponent_insurer' => $third_party ? sanitize_text_field(wp_unslash($_POST['opponent_insurer'] ?? '')) : '',
                'opponent_policy_number' => $third_party ? sanitize_text_field(wp_unslash($_POST['opponent_policy_number'] ?? '')) : '',
                'police_involved' => isset($_POST['police_involved']) ? 1 : 0,
                'drivable' => isset($_POST['not_drivable']) ? 0 : 1,
                'attachment_ids' => wp_json_encode($attachments),
                'status' => 'neu',
                'created_at' => $now,
                'updated_at' => $now,
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s')
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

        if(isset($_POST['return_frontend'])) {
            $url=wp_get_referer()?:home_url('/');
            wp_safe_redirect(add_query_arg('vehicle_damage_management','saved',$url));
        } else {
            wp_safe_redirect(admin_url('admin.php?page=my-brehl-vehicle-damages&updated=1'));
        }
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
                            <strong>Schadenart:</strong> <?php echo esc_html($this->incident_type_label($item->incident_type ?? 'single_vehicle')); ?><br><strong>Fahrbereit:</strong> <?php echo $item->drivable ? 'Ja' : 'Nein'; ?> · <strong>Polizei:</strong> <?php echo $item->police_involved ? 'Ja' : 'Nein'; ?> · <strong>Dritte beteiligt:</strong> <?php echo $item->third_party_involved ? 'Ja' : 'Nein'; ?></p>
                        </div>
                        <span style="padding:7px 12px;border-radius:999px;background:#f1f1f1;font-weight:600"><?php echo esc_html($this->status_label($item->status)); ?></span>
                    </div>
                    <p><strong>Beschreibung:</strong><br><?php echo nl2br(esc_html($item->description)); ?></p>
                    <?php if ($item->third_party_involved) : ?><div style="padding:14px;border-radius:10px;background:#f7f8fb"><strong>Unfallgegner</strong><p><?php echo esc_html($item->opponent_name ?: '–'); ?> · <?php echo esc_html($item->opponent_license_plate ?: '–'); ?><br><?php echo esc_html($item->opponent_address ?: 'Keine Anschrift'); ?><?php echo $item->opponent_phone ? ' · ' . esc_html($item->opponent_phone) : ''; ?><br>Versicherung: <?php echo esc_html($item->opponent_insurer ?: '–'); ?><?php echo $item->opponent_policy_number ? ' · ' . esc_html($item->opponent_policy_number) : ''; ?></p></div><?php endif; ?>

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

    private function vehicle_status_label(string $status): string {
        return array(
            'active' => __('Aktiv', 'brehl-intranet'),
            'workshop' => __('In Werkstatt', 'brehl-intranet'),
            'inactive' => __('Außer Betrieb', 'brehl-intranet'),
            'sold' => __('Verkauft', 'brehl-intranet'),
            'archived' => __('Archiviert', 'brehl-intranet'),
        )[$status] ?? __('Aktiv', 'brehl-intranet');
    }

    private function incident_type_label(string $type): string {
        return array(
            'single_vehicle' => __('Alleinunfall / ohne Unfallgegner', 'brehl-intranet'),
            'collision' => __('Unfall mit anderem Fahrzeug', 'brehl-intranet'),
            'unknown_third_party' => __('Parkschaden / unbekannter Verursacher', 'brehl-intranet'),
            'wildlife' => __('Wildunfall', 'brehl-intranet'),
            'other' => __('Sonstiger Fahrzeugschaden', 'brehl-intranet'),
        )[$type] ?? __('Sonstiger Fahrzeugschaden', 'brehl-intranet');
    }

    private function optional_date($date): ?string {
        $date = sanitize_text_field(wp_unslash((string) $date));
        if ('' === $date) return null;
        $parsed = DateTime::createFromFormat('Y-m-d', $date);
        return $parsed && $parsed->format('Y-m-d') === $date ? $date : null;
    }

    private function redirect_vehicle(string $result): void {
        $url = wp_get_referer() ?: home_url('/dashboard/');
        wp_safe_redirect(add_query_arg('vehicle_result', $result, remove_query_arg('vehicle_id', $url)));
        exit;
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
                    'link_url' => home_url('/fuhrparkverwaltung/'),
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
                'link_url' => home_url('/fuhrpark/'),
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
