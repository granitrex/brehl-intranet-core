<?php
defined('ABSPATH') || exit;

final class My_Brehl_Account_Widget extends \Elementor\Widget_Base {
    public function get_name(): string { return 'my_brehl_account'; }
    public function get_title(): string { return __('My Brehl – Mein Profil', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-user-circle-o'; }
    public function get_categories(): array { return array('brehl-mitarbeiter'); }
    public function get_style_depends(): array { return array('brehl-intranet'); }

    protected function render(): void {
        if(!is_user_logged_in()) return;
        $user=wp_get_current_user();
        $meta=static fn(string $key):string=>(string)get_user_meta($user->ID,$key,true);
        $result=sanitize_key($_GET['brehl_profile']??'');
        $messages=array(
            'saved'=>array('success',__('Ihr neues Passwort wurde gespeichert.','brehl-intranet')),
            'weak_password'=>array('error',__('Das Passwort stimmt nicht überein oder erfüllt die Sicherheitsanforderungen nicht.','brehl-intranet')),
            'error'=>array('error',__('Die Profildaten konnten nicht gespeichert werden.','brehl-intranet')),
        );
        $initials=mb_strtoupper(mb_substr($user->first_name?:$user->display_name,0,1).mb_substr($user->last_name,0,1));
        ?>
        <section class="mbs-account"><div class="mbs-card">
            <div class="mbs-account__identity"><span class="mbs-account__avatar"><?php echo esc_html($initials?:'MB'); ?></span><div><strong><?php echo esc_html($user->display_name); ?></strong><small><?php echo esc_html($meta('brehl_position')?:__('Mitarbeiter','brehl-intranet')); ?></small></div></div>
            <?php if(isset($messages[$result])): ?><div class="mbs-form-message is-<?php echo esc_attr($messages[$result][0]); ?>"><?php echo esc_html($messages[$result][1]); ?></div><?php endif; ?>
            <div class="mbs-account__section"><h3><?php esc_html_e('Stammdaten','brehl-intranet'); ?></h3><p><?php esc_html_e('Diese Angaben werden von der Personalverwaltung gepflegt.','brehl-intranet'); ?></p><div class="mbs-account__facts">
                <?php foreach(array(__('Vorname','brehl-intranet')=>$user->first_name,__('Nachname','brehl-intranet')=>$user->last_name,__('Personalnummer','brehl-intranet')=>$meta('brehl_personnel_number'),__('E-Mail-Adresse','brehl-intranet')=>$user->user_email,__('Telefon','brehl-intranet')=>$meta('brehl_phone'),__('Abteilung','brehl-intranet')=>$meta('my_brehl_department'),__('Position','brehl-intranet')=>$meta('brehl_position'),__('Standort','brehl-intranet')=>$meta('brehl_location'),__('Kennzeichen','brehl-intranet')=>$meta('brehl_vehicle_license_plate')) as $label=>$value): ?><div><span><?php echo esc_html($label); ?></span><strong><?php echo esc_html($value?:'–'); ?></strong></div><?php endforeach; ?>
            </div></div>
            <div class="mbs-account__section"><h3><?php esc_html_e('Mein Urlaubskonto','brehl-intranet'); ?></h3><p><?php esc_html_e('Aktueller Anspruch, genehmigte, beantragte und verfügbare Urlaubstage.','brehl-intranet'); ?></p><?php echo do_shortcode('[my_brehl_urlaub_uebersicht]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
            <form class="mbs-account__form mbs-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="brehl_save_own_profile"><?php wp_nonce_field('brehl_save_own_profile'); ?>
                <div class="mbs-account__section"><h3><?php esc_html_e('Passwort ändern','brehl-intranet'); ?></h3><p><?php esc_html_e('Nur das Passwort kann vom Mitarbeiter selbst geändert werden. Alle persönlichen Daten pflegt die Personalverwaltung.','brehl-intranet'); ?></p><div class="mbs-form-grid"><label><span><?php esc_html_e('Neues Passwort','brehl-intranet'); ?></span><input type="password" name="password" minlength="12" autocomplete="new-password" required><small><?php esc_html_e('Mindestens 12 Zeichen mit Groß- und Kleinbuchstaben, Zahl und Sonderzeichen.','brehl-intranet'); ?></small></label><label><span><?php esc_html_e('Passwort bestätigen','brehl-intranet'); ?></span><input type="password" name="password_confirm" minlength="12" autocomplete="new-password" required></label></div></div>
                <button class="mbs-primary-button" type="submit"><?php esc_html_e('Neues Passwort speichern','brehl-intranet'); ?></button>
            </form>
        </div></section><?php
    }
}
