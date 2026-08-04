<?php

defined('ABSPATH') || exit;

/**
 * Central registry for all Elementor widgets shipped with My Brehl.
 */
final class Brehl_Elementor_Widget_Manager {
    private static $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function register_category($elements_manager): void {
        $elements_manager->add_category('brehl-intranet', array(
            'title' => __('My Brehl Widgets', 'brehl-intranet'),
            'icon' => 'fa fa-building',
        ));
    }

    /** @return array<int,array{file:string,class:string}> */
    private function widgets(): array {
        return array(
            array('file' => 'class-my-brehl-logo-widget.php', 'class' => 'My_Brehl_Logo_Widget'),
            array('file' => 'class-my-brehl-kpi-widget.php', 'class' => 'My_Brehl_KPI_Widget'),
            array('file' => 'class-my-brehl-search-widget.php', 'class' => 'My_Brehl_Search_Widget'),
            array('file' => 'class-my-brehl-profile-widget.php', 'class' => 'My_Brehl_Profile_Widget'),
            array('file' => 'class-my-brehl-notifications-widget.php', 'class' => 'My_Brehl_Notifications_Widget'),
            array('file' => 'class-my-brehl-date-widget.php', 'class' => 'My_Brehl_Date_Widget'),
            array('file' => 'class-my-brehl-section-title-widget.php', 'class' => 'My_Brehl_Section_Title_Widget'),
            array('file' => 'class-my-brehl-quick-link-widget.php', 'class' => 'My_Brehl_Quick_Link_Widget'),
            array('file' => 'class-my-brehl-avatar-widget.php', 'class' => 'My_Brehl_Avatar_Widget'),
            array('file' => 'class-brehl-login-widget.php', 'class' => 'Brehl_Login_Widget'),
            array('file' => 'class-brehl-userbar-widget.php', 'class' => 'Brehl_Userbar_Widget'),
            array('file' => 'class-brehl-greeting-widget.php', 'class' => 'Brehl_Greeting_Widget'),
            array('file' => 'class-brehl-mobile-nav-widget.php', 'class' => 'Brehl_Mobile_Nav_Widget'),
            array('file' => 'class-brehl-card-widget.php', 'class' => 'Brehl_Card_Widget'),
            array('file' => 'class-brehl-quick-links-widget.php', 'class' => 'Brehl_Quick_Links_Widget'),
            array('file' => 'class-brehl-sidebar-widget.php', 'class' => 'Brehl_Sidebar_Widget'),
            array('file' => 'class-brehl-news-widget.php', 'class' => 'Brehl_News_Widget'),
            array('file' => 'class-brehl-documents-widget.php', 'class' => 'Brehl_Documents_Widget'),
            array('file' => 'class-brehl-hr-widget.php', 'class' => 'Brehl_HR_Widget'),
            array('file' => 'class-brehl-hr-metrics-widget.php', 'class' => 'Brehl_HR_Metrics_Widget'),
            array('file' => 'class-brehl-hr-people-widget.php', 'class' => 'Brehl_HR_People_Widget'),
            array('file' => 'class-brehl-employee-list-widget.php', 'class' => 'Brehl_Employee_List_Widget'),
            array('file' => 'class-brehl-employee-form-widget.php', 'class' => 'Brehl_Employee_Form_Widget'),
            array('file' => 'class-brehl-hr-vacation-widget.php', 'class' => 'Brehl_HR_Vacation_Widget'),
            array('file' => 'class-brehl-hr-sick-widget.php', 'class' => 'Brehl_HR_Sick_Widget'),
            array('file' => 'class-brehl-dashboard-hero-widget.php', 'class' => 'Brehl_Dashboard_Hero_Widget'),
            array('file' => 'class-my-brehl-vacation-widget.php', 'class' => 'My_Brehl_Vacation_Widget'),
            array('file' => 'class-my-brehl-vacation-overview-widget.php', 'class' => 'My_Brehl_Vacation_Overview_Widget'),
            array('file' => 'class-my-brehl-vacation-request-widget.php', 'class' => 'My_Brehl_Vacation_Request_Widget'),
            array('file' => 'class-my-brehl-vacation-status-widget.php', 'class' => 'My_Brehl_Vacation_Status_Widget'),
            array('file' => 'class-my-brehl-sick-overview-widget.php', 'class' => 'My_Brehl_Sick_Overview_Widget'),
            array('file' => 'class-my-brehl-sick-request-widget.php', 'class' => 'My_Brehl_Sick_Request_Widget'),
            array('file' => 'class-my-brehl-sick-status-widget.php', 'class' => 'My_Brehl_Sick_Status_Widget'),
            array('file' => 'class-my-brehl-vehicle-damage-widget.php', 'class' => 'My_Brehl_Vehicle_Damage_Widget'),
            array('file' => 'class-my-brehl-tasks-widget.php', 'class' => 'My_Brehl_Tasks_Widget'),
        );
    }

    public function register_widgets($widgets_manager): void {
        if (!class_exists('\\Elementor\\Widget_Base')) {
            return;
        }

        require_once BREHL_INTR_DIR . 'includes/elementor/class-my-brehl-widget-base.php';

        foreach ($this->widgets() as $widget) {
            $path = BREHL_INTR_DIR . 'includes/elementor/' . $widget['file'];
            if (!is_readable($path)) {
                continue;
            }
            require_once $path;
            if (class_exists($widget['class'])) {
                $widgets_manager->register(new $widget['class']());
            }
        }
    }
}
