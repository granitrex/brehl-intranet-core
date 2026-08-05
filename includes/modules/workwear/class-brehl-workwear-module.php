<?php
defined('ABSPATH') || exit;

final class Brehl_Workwear_Module {
    private static ?self $instance = null;
    private const DB_VERSION = '1.0';

    public static function instance(): self { return self::$instance ??= new self(); }

    private function __construct() {
        add_action('init', array($this, 'maybe_install'));
        add_action('admin_post_brehl_submit_workwear', array($this, 'handle_submission'));
        add_action('admin_post_brehl_manage_workwear', array($this, 'handle_management'));
    }

    public static function install(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = $wpdb->prefix . 'brehl_workwear_orders';
        $charset = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            items LONGTEXT NOT NULL,
            employee_note TEXT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'submitted',
            admin_note TEXT NULL,
            handled_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
            ordered_at DATETIME NULL,
            received_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id), KEY user_id (user_id), KEY status (status)
        ) {$charset};");
        update_option('brehl_workwear_db_version', self::DB_VERSION);
    }

    public function maybe_install(): void {
        if (self::DB_VERSION !== get_option('brehl_workwear_db_version')) self::install();
    }

    public function order_panel(): string {
        if (!is_user_logged_in() || !(current_user_can('my_brehl_submit_workwear') || current_user_can('my_brehl_manage_system'))) return '';
        wp_enqueue_style('brehl-intranet'); wp_enqueue_style('my-brehl-system');
        $result = sanitize_key($_GET['workwear'] ?? '');
        $groups = $this->catalogue();
        ob_start(); ?>
        <section class="mbs-workwear"><div class="mbs-card">
            <div class="mbs-card-head"><div><span class="mbs-kicker"><?php esc_html_e('Arbeitsbekleidung', 'brehl-intranet'); ?></span><h3><?php esc_html_e('Bekleidung bestellen', 'brehl-intranet'); ?></h3></div></div>
            <p class="mbs-workwear-intro"><?php esc_html_e('Wählen Sie nur die benötigten Artikel aus. Name und Personalnummer werden automatisch übernommen.', 'brehl-intranet'); ?></p>
            <?php if ('saved' === $result) : ?><div class="mbs-form-message is-success"><?php esc_html_e('Ihre Bestellung wurde übermittelt.', 'brehl-intranet'); ?></div><?php elseif ('error' === $result) : ?><div class="mbs-form-message is-error"><?php esc_html_e('Bitte wählen Sie mindestens einen Artikel mit Größe und Menge aus.', 'brehl-intranet'); ?></div><?php endif; ?>
            <form class="mbs-form mbs-workwear-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="brehl_submit_workwear"><?php wp_nonce_field('brehl_submit_workwear'); ?>
                <div class="mbs-workwear-groups">
                <?php foreach ($groups as $group_key => $group) : ?><details <?php echo 'trousers' === $group_key ? 'open' : ''; ?>><summary><strong><?php echo esc_html($group['label']); ?></strong><span><?php echo esc_html(sprintf(_n('%d Artikel', '%d Artikel', count($group['items']), 'brehl-intranet'), count($group['items']))); ?></span></summary><div class="mbs-workwear-items">
                    <?php foreach ($group['items'] as $key => $item) : ?><article class="mbs-workwear-item">
                        <label class="mbs-workwear-item__choose"><input type="checkbox" name="selected[]" value="<?php echo esc_attr($key); ?>"><span><strong><?php echo esc_html($item['label']); ?></strong><small><?php echo esc_html($item['number'] ? __('Art.-Nr. ', 'brehl-intranet') . $item['number'] : __('Einheitsgröße', 'brehl-intranet')); ?></small></span></label>
                        <label><span><?php esc_html_e('Größe', 'brehl-intranet'); ?></span><select name="size[<?php echo esc_attr($key); ?>]"><option value=""><?php esc_html_e('Bitte wählen', 'brehl-intranet'); ?></option><?php foreach ($item['sizes'] as $size) : ?><option value="<?php echo esc_attr($size); ?>"><?php echo esc_html($size); ?></option><?php endforeach; ?></select></label>
                        <label><span><?php esc_html_e('Menge', 'brehl-intranet'); ?></span><input name="quantity[<?php echo esc_attr($key); ?>]" type="number" min="1" max="10" value="1"></label>
                    </article><?php endforeach; ?>
                </div></details><?php endforeach; ?>
                </div>
                <label class="mbs-form-full"><span><?php esc_html_e('Bemerkung (optional)', 'brehl-intranet'); ?></span><textarea name="employee_note" rows="3" placeholder="<?php echo esc_attr__('Zum Beispiel besondere Passform oder Rückfrage', 'brehl-intranet'); ?>"></textarea></label>
                <button class="mbs-primary-button" type="submit"><?php esc_html_e('Bestellung absenden', 'brehl-intranet'); ?></button>
            </form>
        </div></section>
        <?php return (string) ob_get_clean();
    }

    public function status_panel(): string {
        if (!is_user_logged_in()) return '';
        wp_enqueue_style('brehl-intranet'); wp_enqueue_style('my-brehl-system');
        global $wpdb;
        $orders = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table()} WHERE user_id=%d ORDER BY created_at DESC LIMIT 30", get_current_user_id()));
        ob_start(); ?><section class="mbs-workwear-status"><div class="mbs-card"><div class="mbs-card-head"><h3><?php esc_html_e('Meine Bekleidungsbestellungen', 'brehl-intranet'); ?></h3></div><div class="mbs-list">
        <?php if (!$orders) : ?><p class="mbs-empty"><?php esc_html_e('Sie haben noch keine Arbeitsbekleidung bestellt.', 'brehl-intranet'); ?></p><?php endif; ?>
        <?php foreach ($orders as $order) : ?><article class="mbs-workwear-order"><header><div><strong><?php echo esc_html(sprintf(__('Bestellung #%d', 'brehl-intranet'), $order->id)); ?></strong><small><?php echo esc_html(wp_date('d.m.Y', strtotime($order->created_at))); ?></small></div><span class="mbs-status mbs-status--<?php echo esc_attr($order->status); ?>"><?php echo esc_html($this->status_label($order->status)); ?></span></header><?php echo $this->items_html($order->items); ?><?php if ($order->admin_note) : ?><p class="mbs-workwear-note"><strong><?php esc_html_e('Rückmeldung:', 'brehl-intranet'); ?></strong> <?php echo esc_html($order->admin_note); ?></p><?php endif; ?></article><?php endforeach; ?>
        </div></div></section><?php return (string) ob_get_clean();
    }

    public function management_panel(): string {
        if (!$this->can_manage()) return '';
        wp_enqueue_style('brehl-intranet'); wp_enqueue_style('my-brehl-system');
        global $wpdb;
        $orders = $wpdb->get_results("SELECT o.*,u.display_name FROM {$this->table()} o LEFT JOIN {$wpdb->users} u ON u.ID=o.user_id ORDER BY FIELD(o.status,'submitted','approved','ordered','ready','issued','rejected'),o.created_at DESC LIMIT 100");
        $result = sanitize_key($_GET['workwear_management'] ?? '');
        ob_start(); ?><section class="mbs-workwear-management"><div class="mbs-card"><div class="mbs-card-head"><div><span class="mbs-kicker"><?php esc_html_e('Arbeitsbekleidung', 'brehl-intranet'); ?></span><h3><?php esc_html_e('Bestellungen verwalten', 'brehl-intranet'); ?></h3></div><span class="mbs-count"><?php echo esc_html(sprintf(_n('%d Bestellung', '%d Bestellungen', count($orders), 'brehl-intranet'), count($orders))); ?></span></div>
        <?php if ('saved' === $result) : ?><div class="mbs-form-message is-success"><?php esc_html_e('Die Bestellung wurde aktualisiert.', 'brehl-intranet'); ?></div><?php endif; ?>
        <div class="mbs-workwear-management__list"><?php if (!$orders) : ?><p class="mbs-empty"><?php esc_html_e('Derzeit liegen keine Bestellungen vor.', 'brehl-intranet'); ?></p><?php endif; ?>
        <?php foreach ($orders as $order) : ?><article class="mbs-workwear-case"><header><div><strong><?php echo esc_html($order->display_name ?: __('Unbekannter Mitarbeiter', 'brehl-intranet')); ?></strong><small><?php echo esc_html(__('Personalnummer: ', 'brehl-intranet') . (get_user_meta((int)$order->user_id, 'brehl_personnel_number', true) ?: '–') . ' · ' . wp_date('d.m.Y', strtotime($order->created_at))); ?></small></div><span class="mbs-status mbs-status--<?php echo esc_attr($order->status); ?>"><?php echo esc_html($this->status_label($order->status)); ?></span></header><?php echo $this->items_html($order->items); ?><?php if ($order->employee_note) : ?><p class="mbs-workwear-note"><strong><?php esc_html_e('Bemerkung:', 'brehl-intranet'); ?></strong> <?php echo esc_html($order->employee_note); ?></p><?php endif; ?>
        <form class="mbs-workwear-management__form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="brehl_manage_workwear"><input type="hidden" name="order_id" value="<?php echo esc_attr((string)$order->id); ?>"><?php wp_nonce_field('brehl_manage_workwear_' . $order->id); ?><label><span><?php esc_html_e('Status', 'brehl-intranet'); ?></span><select name="status"><?php foreach ($this->statuses() as $key => $label) : ?><option value="<?php echo esc_attr($key); ?>" <?php selected($order->status, $key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></label><label class="mbs-form-full"><span><?php esc_html_e('Rückmeldung an den Mitarbeiter', 'brehl-intranet'); ?></span><textarea name="admin_note" rows="3"><?php echo esc_textarea((string)$order->admin_note); ?></textarea></label><button class="mbs-primary-button" type="submit"><?php esc_html_e('Änderungen speichern', 'brehl-intranet'); ?></button></form>
        </article><?php endforeach; ?></div></div></section><?php return (string) ob_get_clean();
    }

    public function handle_submission(): void {
        if (!is_user_logged_in() || !(current_user_can('my_brehl_submit_workwear') || current_user_can('my_brehl_manage_system'))) wp_die(__('Keine Berechtigung.', 'brehl-intranet'));
        check_admin_referer('brehl_submit_workwear');
        $catalogue = $this->flat_catalogue();
        $selected = array_map('sanitize_key', (array)($_POST['selected'] ?? array()));
        $sizes = (array)($_POST['size'] ?? array()); $quantities = (array)($_POST['quantity'] ?? array()); $items = array();
        foreach (array_unique($selected) as $key) {
            if (!isset($catalogue[$key])) continue;
            $size = sanitize_text_field(wp_unslash((string)($sizes[$key] ?? ''))); $quantity = min(10, max(1, absint($quantities[$key] ?? 1)));
            if (!in_array($size, $catalogue[$key]['sizes'], true)) continue;
            $items[] = array('key'=>$key, 'label'=>$catalogue[$key]['label'], 'number'=>$catalogue[$key]['number'], 'size'=>$size, 'quantity'=>$quantity);
        }
        if (!$items) $this->redirect('workwear', 'error');
        global $wpdb; $now = current_time('mysql');
        $saved = $wpdb->insert($this->table(), array('user_id'=>get_current_user_id(), 'items'=>wp_json_encode($items), 'employee_note'=>sanitize_textarea_field(wp_unslash($_POST['employee_note'] ?? '')), 'status'=>'submitted', 'created_at'=>$now, 'updated_at'=>$now));
        if ($saved) $this->notify_managers();
        $this->redirect('workwear', $saved ? 'saved' : 'error');
    }

    public function handle_management(): void {
        if (!$this->can_manage()) wp_die(__('Keine Berechtigung.', 'brehl-intranet'));
        $id = absint($_POST['order_id'] ?? 0); check_admin_referer('brehl_manage_workwear_' . $id);
        global $wpdb; $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table()} WHERE id=%d", $id));
        if (!$order) $this->redirect('workwear_management', 'error');
        $status = sanitize_key($_POST['status'] ?? 'submitted'); if (!isset($this->statuses()[$status])) $status = 'submitted';
        $data = array('status'=>$status, 'admin_note'=>sanitize_textarea_field(wp_unslash($_POST['admin_note'] ?? '')), 'handled_by'=>get_current_user_id(), 'updated_at'=>current_time('mysql'));
        if ('ordered' === $status && !$order->ordered_at) $data['ordered_at'] = current_time('mysql');
        if ('issued' === $status && !$order->received_at) $data['received_at'] = current_time('mysql');
        $wpdb->update($this->table(), $data, array('id'=>$id)); $this->notify_employee((int)$order->user_id, $status);
        $this->redirect('workwear_management', 'saved');
    }

    private function table(): string { global $wpdb; return $wpdb->prefix . 'brehl_workwear_orders'; }
    private function can_manage(): bool { return is_user_logged_in() && (current_user_can('my_brehl_manage_workwear') || current_user_can('my_brehl_manage_system')); }
    private function redirect(string $key, string $value): void { $url = wp_get_referer() ?: home_url('/dashboard/'); wp_safe_redirect(add_query_arg($key, $value, $url)); exit; }
    private function statuses(): array { return array('submitted'=>'Eingereicht','approved'=>'Freigegeben','ordered'=>'Bestellt','ready'=>'Abholbereit','issued'=>'Ausgegeben','rejected'=>'Abgelehnt'); }
    private function status_label(string $status): string { return $this->statuses()[$status] ?? 'Eingereicht'; }
    private function items_html(string $json): string { $items=(array)json_decode($json,true); ob_start(); ?><ul class="mbs-workwear-order__items"><?php foreach($items as $item): ?><li><strong><?php echo esc_html((string)($item['quantity']??1).' × '.(string)($item['label']??'')); ?></strong><span><?php echo esc_html(__('Größe ', 'brehl-intranet').(string)($item['size']??'').(!empty($item['number'])?' · Art.-Nr. '.$item['number']:'')); ?></span></li><?php endforeach; ?></ul><?php return (string)ob_get_clean(); }
    private function notify_managers(): void { global $wpdb; foreach(get_users(array('role__in'=>array('administrator','personalverwaltung'),'fields'=>'ID')) as $uid) $wpdb->insert($wpdb->prefix.'my_brehl_notifications',array('user_id'=>(int)$uid,'title'=>'Neue Bekleidungsbestellung','message'=>'Eine neue Bestellung für Arbeitsbekleidung liegt vor.','type'=>'info','link_url'=>'','is_read'=>0,'created_at'=>current_time('mysql'))); }
    private function notify_employee(int $uid,string $status): void { global $wpdb; $wpdb->insert($wpdb->prefix.'my_brehl_notifications',array('user_id'=>$uid,'title'=>'Bekleidungsbestellung aktualisiert','message'=>'Der Status Ihrer Bestellung lautet: '.$this->status_label($status).'.','type'=>'info','link_url'=>'','is_read'=>0,'created_at'=>current_time('mysql'))); }

    private function flat_catalogue(): array { $flat=array(); foreach($this->catalogue() as $group) foreach($group['items'] as $key=>$item) $flat[$key]=$item; return $flat; }
    private function catalogue(): array {
        $range = static fn(int $from,int $to,int $step=2): array => range($from,$to,$step);
        return array(
            'trousers'=>array('label'=>'Hosen','items'=>array(
                'bundhose'=>array('label'=>'Bundhose e.s.motion 2020','number'=>'65571','sizes'=>array_merge(array_map('strval',$range(42,62)),array('94 (schlank)','98 (schlank)','102 (schlank)','106 (schlank)','110 (schlank)','23 (untersetzt)','24 (untersetzt)','25 (untersetzt)','26 (untersetzt)','27 (untersetzt)','28 (untersetzt)'))),
                'latzhose'=>array('label'=>'Latzhose e.s.motion 2020','number'=>'65576','sizes'=>array_merge(array_map('strval',$range(46,62)),array('98 (schlank)','102 (schlank)','106 (schlank)','25 (untersetzt)','26 (untersetzt)','27 (untersetzt)','28 (untersetzt)','29 (untersetzt)'))),
                'short'=>array('label'=>'Short e.s.motion 2020','number'=>'65573','sizes'=>array_map('strval',$range(42,60))),
                'piratenhose'=>array('label'=>'Piratenhose e.s.motion 2020','number'=>'65903','sizes'=>array_map('strval',$range(46,56))),
            )),
            'tops'=>array('label'=>'Oberteile','items'=>array(
                'tshirt'=>array('label'=>'e.s. T-Shirt cotton','number'=>'89600','sizes'=>array('S','M','L','XL','2XL','3XL')),
                'polo'=>array('label'=>'e.s. Polo-Shirt cotton','number'=>'89914','sizes'=>array('S','M','L','XL','2XL','3XL')),
                'hoodie_jacket'=>array('label'=>'e.s. Hoody Sweatjacke poly cotton','number'=>'22456','sizes'=>array('XS','S','M','L','XL','2XL','3XL','4XL')),
                'hoodie'=>array('label'=>'e.s. Hoody-Sweatshirt poly cotton','number'=>'22446','sizes'=>array('S','M','L','XL','2XL','3XL','4XL')),
                'sweatshirt'=>array('label'=>'Sweatshirt poly cotton','number'=>'22436','sizes'=>array('S','M','L','XL','2XL','3XL')),
            )),
            'jackets'=>array('label'=>'Westen & Jacken','items'=>array(
                'vest'=>array('label'=>'Funk-Weste thermo stretch e.s.motion 2020','number'=>'65577','sizes'=>array('S','M','L','XL','2XL','3XL')),
                'softshell'=>array('label'=>'Softshelljacke e.s.motion 2020','number'=>'65572','sizes'=>array('XS','S','M','L','XL','2XL','3XL','4XL','5XL')),
                'winter_softshell'=>array('label'=>'Winter-Softshelljacke e.s.motion 2020','number'=>'65578','sizes'=>array('S','M','L','XL','2XL','3XL','4XL')),
            )),
            'accessories'=>array('label'=>'Zubehör','items'=>array(
                'winter_hat'=>array('label'=>'Wintermütze','number'=>'','sizes'=>array('Unigröße')),
            )),
        );
    }
}
