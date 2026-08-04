<?php
defined('ABSPATH') || exit;
use Elementor\Widget_Base;
final class My_Brehl_Sick_Request_Widget extends Widget_Base {
    public function get_name(): string { return 'my-brehl-sick-request'; }
    public function get_title(): string { return __('My Brehl – Krankmeldung einreichen','brehl-intranet'); }
    public function get_icon(): string { return 'eicon-form-horizontal'; }
    public function get_categories(): array { return array('brehl-intranet'); }
    public function get_style_depends(): array { return array('brehl-intranet','my-brehl-system'); }
    public function get_script_depends(): array { return array('brehl-intranet-vacation'); }
    protected function render(): void { if(is_user_logged_in()) echo do_shortcode('[my_brehl_krank_melden]'); }
}
