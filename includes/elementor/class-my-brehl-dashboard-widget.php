<?php
defined('ABSPATH') || exit;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

final class My_Brehl_Dashboard_Widget extends Widget_Base {
    public function get_name(): string { return 'my-brehl-dashboard'; }
    public function get_title(): string { return __('My Brehl – Dashboard 2.0', 'brehl-intranet'); }
    public function get_icon(): string { return 'eicon-dashboard'; }
    public function get_categories(): array { return array('brehl-intranet'); }
    public function get_style_depends(): array { return array('brehl-intranet'); }
    public function get_script_depends(): array { return array('brehl-intranet-news'); }

    protected function register_controls(): void {
        $this->start_controls_section('content', array('label' => __('Dashboard', 'brehl-intranet')));
        $this->add_control('news_limit', array('label'=>__('Anzahl Unternehmensnews','brehl-intranet'),'type'=>Controls_Manager::NUMBER,'default'=>3,'min'=>1,'max'=>6));
        $this->add_control('news_url', array('label'=>__('Unternehmensnews-Seite','brehl-intranet'),'type'=>Controls_Manager::URL,'default'=>array('url'=>'/news')));
        $this->add_control('documents_url', array('label'=>__('Dokumente-Seite','brehl-intranet'),'type'=>Controls_Manager::URL,'default'=>array('url'=>'/dokumente')));
        $this->add_control('vacation_url', array('label'=>__('Urlaubs-Seite','brehl-intranet'),'type'=>Controls_Manager::URL,'default'=>array('url'=>'/urlaub')));
        $this->add_control('employees_url', array('label'=>__('Mitarbeiter-Seite','brehl-intranet'),'type'=>Controls_Manager::URL,'default'=>array('url'=>'/mitarbeiter')));
        $this->add_control('fleet_url', array('label'=>__('Fuhrpark-Seite','brehl-intranet'),'type'=>Controls_Manager::URL,'default'=>array('url'=>'/fuhrpark')));
        $this->end_controls_section();
    }

    private static function icon(string $name): string {
        $icons = array(
            'home'=>'<svg viewBox="0 0 24 24"><path d="M3 11.5 12 4l9 7.5v8a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>',
            'news'=>'<svg viewBox="0 0 24 24"><path d="M4 5h13a2 2 0 0 1 2 2v12H6a2 2 0 0 1-2-2zm3 3v2h8V8zm0 5v2h8v-2zM19 9h1a1 1 0 0 1 1 1v8a2 2 0 0 1-2 2z"/></svg>',
            'bell'=>'<svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9m-8 11h4a2 2 0 0 1-4 0"/></svg>',
            'doc'=>'<svg viewBox="0 0 24 24"><path d="M6 2h8l4 4v16H6zm8 1.5V7h3.5M9 11h6v2H9zm0 4h6v2H9z"/></svg>',
            'vacation'=>'<svg viewBox="0 0 24 24"><path d="M4 20h16v2H4zm8-18c4 0 7 3 7 7H5c0-4 3-7 7-7m-1 7v11h2V9z"/></svg>',
            'people'=>'<svg viewBox="0 0 24 24"><path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8m6-2a3 3 0 1 0 0-6m-6 10H2v-2c0-3 3-5 7-5s7 2 7 5v2zm7-7c4 0 6 2 6 5v2h-4v-2c0-2-1-4-3-5z"/></svg>',
            'fleet'=>'<svg viewBox="0 0 24 24"><path d="m5 6 2-3h10l2 3 2 2v9h-2v2h-3v-2H8v2H5v-2H3V8zm2.5-1L6 8h12l-1.5-3zM7 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4m10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4"/></svg>',
            'settings'=>'<svg viewBox="0 0 24 24"><path d="M19.4 13a7 7 0 0 0 0-2l2-1.5-2-3.5-2.4 1a8 8 0 0 0-1.7-1L15 3h-4l-.4 3a8 8 0 0 0-1.7 1L6.5 6l-2 3.5L6.6 11a7 7 0 0 0 0 2l-2 1.5 2 3.5 2.4-1a8 8 0 0 0 1.7 1L11 21h4l.4-3a8 8 0 0 0 1.7-1l2.4 1 2-3.5zM13 15a3 3 0 1 1 0-6 3 3 0 0 1 0 6"/></svg>',
            'search'=>'<svg viewBox="0 0 24 24"><path d="m21 20-5-5a7 7 0 1 0-1 1l5 5zM5 10a5 5 0 1 1 10 0 5 5 0 0 1-10 0"/></svg>',
        );
        return $icons[$name] ?? '';
    }

