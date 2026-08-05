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
            'saved'=>array('success',__('Ihre Profildaten wurden gespeichert.','brehl-intranet')),
            'invalid_email'=>array('error',__('Bitte geben Sie eine gültige E-Mail-Adresse ein.','brehl-intranet')),
            'email_exists'=>array('error',__('Diese E-Mail-Adresse wird bereits verwendet.','brehl-intranet')),
            'weak_password'=>array('error',__('Das Passwort stimmt nicht überein oder erfüllt die Sicherheitsanforderungen nicht.','brehl-intranet')),
            'error'=>array('error',__('Die Profildaten konnten nicht gespeichert werden.','brehl-intranet')),
        );
        $initials=mb_strtoupper(mb_substr($user->first_name?:$user->display_name,0,1).mb_substr($user->last_name,0,1));
        ?>
        <section class="mbs-account"><div class="mbs-card">
            <div class="mbs-account__identity"><span class="mbs-account__avatar"><?php echo esc_html($initials?:'MB'); ?></span><div><strong><?php echo esc_html($user->display_name); ?></strong><small><?php echo esc_html($meta('brehl_position')?:__('Mitarbeiter','brehl-intranet')); ?></small></div></div>
            <?php if(isset($messages[$result])): ?><div class="mbs-form-message is-<?php echo esc_attr($messages[$result][0]); ?>"><?php echo esc_html($messages[$result][1]); ?></div><?php endif; ?>
            <div class="mbs-account__section"><h3><?php esc_html_e('Stammdaten','brehl-intranet'); ?></h3><p><?php esc_html_e('Diese Angaben werden von der Personalverwaltung gepflegt.','brehl-intranet'); ?></p><div class="mbs-account__facts">
                <?php foreach(array(__('Vorname','brehl-intranet')=>$user->first_name,__('Nachname','brehl-intranet')=>$user->last_name,__('Personalnummer','brehl-intranet')=>$meta('brehl_personnel_number'),__('Abteilung','brehl-intranet')=>$meta('my_brehl_department'),__('Position','brehl-intranet')=>$meta('brehl_position'),__('Standort','brehl-intranet')=>$meta('brehl_location'),__('Kennzeichen','brehl-intranet')=>$meta('brehl_vehicle_license_plate')) as $label=>$value): ?><div><span><?php echo esc_html($label); ?></span><strong><?php echo esc_html($value?:'–'); ?></strong></div><?php endforeach; ?>
            </div></div>
            <form class="mbs-account__form mbs-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="brehl_save_own_profile"><?php wp_nonce_field('brehl_save_own_profile'); ?>
                <div class="mbs-account__section"><h3><?php esc_html_e('Kontaktdaten','brehl-intranet'); ?></h3><div class="mbs-form-grid"><label><span><?php esc_html_e('E-Mail-Adresse','brehl-intranet'); ?></span><input type="email" name="email" required value="<?php echo esc_attr($user->user_email); ?>"></label><label><span><?php esc_html_e('Telefon','brehl-intranet'); ?></span><input name="phone" value="<?php echo esc_attr($meta('brehl_phone')); ?>"></label><label><span><?php esc_html_e('Sprache','brehl-intranet'); ?></span><select name="language"><option value="de" <?php selected($meta('brehl_language')?:'de','de'); ?>>Deutsch</option><option value="en" <?php selected($meta('brehl_language'),'en'); ?>>English</option><option value="sq" <?php selected($meta('brehl_language'),'sq'); ?>>Shqip</option></select></label></div></div>
                <div class="mbs-account__section"><h3><?php esc_html_e('Passwort ändern','brehl-intranet'); ?></h3><p><?php esc_html_e('Nur ausfüllen, wenn Sie ein neues Passwort vergeben möchten.','brehl-intranet'); ?></p><div class="mbs-form-grid"><label><span><?php esc_html_e('Neues Passwort','brehl-intranet'); ?></span><input type="password" name="password" minlength="12" autocomplete="new-password"><small><?php esc_html_e('Mindestens 12 Zeichen mit Groß- und Kleinbuchstaben, Zahl und Sonderzeichen.','brehl-intranet'); ?></small></label><label><span><?php esc_html_e('Passwort bestätigen','brehl-intranet'); ?></span><input type="password" name="password_confirm" minlength="12" autocomplete="new-password"></label></div></div>
                <button class="mbs-primary-button" type="submit"><?php esc_html_e('Profil speichern','brehl-intranet'); ?></button>
            </form>
        </div></section><?php
    }
}
