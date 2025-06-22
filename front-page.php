<?php
/**
 * Template for displaying the front page
 *
 * @package Valon
 */

get_header(); ?>

<main id="primary" class="site-main">
    <div class="container">
        
        <!-- Naval-style Blog Layout -->
        <?php if (have_posts()) : ?>
            <div class="blog-posts-grid">
            <?php
            /* Start the Loop */
            while (have_posts()) :
                the_post();
                get_template_part('template-parts/content', get_post_type());
            endwhile;
            ?>
            </div>
            
            <?php
            // Previous/next page navigation
            the_posts_navigation(array(
                'prev_text' => __('← Older thoughts', 'valon'),
                'next_text' => __('Newer thoughts →', 'valon'),
            ));
            ?>

        <?php else : ?>
            <?php get_template_part('template-parts/content', 'none'); ?>
        <?php endif; ?>

    </div>
</main><!-- #primary -->

<?php
get_footer();
