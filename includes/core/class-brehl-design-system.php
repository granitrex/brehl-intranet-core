<?php
defined('ABSPATH') || exit;

/**
 * Central visual tokens for the complete intranet.
 *
 * Widgets and modules should reference these values instead of inventing
 * individual colors, radii or shadows.
 */
final class Brehl_Design_System {
    public static function tokens(): array {
        return array(
            // Brand
            'navy'             => '#17235A',
            'navy_dark'        => '#0F173F',
            'navy_soft'        => '#27366F',
            'magenta'          => '#EC008C',
            'magenta_soft'     => '#FDE7F4',

            // Surfaces
            'background'       => '#F4F6FA',
            'surface'          => '#FFFFFF',
            'surface_soft'     => '#F8F9FC',
            'border'           => '#E6EAF1',

            // Text
            'text'             => '#17235A',
            'text_muted'       => '#687386',
            'text_soft'        => '#8C95A5',

            // Feedback
            'success'          => '#1E9E61',
            'warning'          => '#D9822B',
            'danger'           => '#D64545',
            'info'             => '#2F6BFF',

            // Radius
            'radius_sm'        => '10px',
            'radius_md'        => '14px',
            'radius_lg'        => '18px',
            'radius_xl'        => '22px',

            // Spacing
            'space_1'          => '4px',
            'space_2'          => '8px',
            'space_3'          => '12px',
            'space_4'          => '16px',
            'space_5'          => '20px',
            'space_6'          => '24px',
            'space_8'          => '32px',

            // Shadows
            'shadow_sm'        => '0 4px 14px rgba(23, 35, 90, .05)',
            'shadow_card'      => '0 10px 30px rgba(23, 35, 90, .08)',
            'shadow_hover'     => '0 16px 40px rgba(23, 35, 90, .14)',

            // Motion
            'transition_fast'  => '160ms ease',
            'transition_base'  => '220ms ease',
        );
    }

    public static function css_variables(): string {
        $css = ':root{';
        foreach (self::tokens() as $name => $value) {
            $css .= '--my-brehl-' . str_replace('_', '-', $name) . ':' . $value . ';';
        }
        return $css . '}';
    }
}
