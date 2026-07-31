<?php
defined('ABSPATH') || exit;

final class Brehl_News_Module {
    private static ?self $instance = null;
    private const REACTIONS = array('hilfreich' => '👍', 'gefaellt' => '❤️', 'super' => '🎉', 'danke' => '👏');

    public static function instance(): self {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'register_content_types'));
        add_action('init', array($this, 'register_shortcode'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_brehl_news', array($this, 'save_meta'), 10, 2);
        add_filter('manage_brehl_news_posts_columns', array($this, 'columns'));
        add_action('manage_brehl_news_posts_custom_column', array($this, 'column_content'), 10, 2);
        add_filter('manage_edit-brehl_news_sortable_columns', array($this, 'sortable_columns'));
        add_action('pre_get_posts', array($this, 'admin_sorting'));
        add_action('wp_ajax_my_brehl_news_react', array($this, 'ajax_react'));
        add_action('wp_ajax_my_brehl_news_comment', array($this, 'ajax_comment'));
        add_action('wp_ajax_my_brehl_news_read', array($this, 'ajax_mark_read'));
    }

    public function register_shortcode(): void { add_shortcode('brehl_news', array($this, 'shortcode')); }

    public function register_content_types(): void {
        register_post_type('brehl_news', array(
            'labels' => array(
                'name' => __('Unternehmensnews', 'brehl-intranet'), 'singular_name' => __('Unternehmensnews', 'brehl-intranet'),
                'add_new' => __('Neu erstellen', 'brehl-intranet'), 'add_new_item' => __('Neue Unternehmensnews erstellen', 'brehl-intranet'),
                'edit_item' => __('Unternehmensnews bearbeiten', 'brehl-intranet'), 'new_item' => __('Neue Unternehmensnews', 'brehl-intranet'),
                'view_item' => __('Unternehmensnews ansehen', 'brehl-intranet'), 'search_items' => __('Unternehmensnews durchsuchen', 'brehl-intranet'),
                'not_found' => __('Keine Unternehmensnews gefunden', 'brehl-intranet'), 'menu_name' => __('Unternehmensnews', 'brehl-intranet'),
            ),
            'public' => false, 'show_ui' => true, 'show_in_menu' => true, 'show_in_rest' => true,
            'menu_icon' => 'dashicons-megaphone',
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions', 'comments'),
            'has_archive' => false, 'rewrite' => false, 'exclude_from_search' => true, 'map_meta_cap' => true,
        ));

        register_taxonomy('brehl_news_category', array('brehl_news'), array(
            'labels' => array('name' => __('News-Kategorien', 'brehl-intranet'), 'singular_name' => __('News-Kategorie', 'brehl-intranet'), 'add_new_item' => __('Neue News-Kategorie', 'brehl-intranet')),
            'public' => false, 'show_ui' => true, 'show_admin_column' => true, 'show_in_rest' => true, 'hierarchical' => true, 'rewrite' => false,
        ));

        foreach (array(
            '_brehl_news_important' => array('type' => 'boolean', 'sanitize_callback' => 'rest_sanitize_boolean'),
            '_brehl_news_teaser' => array('type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field'),
            '_brehl_news_expiry' => array('type' => 'string', 'sanitize_callback' => 'sanitize_text_field'),
            '_brehl_news_attachment_url' => array('type' => 'string', 'sanitize_callback' => 'esc_url_raw'),
            '_brehl_news_attachment_label' => array('type' => 'string', 'sanitize_callback' => 'sanitize_text_field'),
        ) as $key => $args) {
            register_post_meta('brehl_news', $key, array('type' => $args['type'], 'single' => true, 'default' => 'boolean' === $args['type'] ? false : '', 'show_in_rest' => true, 'sanitize_callback' => $args['sanitize_callback'], 'auth_callback' => static fn(): bool => current_user_can('edit_posts')));
        }
    }

    public function add_meta_boxes(): void { add_meta_box('brehl-news-settings', __('News-Einstellungen', 'brehl-intranet'), array($this, 'render_meta_box'), 'brehl_news', 'side', 'high'); }

    public function render_meta_box(WP_Post $post): void {
        wp_nonce_field('brehl_news_meta', 'brehl_news_meta_nonce');
        $important = (bool) get_post_meta($post->ID, '_brehl_news_important', true);
        $teaser = (string) get_post_meta($post->ID, '_brehl_news_teaser', true);
        $expiry = (string) get_post_meta($post->ID, '_brehl_news_expiry', true);
        $attachment_url = (string) get_post_meta($post->ID, '_brehl_news_attachment_url', true);
        $attachment_label = (string) get_post_meta($post->ID, '_brehl_news_attachment_label', true); ?>
        <p><label><input type="checkbox" name="brehl_news_important" value="1" <?php checked($important); ?>> <?php esc_html_e('Als wichtig markieren', 'brehl-intranet'); ?></label></p>
        <p><label for="brehl_news_teaser"><strong><?php esc_html_e('Kurzer Teaser', 'brehl-intranet'); ?></strong></label><textarea id="brehl_news_teaser" name="brehl_news_teaser" rows="4" style="width:100%;"><?php echo esc_textarea($teaser); ?></textarea></p>
        <p><label for="brehl_news_expiry"><strong><?php esc_html_e('Ablaufdatum', 'brehl-intranet'); ?></strong></label><input id="brehl_news_expiry" name="brehl_news_expiry" type="date" value="<?php echo esc_attr($expiry); ?>" style="width:100%;"><small><?php esc_html_e('Optional. Danach wird die Meldung nicht mehr angezeigt.', 'brehl-intranet'); ?></small></p>
        <hr><p><strong><?php esc_html_e('Anhang', 'brehl-intranet'); ?></strong></p>
        <p><label for="brehl_news_attachment_url"><?php esc_html_e('Datei-URL', 'brehl-intranet'); ?></label><input id="brehl_news_attachment_url" name="brehl_news_attachment_url" type="url" value="<?php echo esc_attr($attachment_url); ?>" placeholder="https://…" style="width:100%;"></p>
        <p><label for="brehl_news_attachment_label"><?php esc_html_e('Bezeichnung', 'brehl-intranet'); ?></label><input id="brehl_news_attachment_label" name="brehl_news_attachment_label" type="text" value="<?php echo esc_attr($attachment_label); ?>" placeholder="PDF öffnen" style="width:100%;"></p>
        <?php
    }

    public function save_meta(int $post_id, WP_Post $post): void {
        if (!isset($_POST['brehl_news_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['brehl_news_meta_nonce'])), 'brehl_news_meta') || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id)) return;
        update_post_meta($post_id, '_brehl_news_important', isset($_POST['brehl_news_important']) ? '1' : '0');
        update_post_meta($post_id, '_brehl_news_teaser', isset($_POST['brehl_news_teaser']) ? sanitize_textarea_field(wp_unslash($_POST['brehl_news_teaser'])) : '');
        update_post_meta($post_id, '_brehl_news_expiry', isset($_POST['brehl_news_expiry']) ? sanitize_text_field(wp_unslash($_POST['brehl_news_expiry'])) : '');
        update_post_meta($post_id, '_brehl_news_attachment_url', isset($_POST['brehl_news_attachment_url']) ? esc_url_raw(wp_unslash($_POST['brehl_news_attachment_url'])) : '');
        update_post_meta($post_id, '_brehl_news_attachment_label', isset($_POST['brehl_news_attachment_label']) ? sanitize_text_field(wp_unslash($_POST['brehl_news_attachment_label'])) : '');
    }

    public function get_news(int $limit = 6, string $category = ''): WP_Query {
        $meta_query = array('relation' => 'OR', array('key' => '_brehl_news_expiry', 'compare' => 'NOT EXISTS'), array('key' => '_brehl_news_expiry', 'value' => '', 'compare' => '='), array('key' => '_brehl_news_expiry', 'value' => current_time('Y-m-d'), 'compare' => '>=', 'type' => 'DATE'));
        $args = array('post_type' => 'brehl_news', 'post_status' => 'publish', 'posts_per_page' => max(1, min(24, $limit)), 'meta_query' => $meta_query, 'orderby' => array('meta_value_num' => 'DESC', 'date' => 'DESC'), 'meta_key' => '_brehl_news_important');
        if ('' !== $category) $args['tax_query'] = array(array('taxonomy' => 'brehl_news_category', 'field' => 'slug', 'terms' => sanitize_title($category)));
        return new WP_Query($args);
    }

    public function shortcode($atts = array()): string {
        if (!is_user_logged_in()) return '';
        $atts = shortcode_atts(array('limit' => '6', 'title' => 'Unternehmensnews', 'category' => '', 'show_all_url' => '', 'filters' => 'yes'), is_array($atts) ? $atts : array(), 'brehl_news');
        return $this->render_feed((int) $atts['limit'], sanitize_text_field($atts['title']), sanitize_title($atts['category']), esc_url_raw($atts['show_all_url']), 'yes' === $atts['filters']);
    }

    public function render_feed(int $limit = 6, string $title = 'Unternehmensnews', string $category = '', string $show_all_url = '', bool $show_filters = true): string {
        wp_enqueue_style('brehl-intranet'); wp_enqueue_script('brehl-intranet-news');
        $query = $this->get_news($limit, $category);
        $categories = get_terms(array('taxonomy' => 'brehl_news_category', 'hide_empty' => true));
        ob_start(); ?>
        <section class="brehl-news-feed" aria-label="<?php echo esc_attr($title); ?>">
            <div class="brehl-news-head"><div><span class="brehl-news-eyebrow"><?php esc_html_e('Aktuelles aus My Brehl', 'brehl-intranet'); ?></span><h2><?php echo esc_html($title); ?></h2></div><?php if ($show_all_url) : ?><a class="brehl-news-all" href="<?php echo esc_url($show_all_url); ?>"><?php esc_html_e('Alle anzeigen', 'brehl-intranet'); ?> <span aria-hidden="true">→</span></a><?php endif; ?></div>
            <?php if ($show_filters && $query->have_posts()) : ?>
                <div class="brehl-news-tools">
                    <label class="brehl-news-search"><span aria-hidden="true">⌕</span><input type="search" data-brehl-news-search placeholder="<?php esc_attr_e('News durchsuchen …', 'brehl-intranet'); ?>" aria-label="<?php esc_attr_e('News durchsuchen', 'brehl-intranet'); ?>"></label>
                    <div class="brehl-news-filters" role="group" aria-label="<?php esc_attr_e('Nach Kategorie filtern', 'brehl-intranet'); ?>">
                        <button type="button" class="is-active" data-brehl-news-filter="all"><?php esc_html_e('Alle', 'brehl-intranet'); ?></button>
                        <?php if (!is_wp_error($categories)) foreach ($categories as $term) : ?><button type="button" data-brehl-news-filter="<?php echo esc_attr($term->slug); ?>"><?php echo esc_html($term->name); ?></button><?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($query->have_posts()) : ?><div class="brehl-news-grid" data-brehl-news-grid>
            <?php while ($query->have_posts()) : $query->the_post();
                $id = get_the_ID(); $important = (bool) get_post_meta($id, '_brehl_news_important', true); $teaser = (string) get_post_meta($id, '_brehl_news_teaser', true);
                $terms = get_the_terms($id, 'brehl_news_category'); $category_name = (!is_wp_error($terms) && $terms) ? $terms[0]->name : __('Unternehmen', 'brehl-intranet'); $category_slug = (!is_wp_error($terms) && $terms) ? $terms[0]->slug : 'unternehmen';
                $author = get_the_author_meta('display_name', (int) get_post_field('post_author', $id)); $read = $this->has_user_read($id); ?>
                <article class="brehl-news-card<?php echo $important ? ' is-important' : ''; ?><?php echo $read ? ' is-read' : ' is-unread'; ?>" data-news-card data-category="<?php echo esc_attr($category_slug); ?>" data-search="<?php echo esc_attr(mb_strtolower(get_the_title() . ' ' . $teaser . ' ' . $category_name . ' ' . $author)); ?>">
                    <button type="button" class="brehl-news-card-button" data-brehl-news-open="<?php echo esc_attr((string) $id); ?>" aria-label="<?php echo esc_attr(sprintf(__('News öffnen: %s', 'brehl-intranet'), get_the_title())); ?>">
                        <div class="brehl-news-media"><?php if (has_post_thumbnail()) the_post_thumbnail('large', array('loading' => 'lazy')); else echo '<span class="brehl-news-placeholder" aria-hidden="true">MB</span>'; ?><?php if ($important) : ?><span class="brehl-news-badge"><?php esc_html_e('Wichtig', 'brehl-intranet'); ?></span><?php endif; ?><?php if (!$read) : ?><span class="brehl-news-unread"><?php esc_html_e('Neu', 'brehl-intranet'); ?></span><?php endif; ?></div>
                        <div class="brehl-news-body"><div class="brehl-news-meta"><span><?php echo esc_html($category_name); ?></span><time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(human_time_diff(get_the_time('U'), current_time('timestamp'))); ?> <?php esc_html_e('zuvor', 'brehl-intranet'); ?></time></div><h3><?php the_title(); ?></h3><p><?php echo esc_html($teaser ?: wp_trim_words(wp_strip_all_tags(get_the_excerpt() ?: get_the_content()), 24)); ?></p><div class="brehl-news-card-footer"><span><?php echo esc_html($author); ?></span><span><?php echo esc_html((string) get_comments_number($id)); ?> 💬</span></div><span class="brehl-news-more"><?php esc_html_e('Mehr lesen', 'brehl-intranet'); ?> <span aria-hidden="true">→</span></span></div>
                    </button>
                </article>
                <?php echo $this->render_modal($id, $category_name, $important); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php endwhile; wp_reset_postdata(); ?></div><div class="brehl-news-no-results" hidden><?php esc_html_e('Keine passenden News gefunden.', 'brehl-intranet'); ?></div>
            <?php else : ?><div class="brehl-news-empty"><span aria-hidden="true">📢</span><h3><?php esc_html_e('Noch keine News veröffentlicht', 'brehl-intranet'); ?></h3><p><?php esc_html_e('Neue Unternehmensmeldungen erscheinen automatisch an dieser Stelle.', 'brehl-intranet'); ?></p></div><?php endif; ?>
        </section><?php return (string) ob_get_clean();
    }

    private function render_modal(int $id, string $category_name, bool $important): string {
        $attachment_url = (string) get_post_meta($id, '_brehl_news_attachment_url', true); $attachment_label = (string) get_post_meta($id, '_brehl_news_attachment_label', true);
        $comments = get_comments(array('post_id' => $id, 'status' => 'approve', 'order' => 'ASC')); $author = get_the_author_meta('display_name', (int) get_post_field('post_author', $id));
        ob_start(); ?>
        <div id="brehl-news-modal-<?php echo esc_attr((string) $id); ?>" class="brehl-news-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="brehl-news-title-<?php echo esc_attr((string) $id); ?>" data-news-id="<?php echo esc_attr((string) $id); ?>">
            <div class="brehl-news-dialog"><button type="button" class="brehl-news-close" data-brehl-news-close aria-label="<?php esc_attr_e('Schließen', 'brehl-intranet'); ?>">×</button>
                <?php if (has_post_thumbnail($id)) : ?><div class="brehl-news-dialog-image"><?php echo get_the_post_thumbnail($id, 'large'); ?></div><?php endif; ?>
                <div class="brehl-news-dialog-content"><div class="brehl-news-meta"><span><?php echo esc_html($category_name); ?></span><time datetime="<?php echo esc_attr(get_the_date('c', $id)); ?>"><?php echo esc_html(get_the_date('d.m.Y', $id)); ?></time><span><?php echo esc_html($author); ?></span><?php if ($important) : ?><strong><?php esc_html_e('Wichtig', 'brehl-intranet'); ?></strong><?php endif; ?></div><h2 id="brehl-news-title-<?php echo esc_attr((string) $id); ?>"><?php echo esc_html(get_the_title($id)); ?></h2><div class="brehl-news-content"><?php echo wp_kses_post(apply_filters('the_content', get_post_field('post_content', $id))); ?></div><?php if ($attachment_url) : ?><a class="brehl-news-attachment" href="<?php echo esc_url($attachment_url); ?>" target="_blank" rel="noopener">📎 <?php echo esc_html($attachment_label ?: __('Anhang öffnen', 'brehl-intranet')); ?></a><?php endif; ?>
                    <div class="brehl-news-reactions" aria-label="<?php esc_attr_e('Auf diese Nachricht reagieren', 'brehl-intranet'); ?>"><?php foreach (self::REACTIONS as $key => $emoji) : ?><button type="button" data-brehl-reaction="<?php echo esc_attr($key); ?>" data-news-id="<?php echo esc_attr((string) $id); ?>"><span><?php echo esc_html($emoji); ?></span><span data-reaction-count="<?php echo esc_attr($key); ?>"><?php echo esc_html((string) $this->reaction_count($id, $key)); ?></span></button><?php endforeach; ?></div>
                    <section class="brehl-news-comments"><h3><?php esc_html_e('Kommentare', 'brehl-intranet'); ?> <span data-comment-total><?php echo esc_html((string) count($comments)); ?></span></h3><div class="brehl-comment-list" data-comment-list><?php foreach ($comments as $comment) echo $this->comment_html($comment); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                        <form class="brehl-comment-form" data-brehl-comment-form data-news-id="<?php echo esc_attr((string) $id); ?>"><label for="brehl-comment-<?php echo esc_attr((string) $id); ?>"><?php esc_html_e('Kommentar schreiben', 'brehl-intranet'); ?></label><textarea id="brehl-comment-<?php echo esc_attr((string) $id); ?>" name="comment" rows="3" placeholder="<?php esc_attr_e('Ihre Nachricht …', 'brehl-intranet'); ?>" required></textarea><div><span data-comment-status aria-live="polite"></span><button type="submit"><?php esc_html_e('Veröffentlichen', 'brehl-intranet'); ?></button></div></form>
                    </section>
                </div>
            </div>
        </div><?php return (string) ob_get_clean();
    }

    private function comment_html(WP_Comment $comment): string {
        ob_start(); ?><article class="brehl-comment"><div class="brehl-comment-avatar"><?php echo esc_html(mb_strtoupper(mb_substr($comment->comment_author, 0, 1))); ?></div><div><strong><?php echo esc_html($comment->comment_author); ?></strong><time><?php echo esc_html(human_time_diff(strtotime($comment->comment_date_gmt . ' GMT'), current_time('timestamp'))); ?> <?php esc_html_e('zuvor', 'brehl-intranet'); ?></time><p><?php echo esc_html($comment->comment_content); ?></p></div></article><?php return (string) ob_get_clean();
    }

    private function reaction_count(int $post_id, string $reaction): int { return count((array) get_post_meta($post_id, '_my_brehl_reaction_' . $reaction, true)); }
    private function has_user_read(int $post_id): bool { return in_array($post_id, array_map('intval', (array) get_user_meta(get_current_user_id(), '_my_brehl_read_news', true)), true); }

    public function ajax_react(): void {
        $this->verify_ajax(); $post_id = absint($_POST['post_id'] ?? 0); $reaction = sanitize_key($_POST['reaction'] ?? '');
        if ('brehl_news' !== get_post_type($post_id) || !isset(self::REACTIONS[$reaction])) wp_send_json_error();
        $key = '_my_brehl_reaction_' . $reaction; $users = array_map('intval', (array) get_post_meta($post_id, $key, true)); $uid = get_current_user_id();
        if (in_array($uid, $users, true)) $users = array_values(array_diff($users, array($uid))); else $users[] = $uid;
        update_post_meta($post_id, $key, array_values(array_unique($users))); wp_send_json_success(array('count' => count($users), 'active' => in_array($uid, $users, true)));
    }

    public function ajax_comment(): void {
        $this->verify_ajax(); $post_id = absint($_POST['post_id'] ?? 0); $content = sanitize_textarea_field(wp_unslash($_POST['comment'] ?? ''));
        if ('brehl_news' !== get_post_type($post_id) || '' === trim($content)) wp_send_json_error();
        $user = wp_get_current_user(); $comment_id = wp_insert_comment(array('comment_post_ID' => $post_id, 'comment_content' => $content, 'user_id' => $user->ID, 'comment_author' => $user->display_name, 'comment_author_email' => $user->user_email, 'comment_approved' => 1));
        if (!$comment_id) wp_send_json_error(); $comment = get_comment($comment_id); wp_send_json_success(array('html' => $this->comment_html($comment), 'total' => get_comments_number($post_id)));
    }

    public function ajax_mark_read(): void {
        $this->verify_ajax(); $post_id = absint($_POST['post_id'] ?? 0); if ('brehl_news' !== get_post_type($post_id)) wp_send_json_error();
        $read = array_map('intval', (array) get_user_meta(get_current_user_id(), '_my_brehl_read_news', true)); $read[] = $post_id; update_user_meta(get_current_user_id(), '_my_brehl_read_news', array_values(array_unique($read))); wp_send_json_success();
    }

    private function verify_ajax(): void {
        if (!is_user_logged_in() || !check_ajax_referer('my_brehl_news', 'nonce', false)) wp_send_json_error(array('message' => __('Nicht autorisiert.', 'brehl-intranet')), 403);
    }

    public function columns(array $columns): array { $columns['brehl_important'] = __('Wichtig', 'brehl-intranet'); $columns['brehl_expiry'] = __('Ablaufdatum', 'brehl-intranet'); $columns['brehl_comments'] = __('Kommentare', 'brehl-intranet'); return $columns; }
    public function column_content(string $column, int $post_id): void { if ('brehl_important' === $column) echo get_post_meta($post_id, '_brehl_news_important', true) ? esc_html__('Ja', 'brehl-intranet') : '—'; if ('brehl_expiry' === $column) echo esc_html((string) get_post_meta($post_id, '_brehl_news_expiry', true) ?: '—'); if ('brehl_comments' === $column) echo esc_html((string) get_comments_number($post_id)); }
    public function sortable_columns(array $columns): array { $columns['brehl_important'] = 'brehl_important'; return $columns; }
    public function admin_sorting(WP_Query $query): void { if (is_admin() && $query->is_main_query() && 'brehl_important' === $query->get('orderby')) { $query->set('meta_key', '_brehl_news_important'); $query->set('orderby', 'meta_value_num'); } }
}
