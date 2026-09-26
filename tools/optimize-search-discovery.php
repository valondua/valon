<?php
/**
 * Two reviewed English SEO changes; dry run unless --apply is supplied.
 *
 * php tools/optimize-search-discovery.php --wp-load=/path/to/wp-load.php
 * php tools/optimize-search-discovery.php --wp-load=/path/to/wp-load.php \
 *   --user=ADMIN_ID --apply --backup-dir=/srv/private/search-discovery
 *
 * WP-CLI dry run: wp eval-file /private/optimize-search-discovery.php
 * WP-CLI apply: wp --user=ADMIN_ID eval '$args=["--apply",
 *   "--backup-dir=/srv/private/search-discovery"];
 *   require "/private/optimize-search-discovery.php";'
 * (eval-file does not accept arbitrary --apply/--backup-dir flags.)
 *
 * Already bootstrapped WordPress / WP-CLI: set $args to the same --arguments
 * and require this file. An empty $args array is a dry run.
 * The backup directory must already exist, be mode 0700, and be outside the
 * WordPress, content and HTTP document roots. Backups are exclusively created
 * with mode 0600. They contain the original page, all its metadata, and the
 * complete Yoast taxonomy option for manual, field-scoped recovery.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

$valon_search_arguments = $args ?? array_slice($_SERVER['argv'] ?? [], 1);
if (!defined('ABSPATH')) {
    // WordPress bootstrap must run in the standalone script's global scope.
    $valon_search_loaders = array_values(array_filter(
        $valon_search_arguments,
        static fn($argument): bool => str_starts_with($argument, '--wp-load='),
    ));
    $valon_search_loader = count($valon_search_loaders) === 1
        ? realpath(substr($valon_search_loaders[0], strlen('--wp-load='))) : false;
    if (!$valon_search_loader || basename($valon_search_loader) !== 'wp-load.php' || !is_file($valon_search_loader)) {
        throw new RuntimeException('Load WordPress first or pass --wp-load=/absolute/path/to/wp-load.php.');
    }
    require_once $valon_search_loader;
}

(static function (array $arguments): void {
    $fail = static function (string $message): void {
        throw new RuntimeException($message);
    };
    $options = [];
    foreach ($arguments as $argument) {
        if ($argument === '--apply') {
            $key = 'apply';
            $value = true;
        } elseif (preg_match('/^--(wp-load|backup-dir|user)=(.+)$/D', $argument, $match)) {
            [, $key, $value] = $match;
        } else {
            $fail('Unknown argument: ' . $argument);
        }
        if (isset($options[$key])) {
            $fail('Duplicate argument: --' . $key);
        }
        $options[$key] = $value;
    }
    if (isset($options['user'])) {
        if (!ctype_digit($options['user']) || !get_user_by('id', (int) $options['user'])) {
            $fail('--user must identify an existing WordPress user ID.');
        }
        wp_set_current_user((int) $options['user']);
    }
    foreach (['pll_get_post_language', 'pll_get_term_language', 'YoastSEO', 'vp_can_approve', 'vp_review_hash'] as $function) {
        if (!function_exists($function)) {
            $fail('Required integration unavailable: ' . $function);
        }
    }
    if (!is_callable(['WPSEO_Taxonomy_Meta', 'set_values']) ||
        !is_callable(['WPSEO_Taxonomy_Meta', 'get_term_meta']) ||
        !is_callable(['WPSEO_Taxonomy_Meta', 'validate_term_meta_data'])) {
        $fail('The inspected Yoast taxonomy metadata API is unavailable.');
    }
    $term_watcher = YoastSEO()->classes->get(\Yoast\WP\SEO\Integrations\Watchers\Indexable_Term_Watcher::class);
    if (!is_object($term_watcher) || !is_callable([$term_watcher, 'build_indexable'])) {
        $fail('The inspected Yoast term indexable watcher is unavailable.');
    }

    $term = get_term_by('slug', 'life-between-cultures', 'category');
    $post = get_page_by_path('about', OBJECT, 'page');
    if (!$term || is_wp_error($term) || $term->name !== 'Life between cultures' ||
        pll_get_term_language($term->term_id, 'slug') !== 'en') {
        $fail('Expected English Life between cultures category was not found.');
    }
    if (!$post || $post->post_status !== 'publish' || $post->post_name !== 'about' ||
        $post->post_title !== 'A little about me.' ||
        pll_get_post_language($post->ID, 'slug') !== 'en') {
        $fail('Expected published English About page was not found.');
    }
    $source = 'I write about relationships, personal growth, business and technology, and life between cultures.';
    $replacement = 'I write about relationships, personal growth, business and technology, and <a href="/category/life-between-cultures/">life between cultures</a>.';
    $source_count = substr_count($post->post_content, $source);
    $replacement_count = substr_count($post->post_content, $replacement);
    if (!(($source_count === 1 && $replacement_count === 0) ||
        ($source_count === 0 && $replacement_count === 1))) {
        $fail('About must contain exactly one reviewed original or already-linked sentence.');
    }
    $content = $source_count === 1
        ? str_replace($source, $replacement, $post->post_content)
        : $post->post_content;
    $desired = [
        'wpseo_title' => 'Life Between Cultures: Home and Diaspora | Valon Asani',
        'wpseo_desc' => 'Personal reflections on home, identity and life between cultures, from returning to Kosovo to meeting Albanian communities across Europe.',
    ];
    $taxonomy_option = get_option('wpseo_taxonomy_meta', []);
    if (!is_array($taxonomy_option)) {
        $fail('Unexpected Yoast taxonomy option format.');
    }
    $raw_meta = $taxonomy_option['category'][$term->term_id] ?? [];
    $meta = WPSEO_Taxonomy_Meta::get_term_meta($term, 'category');
    if (!is_array($raw_meta) || !is_array($meta)) {
        $fail('Unexpected category metadata format.');
    }
    foreach ($desired as $key => $value) {
        if (!isset($meta[$key]) || !in_array($meta[$key], ['', $value], true)) {
            $fail('Unexpected existing ' . $key . '; preserve it and review the plan.');
        }
    }
    // Passing the complete current set avoids resetting another Yoast field.
    $expected_meta = array_replace($meta, $desired);
    $expected_raw = WPSEO_Taxonomy_Meta::validate_term_meta_data($expected_meta, $meta);
    $without_target = static function (array $values) use ($desired): array {
        $values = array_diff_key($values, $desired);
        ksort($values);
        return $values;
    };
    if ($without_target($expected_raw) !== $without_target($raw_meta)) {
        $fail('Yoast would normalize unrelated stored category metadata; no changes made.');
    }
    $change_term = $raw_meta !== $expected_raw;
    $change_post = $post->post_content !== $content;
    $post_meta = get_post_meta($post->ID);
    $approval_needed = $change_post && get_post_meta($post->ID, '_vp_requires_review', true) === '1';
    $expected_post_meta = $post_meta;
    $owner = (int) get_option('vp_editorial_owner', 0);
    if ($approval_needed) {
        foreach (['_vp_approved_hash', '_vp_approved_by'] as $key) {
            if (isset($post_meta[$key]) && count($post_meta[$key]) !== 1) {
                $fail('Unexpected duplicate approval metadata: ' . $key);
            }
        }
        $old_hash = $post_meta['_vp_approved_hash'][0] ?? '';
        if (!is_string($old_hash) || !hash_equals(vp_review_hash($post->post_title, $post->post_content), $old_hash)) {
            $fail('The current published About content must match its existing approval hash.');
        }
        $expected_post_meta['_vp_approved_hash'] = [vp_review_hash($post->post_title, $content)];
        $expected_post_meta['_vp_approved_by'] = [(string) $owner];
    }
    $before_post = $post->to_array();
    $before_term = $term->to_array();
    echo wp_json_encode([
        'mode' => !empty($options['apply']) ? 'apply' : 'dry-run',
        'category_id' => $term->term_id,
        'about_id' => $post->ID,
        'category_changes' => $change_term ? [
            'before' => array_intersect_key($meta, $desired), 'after' => $desired,
        ] : [],
        'about_change' => $change_post ? ['before' => $source, 'after' => $replacement] : null,
        'record_exact_revision_approval_by_owner' => $approval_needed ? $owner : null,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    if (empty($options['apply'])) {
        echo "Dry run complete; no backup, revision or content changes made.\n";
        return;
    }
    if (!$change_term && !$change_post) {
        echo "Already applied; no writes needed.\n";
        return;
    }
    if (!current_user_can('manage_options') || !current_user_can('manage_categories') ||
        !current_user_can('edit_post', $post->ID) || !vp_can_approve()) {
        $fail('Apply requires the configured editorial owner; pass --user=OWNER_ID.');
    }
    if ($change_post && !wp_revisions_enabled($post)) {
        $fail('About revisions must be enabled before applying.');
    }
    $backup_dir = realpath($options['backup-dir'] ?? '');
    if (!$backup_dir || !is_dir($backup_dir) || !is_writable($backup_dir) ||
        (fileperms($backup_dir) & 0777) !== 0700) {
        $fail('--backup-dir must be an existing writable private directory with mode 0700.');
    }
    $inside = static function (string $path, string $root): bool {
        return $path === $root || str_starts_with($path, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
    };
    foreach ([ABSPATH, WP_CONTENT_DIR, $_SERVER['DOCUMENT_ROOT'] ?? ''] as $root) {
        $resolved = $root !== '' ? realpath($root) : false;
        if ($resolved && $inside($backup_dir, $resolved)) {
            $fail('Backup directory must be outside every web/content root.');
        }
    }
    $backup = wp_json_encode([
        'created_utc' => gmdate('c'), 'site_url' => home_url('/'),
        'post' => $before_post, 'post_meta' => $post_meta,
        'term' => $before_term, 'wpseo_taxonomy_meta' => $taxonomy_option,
        'planned_content' => $content, 'planned_term_meta' => $expected_raw,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (!is_string($backup)) {
        $fail('Could not encode the recovery backup.');
    }
    $backup_path = $backup_dir . '/search-discovery-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.json';
    $previous_umask = umask(0077);
    try {
        $handle = fopen($backup_path, 'xb');
    } finally {
        umask($previous_umask);
    }
    if (!$handle) {
        $fail('Could not exclusively create the private backup.');
    }
    $written = fwrite($handle, $backup);
    $flushed = fflush($handle);
    if (function_exists('fsync')) {
        $flushed = fsync($handle) && $flushed;
    }
    fclose($handle);
    if ($written !== strlen($backup) || !$flushed ||
        (fileperms($backup_path) & 0777) !== 0600 ||
        file_get_contents($backup_path) !== $backup) {
        $fail('Backup verification failed; no target changes made.');
    }
    echo 'Private recovery backup: ' . $backup_path . "\n";

    // Recheck immediately before mutations, including unrelated fields that
    // must survive the migration. A changed editor revision fails closed.
    $assert_unchanged = static function () use ($post, $term, $before_post, $post_meta, $before_term, $taxonomy_option, $fail): void {
        clean_post_cache($post->ID);
        clean_term_cache($term->term_id, 'category');
        wp_cache_delete('wpseo_taxonomy_meta', 'options');
        wp_cache_delete('alloptions', 'options');
        if (get_post($post->ID)->to_array() !== $before_post ||
            get_post_meta($post->ID) !== $post_meta ||
            get_term($term->term_id, 'category')->to_array() !== $before_term ||
            get_option('wpseo_taxonomy_meta', []) !== $taxonomy_option ||
            pll_get_post_language($post->ID, 'slug') !== 'en' ||
            pll_get_term_language($term->term_id, 'slug') !== 'en') {
            $fail('Content, metadata or language changed during preflight; rerun the dry run.');
        }
    };
    $assert_unchanged();
    if ($change_post) {
        $force_revision = static fn(): bool => false;
        add_filter('wp_save_post_revision_check_for_changes', $force_revision);
        try {
            $revision_id = wp_save_post_revision($post->ID);
        } finally {
            remove_filter('wp_save_post_revision_check_for_changes', $force_revision);
        }
        $revision = $revision_id && !is_wp_error($revision_id) ? get_post($revision_id) : null;
        if (!$revision || $revision->post_content !== $post->post_content ||
            $revision->post_title !== $post->post_title || (int) $revision->post_parent !== (int) $post->ID) {
            $fail('Could not verify an About revision; no target changes made.');
        }
        echo 'Original About revision: ' . $revision_id . "\n";
        $assert_unchanged();
        // The owner authorized this exact link edit. Record only its new hash
        // through the existing approval mechanism; keep the review flag intact.
        $restore_approval = static function () use ($post, $post_meta): void {
            foreach (['_vp_approved_hash', '_vp_approved_by'] as $key) {
                if (isset($post_meta[$key])) {
                    update_post_meta($post->ID, $key, $post_meta[$key][0]);
                } else {
                    delete_post_meta($post->ID, $key);
                }
            }
        };
        try {
            if ($approval_needed) {
                update_post_meta($post->ID, '_vp_approved_hash', $expected_post_meta['_vp_approved_hash'][0]);
                update_post_meta($post->ID, '_vp_approved_by', $owner);
                if (get_post_meta($post->ID) !== $expected_post_meta) {
                    $fail('Could not verify exact-revision approval.');
                }
            }
            $saved = wp_update_post(wp_slash(['ID' => $post->ID, 'post_content' => $content]), true);
            if (is_wp_error($saved) || (int) $saved !== (int) $post->ID) {
                $fail('About update failed.');
            }
        } catch (Throwable $error) {
            if ($approval_needed && get_post($post->ID)->post_content === $post->post_content) {
                $restore_approval();
            }
            $fail($error->getMessage() . ' Recovery backup: ' . $backup_path);
        }
        $actual = get_post($post->ID)->to_array();
        $expected_post = array_replace($before_post, ['post_content' => $content]);
        // WordPress owns modification timestamps; all other stored fields stay intact.
        foreach (['post_modified', 'post_modified_gmt'] as $field) {
            unset($actual[$field], $expected_post[$field]);
        }
        if ($actual !== $expected_post || get_post_meta($post->ID) !== $expected_post_meta) {
            $fail('About verification failed; stop and recover using: ' . $backup_path);
        }
    }
    if ($change_term) {
        if (get_option('wpseo_taxonomy_meta', []) !== $taxonomy_option) {
            $fail('Taxonomy metadata changed during the page update; recovery backup: ' . $backup_path);
        }
        WPSEO_Taxonomy_Meta::set_values($term->term_id, 'category', $expected_meta);
        $expected_option = $taxonomy_option;
        $expected_option['category'][$term->term_id] = $expected_raw;
        if (get_option('wpseo_taxonomy_meta', []) !== $expected_option) {
            $fail('Yoast taxonomy verification failed; recovery backup: ' . $backup_path);
        }
        // The taxonomy API writes its option without firing edited_term.
        // Rebuild just this term through the same watcher used by Yoast's UI.
        $term_watcher->build_indexable($term->term_id);
        clean_term_cache($term->term_id, 'category');
    }
    echo "Saved and verified the reviewed changes. Check public HTML after page-cache invalidation.\n";
})($valon_search_arguments);
