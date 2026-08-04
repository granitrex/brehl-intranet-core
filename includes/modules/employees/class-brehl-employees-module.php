<?php

defined('ABSPATH') || exit;

final class Brehl_Employees_Module {
    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'register_shortcode'));
        add_action('admin_post_my_brehl_save_employee', array($this, 'handle_save_employee'));
        add_action('admin_post_my_brehl_send_password_reset', array($this, 'handle_send_password_reset'));
    }

    public function register_shortcode(): void {
        add_shortcode('my_brehl_personalverwaltung', array($this, 'shortcode'));
    }

    public function shortcode(): string {
        if (!is_user_logged_in() || !current_user_can('my_brehl_manage_people')) {
            return '';
        }
        wp_enqueue_style('brehl-intranet');
        wp_enqueue_script('brehl-intranet-hr');
        $employees = get_users(array(
            'role' => Brehl_Roles::EMPLOYEE_ROLE,
            'orderby' => 'display_name',
            'order' => 'ASC',
        ));
        $editing_id = absint($_GET['employee_id'] ?? 0);
        $editing = $editing_id ? get_userdata($editing_id) : null;
        if ($editing && !in_array(Brehl_Roles::EMPLOYEE_ROLE, (array) $editing->roles, true)) {
            $editing = null;
        }
        $counts = $this->overview_counts();
        $result = sanitize_key($_GET['people_result'] ?? '');
        ob_start(); ?>
        <section class="brehl-hr">
            <header class="brehl-hr__header">
                <div><span><?php esc_html_e('Geschützter Bereich', 'brehl-intranet'); ?></span><h2><?php esc_html_e('Personalverwaltung', 'brehl-intranet'); ?></h2></div>
                <strong><?php echo esc_html(sprintf(__('%d Mitarbeiter', 'brehl-intranet'), count($employees))); ?></strong>
            </header>
            <?php if ($result) : ?><div class="brehl-hr__notice brehl-hr__notice--<?php echo esc_attr($result); ?>"><?php echo esc_html($this->result_message($result)); ?></div><?php endif; ?>
            <div class="brehl-hr__metrics">
                <article><span><?php esc_html_e('Mitarbeiter', 'brehl-intranet'); ?></span><strong><?php echo esc_html((string) count($employees)); ?></strong></article>
                <article><span><?php esc_html_e('Offene Urlaubsanträge', 'brehl-intranet'); ?></span><strong><?php echo esc_html((string) $counts['vacation']); ?></strong></article>
                <article><span><?php esc_html_e('Neue Krankmeldungen', 'brehl-intranet'); ?></span><strong><?php echo esc_html((string) $counts['sick']); ?></strong></article>
                <article><span><?php esc_html_e('Offene Fahrzeugschäden', 'brehl-intranet'); ?></span><strong><?php echo esc_html((string) $counts['vehicle']); ?></strong></article>
            </div>
            <div class="brehl-hr__layout">
                <div class="brehl-hr__panel">
                    <div class="brehl-hr__panel-head"><h3><?php esc_html_e('Mitarbeiter', 'brehl-intranet'); ?></h3><a href="<?php echo esc_url(remove_query_arg('employee_id')); ?>"><?php esc_html_e('Neu anlegen', 'brehl-intranet'); ?></a></div>
                    <div class="brehl-hr__people">
                    <?php foreach ($employees as $employee) :
                        $active = '0' !== get_user_meta($employee->ID, '_my_brehl_account_active', true);
                        $year = (int) wp_date('Y');
                        $entitlement = get_user_meta($employee->ID, 'brehl_vacation_entitlement_' . $year, true); ?>
                        <?php $employee_data = array(
                            'id' => (int) $employee->ID,
                            'first_name' => (string) $employee->first_name,
                            'last_name' => (string) $employee->last_name,
                            'email' => (string) $employee->user_email,
                            'personnel_number' => (string) get_user_meta($employee->ID, 'brehl_personnel_number', true),
                            'department' => (string) get_user_meta($employee->ID, 'my_brehl_department', true),
                            'position' => (string) get_user_meta($employee->ID, 'brehl_position', true),
                            'phone' => (string) get_user_meta($employee->ID, 'brehl_phone', true),
                            'location' => (string) get_user_meta($employee->ID, 'brehl_location', true),
                            'vacation_entitlement' => '' === $entitlement ? '30' : (string) $entitlement,
                            'vacation_carryover' => (string) get_user_meta($employee->ID, 'brehl_vacation_carryover_' . $year, true),
                            'directory_visible' => '0' !== get_user_meta($employee->ID, '_my_brehl_directory_visible', true),
                            'account_active' => $active,
                            'nonce' => wp_create_nonce('my_brehl_save_employee_' . (int) $employee->ID),
                        ); ?>
                        <article class="brehl-hr-person<?php echo $active ? '' : ' is-inactive'; ?>">
                            <span class="brehl-hr-person__avatar"><?php echo esc_html(mb_strtoupper(mb_substr($employee->display_name, 0, 1))); ?></span>
                            <div><strong><?php echo esc_html($employee->display_name); ?></strong><small><?php echo esc_html((string) get_user_meta($employee->ID, 'brehl_position', true) ?: __('Mitarbeiter', 'brehl-intranet')); ?> · <?php echo esc_html((string) get_user_meta($employee->ID, 'my_brehl_department', true) ?: __('Keine Abteilung', 'brehl-intranet')); ?></small></div>
                            <span class="brehl-hr-person__status"><?php echo $active ? esc_html__('Aktiv', 'brehl-intranet') : esc_html__('Deaktiviert', 'brehl-intranet'); ?></span>
                            <a href="<?php echo esc_url(add_query_arg('employee_id', $employee->ID)); ?>" data-brehl-edit-employee="<?php echo esc_attr(wp_json_encode($employee_data)); ?>"><?php esc_html_e('Bearbeiten', 'brehl-intranet'); ?></a>
                        </article>
                    <?php endforeach; ?>
                    <?php if (!$employees) : ?><p class="brehl-hr__empty"><?php esc_html_e('Noch keine Mitarbeiter angelegt.', 'brehl-intranet'); ?></p><?php endif; ?>
                    </div>
                </div>
                <div class="brehl-hr__panel">
                    <h3 data-brehl-employee-form-title><?php echo $editing ? esc_html__('Mitarbeiter bearbeiten', 'brehl-intranet') : esc_html__('Mitarbeiter anlegen', 'brehl-intranet'); ?></h3>
                    <?php echo $this->employee_form($editing); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            </div>
            <?php if (class_exists('Brehl_Vacation_Module')) : ?>
                <?php echo Brehl_Vacation_Module::instance()->management_panel(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php endif; ?>
            <?php if (class_exists('Brehl_Sick_Leave_Module')) : ?>
                <?php echo Brehl_Sick_Leave_Module::instance()->management_panel(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php endif; ?>
        </section>
        <?php return (string) ob_get_clean();
    }

    public function metrics_widget(): string {
        if (!is_user_logged_in() || !current_user_can('my_brehl_manage_people')) return '';
        wp_enqueue_style('brehl-intranet');
        $employees = get_users(array('role' => Brehl_Roles::EMPLOYEE_ROLE, 'fields' => 'ids'));
        $counts = $this->overview_counts();
        ob_start(); ?>
        <section class="brehl-hr">
            <header class="brehl-hr__header">
                <div><span><?php esc_html_e('Geschützter Bereich', 'brehl-intranet'); ?></span><h2><?php esc_html_e('Personalverwaltung', 'brehl-intranet'); ?></h2></div>
                <strong><?php echo esc_html(sprintf(__('%d Mitarbeiter', 'brehl-intranet'), count($employees))); ?></strong>
            </header>
            <div class="brehl-hr__metrics">
                <article><span><?php esc_html_e('Mitarbeiter', 'brehl-intranet'); ?></span><strong><?php echo esc_html((string) count($employees)); ?></strong></article>
                <article><span><?php esc_html_e('Offene Urlaubsanträge', 'brehl-intranet'); ?></span><strong><?php echo esc_html((string) $counts['vacation']); ?></strong></article>
                <article><span><?php esc_html_e('Neue Krankmeldungen', 'brehl-intranet'); ?></span><strong><?php echo esc_html((string) $counts['sick']); ?></strong></article>
                <article><span><?php esc_html_e('Offene Fahrzeugschäden', 'brehl-intranet'); ?></span><strong><?php echo esc_html((string) $counts['vehicle']); ?></strong></article>
            </div>
        </section>
        <?php return (string) ob_get_clean();
    }

    public function people_widget(): string {
        if (!is_user_logged_in() || !current_user_can('my_brehl_manage_people')) return '';
        wp_enqueue_style('brehl-intranet');
        wp_enqueue_script('brehl-intranet-hr');
        $employees = get_users(array('role' => Brehl_Roles::EMPLOYEE_ROLE, 'orderby' => 'display_name', 'order' => 'ASC'));
        $editing_id = absint($_GET['employee_id'] ?? 0);
        $editing = $editing_id ? get_userdata($editing_id) : null;
        if ($editing && !in_array(Brehl_Roles::EMPLOYEE_ROLE, (array) $editing->roles, true)) $editing = null;
        $result = sanitize_key($_GET['people_result'] ?? '');
        $year = (int) wp_date('Y');
        ob_start(); ?>
        <section class="brehl-hr">
            <?php if ($result) : ?><div class="brehl-hr__notice brehl-hr__notice--<?php echo esc_attr($result); ?>"><?php echo esc_html($this->result_message($result)); ?></div><?php endif; ?>
            <div class="brehl-hr__layout">
                <div class="brehl-hr__panel">
                    <div class="brehl-hr__panel-head"><h3><?php esc_html_e('Mitarbeiter', 'brehl-intranet'); ?></h3><a href="<?php echo esc_url(remove_query_arg('employee_id')); ?>"><?php esc_html_e('Neu anlegen', 'brehl-intranet'); ?></a></div>
                    <div class="brehl-hr__people">
                    <?php foreach ($employees as $employee) :
                        $active = '0' !== get_user_meta($employee->ID, '_my_brehl_account_active', true);
                        $entitlement = get_user_meta($employee->ID, 'brehl_vacation_entitlement_' . $year, true);
                        $employee_data = array(
                            'id'=>(int)$employee->ID, 'first_name'=>(string)$employee->first_name, 'last_name'=>(string)$employee->last_name,
                            'email'=>(string)$employee->user_email, 'personnel_number'=>(string)get_user_meta($employee->ID,'brehl_personnel_number',true),
                            'department'=>(string)get_user_meta($employee->ID,'my_brehl_department',true), 'position'=>(string)get_user_meta($employee->ID,'brehl_position',true),
                            'phone'=>(string)get_user_meta($employee->ID,'brehl_phone',true), 'location'=>(string)get_user_meta($employee->ID,'brehl_location',true),
                            'vacation_entitlement'=>'' === $entitlement ? '30' : (string)$entitlement,
                            'vacation_carryover'=>(string)get_user_meta($employee->ID,'brehl_vacation_carryover_' . $year,true),
                            'directory_visible'=>'0' !== get_user_meta($employee->ID,'_my_brehl_directory_visible',true), 'account_active'=>$active,
                            'nonce'=>wp_create_nonce('my_brehl_save_employee_' . (int)$employee->ID),
                        ); ?>
                        <article class="brehl-hr-person<?php echo $active ? '' : ' is-inactive'; ?>">
                            <span class="brehl-hr-person__avatar"><?php echo esc_html(mb_strtoupper(mb_substr($employee->display_name,0,1))); ?></span>
                            <div><strong><?php echo esc_html($employee->display_name); ?></strong><small><?php echo esc_html((string)get_user_meta($employee->ID,'brehl_position',true) ?: __('Mitarbeiter','brehl-intranet')); ?> · <?php echo esc_html((string)get_user_meta($employee->ID,'my_brehl_department',true) ?: __('Keine Abteilung','brehl-intranet')); ?></small></div>
                            <span class="brehl-hr-person__status"><?php echo $active ? esc_html__('Aktiv','brehl-intranet') : esc_html__('Deaktiviert','brehl-intranet'); ?></span>
                            <a href="<?php echo esc_url(add_query_arg('employee_id',$employee->ID)); ?>" data-brehl-edit-employee="<?php echo esc_attr(wp_json_encode($employee_data)); ?>"><?php esc_html_e('Bearbeiten','brehl-intranet'); ?></a>
                        </article>
                    <?php endforeach; ?>
                    <?php if (!$employees) : ?><p class="brehl-hr__empty"><?php esc_html_e('Noch keine Mitarbeiter angelegt.','brehl-intranet'); ?></p><?php endif; ?>
                    </div>
                </div>
                <div class="brehl-hr__panel">
                    <h3 data-brehl-employee-form-title><?php echo $editing ? esc_html__('Mitarbeiter bearbeiten','brehl-intranet') : esc_html__('Mitarbeiter anlegen','brehl-intranet'); ?></h3>
                    <?php echo $this->employee_form($editing); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            </div>
        </section>
        <?php return (string) ob_get_clean();
    }

    private function employee_form(?WP_User $user): string {
        $id = $user ? (int) $user->ID : 0;
        $year = (int) wp_date('Y');
        $value = static function (string $key) use ($user, $id): string {
            if (!$user) return '';
            if ('email' === $key) return (string) $user->user_email;
            if ('first_name' === $key) return (string) $user->first_name;
            if ('last_name' === $key) return (string) $user->last_name;
            return (string) get_user_meta($id, $key, true);
        };
        $active = !$user || '0' !== get_user_meta($id, '_my_brehl_account_active', true);
        ob_start(); ?>
        <form class="brehl-hr-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-brehl-employee-form data-create-nonce="<?php echo esc_attr(wp_create_nonce('my_brehl_save_employee_0')); ?>">
            <input type="hidden" name="action" value="my_brehl_save_employee">
            <input type="hidden" name="employee_id" value="<?php echo esc_attr((string) $id); ?>">
            <?php wp_nonce_field('my_brehl_save_employee_' . $id); ?>
            <label><span><?php esc_html_e('Vorname', 'brehl-intranet'); ?></span><input name="first_name" value="<?php echo esc_attr($value('first_name')); ?>" required></label>
            <label><span><?php esc_html_e('Nachname', 'brehl-intranet'); ?></span><input name="last_name" value="<?php echo esc_attr($value('last_name')); ?>" required></label>
            <label class="is-wide"><span><?php esc_html_e('E-Mail-Adresse', 'brehl-intranet'); ?></span><input name="email" type="email" value="<?php echo esc_attr($value('email')); ?>" required></label>
            <label><span><?php esc_html_e('Personalnummer', 'brehl-intranet'); ?></span><input name="personnel_number" value="<?php echo esc_attr($value('brehl_personnel_number')); ?>" required></label>
            <label><span><?php esc_html_e('Abteilung', 'brehl-intranet'); ?></span><input name="department" value="<?php echo esc_attr($value('my_brehl_department')); ?>"></label>
            <label><span><?php esc_html_e('Position', 'brehl-intranet'); ?></span><input name="position" value="<?php echo esc_attr($value('brehl_position')); ?>"></label>
            <label><span><?php esc_html_e('Telefon', 'brehl-intranet'); ?></span><input name="phone" value="<?php echo esc_attr($value('brehl_phone')); ?>"></label>
            <label><span><?php esc_html_e('Standort', 'brehl-intranet'); ?></span><input name="location" value="<?php echo esc_attr($value('brehl_location')); ?>"></label>
            <?php $entitlement = $user ? get_user_meta($id, 'brehl_vacation_entitlement_' . $year, true) : '30'; ?>
            <label><span><?php echo esc_html(sprintf(__('Urlaubsanspruch %d', 'brehl-intranet'), $year)); ?></span><input name="vacation_entitlement" type="number" step="0.5" min="0" max="365" value="<?php echo esc_attr('' === $entitlement ? '30' : (string) $entitlement); ?>" required></label>
            <label><span><?php echo esc_html(sprintf(__('Urlaubsübertrag %d', 'brehl-intranet'), $year)); ?></span><input name="vacation_carryover" type="number" step="0.5" min="-365" max="365" value="<?php echo esc_attr($user ? (string) get_user_meta($id, 'brehl_vacation_carryover_' . $year, true) : '0'); ?>"></label>
            <label class="is-wide"><span data-brehl-password-label><?php echo $user ? esc_html__('Neues Passwort (optional)', 'brehl-intranet') : esc_html__('Anfangspasswort', 'brehl-intranet'); ?></span><input name="password" type="password" autocomplete="new-password" minlength="12" <?php echo $user ? '' : 'required'; ?>><small><?php esc_html_e('Mindestens 12 Zeichen mit Groß- und Kleinbuchstaben, Zahl und Sonderzeichen.', 'brehl-intranet'); ?></small></label>
            <label class="is-wide"><span><?php esc_html_e('Passwort bestätigen', 'brehl-intranet'); ?></span><input name="password_confirm" type="password" autocomplete="new-password" minlength="12" <?php echo $user ? '' : 'required'; ?>></label>
            <label class="is-wide brehl-hr-form__check"><input name="directory_visible" type="checkbox" value="1" <?php checked('0' !== $value('_my_brehl_directory_visible')); ?>> <span><?php esc_html_e('Im Mitarbeiterverzeichnis anzeigen', 'brehl-intranet'); ?></span></label>
            <label class="is-wide brehl-hr-form__check" data-brehl-account-active<?php echo $user ? '' : ' hidden'; ?>><input name="account_active" type="checkbox" value="1" <?php checked($active); ?>> <span><?php esc_html_e('Anmeldung aktiv', 'brehl-intranet'); ?></span></label>
            <div class="is-wide brehl-hr-form__actions"><button type="submit" data-brehl-employee-submit><?php echo $user ? esc_html__('Änderungen speichern', 'brehl-intranet') : esc_html__('Mitarbeiter anlegen', 'brehl-intranet'); ?></button><button type="button" class="brehl-hr-form__cancel" data-brehl-employee-cancel<?php echo $user ? '' : ' hidden'; ?>><?php esc_html_e('Abbrechen', 'brehl-intranet'); ?></button></div>
            <div class="is-wide brehl-hr-form__reset" data-brehl-password-reset<?php echo $user ? '' : ' hidden'; ?>>
                <button type="submit" name="action" value="my_brehl_send_password_reset" formnovalidate><?php esc_html_e('Passwort-Link per E-Mail senden', 'brehl-intranet'); ?></button>
            </div>
        </form>
        <?php return (string) ob_get_clean();
    }

    public function handle_save_employee(): void {
        if (!is_user_logged_in() || !current_user_can('my_brehl_manage_people')) {
            wp_die(esc_html__('Keine Berechtigung.', 'brehl-intranet'), 403);
        }
        $id = absint($_POST['employee_id'] ?? 0);
        check_admin_referer('my_brehl_save_employee_' . $id);
        $existing = $id ? get_userdata($id) : null;
        if ($existing && !in_array(Brehl_Roles::EMPLOYEE_ROLE, (array) $existing->roles, true)) {
            $this->redirect('forbidden');
        }
        $first = sanitize_text_field(wp_unslash($_POST['first_name'] ?? ''));
        $last = sanitize_text_field(wp_unslash($_POST['last_name'] ?? ''));
        $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $personnel = sanitize_text_field(wp_unslash($_POST['personnel_number'] ?? ''));
        $password = (string) wp_unslash($_POST['password'] ?? '');
        $password_confirm = (string) wp_unslash($_POST['password_confirm'] ?? '');
        if (!$first || !$last || !is_email($email) || !$personnel) {
            $this->redirect('invalid');
        }
        if ((!$existing || '' !== $password) && (!Brehl_Roles::is_strong_password($password) || !hash_equals($password, $password_confirm))) {
            $this->redirect('weak_password');
        }
        $duplicate = get_users(array('meta_key' => 'brehl_personnel_number', 'meta_value' => $personnel, 'exclude' => $id ? array($id) : array(), 'number' => 1, 'fields' => 'ids'));
        if ($duplicate || (($owner = email_exists($email)) && (int) $owner !== $id)) {
            $this->redirect('duplicate');
        }
        if (!$existing) {
            $login = sanitize_user($personnel, true);
            if (!$login || username_exists($login)) $this->redirect('duplicate');
            $id = wp_insert_user(array(
                'user_login' => $login,
                'user_pass' => $password,
                'user_email' => $email,
                'first_name' => $first,
                'last_name' => $last,
                'display_name' => trim($first . ' ' . $last),
                'role' => Brehl_Roles::EMPLOYEE_ROLE,
            ));
            if (is_wp_error($id)) $this->redirect('error');
            $mail_result = retrieve_password($login);
        } else {
            $update_data = array('ID' => $id, 'user_email' => $email, 'first_name' => $first, 'last_name' => $last, 'display_name' => trim($first . ' ' . $last));
            if ('' !== $password) $update_data['user_pass'] = $password;
            $updated = wp_update_user($update_data);
            if (is_wp_error($updated)) $this->redirect('error');
        }
        update_user_meta((int) $id, 'brehl_personnel_number', $personnel);
        update_user_meta((int) $id, 'my_brehl_department', sanitize_text_field(wp_unslash($_POST['department'] ?? '')));
        update_user_meta((int) $id, 'brehl_position', sanitize_text_field(wp_unslash($_POST['position'] ?? '')));
        update_user_meta((int) $id, 'brehl_phone', sanitize_text_field(wp_unslash($_POST['phone'] ?? '')));
        update_user_meta((int) $id, 'brehl_location', sanitize_text_field(wp_unslash($_POST['location'] ?? '')));
        $year = (int) wp_date('Y');
        $entitlement = max(0, min(365, (float) str_replace(',', '.', (string) wp_unslash($_POST['vacation_entitlement'] ?? '30'))));
        $carryover = max(-365, min(365, (float) str_replace(',', '.', (string) wp_unslash($_POST['vacation_carryover'] ?? '0'))));
        update_user_meta((int) $id, 'brehl_vacation_entitlement_' . $year, $entitlement);
        update_user_meta((int) $id, 'brehl_vacation_carryover_' . $year, $carryover);
        update_user_meta((int) $id, '_my_brehl_directory_visible', isset($_POST['directory_visible']) ? '1' : '0');
        update_user_meta((int) $id, '_my_brehl_account_active', !$existing || isset($_POST['account_active']) ? '1' : '0');
        $this->redirect($existing ? 'updated' : (is_wp_error($mail_result) ? 'created_mail_failed' : 'created'));
    }

    public function handle_send_password_reset(): void {
        if (!is_user_logged_in() || !current_user_can('my_brehl_manage_people')) {
            wp_die(esc_html__('Keine Berechtigung.', 'brehl-intranet'), 403);
        }
        $id = absint($_POST['employee_id'] ?? 0);
        check_admin_referer('my_brehl_save_employee_' . $id);
        $user = $id ? get_userdata($id) : null;
        if (!$user || !in_array(Brehl_Roles::EMPLOYEE_ROLE, (array) $user->roles, true)) {
            $this->redirect('forbidden');
        }
        $result = retrieve_password($user->user_login);
        $this->redirect(is_wp_error($result) ? 'mail_failed' : 'mail_sent');
    }

    private function overview_counts(): array {
        global $wpdb;
        $vacation = $wpdb->prefix . 'brehl_vacation_requests';
        $vehicle = $wpdb->prefix . 'brehl_vehicle_damages';
        $sick = $wpdb->prefix . 'brehl_sick_leave';
        return array(
            'vacation' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$vacation} WHERE status='eingereicht'"),
            'sick' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$sick} WHERE status='gemeldet'"),
            'vehicle' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$vehicle} WHERE status IN ('neu','in_pruefung','beauftragt')"),
        );
    }

    private function result_message(string $result): string {
        return array(
            'created' => __('Mitarbeiter wurde angelegt und kann sich mit dem vergebenen Anfangspasswort anmelden.', 'brehl-intranet'),
            'created_mail_failed' => __('Mitarbeiter wurde angelegt, aber WordPress konnte die Passwort-E-Mail nicht versenden.', 'brehl-intranet'),
            'updated' => __('Mitarbeiter wurde aktualisiert.', 'brehl-intranet'),
            'mail_sent' => __('Der sichere Passwort-Link wurde per E-Mail versendet.', 'brehl-intranet'),
            'mail_failed' => __('WordPress konnte die Passwort-E-Mail nicht versenden. Bitte die Mail-Konfiguration prüfen.', 'brehl-intranet'),
            'duplicate' => __('E-Mail-Adresse oder Personalnummer wird bereits verwendet.', 'brehl-intranet'),
            'invalid' => __('Bitte alle Pflichtfelder korrekt ausfüllen.', 'brehl-intranet'),
            'weak_password' => __('Das Passwort stimmt nicht überein oder erfüllt die Sicherheitsanforderungen nicht.', 'brehl-intranet'),
            'forbidden' => __('Dieses Benutzerkonto darf hier nicht bearbeitet werden.', 'brehl-intranet'),
            'error' => __('Der Mitarbeiter konnte nicht gespeichert werden.', 'brehl-intranet'),
        )[$result] ?? '';
    }

    private function redirect(string $result): void {
        $url = remove_query_arg('employee_id', wp_get_referer() ?: home_url('/dashboard/'));
        wp_safe_redirect(add_query_arg('people_result', $result, $url));
        exit;
    }

}