    protected function render(): void {
        if (!is_user_logged_in()) return;
        $s = $this->get_settings_for_display();
        $user = wp_get_current_user();
        $name = $user->first_name ?: $user->display_name;
        $initial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($name, 0, 1)) : strtoupper(substr($name, 0, 1));
        $hour = (int) current_time('G');
        $greeting = $hour < 12 ? 'Guten Morgen' : ($hour < 18 ? 'Guten Tag' : 'Guten Abend');
        $urls = array(
            'news'=>$s['news_url']['url'] ?? '/news', 'doc'=>$s['documents_url']['url'] ?? '/dokumente',
            'vacation'=>$s['vacation_url']['url'] ?? '/urlaub', 'people'=>$s['employees_url']['url'] ?? '/mitarbeiter',
            'fleet'=>$s['fleet_url']['url'] ?? '/fuhrpark'
        );
        ?>
        <div class="mybrehl-app">
            <aside class="mybrehl-app__sidebar">
                <a class="mybrehl-app__brand" href="<?php echo esc_url(home_url('/dashboard')); ?>"><strong>My Brehl</strong><span>Mitarbeiterportal</span></a>
                <nav class="mybrehl-app__nav" aria-label="Hauptnavigation">
                    <a class="is-active" href="<?php echo esc_url(home_url('/dashboard')); ?>"><?php echo self::icon('home'); ?><span>Startseite</span></a>
                    <a href="<?php echo esc_url($urls['news']); ?>"><?php echo self::icon('news'); ?><span>Unternehmensnews</span></a>
                    <a href="#mybrehl-benachrichtigungen"><?php echo self::icon('bell'); ?><span>Benachrichtigungen</span></a>
                    <a href="<?php echo esc_url($urls['doc']); ?>"><?php echo self::icon('doc'); ?><span>Dokumente</span></a>
                    <a href="<?php echo esc_url($urls['vacation']); ?>"><?php echo self::icon('vacation'); ?><span>Urlaub</span></a>
                    <a href="<?php echo esc_url($urls['people']); ?>"><?php echo self::icon('people'); ?><span>Mitarbeiter</span></a>
                    <a href="<?php echo esc_url($urls['fleet']); ?>"><?php echo self::icon('fleet'); ?><span>Fuhrpark</span><em>Später</em></a>
                </nav>
                <div class="mybrehl-app__sidebar-bottom">
                    <a href="<?php echo esc_url(admin_url('profile.php')); ?>"><?php echo self::icon('settings'); ?><span>Einstellungen</span></a>
                    <a class="mybrehl-app__profile" href="<?php echo esc_url(wp_logout_url(home_url('/login'))); ?>"><b><?php echo esc_html($initial); ?></b><span><strong><?php echo esc_html($name); ?></strong><small>Abmelden</small></span></a>
                </div>
            </aside>

            <main class="mybrehl-app__main">
                <header class="mybrehl-app__topbar">
                    <label class="mybrehl-app__search"><?php echo self::icon('search'); ?><input type="search" placeholder="News, Dokumente oder Personen suchen …" aria-label="My Brehl durchsuchen"></label>
                    <div class="mybrehl-app__top-actions"><button type="button" class="mybrehl-app__bell" aria-label="Benachrichtigungen"><?php echo self::icon('bell'); ?><span class="brehl-notification-count">0</span></button><span class="mybrehl-app__language">DE</span><span class="mybrehl-app__avatar"><?php echo esc_html($initial); ?></span><strong><?php echo esc_html($name); ?></strong></div>
                </header>

                <section class="mybrehl-app__hero">
                    <div><p>MY BREHL</p><h1><?php echo esc_html($greeting . ', ' . $name); ?> <span aria-hidden="true">👋</span></h1><h2>Schön, dass Sie da sind.</h2><time><?php echo esc_html(wp_date('l, j. F Y')); ?></time></div>
                    <div class="mybrehl-app__metrics">
                        <a href="<?php echo esc_url($urls['news']); ?>"><i></i><strong class="brehl-unread-news-count">0</strong><span>Neue News</span></a>
                        <a href="<?php echo esc_url($urls['doc']); ?>"><i></i><strong>—</strong><span>Neue Dokumente</span></a>
                        <a href="<?php echo esc_url($urls['vacation']); ?>"><i></i><strong>—</strong><span>Resturlaub</span></a>
                    </div>
                </section>

                <section class="mybrehl-app__news">
                    <?php if (class_exists('Brehl_News_Module')) {
                        echo Brehl_News_Module::instance()->render_feed((int)($s['news_limit'] ?? 3), 'Unternehmensnews', '', $urls['news'], false); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    } ?>
                </section>

                <section class="mybrehl-app__modules"><div class="mybrehl-app__section-title"><div><span>Schnellzugriffe</span><h2>Was möchten Sie erledigen?</h2></div></div><div class="mybrehl-app__module-grid">
                    <a href="<?php echo esc_url($urls['news']); ?>"><span><?php echo self::icon('news'); ?></span><div><h3>Unternehmensnews</h3><p>Aktuelle Mitteilungen und wichtige Informationen.</p></div><b>→</b></a>
                    <a href="<?php echo esc_url($urls['doc']); ?>"><span><?php echo self::icon('doc'); ?></span><div><h3>Dokumente</h3><p>Formulare, Nachweise und persönliche Unterlagen.</p></div><b>→</b></a>
                    <a href="<?php echo esc_url($urls['vacation']); ?>"><span><?php echo self::icon('vacation'); ?></span><div><h3>Urlaub</h3><p>Urlaub beantragen und Bearbeitungsstand prüfen.</p></div><b>→</b></a>
                    <a href="<?php echo esc_url($urls['people']); ?>"><span><?php echo self::icon('people'); ?></span><div><h3>Mitarbeiter</h3><p>Ansprechpartner und Kontaktdaten finden.</p></div><b>→</b></a>
                </div></section>
            </main>
        </div>
        <?php
    }
}
