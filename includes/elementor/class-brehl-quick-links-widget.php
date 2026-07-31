<?php
defined('ABSPATH') || exit;

use Elementor\Controls_Manager;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

final class Brehl_Quick_Links_Widget extends Widget_Base {
    public function get_name(): string { return 'brehl-quick-links'; }
    public function get_title(): string { return __('Brehl Schnellzugriffe', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-gallery-grid'; }
    public function get_categories(): array { return array('brehl-intranet'); }
    public function get_style_depends(): array { return array('brehl-intranet'); }

    protected function register_controls(): void {
        $this->start_controls_section('content', array('label' => __('Schnellzugriffe', 'brehl-intranet')));
        $this->add_control('heading', array(
            'label' => __('Bereichsüberschrift', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => __('Schnellzugriffe', 'brehl-intranet'),
        ));
        $this->add_control('show_heading', array(
            'label' => __('Überschrift anzeigen', 'brehl-intranet'),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => 'yes',
        ));

        $r = new Repeater();
        $r->add_control('icon', array(
            'label' => __('Icon', 'brehl-intranet'),
            'type' => Controls_Manager::ICONS,
            'default' => array('value'=>'fas fa-newspaper','library'=>'fa-solid'),
        ));
        $r->add_control('title', array(
            'label' => __('Titel', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'default' => __('News', 'brehl-intranet'),
        ));
        $r->add_control('description', array(
            'label' => __('Beschreibung', 'brehl-intranet'),
            'type' => Controls_Manager::TEXTAREA,
            'default' => __('Aktuelle Mitteilungen und wichtige Informationen.', 'brehl-intranet'),
        ));
        $r->add_control('link', array(
            'label' => __('Link', 'brehl-intranet'),
            'type' => Controls_Manager::URL,
            'default' => array('url'=>'#'),
        ));
        $r->add_control('badge', array(
            'label' => __('Badge', 'brehl-intranet'),
            'type' => Controls_Manager::TEXT,
            'placeholder' => __('z. B. 3 oder Neu', 'brehl-intranet'),
        ));
        $r->add_control('icon_color', array(
            'label' => __('Iconfarbe', 'brehl-intranet'),
            'type' => Controls_Manager::COLOR,
            'default' => '#EC008C',
        ));
        $r->add_control('icon_background', array(
            'label' => __('Icon-Hintergrund', 'brehl-intranet'),
            'type' => Controls_Manager::COLOR,
            'default' => '#FDE7F4',
        ));

        $this->add_control('items', array(
            'label' => __('Karten', 'brehl-intranet'),
            'type' => Controls_Manager::REPEATER,
            'fields' => $r->get_controls(),
            'title_field' => '{{{ title }}}',
            'default' => array(
                array('title'=>'News','description'=>'Aktuelle Mitteilungen und wichtige Informationen.','link'=>array('url'=>'/news'),'icon'=>array('value'=>'fas fa-newspaper','library'=>'fa-solid'),'icon_color'=>'#EC008C','icon_background'=>'#FDE7F4'),
                array('title'=>'Urlaubsantrag','description'=>'Urlaub beantragen und den Bearbeitungsstand prüfen.','link'=>array('url'=>'/urlaub'),'icon'=>array('value'=>'fas fa-umbrella-beach','library'=>'fa-solid'),'icon_color'=>'#EC008C','icon_background'=>'#FDE7F4'),
                array('title'=>'Dokumente','description'=>'Formulare, Nachweise und persönliche Unterlagen.','link'=>array('url'=>'/dokumente'),'icon'=>array('value'=>'fas fa-file-alt','library'=>'fa-solid'),'icon_color'=>'#EC008C','icon_background'=>'#FDE7F4'),
                array('title'=>'Stundenzettel','description'=>'Arbeitszeiten und Stundennachweise aufrufen.','link'=>array('url'=>'/stundenzettel'),'icon'=>array('value'=>'fas fa-clock','library'=>'fa-solid'),'icon_color'=>'#EC008C','icon_background'=>'#FDE7F4'),
            ),
        ));
        $this->end_controls_section();

        $this->start_controls_section('layout', array(
            'label' => __('Layout', 'brehl-intranet'),
            'tab' => Controls_Manager::TAB_STYLE,
        ));
        $this->add_responsive_control('columns', array(
            'label' => __('Spalten', 'brehl-intranet'),
            'type' => Controls_Manager::SELECT,
            'default' => '4',
            'tablet_default' => '2',
            'mobile_default' => '2',
            'options' => array('1'=>'1','2'=>'2','3'=>'3','4'=>'4'),
            'selectors' => array('{{WRAPPER}} .brehl-quick-links__grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0,1fr));'),
        ));
        $this->add_responsive_control('gap', array(
            'label' => __('Abstand', 'brehl-intranet'),
            'type' => Controls_Manager::SLIDER,
            'range' => array('px'=>array('min'=>0,'max'=>40)),
            'default' => array('unit'=>'px','size'=>16),
            'mobile_default' => array('unit'=>'px','size'=>12),
            'selectors' => array('{{WRAPPER}} .brehl-quick-links__grid' => 'gap: {{SIZE}}{{UNIT}};'),
        ));
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        ?>
        <section class="brehl-quick-links">
            <?php if ('yes' === ($s['show_heading'] ?? '')) : ?>
                <div class="brehl-section-heading">
                    <h2><?php echo esc_html($s['heading']); ?></h2>
                </div>
            <?php endif; ?>
            <div class="brehl-quick-links__grid">
                <?php foreach (($s['items'] ?? array()) as $item) :
                    $url = $item['link']['url'] ?? '#';
                    $target = !empty($item['link']['is_external']) ? ' target="_blank"' : '';
                    $rel = !empty($item['link']['nofollow']) ? ' rel="nofollow"' : '';
                    ?>
                    <a class="brehl-card brehl-card--hover-lift" href="<?php echo esc_url($url); ?>"<?php echo $target . $rel; ?>>
                        <div class="brehl-card__top">
                            <span class="brehl-card__icon-wrap" style="background:<?php echo esc_attr($item['icon_background']); ?>">
                                <span class="brehl-card__icon" style="color:<?php echo esc_attr($item['icon_color']); ?>">
                                    <?php Icons_Manager::render_icon($item['icon'], array('aria-hidden'=>'true')); ?>
                                </span>
                            </span>
                            <?php if (!empty($item['badge'])) : ?>
                                <span class="brehl-card__badge"><?php echo esc_html($item['badge']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="brehl-card__content">
                            <div class="brehl-card__title-row">
                                <h3 class="brehl-card__title"><?php echo esc_html($item['title']); ?></h3>
                                <span class="brehl-card__arrow" aria-hidden="true">→</span>
                            </div>
                            <p class="brehl-card__description"><?php echo esc_html($item['description']); ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }
}
