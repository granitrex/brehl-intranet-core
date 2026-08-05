<?php

defined('ABSPATH') || exit;

final class Brehl_Sick_Leave_Module {
    private static ?self $instance = null;
    private const DB_VERSION = '1.0';
    private const MAX_FILE_SIZE = 3145728;

    public static function instance(): self {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'maybe_install'));
        add_action('init', array($this, 'register_shortcodes'));
        add_action('admin_post_brehl_submit_sick_leave', array($this, 'handle_submission'));
        add_action('admin_post_brehl_update_sick_leave', array($this, 'handle_status_update'));
        add_action('admin_post_brehl_download_sick_certificate', array($this, 'handle_download'));
    }

    public static function install(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = $wpdb->prefix . 'brehl_sick_leave';
        $charset = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            employee_note TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'gemeldet',
            hr_note TEXT NULL,
            certificate_name VARCHAR(255) NULL,
            certificate_mime VARCHAR(80) NULL,
            certificate_data LONGBLOB NULL,
            reviewed_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
            reviewed_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY start_date (start_date)
        ) {$charset};");
        update_option('brehl_sick_leave_db_version', self::DB_VERSION);
    }

    public function maybe_install(): void {
        if (self::DB_VERSION !== get_option('brehl_sick_leave_db_version')) self::install();
    }

    public function register_shortcodes(): void {
        add_shortcode('my_brehl_krank_uebersicht', array($this, 'overview_shortcode'));
        add_shortcode('my_brehl_krank_melden', array($this, 'request_shortcode'));
        add_shortcode('my_brehl_krank_status', array($this, 'status_shortcode'));
    }

    private function table(): string { global $wpdb; return $wpdb->prefix . 'brehl_sick_leave'; }
    private function can_manage(): bool { return current_user_can('my_brehl_manage_sick_leave') || current_user_can('manage_options'); }
    private function enqueue(): void { wp_enqueue_style('brehl-intranet'); wp_enqueue_style('my-brehl-system'); wp_enqueue_script('brehl-intranet-vacation'); }

    public function overview_shortcode(): string {
        if (!is_user_logged_in()) return '';
        $this->enqueue();
        global $wpdb;
        $uid = get_current_user_id();
        $year = (int) wp_date('Y');
        $reports = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table()} WHERE user_id=%d AND YEAR(start_date)=%d", $uid, $year));
        $days = (int) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(DATEDIFF(end_date,start_date)+1),0) FROM {$this->table()} WHERE user_id=%d AND YEAR(start_date)=%d", $uid, $year));
        $open = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table()} WHERE user_id=%d AND status='gemeldet'", $uid));
        ob_start(); ?>
        <section class="mbs-sick-kpis"><div class="mbs-kpi"><span><?php echo esc_html(sprintf(__('Krankmeldungen %d', 'brehl-intranet'), $year)); ?></span><strong><?php echo esc_html((string) $reports); ?></strong><small><?php esc_html_e('Meldungen', 'brehl-intranet'); ?></small></div><div class="mbs-kpi"><span><?php esc_html_e('Gemeldeter Zeitraum', 'brehl-intranet'); ?></span><strong><?php echo esc_html((string) $days); ?></strong><small><?php esc_html_e('Kalendertage', 'brehl-intranet'); ?></small></div><div class="mbs-kpi"><span><?php esc_html_e('Offen', 'brehl-intranet'); ?></span><strong><?php echo esc_html((string) $open); ?></strong><small><?php esc_html_e('Noch nicht bestätigt', 'brehl-intranet'); ?></small></div></section>
        <?php return (string) ob_get_clean();
    }

    public function request_shortcode(): string {
        if (!is_user_logged_in()) return '';
        $this->enqueue();
        $result = sanitize_key($_GET['sick_leave'] ?? '');
        ob_start(); ?>
        <section class="mbs-sick-form mbs-vacation-form mbs-card">
            <div class="mbs-card-head"><div><span class="mbs-kicker"><?php esc_html_e('PERSONAL', 'brehl-intranet'); ?></span><h3><?php esc_html_e('Krankmeldung einreichen', 'brehl-intranet'); ?></h3></div></div>
            <?php if ('saved' === $result) : ?><div class="mbs-form-message is-success"><?php esc_html_e('Ihre Krankmeldung wurde sicher übermittelt.', 'brehl-intranet'); ?></div><?php endif; ?>
            <?php if (in_array($result, array('error','file_error'), true)) : ?><div class="mbs-form-message is-error"><?php echo esc_html('file_error' === $result ? __('Die Bescheinigung muss PDF, JPG oder PNG und höchstens 3 MB groß sein.', 'brehl-intranet') : __('Die Krankmeldung konnte nicht gespeichert werden. Bitte prüfen Sie die Angaben.', 'brehl-intranet')); ?></div><?php endif; ?>
            <form class="mbs-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="brehl_submit_sick_leave"><?php wp_nonce_field('brehl_submit_sick_leave', 'brehl_sick_nonce'); ?>
                <div class="mbs-form-grid"><?php echo $this->date_field('start_date', __('Erster Krankheitstag', 'brehl-intranet')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $this->date_field('end_date', __('Voraussichtlich bis', 'brehl-intranet')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><label class="mbs-form-full"><span><?php esc_html_e('Bemerkung', 'brehl-intranet'); ?></span><textarea name="employee_note" rows="3" placeholder="<?php echo esc_attr__('Optional, z. B. Erreichbarkeit oder organisatorischer Hinweis', 'brehl-intranet'); ?>"></textarea></label><label class="mbs-form-full mbs-file-field"><span><?php esc_html_e('Bescheinigung (optional)', 'brehl-intranet'); ?></span><input type="file" name="certificate" accept="application/pdf,image/jpeg,image/png"><small><?php esc_html_e('PDF, JPG oder PNG · maximal 3 MB · nur für Sie und die Personalverwaltung sichtbar', 'brehl-intranet'); ?></small></label></div>
                <p class="mbs-form-hint"><?php esc_html_e('Bitte tragen Sie keine Diagnose oder andere medizinische Einzelheiten ein.', 'brehl-intranet'); ?></p><button type="submit" class="mbs-primary-button"><?php esc_html_e('Krankmeldung absenden', 'brehl-intranet'); ?></button>
            </form>
        </section>
        <?php return (string) ob_get_clean();
    }

    public function status_shortcode(): string {
        if (!is_user_logged_in()) return '';
        $this->enqueue();
        global $wpdb;
        $items = $wpdb->get_results($wpdb->prepare("SELECT id,start_date,end_date,status,hr_note,certificate_name,created_at FROM {$this->table()} WHERE user_id=%d ORDER BY created_at DESC LIMIT 30", get_current_user_id()));
        ob_start(); ?>
        <section class="mbs-sick-status mbs-vacation-status mbs-card"><div class="mbs-card-head"><h3><?php esc_html_e('Meine Krankmeldungen', 'brehl-intranet'); ?></h3></div><div class="mbs-list">
            <?php if (!$items) : ?><p class="mbs-empty"><?php esc_html_e('Sie haben noch keine Krankmeldung eingereicht.', 'brehl-intranet'); ?></p><?php endif; ?>
            <?php foreach ($items as $item) : ?><article class="mbs-vacation-item"><div><strong><?php echo esc_html($this->period_label($item)); ?></strong><p><?php echo esc_html(wp_date('d.m.Y', strtotime($item->created_at))); ?><?php if ($item->certificate_name) : ?> · <a href="<?php echo esc_url($this->download_url((int) $item->id)); ?>"><?php esc_html_e('Bescheinigung öffnen', 'brehl-intranet'); ?></a><?php endif; ?></p><?php if ($item->hr_note) : ?><small><?php esc_html_e('Rückmeldung:', 'brehl-intranet'); ?> <?php echo esc_html($item->hr_note); ?></small><?php endif; ?></div><span class="mbs-status mbs-status-<?php echo esc_attr($item->status); ?>"><?php echo esc_html($this->status_label($item->status)); ?></span></article><?php endforeach; ?>
        </div></section>
        <?php return (string) ob_get_clean();
    }

    public function management_panel(): string {
        if (!$this->can_manage()) return '';
        global $wpdb;
        $items = $wpdb->get_results("SELECT id,user_id,start_date,end_date,employee_note,status,hr_note,certificate_name,created_at FROM {$this->table()} ORDER BY FIELD(status,'gemeldet','bestaetigt'), created_at DESC LIMIT 100");
        $result = sanitize_key($_GET['sick_management'] ?? '');
        ob_start(); ?>
        <section class="brehl-hr-requests brehl-sick-management"><div class="brehl-hr__panel-head"><div><span class="brehl-hr-requests__kicker"><?php esc_html_e('Abwesenheiten', 'brehl-intranet'); ?></span><h3><?php esc_html_e('Krankmeldungen', 'brehl-intranet'); ?></h3></div><strong><?php echo esc_html(sprintf(__('%d Vorgänge', 'brehl-intranet'), count($items))); ?></strong></div>
        <?php if ('updated' === $result) : ?><div class="brehl-hr__notice"><?php esc_html_e('Die Krankmeldung wurde aktualisiert.', 'brehl-intranet'); ?></div><?php endif; ?>
        <div class="brehl-hr-requests__list"><?php if (!$items) : ?><p class="brehl-hr__empty"><?php esc_html_e('Es liegen noch keine Krankmeldungen vor.', 'brehl-intranet'); ?></p><?php endif; ?><?php foreach ($items as $item) : $user = get_userdata((int) $item->user_id); ?><article class="brehl-hr-request"><div class="brehl-hr-request__summary"><div><span class="brehl-hr-request__status brehl-hr-request__status--<?php echo esc_attr($item->status); ?>"><?php echo esc_html($this->status_label($item->status)); ?></span><h4><?php echo esc_html($user ? $user->display_name : __('Unbekannter Mitarbeiter', 'brehl-intranet')); ?></h4><p><?php echo esc_html($this->period_label($item)); ?></p><?php if ($item->employee_note) : ?><small><?php esc_html_e('Hinweis:', 'brehl-intranet'); ?> <?php echo esc_html($item->employee_note); ?></small><?php endif; ?><?php if ($item->certificate_name) : ?><a class="brehl-sick-certificate" href="<?php echo esc_url($this->download_url((int) $item->id)); ?>"><?php esc_html_e('Geschützte Bescheinigung öffnen', 'brehl-intranet'); ?></a><?php endif; ?></div></div><form class="brehl-hr-request__form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="brehl_update_sick_leave"><input type="hidden" name="report_id" value="<?php echo esc_attr((string) $item->id); ?>"><input type="hidden" name="redirect_to" value="<?php echo esc_url(remove_query_arg(array('sick_management','vacation_management','people_result','employee_id'))); ?>"><?php wp_nonce_field('brehl_update_sick_leave_' . $item->id); ?><label><span><?php esc_html_e('Status', 'brehl-intranet'); ?></span><select name="status"><option value="gemeldet" <?php selected($item->status, 'gemeldet'); ?>><?php esc_html_e('Gemeldet', 'brehl-intranet'); ?></option><option value="bestaetigt" <?php selected($item->status, 'bestaetigt'); ?>><?php esc_html_e('Zur Kenntnis genommen', 'brehl-intranet'); ?></option></select></label><label class="is-wide"><span><?php esc_html_e('Rückmeldung', 'brehl-intranet'); ?></span><textarea name="hr_note" rows="2"><?php echo esc_textarea($item->hr_note); ?></textarea></label><button type="submit"><?php esc_html_e('Speichern', 'brehl-intranet'); ?></button></form></article><?php endforeach; ?></div></section>
        <?php return (string) ob_get_clean();
    }

    public function handle_submission(): void {
        if (!is_user_logged_in() || (!current_user_can('my_brehl_submit_sick_leave') && !$this->can_manage())) wp_die(esc_html__('Keine Berechtigung.', 'brehl-intranet'), 403);
        if (!isset($_POST['brehl_sick_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['brehl_sick_nonce'])), 'brehl_submit_sick_leave')) $this->redirect('error');
        $start = sanitize_text_field(wp_unslash($_POST['start_date'] ?? '')); $end = sanitize_text_field(wp_unslash($_POST['end_date'] ?? ''));
        if (!$this->valid_date($start) || !$this->valid_date($end) || $end < $start) $this->redirect('error');
        $file = $this->validated_file(); if (is_wp_error($file)) $this->redirect('file_error');
        global $wpdb; $now = current_time('mysql');
        $data = array('user_id'=>get_current_user_id(),'start_date'=>$start,'end_date'=>$end,'employee_note'=>sanitize_textarea_field(wp_unslash($_POST['employee_note'] ?? '')),'status'=>'gemeldet','created_at'=>$now,'updated_at'=>$now);
        $formats = array('%d','%s','%s','%s','%s','%s','%s');
        if ($file) { $data['certificate_name']=$file['name']; $data['certificate_mime']=$file['mime']; $data['certificate_data']=$file['data']; $formats=array_merge($formats,array('%s','%s','%s')); }
        if (!$wpdb->insert($this->table(), $data, $formats)) $this->redirect('error');
        $id = (int) $wpdb->insert_id; $this->notify_managers($id, get_current_user_id(), $start, $end); $this->log_activity(get_current_user_id(), get_current_user_id(), 'Krankmeldung eingereicht', $id); $this->redirect('saved');
    }

    public function handle_status_update(): void {
        if (!$this->can_manage()) wp_die(esc_html__('Keine Berechtigung.', 'brehl-intranet'), 403);
        $id = absint($_POST['report_id'] ?? 0); check_admin_referer('brehl_update_sick_leave_' . $id);
        $status = sanitize_key($_POST['status'] ?? 'gemeldet'); if (!in_array($status, array('gemeldet','bestaetigt'), true)) $status='gemeldet';
        global $wpdb; $item=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table()} WHERE id=%d",$id)); if(!$item) wp_die(esc_html__('Krankmeldung nicht gefunden.', 'brehl-intranet'));
        $now=current_time('mysql'); $wpdb->update($this->table(),array('status'=>$status,'hr_note'=>sanitize_textarea_field(wp_unslash($_POST['hr_note'] ?? '')),'reviewed_by'=>get_current_user_id(),'reviewed_at'=>$status==='bestaetigt'?$now:null,'updated_at'=>$now),array('id'=>$id));
        $this->notify_user((int)$item->user_id,$status,$item); $this->log_activity((int)$item->user_id,get_current_user_id(),'Krankmeldung '.$this->status_label($status),$id);
        $redirect=isset($_POST['redirect_to'])?wp_validate_redirect(esc_url_raw(wp_unslash($_POST['redirect_to'])),''):''; wp_safe_redirect(add_query_arg('sick_management','updated',$redirect?:home_url('/dashboard/'))); exit;
    }

    public function handle_download(): void {
        if (!is_user_logged_in()) auth_redirect();
        $id=absint($_GET['report_id'] ?? 0); check_admin_referer('brehl_download_sick_certificate_'.$id);
        global $wpdb; $item=$wpdb->get_row($wpdb->prepare("SELECT user_id,certificate_name,certificate_mime,certificate_data FROM {$this->table()} WHERE id=%d",$id));
        if(!$item || (!$this->can_manage() && (int)$item->user_id!==get_current_user_id()) || !$item->certificate_data) wp_die(esc_html__('Datei nicht gefunden oder keine Berechtigung.', 'brehl-intranet'),403);
        nocache_headers(); header('Content-Type: '.sanitize_text_field($item->certificate_mime)); header('Content-Disposition: inline; filename="'.rawurlencode(sanitize_file_name($item->certificate_name)).'"'); header('X-Content-Type-Options: nosniff'); echo $item->certificate_data; exit; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    private function validated_file() {
        if (empty($_FILES['certificate']['name'])) return null;
        if (!isset($_FILES['certificate']['error'],$_FILES['certificate']['size'],$_FILES['certificate']['tmp_name']) || UPLOAD_ERR_OK !== (int)$_FILES['certificate']['error'] || (int)$_FILES['certificate']['size'] > self::MAX_FILE_SIZE) return new WP_Error('invalid_file');
        $check=wp_check_filetype_and_ext($_FILES['certificate']['tmp_name'],sanitize_file_name(wp_unslash($_FILES['certificate']['name'])),array('pdf'=>'application/pdf','jpg|jpeg'=>'image/jpeg','png'=>'image/png'));
        if(empty($check['type']) || !in_array($check['type'],array('application/pdf','image/jpeg','image/png'),true)) return new WP_Error('invalid_file');
        $data=file_get_contents($_FILES['certificate']['tmp_name']); if(false===$data) return new WP_Error('invalid_file');
        return array('name'=>sanitize_file_name(wp_unslash($_FILES['certificate']['name'])),'mime'=>$check['type'],'data'=>$data);
    }

    private function date_field(string $name,string $label): string { $min=wp_date('Y-m-d'); ob_start(); ?><label class="mbs-date-field"><span><?php echo esc_html($label); ?> *</span><span class="mbs-date-picker" data-min="<?php echo esc_attr($min); ?>"><input class="mbs-date-picker__display" type="text" readonly placeholder="TT.MM.JJJJ" aria-label="<?php echo esc_attr($label); ?>" required><input class="mbs-date-picker__value" type="hidden" name="<?php echo esc_attr($name); ?>"><button class="mbs-date-picker__button" type="button" aria-label="<?php echo esc_attr(sprintf(__('%s im Kalender auswählen','brehl-intranet'),$label)); ?>">▦</button><span class="mbs-calendar" hidden></span></span></label><?php return (string)ob_get_clean(); }
    private function valid_date(string $date): bool { $d=DateTime::createFromFormat('Y-m-d',$date); return $d && $d->format('Y-m-d')===$date; }
    private function period_label($item): string { $label=wp_date('d.m.Y',strtotime($item->start_date)); if($item->end_date!==$item->start_date)$label.=' – '.wp_date('d.m.Y',strtotime($item->end_date)); return $label; }
    private function status_label(string $status): string { return array('gemeldet'=>__('Gemeldet','brehl-intranet'),'bestaetigt'=>__('Zur Kenntnis genommen','brehl-intranet'))[$status]??__('Gemeldet','brehl-intranet'); }
    private function download_url(int $id): string { return wp_nonce_url(admin_url('admin-post.php?action=brehl_download_sick_certificate&report_id='.$id),'brehl_download_sick_certificate_'.$id); }
    private function redirect(string $result): void { $url=wp_get_referer()?:home_url('/dashboard/'); wp_safe_redirect(add_query_arg('sick_leave',$result,$url)); exit; }
    private function notify_managers(int $id,int $uid,string $start,string $end): void { global $wpdb; $u=get_userdata($uid); $managers=get_users(array('role__in'=>array('administrator',Brehl_Roles::HR_ROLE),'fields'=>'ID')); foreach(array_unique(array_map('intval',$managers)) as $manager)$wpdb->insert($wpdb->prefix.'my_brehl_notifications',array('user_id'=>$manager,'title'=>'Neue Krankmeldung','message'=>($u?$u->display_name:'Ein Mitarbeiter').' ist vom '.wp_date('d.m.Y',strtotime($start)).' bis '.wp_date('d.m.Y',strtotime($end)).' krankgemeldet.','type'=>'warning','link_url'=>home_url('/personalverwaltung/'),'is_read'=>0,'created_at'=>current_time('mysql'))); }
    private function notify_user(int $uid,string $status,$item): void { global $wpdb; $wpdb->insert($wpdb->prefix.'my_brehl_notifications',array('user_id'=>$uid,'title'=>'Krankmeldung aktualisiert','message'=>'Ihre Krankmeldung für '.$this->period_label($item).' wurde '.$this->status_label($status).'.','type'=>'info','link_url'=>home_url('/krankmeldung/'),'is_read'=>0,'created_at'=>current_time('mysql'))); }
    private function log_activity(int $uid,int $actor,string $action,int $id): void { global $wpdb; $wpdb->insert($wpdb->prefix.'my_brehl_activity',array('user_id'=>$uid,'actor_id'=>$actor,'module'=>'krankmeldung','action'=>$action,'object_type'=>'krankmeldung','object_id'=>$id,'details'=>'','created_at'=>current_time('mysql'))); }
}
