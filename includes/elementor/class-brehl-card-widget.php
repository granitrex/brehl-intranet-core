<?php
defined('ABSPATH') || exit;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Widget_Base;

final class Brehl_Card_Widget extends Widget_Base {
    public function get_name(): string { return 'brehl-card'; }
    public function get_title(): string { return __('Brehl Card','brehl-intranet'); }
    public function get_icon(): string { return 'eicon-call-to-action'; }
    public function get_categories(): array { return array('brehl-intranet'); }
    public function get_style_depends(): array { return array('brehl-intranet'); }

    protected function register_controls(): void {
        $this->start_controls_section('content',array('label'=>__('Inhalt','brehl-intranet')));
        $this->add_control('icon',array('label'=>__('Icon','brehl-intranet'),'type'=>Controls_Manager::ICONS,'default'=>array('value'=>'fas fa-newspaper','library'=>'fa-solid')));
        $this->add_control('title',array('label'=>__('Titel','brehl-intranet'),'type'=>Controls_Manager::TEXT,'default'=>__('News','brehl-intranet'),'label_block'=>true));
        $this->add_control('description',array('label'=>__('Beschreibung','brehl-intranet'),'type'=>Controls_Manager::TEXTAREA,'default'=>__('Aktuelle Mitteilungen und wichtige Informationen.','brehl-intranet'),'rows'=>3));
        $this->add_control('link',array('label'=>__('Link','brehl-intranet'),'type'=>Controls_Manager::URL,'placeholder'=>home_url('/news'),'default'=>array('url'=>home_url('/news'))));
        $this->add_control('badge_text',array('label'=>__('Badge','brehl-intranet'),'type'=>Controls_Manager::TEXT,'placeholder'=>__('z. B. 3 oder Neu','brehl-intranet')));
        $this->add_control('status',array('label'=>__('Status','brehl-intranet'),'type'=>Controls_Manager::SELECT,'default'=>'default','options'=>array('default'=>__('Standard','brehl-intranet'),'new'=>__('Neu','brehl-intranet'),'important'=>__('Wichtig','brehl-intranet'),'success'=>__('Erledigt','brehl-intranet'),'warning'=>__('Hinweis','brehl-intranet'))));
        $this->add_control('show_arrow',array('label'=>__('Pfeil anzeigen','brehl-intranet'),'type'=>Controls_Manager::SWITCHER,'return_value'=>'yes','default'=>'yes'));
        $this->end_controls_section();

        $this->start_controls_section('card_style',array('label'=>__('Karte','brehl-intranet'),'tab'=>Controls_Manager::TAB_STYLE));
        $this->add_control('background_color',array('label'=>__('Hintergrund','brehl-intranet'),'type'=>Controls_Manager::COLOR,'default'=>'#FFFFFF','selectors'=>array('{{WRAPPER}} .brehl-card'=>'background-color:{{VALUE}};')));
        $this->add_control('border_color',array('label'=>__('Rahmenfarbe','brehl-intranet'),'type'=>Controls_Manager::COLOR,'default'=>'#E8EBF2','selectors'=>array('{{WRAPPER}} .brehl-card'=>'border-color:{{VALUE}};')));
        $this->add_responsive_control('min_height',array('label'=>__('Mindesthöhe','brehl-intranet'),'type'=>Controls_Manager::SLIDER,'size_units'=>array('px'),'range'=>array('px'=>array('min'=>110,'max'=>320)),'default'=>array('unit'=>'px','size'=>170),'mobile_default'=>array('unit'=>'px','size'=>150),'selectors'=>array('{{WRAPPER}} .brehl-card'=>'min-height:{{SIZE}}{{UNIT}};')));
        $this->add_responsive_control('padding',array('label'=>__('Innenabstand','brehl-intranet'),'type'=>Controls_Manager::DIMENSIONS,'size_units'=>array('px'),'default'=>array('top'=>20,'right'=>20,'bottom'=>20,'left'=>20,'unit'=>'px','isLinked'=>true),'mobile_default'=>array('top'=>16,'right'=>16,'bottom'=>16,'left'=>16,'unit'=>'px','isLinked'=>true),'selectors'=>array('{{WRAPPER}} .brehl-card'=>'padding:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};')));
        $this->add_control('radius',array('label'=>__('Eckenradius','brehl-intranet'),'type'=>Controls_Manager::SLIDER,'size_units'=>array('px'),'range'=>array('px'=>array('min'=>0,'max'=>40)),'default'=>array('unit'=>'px','size'=>18),'selectors'=>array('{{WRAPPER}} .brehl-card'=>'border-radius:{{SIZE}}{{UNIT}};')));
        $this->add_group_control(Group_Control_Box_Shadow::get_type(),array('name'=>'box_shadow','selector'=>'{{WRAPPER}} .brehl-card'));
        $this->add_control('hover_effect',array('label'=>__('Hover-Effekt','brehl-intranet'),'type'=>Controls_Manager::SELECT,'default'=>'lift','options'=>array('none'=>__('Keiner','brehl-intranet'),'lift'=>__('Anheben','brehl-intranet'),'border'=>__('Rahmen hervorheben','brehl-intranet'),'scale'=>__('Leicht vergrößern','brehl-intranet'))));
        $this->end_controls_section();

        $this->start_controls_section('icon_style',array('label'=>__('Icon','brehl-intranet'),'tab'=>Controls_Manager::TAB_STYLE));
        $this->add_control('icon_color',array('label'=>__('Iconfarbe','brehl-intranet'),'type'=>Controls_Manager::COLOR,'default'=>'#EC008C','selectors'=>array('{{WRAPPER}} .brehl-card__icon'=>'color:{{VALUE}};')));
        $this->add_control('icon_background',array('label'=>__('Icon-Hintergrund','brehl-intranet'),'type'=>Controls_Manager::COLOR,'default'=>'#FDE7F4','selectors'=>array('{{WRAPPER}} .brehl-card__icon-wrap'=>'background-color:{{VALUE}};')));
        $this->end_controls_section();

        $this->start_controls_section('type_style',array('label'=>__('Typografie','brehl-intranet'),'tab'=>Controls_Manager::TAB_STYLE));
        $this->add_control('title_color',array('label'=>__('Titelfarbe','brehl-intranet'),'type'=>Controls_Manager::COLOR,'default'=>'#17235A','selectors'=>array('{{WRAPPER}} .brehl-card__title'=>'color:{{VALUE}};')));
        $this->add_group_control(Group_Control_Typography::get_type(),array('name'=>'title_typography','selector'=>'{{WRAPPER}} .brehl-card__title'));
        $this->add_control('description_color',array('label'=>__('Beschreibung','brehl-intranet'),'type'=>Controls_Manager::COLOR,'default'=>'#687386','selectors'=>array('{{WRAPPER}} .brehl-card__description'=>'color:{{VALUE}};')));
        $this->add_group_control(Group_Control_Typography::get_type(),array('name'=>'description_typography','selector'=>'{{WRAPPER}} .brehl-card__description'));
        $this->end_controls_section();
    }

