<?php
defined('ABSPATH') || exit;

final class Brehl_Vehicle_Service_Module {
    private static ?self $instance = null;
    private const DB_VERSION = '1.1';

    public static function instance(): self { return self::$instance ??= new self(); }

    private function __construct() {
        add_action('init', array($this, 'maybe_install'));
        add_action('admin_post_brehl_submit_vehicle_service', array($this, 'handle_submission'));
        add_action('admin_post_brehl_manage_vehicle_service', array($this, 'handle_management'));
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
            completed_mileage BIGINT UNSIGNED NOT NULL DEFAULT 0,
            handled_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
            completed_at DATETIME NULL,
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
        $groups = $this->service_type_groups();
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
                    <?php echo $this->date_field('desired_date', __('Gewünschter Zeitraum', 'brehl-intranet')); ?>
                    <label class="mbs-form-full"><span><?php esc_html_e('Tachofoto (optional)', 'brehl-intranet'); ?></span><input name="odometer_photo" type="file" accept="image/jpeg,image/png,image/webp"><small><?php esc_html_e('JPG, PNG oder WebP, maximal 8 MB.', 'brehl-intranet'); ?></small></label>
                </div>
                <fieldset class="mbs-service-types"><legend><?php esc_html_e('Benötigte Arbeiten', 'brehl-intranet'); ?> *</legend><p><?php esc_html_e('Bereich auswählen und gewünschte Arbeiten markieren.', 'brehl-intranet'); ?></p><div class="mbs-service-groups" data-brehl-service-groups><?php foreach ($groups as $group) : ?><details><summary><span class="mbs-service-group-name"><?php echo esc_html($group['label']); ?></span><span class="mbs-service-group-count" aria-live="polite"></span><b aria-hidden="true">⌄</b></summary><div><?php foreach ($group['types'] as $key) : ?><label><input type="checkbox" name="service_types[]" value="<?php echo esc_attr($key); ?>"> <span><?php echo esc_html($types[$key]); ?></span></label><?php endforeach; ?></div></details><?php endforeach; ?></div></fieldset>
                <div class="mbs-form-grid">
                    <label class="mbs-form-full"><span><?php esc_html_e('Dringlichkeit', 'brehl-intranet'); ?></span><select name="urgency"><option value="normal">Normal</option><option value="soon">Zeitnah</option><option value="urgent">Dringend</option></select></label>
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

    public function management_panel(): string {
        if (!$this->can_manage()) return '';
        wp_enqueue_style('brehl-intranet'); wp_enqueue_style('my-brehl-system'); wp_enqueue_style('brehl-intranet-vehicle-damage');
        global $wpdb;
        $items = $wpdb->get_results("SELECT r.*,u.display_name FROM {$this->table()} r LEFT JOIN {$wpdb->users} u ON u.ID=r.user_id ORDER BY FIELD(r.status,'submitted','review','scheduled','workshop','completed','rejected'),r.created_at DESC LIMIT 100");
        $result = sanitize_key($_GET['vehicle_service_management'] ?? '');
        ob_start(); ?>
        <section class="mbs-service-management"><div class="mbs-card"><div class="mbs-card-head"><div><span class="mbs-kicker"><?php esc_html_e('Fuhrpark', 'brehl-intranet'); ?></span><h3><?php esc_html_e('Serviceanfragen verwalten', 'brehl-intranet'); ?></h3></div><span class="mbs-count"><?php echo esc_html(sprintf(_n('%d Vorgang','%d Vorgänge',count($items),'brehl-intranet'),count($items))); ?></span></div>
        <?php if ('saved' === $result) : ?><div class="mbs-form-message is-success"><?php esc_html_e('Die Serviceanfrage wurde aktualisiert.', 'brehl-intranet'); ?></div><?php endif; ?>
        <div class="mbs-service-management__list"><?php if (!$items) : ?><p class="mbs-empty"><?php esc_html_e('Derzeit liegen keine Serviceanfragen vor.', 'brehl-intranet'); ?></p><?php endif; ?>
        <?php foreach ($items as $item) : $selected=(array)json_decode($item->service_types,true); ?><article class="mbs-service-case"><header><div><strong><?php echo esc_html($item->license_plate); ?></strong><span><?php echo esc_html($item->display_name ?: __('Unbekannter Mitarbeiter','brehl-intranet')); ?> · <?php echo esc_html(number_format_i18n((int)$item->current_mileage)); ?> km</span></div><span class="mbs-status"><?php echo esc_html($this->status_label($item->status)); ?></span></header><p class="mbs-service-case__types"><?php echo esc_html(implode(' · ',array_map(fn($type)=>$this->service_types()[$type]??$type,$selected))); ?></p><p><?php echo esc_html($item->description); ?></p>
        <form class="mbs-service-management__form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="brehl_manage_vehicle_service"><input type="hidden" name="request_id" value="<?php echo esc_attr((string)$item->id); ?>"><?php wp_nonce_field('brehl_manage_vehicle_service_'.$item->id); ?><div class="mbs-form-grid">
        <label><span><?php esc_html_e('Status','brehl-intranet'); ?></span><select name="status"><?php foreach ($this->statuses() as $key=>$label) : ?><option value="<?php echo esc_attr($key); ?>" <?php selected($item->status,$key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></label>
        <label><span><?php esc_html_e('Werkstatt','brehl-intranet'); ?></span><input name="workshop" value="<?php echo esc_attr((string)$item->workshop); ?>" placeholder="Name oder Standort"></label>
        <?php echo $this->date_field('appointment_date',__('Werkstatttermin','brehl-intranet'),$item->appointment_at ? wp_date('Y-m-d',strtotime($item->appointment_at)) : ''); ?>
        <label><span><?php esc_html_e('Uhrzeit','brehl-intranet'); ?></span><input name="appointment_time" type="time" value="<?php echo esc_attr($item->appointment_at ? wp_date('H:i',strtotime($item->appointment_at)) : ''); ?>"></label>
        <label><span><?php esc_html_e('Kilometerstand bei Abschluss','brehl-intranet'); ?></span><input name="completed_mileage" type="number" min="0" value="<?php echo esc_attr((string)($item->completed_mileage ?: '')); ?>"></label>
        <label class="mbs-form-full"><span><?php esc_html_e('Rückmeldung an den Mitarbeiter','brehl-intranet'); ?></span><textarea name="admin_note" rows="3" placeholder="Termin, Rückfragen oder Abschlussinformation"><?php echo esc_textarea((string)$item->admin_note); ?></textarea></label></div><button class="mbs-primary-button" type="submit"><?php esc_html_e('Änderungen speichern','brehl-intranet'); ?></button></form></article><?php endforeach; ?></div></div></section>
        <?php return (string)ob_get_clean();
    }

    public function history_panel(): string {
        if (!$this->can_manage()) return '';
        wp_enqueue_style('brehl-intranet'); wp_enqueue_style('my-brehl-system'); wp_enqueue_style('brehl-intranet-vehicle-damage');
        global $wpdb;
        $items=$wpdb->get_results("SELECT r.*,u.display_name,h.display_name handled_name FROM {$this->table()} r LEFT JOIN {$wpdb->users} u ON u.ID=r.user_id LEFT JOIN {$wpdb->users} h ON h.ID=r.handled_by WHERE r.status='completed' ORDER BY COALESCE(r.completed_at,r.updated_at) DESC LIMIT 100");
        ob_start(); ?><section class="mbs-service-history"><div class="mbs-card"><div class="mbs-card-head"><div><span class="mbs-kicker"><?php esc_html_e('Historie','brehl-intranet'); ?></span><h3><?php esc_html_e('Fahrzeughistorie','brehl-intranet'); ?></h3></div></div><div class="mbs-service-history__list"><?php if(!$items): ?><p class="mbs-empty"><?php esc_html_e('Noch keine abgeschlossenen Servicevorgänge.','brehl-intranet'); ?></p><?php endif; ?><?php foreach($items as $item): $selected=(array)json_decode($item->service_types,true); ?><article><div class="mbs-service-history__date"><strong><?php echo esc_html(wp_date('d.m.Y',strtotime($item->completed_at ?: $item->updated_at))); ?></strong><span><?php echo esc_html($item->license_plate); ?></span></div><div><strong><?php echo esc_html(implode(', ',array_map(fn($type)=>$this->service_types()[$type]??$type,$selected))); ?></strong><p><?php echo esc_html($item->workshop ?: __('Keine Werkstatt angegeben','brehl-intranet')); ?> · <?php echo esc_html(number_format_i18n((int)($item->completed_mileage ?: $item->current_mileage))); ?> km</p><small><?php echo esc_html(($item->display_name ?: __('Unbekannter Mitarbeiter','brehl-intranet')).($item->handled_name?' · bearbeitet von '.$item->handled_name:'')); ?></small></div></article><?php endforeach; ?></div></div></section><?php return (string)ob_get_clean();
    }

    public function vehicle_detail_panel(): string {
        if (!$this->can_manage()) return '';
        wp_enqueue_style('brehl-intranet'); wp_enqueue_style('my-brehl-system'); wp_enqueue_style('brehl-intranet-vehicle-damage');
        global $wpdb;
        $vehicles=$wpdb->get_results("SELECT v.*,u.display_name FROM {$this->vehicles_table()} v LEFT JOIN {$wpdb->users} u ON u.ID=v.assigned_user_id WHERE v.status<>'archived' ORDER BY v.license_plate");
        $selected_id=absint($_GET['fleet_vehicle']??0); if(!$selected_id&&$vehicles)$selected_id=(int)$vehicles[0]->id;
        $vehicle=null; foreach($vehicles as $candidate)if((int)$candidate->id===$selected_id){$vehicle=$candidate;break;}
        $services=array(); $damages=array(); $latest_service=null; $latest_tires=null; $latest_brakes=null;
        if($vehicle){
            $services=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table()} WHERE vehicle_id=%d OR license_plate=%s ORDER BY COALESCE(completed_at,updated_at) DESC LIMIT 50",$vehicle->id,$vehicle->license_plate));
            $damage_table=$wpdb->prefix.'brehl_vehicle_damages';
            $damages=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$damage_table} WHERE license_plate=%s ORDER BY incident_date DESC LIMIT 20",$vehicle->license_plate));
            foreach($services as $service){ if('completed'!==$service->status)continue; $types=(array)json_decode($service->service_types,true); if(!$latest_service)$latest_service=$service; if(!$latest_tires&&array_intersect($types,array('tires_front','tires_rear','tires_all')))$latest_tires=$service; if(!$latest_brakes&&array_intersect($types,array('pads_front','pads_rear','discs_front','discs_rear','brakes')))$latest_brakes=$service; }
        }
        $open_services=count(array_filter($services,fn($item)=>in_array($item->status,array('submitted','review','scheduled','workshop'),true)));
        $open_damages=count(array_filter($damages,fn($item)=>in_array($item->status,array('neu','in_pruefung','beauftragt'),true)));
        ob_start(); ?><section class="mbs-vehicle-detail"><div class="mbs-card"><div class="mbs-card-head"><div><span class="mbs-kicker"><?php esc_html_e('Fuhrpark','brehl-intranet'); ?></span><h3><?php esc_html_e('Fahrzeugdetails','brehl-intranet'); ?></h3></div><form method="get"><label><span class="screen-reader-text"><?php esc_html_e('Fahrzeug auswählen','brehl-intranet'); ?></span><select name="fleet_vehicle" onchange="this.form.submit()"><?php foreach($vehicles as $option): ?><option value="<?php echo esc_attr((string)$option->id); ?>" <?php selected($selected_id,(int)$option->id); ?>><?php echo esc_html($option->license_plate.' · '.trim($option->manufacturer.' '.$option->model)); ?></option><?php endforeach; ?></select></label></form></div><?php if(!$vehicle): ?><p class="mbs-empty"><?php esc_html_e('Noch kein Fahrzeug angelegt.','brehl-intranet'); ?></p><?php else: ?><div class="mbs-vehicle-detail__hero"><div><strong><?php echo esc_html($vehicle->license_plate); ?></strong><span><?php echo esc_html(trim($vehicle->manufacturer.' '.$vehicle->model)); ?></span></div><span class="brehl-vehicle-row__status brehl-vehicle-row__status--<?php echo esc_attr($vehicle->status); ?>"><?php echo esc_html($this->vehicle_status_label($vehicle->status)); ?></span></div><div class="mbs-vehicle-detail__facts"><div><span><?php esc_html_e('Mitarbeiter','brehl-intranet'); ?></span><strong><?php echo esc_html($vehicle->display_name?:__('Nicht zugeordnet','brehl-intranet')); ?></strong></div><div><span><?php esc_html_e('Kilometerstand','brehl-intranet'); ?></span><strong><?php echo esc_html(number_format_i18n((int)$vehicle->current_mileage).' km'); ?></strong></div><div><span><?php esc_html_e('Nächster TÜV','brehl-intranet'); ?></span><strong><?php echo esc_html($vehicle->next_inspection?wp_date('d.m.Y',strtotime($vehicle->next_inspection)):'–'); ?></strong></div><div><span><?php esc_html_e('Offene Vorgänge','brehl-intranet'); ?></span><strong><?php echo esc_html($open_services.' Service · '.$open_damages.' Schäden'); ?></strong></div></div><div class="mbs-vehicle-detail__last"><div><span><?php esc_html_e('Letzter Service','brehl-intranet'); ?></span><?php echo $this->last_event($latest_service); ?></div><div><span><?php esc_html_e('Letzter Reifenwechsel','brehl-intranet'); ?></span><?php echo $this->last_event($latest_tires); ?></div><div><span><?php esc_html_e('Letzte Bremsenarbeit','brehl-intranet'); ?></span><?php echo $this->last_event($latest_brakes); ?></div></div><div class="mbs-vehicle-detail__timeline"><h4><?php esc_html_e('Letzte Vorgänge','brehl-intranet'); ?></h4><?php if(!$services&&!$damages): ?><p class="mbs-empty"><?php esc_html_e('Für dieses Fahrzeug sind noch keine Vorgänge vorhanden.','brehl-intranet'); ?></p><?php endif; ?><?php foreach(array_slice($services,0,8) as $service): ?><article><span><?php echo esc_html(wp_date('d.m.Y',strtotime($service->completed_at?:$service->created_at))); ?></span><div><strong><?php echo esc_html(implode(', ',array_map(fn($type)=>$this->service_types()[$type]??$type,(array)json_decode($service->service_types,true)))); ?></strong><small><?php echo esc_html($this->status_label($service->status)); ?></small></div></article><?php endforeach; ?><?php foreach(array_slice($damages,0,5) as $damage): ?><article><span><?php echo esc_html(wp_date('d.m.Y',strtotime($damage->incident_date))); ?></span><div><strong><?php esc_html_e('Schadenmeldung','brehl-intranet'); ?> · <?php echo esc_html(wp_trim_words($damage->description,10)); ?></strong><small><?php echo esc_html($damage->status); ?></small></div></article><?php endforeach; ?></div><?php endif; ?></div></section><?php return (string)ob_get_clean();
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

    public function handle_management(): void {
        if (!$this->can_manage()) wp_die(esc_html__('Keine Berechtigung.','brehl-intranet'),403);
        $id=absint($_POST['request_id']??0); check_admin_referer('brehl_manage_vehicle_service_'.$id);
        global $wpdb; $item=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table()} WHERE id=%d",$id)); if(!$item) wp_die(esc_html__('Vorgang nicht gefunden.','brehl-intranet'),404);
        $status=sanitize_key($_POST['status']??'submitted'); if(!isset($this->statuses()[$status])) $status='submitted';
        $appointment=$this->optional_datetime($_POST['appointment_date']??'',$_POST['appointment_time']??''); $mileage=absint($_POST['completed_mileage']??0); $now=current_time('mysql');
        $data=array('status'=>$status,'workshop'=>sanitize_text_field(wp_unslash($_POST['workshop']??'')),'appointment_at'=>$appointment,'completed_mileage'=>$mileage,'admin_note'=>sanitize_textarea_field(wp_unslash($_POST['admin_note']??'')),'handled_by'=>get_current_user_id(),'updated_at'=>$now,'completed_at'=>'completed'===$status?($item->completed_at?:$now):null);
        $wpdb->update($this->table(),$data,array('id'=>$id));
        if($mileage) $wpdb->query($wpdb->prepare("UPDATE {$this->vehicles_table()} SET current_mileage=GREATEST(current_mileage,%d),updated_at=%s WHERE license_plate=%s",$mileage,$now,$item->license_plate));
        $this->notify_employee((int)$item->user_id,$item->license_plate,$status); $url=wp_get_referer()?:home_url('/'); wp_safe_redirect(add_query_arg('vehicle_service_management','saved',$url)); exit;
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
    private function optional_datetime($date,$time): ?string { $date=sanitize_text_field(wp_unslash((string)$date)); $time=sanitize_text_field(wp_unslash((string)$time)); if(!$date)return null; if(!$time)$time='00:00'; $parsed=DateTime::createFromFormat('Y-m-d H:i',$date.' '.$time); return $parsed?$parsed->format('Y-m-d H:i:s'):null; }
    private function redirect(string $result): void { $url=wp_get_referer()?:home_url('/dashboard/'); wp_safe_redirect(add_query_arg('vehicle_service',$result,$url)); exit; }
    private function notify_managers(string $plate): void { global $wpdb; foreach (get_users(array('role__in'=>array('administrator','personalverwaltung'),'fields'=>'ID')) as $uid) $wpdb->insert($wpdb->prefix.'my_brehl_notifications',array('user_id'=>(int)$uid,'title'=>'Neue Serviceanfrage','message'=>'Für '.$plate.' wurde eine Serviceanfrage gestellt.','type'=>'info','link_url'=>home_url('/fuhrparkverwaltung/'),'is_read'=>0,'created_at'=>current_time('mysql'))); }
    private function notify_employee(int $uid,string $plate,string $status): void { global $wpdb; $wpdb->insert($wpdb->prefix.'my_brehl_notifications',array('user_id'=>$uid,'title'=>'Serviceanfrage aktualisiert','message'=>'Der Status für '.$plate.' lautet: '.$this->status_label($status).'.','type'=>'info','link_url'=>home_url('/fuhrpark/'),'is_read'=>0,'created_at'=>current_time('mysql'))); }
    private function can_manage(): bool { return is_user_logged_in()&&(current_user_can('my_brehl_manage_vehicle_damage')||current_user_can('my_brehl_manage_system')); }
    private function statuses(): array { return array('submitted'=>'Eingereicht','review'=>'In Prüfung','scheduled'=>'Termin vereinbart','workshop'=>'In Werkstatt','completed'=>'Abgeschlossen','rejected'=>'Abgelehnt'); }
    private function status_label(string $status): string { return $this->statuses()[$status]??'Eingereicht'; }
    private function last_event($item): string { if(!$item)return '<strong>–</strong><small>Noch kein Eintrag</small>'; $date=$item->completed_at?:$item->updated_at; $mileage=(int)($item->completed_mileage?:$item->current_mileage); return '<strong>'.esc_html(wp_date('d.m.Y',strtotime($date))).'</strong><small>'.esc_html(number_format_i18n($mileage).' km'.($item->workshop?' · '.$item->workshop:'')).'</small>'; }
    private function vehicle_status_label(string $status): string { return array('active'=>'Aktiv','workshop'=>'In Werkstatt','inactive'=>'Außer Betrieb','sold'=>'Verkauft')[$status]??'Aktiv'; }
    private function date_field(string $name,string $label,string $value=''): string { $min=wp_date('Y-m-d'); $display=$value?wp_date('d.m.Y',strtotime($value)):''; ob_start(); ?><label class="mbs-date-field"><span><?php echo esc_html($label); ?></span><span class="mbs-date-picker" data-min="<?php echo esc_attr($min); ?>"><input class="mbs-date-picker__display" type="text" readonly placeholder="TT.MM.JJJJ" aria-label="<?php echo esc_attr($label); ?>" value="<?php echo esc_attr($display); ?>"><input class="mbs-date-picker__value" type="hidden" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>"><button class="mbs-date-picker__button" type="button" aria-label="<?php echo esc_attr(sprintf(__('%s im Kalender auswählen','brehl-intranet'),$label)); ?>">▦</button><span class="mbs-calendar" hidden></span></span></label><?php return (string)ob_get_clean(); }
    private function service_types(): array { return array('inspection'=>'Inspektion','oil'=>'Ölwechsel','tuv'=>'TÜV / Hauptuntersuchung','tires_front'=>'Reifen vorne','tires_rear'=>'Reifen hinten','tires_all'=>'Reifen komplett','pads_front'=>'Bremsbeläge vorne','pads_rear'=>'Bremsbeläge hinten','discs_front'=>'Bremsscheiben vorne','discs_rear'=>'Bremsscheiben hinten','brakes'=>'Bremsanlage allgemein','lighting'=>'Beleuchtung','glass'=>'Scheiben / Spiegel','body'=>'Karosserie','engine'=>'Motor / Antrieb','electrical'=>'Elektrik / Warnleuchte','climate'=>'Klimaanlage / Heizung','other'=>'Sonstiges'); }
    private function service_type_groups(): array { return array(
        array('label'=>'Wartung & Prüfung','types'=>array('inspection','oil','tuv')),
        array('label'=>'Reifen','types'=>array('tires_front','tires_rear','tires_all')),
        array('label'=>'Bremsen','types'=>array('pads_front','pads_rear','discs_front','discs_rear','brakes')),
        array('label'=>'Elektrik & Beleuchtung','types'=>array('lighting','electrical')),
        array('label'=>'Karosserie & Technik','types'=>array('glass','body','engine','climate')),
        array('label'=>'Sonstiges','types'=>array('other')),
    ); }
}
