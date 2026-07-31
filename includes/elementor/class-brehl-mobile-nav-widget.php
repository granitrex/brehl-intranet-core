<?php
defined('ABSPATH') || exit;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

final class Brehl_Mobile_Nav_Widget extends Widget_Base {
    public function get_name(): string { return 'brehl-mobile-nav'; }
    public function get_title(): string { return __('Brehl Mobile Navigation', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-device-mobile'; }
    public function get_categories(): array { return array('brehl-intranet'); }
    public function get_style_depends(): array { return array('brehl-intranet'); }

    protected function register_controls(): void {
        $this->start_controls_section('content', array('label' => __('Menüpunkte', 'brehl-intranet')));
        $repeater = new Repeater();
        $repeater->add_control('label', array('label' => __('Bezeichnung', 'brehl-intranet'), 'type' => Controls_Manager::TEXT, 'default' => __('Menü', 'brehl-intranet')));
        $repeater->add_control('link', array('label' => __('Link', 'brehl-intranet'), 'type' => Controls_Manager::URL, 'default' => array('url' => '#')));
        $repeater->add_control('icon', array('label' => __('Icon', 'brehl-intranet'), 'type' => Controls_Manager::ICONS, 'default' => array('value' => 'fas fa-circle', 'library' => 'fa-solid')));
        $this->add_control('items', array(
            'label' => __('Navigation', 'brehl-intranet'),
            'type' => Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'title_field' => '{{{ label }}}',
            'default' => array(
                array('label' => 'Dashboard', 'link' => array('url' => '/dashboard'), 'icon' => array('value' => 'fas fa-home', 'library' => 'fa-solid')),
                array('label' => 'News', 'link' => array('url' => '/news'), 'icon' => array('value' => 'fas fa-bullhorn', 'library' => 'fa-solid')),
                array('label' => 'Urlaub', 'link' => array('url' => '/urlaub'), 'icon' => array('value' => 'fas fa-umbrella-beach', 'library' => 'fa-solid')),
                array('label' => 'Dokumente', 'link' => array('url' => '/dokumente'), 'icon' => array('value' => 'fas fa-file-alt', 'library' => 'fa-solid')),
                array('label' => 'Mehr', 'link' => array('url' => '/mehr'), 'icon' => array('value' => 'fas fa-bars', 'library' => 'fa-solid')),
            ),
        ));
        $this->end_controls_section();

        $this->start_controls_section('style', array('label' => __('Design', 'brehl-intranet'), 'tab' => Controls_Manager::TAB_STYLE));
        $this->add_control('background', array(
            'label' => __('Hintergrund', 'brehl-intranet'),
            'type' => Controls_Manager::COLOR,
            'default' => '#FFFFFF',
            'selectors' => array('{{WRAPPER}} .brehl-mobile-nav' => 'background: {{VALUE}};'),
        ));
        $this->add_control('color', array(
            'label' => __('Text und Icons', 'brehl-intranet'),
            'type' => Controls_Manager::COLOR,
            'default' => '#687386',
            'selectors' => array('{{WRAPPER}} .brehl-mobile-nav__item' => 'color: {{VALUE}};'),
        ));
        $this->add_control('active', array(
            'label' => __('Aktivfarbe', 'brehl-intranet'),
            'type' => Controls_Manager::COLOR,
            'default' => '#EC008C',
            'selectors' => array('{{WRAPPER}} .brehl-mobile-nav__item.is-active' => 'color: {{VALUE}};'),
        ));
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $current = trailingslashit(wp_parse_url(home_url(add_query_arg(array())), PHP_URL_PATH) ?: '/');
        ?>
        <nav class="brehl-mobile-nav" aria-label="<?php esc_attr_e('Mobile Navigation', 'brehl-intranet'); ?>">
            <?php foreach (($s['items'] ?? array()) as $item) :
                $url = $item['link']['url'] ?? '#';
                $path = trailingslashit(wp_parse_url($url, PHP_URL_PATH) ?: $url);
                $active = ('/' !== $path && str_contains($current, $path)) || ($path === '/dashboard/' && is_page('dashboard'));
                ?>
                <a class="brehl-mobile-nav__item <?php echo $active ? 'is-active' : ''; ?>" href="<?php echo esc_url($url); ?>">
                    <?php \Elementor\Icons_Manager::render_icon($item['icon'], array('aria-hidden' => 'true')); ?>
                    <span><?php echo esc_html($item['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php
    }
}