    protected function render(): void {
        $s=$this->get_settings_for_display();
        $url=$s['link']['url']??'#';
        $target=!empty($s['link']['is_external'])?' target="_blank"':'';
        $rel=!empty($s['link']['nofollow'])?' rel="nofollow"':'';
        $hover=sanitize_html_class($s['hover_effect']??'lift');
        $status=sanitize_html_class($s['status']??'default'); ?>
        <a class="brehl-card brehl-card--hover-<?php echo esc_attr($hover); ?> brehl-card--status-<?php echo esc_attr($status); ?>" href="<?php echo esc_url($url?:'#'); ?>"<?php echo $target.$rel; ?>>
            <div class="brehl-card__top">
                <span class="brehl-card__icon-wrap"><span class="brehl-card__icon"><?php Icons_Manager::render_icon($s['icon'],array('aria-hidden'=>'true')); ?></span></span>
                <?php if(!empty($s['badge_text'])): ?><span class="brehl-card__badge"><?php echo esc_html($s['badge_text']); ?></span><?php endif; ?>
            </div>
            <div class="brehl-card__content">
                <div class="brehl-card__title-row"><h3 class="brehl-card__title"><?php echo esc_html($s['title']); ?></h3><?php if('yes'===($s['show_arrow']??'')): ?><span class="brehl-card__arrow" aria-hidden="true">→</span><?php endif; ?></div>
                <?php if(!empty($s['description'])): ?><p class="brehl-card__description"><?php echo esc_html($s['description']); ?></p><?php endif; ?>
            </div>
        </a><?php
    }
}
