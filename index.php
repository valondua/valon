<?php
/**
 * The main template file
 *
 * @package Valon
 */

get_header(); ?>

<main id="primary" class="site-main">
    <div class="container">

        <?php if (have_posts()) : ?>

            <?php if (is_home() && !is_front_page()) : ?>
                <header class="page-header">
                    <h1 class="page-title"><?php single_post_title(); ?></h1>
                </header>
            <?php endif; ?>

            <div class="blog-posts-grid">
            <?php
            /* Start the Loop */
            while (have_posts()) :
                the_post();

                /*
                 * Include the Post-Type-specific template for the content.
                 * If you want to override this in a child theme, then include a file
                 * called content-___.php (where ___ is the Post Type name) and that will be used instead.
                 */
                get_template_part('template-parts/content', get_post_type());

            endwhile;
            ?>
            </div>
            <?php

            // Previous/next page navigation
            the_posts_navigation(array(
                'prev_text' => __('← Older posts', 'valon'),
                'next_text' => __('Newer posts →', 'valon'),
            ));

        else :

            get_template_part('template-parts/content', 'none');

        endif;
        ?>

    </div>
</main><!-- #primary -->

<?php
get_footer();
