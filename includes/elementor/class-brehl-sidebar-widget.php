<?php
defined('ABSPATH') || exit;

use Elementor\Controls_Manager;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

final class Brehl_Sidebar_Widget extends Widget_Base {
    public function get_name(): string { return 'brehl-sidebar'; }
    public function get_title(): string { return __('Brehl Desktop Sidebar', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-nav-menu'; }
    public function get_categories(): array { return array('brehl-intranet'); }
    public function get_style_depends(): array { return array('brehl-intranet'); }

    protected function register_controls(): void {
        $this->start_controls_section('content', array('label' => __('Navigation', 'brehl-intranet')));
        $this->add_control('logo_text', array('label'=>__('Logo-Text','brehl-intranet'),'type'=>Controls_Manager::TEXT,'default'=>'BREHL'));
        $this->add_control('subtitle', array('label'=>__('Unterzeile','brehl-intranet'),'type'=>Controls_Manager::TEXT,'default'=>__('Mitarbeiterportal','brehl-intranet')));

        $r = new Repeater();
        $r->add_control('label', array('label'=>__('Bezeichnung','brehl-intranet'),'type'=>Controls_Manager::TEXT,'default'=>__('Dashboard','brehl-intranet')));
        $r->add_control('link', array('label'=>__('Link','brehl-intranet'),'type'=>Controls_Manager::URL,'default'=>array('url'=>'/dashboard')));
        $r->add_control('icon', array('label'=>__('Icon','brehl-intranet'),'type'=>Controls_Manager::ICONS,'default'=>array('value'=>'fas fa-home','library'=>'fa-solid')));
        $r->add_control('badge', array('label'=>__('Badge','brehl-intranet'),'type'=>Controls_Manager::TEXT,'placeholder'=>'3'));

        $this->add_control('items', array(
            'label'=>__('Menüpunkte','brehl-intranet'),
            'type'=>Controls_Manager::REPEATER,
            'fields'=>$r->get_controls(),
            'title_field'=>'{{{ label }}}',
            'default'=>array(
                array('label'=>'Dashboard','link'=>array('url'=>'/dashboard'),'icon'=>array('value'=>'fas fa-home','library'=>'fa-solid')),
                array('label'=>'News','link'=>array('url'=>'/news'),'icon'=>array('value'=>'fas fa-bullhorn','library'=>'fa-solid')),
                array('label'=>'Urlaub','link'=>array('url'=>'/urlaub'),'icon'=>array('value'=>'fas fa-umbrella-beach','library'=>'fa-solid')),
                array('label'=>'Dokumente','link'=>array('url'=>'/dokumente'),'icon'=>array('value'=>'fas fa-file-alt','library'=>'fa-solid')),
                array('label'=>'Stundenzettel','link'=>array('url'=>'/stundenzettel'),'icon'=>array('value'=>'fas fa-clock','library'=>'fa-solid')),
                array('label'=>'Mitarbeiter','link'=>array('url'=>'/mitarbeiter'),'icon'=>array('value'=>'fas fa-users','library'=>'fa-solid')),
                array('label'=>'Mehr','link'=>array('url'=>'/mehr'),'icon'=>array('value'=>'fas fa-ellipsis-h','library'=>'fa-solid'))
            )
        ));

        $this->add_control('show_logout', array('label'=>__('Abmelden anzeigen','brehl-intranet'),'type'=>Controls_Manager::SWITCHER,'return_value'=>'yes','default'=>'yes'));
        $this->end_controls_section();

        $this->start_controls_section('style', array('label'=>__('Design','brehl-intranet'),'tab'=>Controls_Manager::TAB_STYLE));
        $this->add_control('background', array('label'=>__('Hintergrund','brehl-intranet'),'type'=>Controls_Manager::COLOR,'default'=>'#17235A','selectors'=>array('{{WRAPPER}} .brehl-sidebar'=>'background: {{VALUE}};')));
        $this->add_control('active_color', array('label'=>__('Aktivfarbe','brehl-intranet'),'type'=>Controls_Manager::COLOR,'default'=>'#EC008C','selectors'=>array('{{WRAPPER}} .brehl-sidebar__item.is-active'=>'background: {{VALUE}};')));
        $this->end_controls_section();
    }

    protected function render(): void {
        if (!is_user_logged_in()) return;
        $s = $this->get_settings_for_display();
        $current_path = trailingslashit(wp_parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        ?>
        <aside class="brehl-sidebar">
            <div class="brehl-sidebar__brand">
                <strong><?php echo esc_html($s['logo_text']); ?></strong>
                <span><?php echo esc_html($s['subtitle']); ?></span>
            </div>
            <nav class="brehl-sidebar__nav" aria-label="<?php esc_attr_e('Hauptnavigation','brehl-intranet'); ?>">
                <?php foreach (($s['items'] ?? array()) as $item) :
                    $url = $item['link']['url'] ?? '#';
                    if (!$this->may_view_url($url)) continue;
                    $path = trailingslashit(wp_parse_url($url, PHP_URL_PATH) ?: '/');
                    $active = ('/' !== $path && str_contains($current_path, $path)); ?>
                    <a class="brehl-sidebar__item <?php echo $active ? 'is-active' : ''; ?>" href="<?php echo esc_url($url); ?>">
                        <span class="brehl-sidebar__icon"><?php Icons_Manager::render_icon($item['icon'], array('aria-hidden'=>'true')); ?></span>
                        <span class="brehl-sidebar__label"><?php echo esc_html($item['label']); ?></span>
                        <?php if (!empty($item['badge'])) : ?><span class="brehl-sidebar__badge"><?php echo esc_html($item['badge']); ?></span><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <?php if ('yes' === ($s['show_logout'] ?? '')) : ?>
                <a class="brehl-sidebar__logout" href="<?php echo esc_url(wp_logout_url(home_url('/login'))); ?>">
                    <span aria-hidden="true">↪</span><span><?php esc_html_e('Abmelden','brehl-intranet'); ?></span>
                </a>
            <?php endif; ?>
        </aside>
        <?php
    }

    private function may_view_url(string $url): bool {
        $path = (string) wp_parse_url($url, PHP_URL_PATH);
        $slug = sanitize_title(basename(untrailingslashit($path)));
        $management = array('mitarbeiterverwaltung','personalverwaltung','fuhrparkverwaltung','bekleidungsverwaltung');
        if (!in_array($slug, $management, true)) return true;
        return current_user_can('my_brehl_manage_system') || current_user_can('manage_options');
    }
}
