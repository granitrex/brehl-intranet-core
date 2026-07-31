<?php

defined('ABSPATH') || exit;

final class Brehl_Documents_Module {
    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'register_content_types'));
        add_action('init', array($this, 'register_shortcode'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_brehl_document', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function register_content_types(): void {
        register_post_type('brehl_document', array(
            'labels' => array(
                'name' => __('Unternehmensdokumente', 'brehl-intranet'),
                'singular_name' => __('Unternehmensdokument', 'brehl-intranet'),
                'add_new_item' => __('Neues Dokument hinzufügen', 'brehl-intranet'),
                'edit_item' => __('Dokument bearbeiten', 'brehl-intranet'),
                'search_items' => __('Dokumente durchsuchen', 'brehl-intranet'),
                'not_found' => __('Keine Dokumente gefunden', 'brehl-intranet'),
                'menu_name' => __('Dokumente', 'brehl-intranet'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-media-document',
            'supports' => array('title', 'editor', 'excerpt', 'author', 'revisions'),
            'has_archive' => false,
            'rewrite' => false,
            'exclude_from_search' => true,
            'map_meta_cap' => true,
        ));

        register_taxonomy('brehl_document_category', array('brehl_document'), array(
            'labels' => array(
                'name' => __('Dokumentkategorien', 'brehl-intranet'),
                'singular_name' => __('Dokumentkategorie', 'brehl-intranet'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'hierarchical' => true,
            'rewrite' => false,
        ));

        register_post_meta('brehl_document', '_brehl_document_url', array(
            'type' => 'string',
            'single' => true,
            'default' => '',
            'show_in_rest' => true,
            'sanitize_callback' => 'esc_url_raw',
            'auth_callback' => static fn(): bool => current_user_can('upload_files'),
        ));
        register_post_meta('brehl_document', '_brehl_document_version', array(
            'type' => 'string',
            'single' => true,
            'default' => '',
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback' => static fn(): bool => current_user_can('edit_posts'),
        ));
        register_post_meta('brehl_document', '_brehl_document_new_until', array(
            'type' => 'string',
            'single' => true,
            'default' => '',
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback' => static fn(): bool => current_user_can('edit_posts'),
        ));
    }

    public function register_shortcode(): void {
        add_shortcode('brehl_documents', array($this, 'shortcode'));
    }

    public function add_meta_boxes(): void {
        add_meta_box(
            'brehl-document-file',
            __('Dokumentdatei', 'brehl-intranet'),
            array($this, 'render_meta_box'),
            'brehl_document',
            'side',
            'high'
        );
    }

    public function enqueue_admin_assets(string $hook): void {
        if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
            return;
        }
        $screen = get_current_screen();
        if (!$screen || 'brehl_document' !== $screen->post_type) {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_script(
            'brehl-intranet-documents-admin',
            BREHL_INTR_URL . 'assets/js/brehl-documents-admin.js',
            array(),
            BREHL_INTR_VERSION,
            true
        );
    }

    public function render_meta_box(WP_Post $post): void {
        wp_nonce_field('brehl_document_meta', 'brehl_document_meta_nonce');
        $url = (string) get_post_meta($post->ID, '_brehl_document_url', true);
        $version = (string) get_post_meta($post->ID, '_brehl_document_version', true);
        $new_until = (string) get_post_meta($post->ID, '_brehl_document_new_until', true); ?>
        <p>
            <label for="brehl-document-url"><strong><?php esc_html_e('Datei-URL', 'brehl-intranet'); ?></strong></label>
            <input id="brehl-document-url" name="brehl_document_url" type="url" value="<?php echo esc_attr($url); ?>" placeholder="Noch keine Datei ausgewählt" style="width:100%;" readonly required>
        </p>
        <p>
            <button type="button" class="button button-primary" data-brehl-document-select><?php esc_html_e('Datei auswählen oder hochladen', 'brehl-intranet'); ?></button>
            <button type="button" class="button" data-brehl-document-remove<?php echo $url ? '' : ' hidden'; ?>><?php esc_html_e('Entfernen', 'brehl-intranet'); ?></button>
        </p>
        <p>
            <label for="brehl-document-version"><strong><?php esc_html_e('Version', 'brehl-intranet'); ?></strong></label>
            <input id="brehl-document-version" name="brehl_document_version" type="text" value="<?php echo esc_attr($version); ?>" placeholder="z. B. 2.1" style="width:100%;">
        </p>
        <p>
            <label for="brehl-document-new-until"><strong><?php esc_html_e('Als neu anzeigen bis', 'brehl-intranet'); ?></strong></label>
            <input id="brehl-document-new-until" name="brehl_document_new_until" type="date" value="<?php echo esc_attr($new_until); ?>" style="width:100%;">
        </p>
        <?php
    }

    public function save_meta(int $post_id): void {
        if (
            !isset($_POST['brehl_document_meta_nonce'])
            || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['brehl_document_meta_nonce'])), 'brehl_document_meta')
            || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            || !current_user_can('edit_post', $post_id)
        ) {
            return;
        }
        update_post_meta($post_id, '_brehl_document_url', isset($_POST['brehl_document_url']) ? esc_url_raw(wp_unslash($_POST['brehl_document_url'])) : '');
        update_post_meta($post_id, '_brehl_document_version', isset($_POST['brehl_document_version']) ? sanitize_text_field(wp_unslash($_POST['brehl_document_version'])) : '');
        update_post_meta($post_id, '_brehl_document_new_until', isset($_POST['brehl_document_new_until']) ? sanitize_text_field(wp_unslash($_POST['brehl_document_new_until'])) : '');
    }

    public function shortcode($atts = array()): string {
        if (!is_user_logged_in()) {
            return '';
        }
        $atts = shortcode_atts(array('limit' => '12', 'category' => '', 'search' => 'yes'), is_array($atts) ? $atts : array(), 'brehl_documents');
        return $this->render_library((int) $atts['limit'], sanitize_title($atts['category']), 'yes' === $atts['search']);
    }

    public function render_library(int $limit = 12, string $category = '', bool $show_search = true): string {
        wp_enqueue_style('brehl-intranet');
        wp_enqueue_script('brehl-intranet-documents');
        $args = array(
            'post_type' => 'brehl_document',
            'post_status' => 'publish',
            'posts_per_page' => max(1, min(50, $limit)),
            'orderby' => array('menu_order' => 'ASC', 'date' => 'DESC'),
        );
        if ('' !== $category) {
            $args['tax_query'] = array(array(
                'taxonomy' => 'brehl_document_category',
                'field' => 'slug',
                'terms' => $category,
            ));
        }
        $query = new WP_Query($args);
        $categories = get_terms(array('taxonomy' => 'brehl_document_category', 'hide_empty' => true));
        ob_start(); ?>
        <section class="brehl-documents" data-brehl-documents>
            <?php if ($show_search && $query->have_posts()) : ?>
                <div class="brehl-documents__tools">
                    <label class="brehl-documents__search">
                        <span aria-hidden="true">⌕</span>
                        <input type="search" data-document-search placeholder="<?php esc_attr_e('Dokumente durchsuchen …', 'brehl-intranet'); ?>">
                    </label>
                    <div class="brehl-documents__filters">
                        <button type="button" class="is-active" data-document-filter="all"><?php esc_html_e('Alle', 'brehl-intranet'); ?></button>
                        <?php if (!is_wp_error($categories)) {
                            foreach ($categories as $term) : ?>
                                <button type="button" data-document-filter="<?php echo esc_attr($term->slug); ?>"><?php echo esc_html($term->name); ?></button>
                            <?php endforeach;
                        } ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($query->have_posts()) : ?>
                <div class="brehl-documents__grid">
                    <?php while ($query->have_posts()) : $query->the_post();
                        $id = get_the_ID();
                        $url = (string) get_post_meta($id, '_brehl_document_url', true);
                        $version = (string) get_post_meta($id, '_brehl_document_version', true);
                        $new_until = (string) get_post_meta($id, '_brehl_document_new_until', true);
                        $terms = get_the_terms($id, 'brehl_document_category');
                        $term = (!is_wp_error($terms) && $terms) ? $terms[0] : null;
                        $is_new = '' !== $new_until && $new_until >= current_time('Y-m-d');
                        $extension = strtoupper((string) pathinfo((string) wp_parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION)); ?>
                        <article class="brehl-document-card" data-document-card data-category="<?php echo esc_attr($term ? $term->slug : 'allgemein'); ?>" data-search="<?php echo esc_attr(mb_strtolower(get_the_title() . ' ' . get_the_excerpt() . ' ' . ($term ? $term->name : ''))); ?>">
                            <div class="brehl-document-card__icon" aria-hidden="true"><?php echo esc_html($extension ?: 'DOC'); ?></div>
                            <div class="brehl-document-card__content">
                                <div class="brehl-document-card__meta">
                                    <span><?php echo esc_html($term ? $term->name : __('Allgemein', 'brehl-intranet')); ?></span>
                                    <?php if ($is_new) : ?><strong><?php esc_html_e('Neu', 'brehl-intranet'); ?></strong><?php endif; ?>
                                </div>
                                <h3><?php the_title(); ?></h3>
                                <?php if (has_excerpt()) : ?><p><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
                                <div class="brehl-document-card__footer">
                                    <span><?php echo $version ? esc_html(sprintf(__('Version %s', 'brehl-intranet'), $version)) : esc_html(get_the_modified_date('d.m.Y')); ?></span>
                                    <?php if ($url) : ?><a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener"><?php esc_html_e('Öffnen', 'brehl-intranet'); ?> <span aria-hidden="true">↗</span></a><?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
                <p class="brehl-documents__no-results" hidden><?php esc_html_e('Keine passenden Dokumente gefunden.', 'brehl-intranet'); ?></p>
            <?php else : ?>
                <div class="brehl-documents__empty"><span aria-hidden="true">📄</span><h3><?php esc_html_e('Noch keine Dokumente', 'brehl-intranet'); ?></h3><p><?php esc_html_e('Veröffentlichte Unternehmensdokumente erscheinen hier automatisch.', 'brehl-intranet'); ?></p></div>
            <?php endif; ?>
        </section>
        <?php return (string) ob_get_clean();
    }
}
