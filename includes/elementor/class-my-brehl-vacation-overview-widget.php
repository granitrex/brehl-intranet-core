<?php
defined('ABSPATH') || exit;

use Elementor\Widget_Base;

final class My_Brehl_Vacation_Overview_Widget extends Widget_Base {
    public function get_name(): string { return 'my-brehl-vacation-overview'; }
    public function get_title(): string { return __('My Brehl – Urlaubsübersicht', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-counter'; }
    public function get_categories(): array { return array('brehl-intranet'); }
    public function get_style_depends(): array { return array('brehl-intranet', 'my-brehl-system'); }
    protected function render(): void { if (is_user_logged_in()) echo do_shortcode('[my_brehl_urlaub_uebersicht]'); }
}
