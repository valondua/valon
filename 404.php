<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @package Valon
 */

get_header(); ?>

<main id="primary" class="site-main">
    <div class="container">

        <section class="error-404 not-found">
            <header class="page-header">
                <h1 class="page-title"><?php esc_html_e('That page can&rsquo;t be found.', 'valon'); ?></h1>
            </header>

            <div class="page-content">
                <p><?php esc_html_e('It looks like nothing was found at this location. Maybe try a search?', 'valon'); ?></p>

                <?php get_search_form(); ?>

                <div class="content-separator">∗ ∗ ∗</div>

                <p><?php esc_html_e('Perhaps you would like to explore some of our recent content:', 'valon'); ?></p>

                <?php
                // Show recent posts
                $recent_posts = wp_get_recent_posts(array(
                    'numberposts' => 5,
                    'post_status' => 'publish'
                ));

                if ($recent_posts) {
                    echo '<ul>';
                    foreach ($recent_posts as $post) {
                        echo '<li><a href="' . get_permalink($post['ID']) . '">' . $post['post_title'] . '</a></li>';
                    }
                    echo '</ul>';
                }
                wp_reset_query();
                ?>
            </div>
        </section>

    </div>
</main><!-- #primary -->

<?php
get_footer();
