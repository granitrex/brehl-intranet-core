<?php
defined('ABSPATH') || exit;

final class Brehl_Workwear_Module {
    private static ?self $instance = null;
    private const DB_VERSION = '1.3';

    public static function instance(): self { return self::$instance ??= new self(); }

    private function __construct() {
        add_action('init', array($this, 'maybe_install'));
        add_action('admin_post_brehl_submit_workwear', array($this, 'handle_submission'));
        add_action('admin_post_brehl_manage_workwear', array($this, 'handle_management'));
        add_action('admin_post_brehl_cancel_workwear', array($this, 'handle_cancellation'));
        add_action('admin_post_brehl_save_workwear_product', array($this, 'handle_product_save'));
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
            status VARCHAR(30) NOT NULL DEFAULT 'ordered',
            admin_note TEXT NULL,
            handled_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
            ordered_at DATETIME NULL,
            received_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id), KEY user_id (user_id), KEY status (status)
        ) {$charset};");
        $products = $wpdb->prefix . 'brehl_workwear_products';
        dbDelta("CREATE TABLE {$products} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_key VARCHAR(80) NOT NULL,
            category VARCHAR(40) NOT NULL,
            label VARCHAR(190) NOT NULL,
            article_number VARCHAR(60) NOT NULL DEFAULT '',
            sizes LONGTEXT NOT NULL,
            image_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id), UNIQUE KEY product_key (product_key), KEY category (category), KEY active (active)
        ) {$charset};");
        self::seed_products($products);
        $wpdb->query("UPDATE {$table} SET status='ordered' WHERE status IN ('submitted','approved','ready')");
        $wpdb->query("UPDATE {$table} SET status='processing' WHERE status='printing'");
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
            <p class="mbs-workwear-intro"><?php esc_html_e('Wählen Sie nur die benötigten Artikel aus. Name und Personalnummer werden automatisch übernommen.', 'brehl-intranet'); ?></p>
            <?php if ('saved' === $result) : ?><div class="mbs-form-message is-success"><?php esc_html_e('Ihre Bestellung wurde übermittelt.', 'brehl-intranet'); ?></div><?php elseif ('error' === $result) : ?><div class="mbs-form-message is-error"><?php esc_html_e('Bitte wählen Sie mindestens einen Artikel mit Größe und Menge aus.', 'brehl-intranet'); ?></div><?php endif; ?>
            <form class="mbs-form mbs-workwear-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="brehl_submit_workwear"><?php wp_nonce_field('brehl_submit_workwear'); ?>
                <div class="mbs-workwear-groups">
                <?php foreach ($groups as $group_key => $group) : ?><details <?php echo 'trousers' === $group_key ? 'open' : ''; ?>><summary><strong><?php echo esc_html($group['label']); ?></strong><span><?php echo esc_html(sprintf(_n('%d Artikel', '%d Artikel', count($group['items']), 'brehl-intranet'), count($group['items']))); ?></span></summary><div class="mbs-workwear-items">
                    <?php foreach ($group['items'] as $key => $item) : ?><article class="mbs-workwear-item">
                        <label class="mbs-workwear-item__choose"><input type="checkbox" name="selected[]" value="<?php echo esc_attr($key); ?>"><?php if(!empty($item['image_id'])): ?><span class="mbs-workwear-item__image"><?php echo wp_get_attachment_image((int)$item['image_id'],'thumbnail'); ?></span><?php endif; ?><span><strong><?php echo esc_html($item['label']); ?></strong><small><?php echo esc_html($item['number'] ? __('Art.-Nr. ', 'brehl-intranet') . $item['number'] : __('Einheitsgröße', 'brehl-intranet')); ?></small></span></label>
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
        $archive=!empty($_GET['workwear_archive']);
        $condition=$archive?"status IN ('issued','cancelled')":"status NOT IN ('issued','cancelled')";
        $orders = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table()} WHERE user_id=%d AND {$condition} ORDER BY created_at DESC LIMIT 100", get_current_user_id()));
        $toggle=$archive?remove_query_arg('workwear_archive'):add_query_arg('workwear_archive','1');
        ob_start(); ?><section class="mbs-workwear-status"><div class="mbs-card"><div class="mbs-workwear-toolbar"><span><?php echo esc_html(sprintf(_n('%d Bestellung','%d Bestellungen',count($orders),'brehl-intranet'),count($orders))); ?></span><a class="mbs-workwear-archive-link" href="<?php echo esc_url($toggle); ?>"><?php echo $archive?esc_html__('Aktuelle Bestellungen','brehl-intranet'):esc_html__('Archiv anzeigen','brehl-intranet'); ?></a></div><div class="mbs-list">
        <?php if ('saved'===sanitize_key($_GET['workwear_cancel']??'')) : ?><div class="mbs-form-message is-success"><?php esc_html_e('Die Bestellung wurde storniert und ins Archiv verschoben.','brehl-intranet'); ?></div><?php endif; ?>
        <?php if (!$orders) : ?><p class="mbs-empty"><?php echo $archive?esc_html__('Das Archiv ist noch leer.','brehl-intranet'):esc_html__('Sie haben noch keine aktuelle Bekleidungsbestellung.','brehl-intranet'); ?></p><?php endif; ?>
        <?php foreach ($orders as $order) : $item_count=count((array)json_decode($order->items,true)); ?><details class="mbs-workwear-order"><summary><div><strong><?php echo esc_html(sprintf(__('Bestellung #%d', 'brehl-intranet'), $order->id)); ?></strong><small><?php echo esc_html(wp_date('d.m.Y', strtotime($order->created_at)) . ' · ' . sprintf(_n('%d Artikel','%d Artikel',$item_count,'brehl-intranet'),$item_count)); ?></small></div><span class="mbs-status mbs-status--<?php echo esc_attr($order->status); ?>"><?php echo esc_html($this->status_label($order->status)); ?></span></summary><div class="mbs-workwear-order__body"><?php echo $this->items_html($order->items); ?><?php if ($order->admin_note) : ?><p class="mbs-workwear-note"><strong><?php esc_html_e('Rückmeldung:', 'brehl-intranet'); ?></strong> <?php echo esc_html($order->admin_note); ?></p><?php endif; ?><?php if ('ordered'===$order->status && !$archive) : ?><form class="mbs-workwear-cancel" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('Möchten Sie diese Bestellung wirklich stornieren?', 'brehl-intranet')); ?>');"><input type="hidden" name="action" value="brehl_cancel_workwear"><input type="hidden" name="order_id" value="<?php echo esc_attr((string)$order->id); ?>"><?php wp_nonce_field('brehl_cancel_workwear_'.$order->id); ?><button type="submit"><?php esc_html_e('Bestellung stornieren','brehl-intranet'); ?></button></form><?php endif; ?></div></details><?php endforeach; ?>
        </div></div></section><?php return (string) ob_get_clean();
    }

    public function management_panel(): string {
        if (!$this->can_manage()) return '';
        wp_enqueue_style('brehl-intranet'); wp_enqueue_style('my-brehl-system');
        global $wpdb;
        $archive=!empty($_GET['workwear_archive']);
        $condition=$archive?"o.status IN ('issued','cancelled')":"o.status NOT IN ('issued','cancelled')";
        $orders = $wpdb->get_results("SELECT o.*,u.display_name FROM {$this->table()} o LEFT JOIN {$wpdb->users} u ON u.ID=o.user_id WHERE {$condition} ORDER BY FIELD(o.status,'ordered','processing','rejected','issued'),o.created_at DESC LIMIT 200");
        $toggle=$archive?remove_query_arg('workwear_archive'):add_query_arg('workwear_archive','1');
        $result = sanitize_key($_GET['workwear_management'] ?? '');
        ob_start(); ?><section class="mbs-workwear-management"><div class="mbs-card"><div class="mbs-workwear-toolbar"><span><?php echo esc_html(sprintf(_n('%d Bestellung', '%d Bestellungen', count($orders), 'brehl-intranet'), count($orders))); ?></span><a class="mbs-workwear-archive-link" href="<?php echo esc_url($toggle); ?>"><?php echo $archive?esc_html__('Aktuelle Bestellungen','brehl-intranet'):esc_html__('Archiv anzeigen','brehl-intranet'); ?></a></div>
        <?php if ('saved' === $result) : ?><div class="mbs-form-message is-success"><?php esc_html_e('Die Bestellung wurde aktualisiert.', 'brehl-intranet'); ?></div><?php endif; ?>
        <div class="mbs-workwear-management__list"><?php if (!$orders) : ?><p class="mbs-empty"><?php echo $archive?esc_html__('Das Archiv ist noch leer.','brehl-intranet'):esc_html__('Derzeit liegen keine aktuellen Bestellungen vor.','brehl-intranet'); ?></p><?php endif; ?>
        <?php foreach ($orders as $order) : $item_count=count((array)json_decode($order->items,true)); ?><details class="mbs-workwear-case"><summary><div><strong><?php echo esc_html($order->display_name ?: __('Unbekannter Mitarbeiter', 'brehl-intranet')); ?></strong><small><?php echo esc_html(__('Personalnummer: ', 'brehl-intranet') . (get_user_meta((int)$order->user_id, 'brehl_personnel_number', true) ?: '–') . ' · ' . wp_date('d.m.Y', strtotime($order->created_at)) . ' · ' . sprintf(_n('%d Artikel','%d Artikel',$item_count,'brehl-intranet'),$item_count)); ?></small></div><span class="mbs-status mbs-status--<?php echo esc_attr($order->status); ?>"><?php echo esc_html($this->status_label($order->status)); ?></span></summary><div class="mbs-workwear-case__body"><?php echo $this->items_html($order->items); ?><?php if ($order->employee_note) : ?><p class="mbs-workwear-note"><strong><?php esc_html_e('Bemerkung:', 'brehl-intranet'); ?></strong> <?php echo esc_html($order->employee_note); ?></p><?php endif; ?>
        <?php if (!$archive) : ?><form class="mbs-workwear-management__form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="brehl_manage_workwear"><input type="hidden" name="order_id" value="<?php echo esc_attr((string)$order->id); ?>"><?php wp_nonce_field('brehl_manage_workwear_' . $order->id); ?><fieldset class="mbs-workwear-status-actions"><legend><?php esc_html_e('Status direkt ändern','brehl-intranet'); ?></legend><?php foreach ($this->manager_statuses() as $key=>$label) : ?><button class="<?php echo $order->status===$key?'is-current':''; ?>" type="submit" name="status" value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></button><?php endforeach; ?></fieldset><label class="mbs-form-full"><span><?php esc_html_e('Rückmeldung an den Mitarbeiter', 'brehl-intranet'); ?></span><textarea name="admin_note" rows="3"><?php echo esc_textarea((string)$order->admin_note); ?></textarea></label><p class="mbs-workwear-save-hint"><?php esc_html_e('Der angeklickte Status wird zusammen mit der Rückmeldung sofort gespeichert.','brehl-intranet'); ?></p></form><?php endif; ?></div>
        </details><?php endforeach; ?></div></div></section><?php return (string) ob_get_clean();
    }

    public function catalogue_panel(): string {
        if (!$this->can_manage()) return '';
        wp_enqueue_style('brehl-intranet'); wp_enqueue_style('my-brehl-system');
        global $wpdb;
        $products = $wpdb->get_results("SELECT * FROM {$this->products_table()} ORDER BY sort_order,label");
        $result = sanitize_key($_GET['workwear_catalogue'] ?? '');
        ob_start(); ?><section class="mbs-workwear-catalogue"><div class="mbs-card"><div class="mbs-workwear-toolbar"><span><?php echo esc_html(sprintf(_n('%d Artikel','%d Artikel',count($products),'brehl-intranet'),count($products))); ?></span><button type="button" class="mbs-workwear-add-toggle" onclick="this.closest('.mbs-card').querySelector('.mbs-workwear-new-product').toggleAttribute('open')"><?php esc_html_e('Neuen Artikel hinzufügen','brehl-intranet'); ?></button></div>
        <p class="mbs-workwear-intro"><?php esc_html_e('Ausgeblendete Artikel bleiben in alten Bestellungen erhalten, können aber nicht mehr neu bestellt werden.', 'brehl-intranet'); ?></p>
        <?php if ('saved' === $result) : ?><div class="mbs-form-message is-success"><?php esc_html_e('Der Artikel wurde gespeichert.', 'brehl-intranet'); ?></div><?php elseif ('error' === $result) : ?><div class="mbs-form-message is-error"><?php esc_html_e('Der Artikel konnte nicht gespeichert werden. Bitte prüfen Sie die Angaben und das Bild.', 'brehl-intranet'); ?></div><?php endif; ?>
        <details class="mbs-workwear-new-product" <?php echo isset($_GET['workwear_product']) && 'new'===$_GET['workwear_product']?'open':''; ?>><summary><?php esc_html_e('Neuen Artikel anlegen','brehl-intranet'); ?></summary><form class="mbs-workwear-product-admin__form" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="brehl_save_workwear_product"><input type="hidden" name="product_id" value="0"><?php wp_nonce_field('brehl_save_workwear_product_0'); ?><div class="mbs-form-grid"><label><span><?php esc_html_e('Artikelname','brehl-intranet'); ?> *</span><input name="label" required></label><label><span><?php esc_html_e('Artikelnummer','brehl-intranet'); ?></span><input name="article_number"></label><label><span><?php esc_html_e('Kategorie','brehl-intranet'); ?></span><select name="category"><?php foreach($this->category_labels() as $key=>$label): ?><option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option><?php endforeach; ?></select></label><label><span><?php esc_html_e('Größen','brehl-intranet'); ?> *</span><input name="sizes" required placeholder="S, M, L, XL"><small><?php esc_html_e('Mit Komma trennen','brehl-intranet'); ?></small></label><label class="mbs-form-full mbs-file-field"><span><?php esc_html_e('Artikelbild','brehl-intranet'); ?></span><input type="file" name="product_image" accept="image/jpeg,image/png,image/webp"><small><?php esc_html_e('JPG, PNG oder WebP, maximal 4 MB.','brehl-intranet'); ?></small></label></div><input type="hidden" name="active" value="1"><button class="mbs-primary-button" type="submit"><?php esc_html_e('Artikel anlegen','brehl-intranet'); ?></button></form></details>
        <div class="mbs-workwear-catalogue__list"><?php foreach($products as $product): $sizes=implode(', ',(array)json_decode($product->sizes,true)); ?><details class="mbs-workwear-product-admin" <?php echo isset($_GET['workwear_product']) && absint($_GET['workwear_product'])===(int)$product->id ? 'open' : ''; ?>><summary><span class="mbs-workwear-product-admin__thumb"><?php echo $product->image_id ? wp_get_attachment_image((int)$product->image_id,'thumbnail') : '<b aria-hidden="true">👕</b>'; ?></span><span><strong><?php echo esc_html($product->label); ?></strong><small><?php echo esc_html(($product->article_number?'Art.-Nr. '.$product->article_number.' · ':'').$this->category_label($product->category)); ?></small></span><span class="mbs-status <?php echo $product->active ? 'is-active' : 'is-inactive'; ?>"><?php echo esc_html($product->active?__('Sichtbar','brehl-intranet'):__('Ausgeblendet','brehl-intranet')); ?></span></summary>
        <form class="mbs-workwear-product-admin__form" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="brehl_save_workwear_product"><input type="hidden" name="product_id" value="<?php echo esc_attr((string)$product->id); ?>"><?php wp_nonce_field('brehl_save_workwear_product_'.$product->id); ?><div class="mbs-form-grid"><label><span><?php esc_html_e('Artikelname','brehl-intranet'); ?> *</span><input name="label" required value="<?php echo esc_attr($product->label); ?>"></label><label><span><?php esc_html_e('Artikelnummer','brehl-intranet'); ?></span><input name="article_number" value="<?php echo esc_attr($product->article_number); ?>"></label><label><span><?php esc_html_e('Kategorie','brehl-intranet'); ?></span><select name="category"><?php foreach($this->category_labels() as $key=>$label): ?><option value="<?php echo esc_attr($key); ?>" <?php selected($product->category,$key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></label><label><span><?php esc_html_e('Größen','brehl-intranet'); ?> *</span><input name="sizes" required value="<?php echo esc_attr($sizes); ?>"><small><?php esc_html_e('Mit Komma trennen, z. B. S, M, L, XL','brehl-intranet'); ?></small></label><label class="mbs-form-full mbs-file-field"><span><?php esc_html_e('Artikelbild','brehl-intranet'); ?></span><input type="file" name="product_image" accept="image/jpeg,image/png,image/webp"><small><?php esc_html_e('JPG, PNG oder WebP, maximal 4 MB. Ein neues Bild ersetzt das bisherige.','brehl-intranet'); ?></small></label></div><label class="mbs-workwear-product-admin__active"><input type="checkbox" name="active" value="1" <?php checked((int)$product->active,1); ?>> <span><?php esc_html_e('Artikel für neue Bestellungen anzeigen','brehl-intranet'); ?></span></label><button class="mbs-primary-button" type="submit"><?php esc_html_e('Artikel speichern','brehl-intranet'); ?></button></form></details><?php endforeach; ?></div></div></section><?php return (string)ob_get_clean();
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
        $saved = $wpdb->insert($this->table(), array('user_id'=>get_current_user_id(), 'items'=>wp_json_encode($items), 'employee_note'=>sanitize_textarea_field(wp_unslash($_POST['employee_note'] ?? '')), 'status'=>'ordered', 'created_at'=>$now, 'updated_at'=>$now));
        if ($saved) $this->notify_managers();
        $this->redirect('workwear', $saved ? 'saved' : 'error');
    }

    public function handle_management(): void {
        if (!$this->can_manage()) wp_die(__('Keine Berechtigung.', 'brehl-intranet'));
        $id = absint($_POST['order_id'] ?? 0); check_admin_referer('brehl_manage_workwear_' . $id);
        global $wpdb; $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table()} WHERE id=%d", $id));
        if (!$order) $this->redirect('workwear_management', 'error');
        $status = sanitize_key($_POST['status'] ?? 'ordered'); if (!isset($this->manager_statuses()[$status])) $status = 'ordered';
        $data = array('status'=>$status, 'admin_note'=>sanitize_textarea_field(wp_unslash($_POST['admin_note'] ?? '')), 'handled_by'=>get_current_user_id(), 'updated_at'=>current_time('mysql'));
        if ('ordered' === $status && !$order->ordered_at) $data['ordered_at'] = current_time('mysql');
        if ('issued' === $status && !$order->received_at) $data['received_at'] = current_time('mysql');
        $wpdb->update($this->table(), $data, array('id'=>$id)); $this->notify_employee((int)$order->user_id, $status);
        $this->redirect('workwear_management', 'saved');
    }

    public function handle_cancellation(): void {
        if (!is_user_logged_in()) wp_die(__('Keine Berechtigung.', 'brehl-intranet'));
        $id=absint($_POST['order_id']??0); check_admin_referer('brehl_cancel_workwear_'.$id);
        global $wpdb;
        $order=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table()} WHERE id=%d AND user_id=%d",$id,get_current_user_id()));
        if(!$order || 'ordered'!==$order->status) $this->redirect('workwear_cancel','error');
        $wpdb->update($this->table(),array('status'=>'cancelled','updated_at'=>current_time('mysql')),array('id'=>$id));
        $this->redirect('workwear_cancel','saved');
    }

    public function handle_product_save(): void {
        if (!$this->can_manage()) wp_die(__('Keine Berechtigung.', 'brehl-intranet'));
        $id=absint($_POST['product_id']??0); check_admin_referer('brehl_save_workwear_product_'.$id);
        global $wpdb; $product=$id?$wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->products_table()} WHERE id=%d",$id)):null;
        if($id&&!$product)$this->redirect('workwear_catalogue','error');
        $label=sanitize_text_field(wp_unslash($_POST['label']??'')); $category=sanitize_key($_POST['category']??'');
        $sizes=array_values(array_unique(array_filter(array_map('trim',explode(',',sanitize_text_field(wp_unslash($_POST['sizes']??'')))))));
        if(!$label||!isset($this->category_labels()[$category])||!$sizes)$this->redirect('workwear_catalogue','error');
        $image_id=$product?(int)$product->image_id:0;
        if(!empty($_FILES['product_image']['name'])){
            $file=$_FILES['product_image'];
            if((int)$file['error']!==UPLOAD_ERR_OK||(int)$file['size']>4*MB_IN_BYTES)$this->redirect('workwear_catalogue','error');
            $checked=wp_check_filetype_and_ext($file['tmp_name'],sanitize_file_name($file['name']));
            if(empty($checked['type'])||!in_array($checked['type'],array('image/jpeg','image/png','image/webp'),true))$this->redirect('workwear_catalogue','error');
            require_once ABSPATH.'wp-admin/includes/file.php'; require_once ABSPATH.'wp-admin/includes/media.php'; require_once ABSPATH.'wp-admin/includes/image.php';
            $uploaded=media_handle_upload('product_image',0); if(is_wp_error($uploaded))$this->redirect('workwear_catalogue','error'); $image_id=(int)$uploaded;
        }
        $data=array('label'=>$label,'article_number'=>sanitize_text_field(wp_unslash($_POST['article_number']??'')),'category'=>$category,'sizes'=>wp_json_encode($sizes),'image_id'=>$image_id,'active'=>empty($_POST['active'])?0:1,'updated_at'=>current_time('mysql'));
        if($product){$saved=$wpdb->update($this->products_table(),$data,array('id'=>$id));$target=(string)$id;}else{$data['product_key']='custom_'.wp_generate_uuid4();$data['sort_order']=1+(int)$wpdb->get_var("SELECT MAX(sort_order) FROM {$this->products_table()}");$saved=$wpdb->insert($this->products_table(),$data);$target=$saved?(string)$wpdb->insert_id:'new';}
        $url=wp_get_referer()?:home_url('/bekleidungsverwaltung/'); $url=add_query_arg(array('workwear_catalogue'=>$saved===false?'error':'saved','workwear_product'=>$target),$url); wp_safe_redirect($url); exit;
    }

    private function table(): string { global $wpdb; return $wpdb->prefix . 'brehl_workwear_orders'; }
    private function products_table(): string { global $wpdb; return $wpdb->prefix . 'brehl_workwear_products'; }
    private function can_manage(): bool { return is_user_logged_in() && (current_user_can('my_brehl_manage_workwear') || current_user_can('my_brehl_manage_system')); }
    private function redirect(string $key, string $value): void { $url = wp_get_referer() ?: home_url('/dashboard/'); wp_safe_redirect(add_query_arg($key, $value, $url)); exit; }
    private function manager_statuses(): array { return array('ordered'=>'Bestellt','processing'=>'In Bearbeitung','issued'=>'Ausgehändigt','rejected'=>'Abgelehnt'); }
    private function statuses(): array { return $this->manager_statuses()+array('cancelled'=>'Storniert'); }
    private function status_label(string $status): string { return $this->statuses()[$status] ?? 'Bestellt'; }
    private function items_html(string $json): string { $items=(array)json_decode($json,true); ob_start(); ?><ul class="mbs-workwear-order__items"><?php foreach($items as $item): ?><li><strong><?php echo esc_html((string)($item['quantity']??1).' × '.(string)($item['label']??'')); ?></strong><span><?php echo esc_html(__('Größe ', 'brehl-intranet').(string)($item['size']??'').(!empty($item['number'])?' · Art.-Nr. '.$item['number']:'')); ?></span></li><?php endforeach; ?></ul><?php return (string)ob_get_clean(); }
    private function notify_managers(): void { global $wpdb; foreach(get_users(array('role__in'=>array('administrator','personalverwaltung'),'fields'=>'ID')) as $uid) $wpdb->insert($wpdb->prefix.'my_brehl_notifications',array('user_id'=>(int)$uid,'title'=>'Neue Bekleidungsbestellung','message'=>'Eine neue Bestellung für Arbeitsbekleidung liegt vor.','type'=>'info','link_url'=>'','is_read'=>0,'created_at'=>current_time('mysql'))); }
    private function notify_employee(int $uid,string $status): void { global $wpdb; $wpdb->insert($wpdb->prefix.'my_brehl_notifications',array('user_id'=>$uid,'title'=>'Bekleidungsbestellung aktualisiert','message'=>'Der Status Ihrer Bestellung lautet: '.$this->status_label($status).'.','type'=>'info','link_url'=>'','is_read'=>0,'created_at'=>current_time('mysql'))); }

    private function flat_catalogue(): array { $flat=array(); foreach($this->catalogue() as $group) foreach($group['items'] as $key=>$item) $flat[$key]=$item; return $flat; }
    private function catalogue(): array {
        global $wpdb; $rows=$wpdb->get_results("SELECT * FROM {$this->products_table()} WHERE active=1 ORDER BY sort_order,label");
        if($rows){ $groups=array(); foreach($this->category_labels() as $key=>$label)$groups[$key]=array('label'=>$label,'items'=>array()); foreach($rows as $row){ if(!isset($groups[$row->category]))continue; $groups[$row->category]['items'][$row->product_key]=array('label'=>$row->label,'number'=>$row->article_number,'sizes'=>(array)json_decode($row->sizes,true),'image_id'=>(int)$row->image_id); } return array_filter($groups,fn($group)=>!empty($group['items'])); }
        return self::default_catalogue();
    }
    private function category_labels(): array { return array('trousers'=>'Hosen','tops'=>'Oberteile','jackets'=>'Westen & Jacken','accessories'=>'Zubehör'); }
    private function category_label(string $key): string { return $this->category_labels()[$key]??'Arbeitsbekleidung'; }
    private static function seed_products(string $table): void { global $wpdb; $sort=0; foreach(self::default_catalogue() as $category=>$group)foreach($group['items'] as $key=>$item){$exists=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE product_key=%s",$key));if($exists)continue;$wpdb->insert($table,array('product_key'=>$key,'category'=>$category,'label'=>$item['label'],'article_number'=>$item['number'],'sizes'=>wp_json_encode($item['sizes']),'image_id'=>0,'active'=>1,'sort_order'=>$sort++,'updated_at'=>current_time('mysql')));} }
    private static function default_catalogue(): array {
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
