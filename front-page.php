<?php
/**
 * Template for displaying the front page
 *
 * @package Valon
 */

get_header(); ?>

<main id="primary" class="site-main">
    <div class="container">

        <?php if (is_front_page() && is_home()) : ?>
            <!-- This is the default homepage -->
            
            <div class="homepage-intro">
                <div class="pull-quote">
                    "A journey of consciousness and authentic expression"
                </div>
                
                <div class="content-separator">∗ ∗ ∗</div>
            </div>

        <?php elseif (is_front_page()) : ?>
            <!-- This is a static front page -->
            
            <?php
            while (have_posts()) :
                the_post();
            ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <header class="article-header">
                        <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                    </header>

                    <div class="entry-content">
                        <?php the_content(); ?>
                    </div>
                </article>
            <?php
            endwhile;
            ?>

        <?php else : ?>
            <!-- This is the blog homepage -->
            
            <header class="page-header">
                <h1 class="page-title"><?php esc_html_e('Recent Thoughts', 'valon'); ?></h1>
            </header>

        <?php endif; ?>

        <?php if (is_home()) : ?>
            
            <?php if (have_posts()) : ?>

                <?php
                /* Start the Loop */
                while (have_posts()) :
                    the_post();
                    get_template_part('template-parts/content', get_post_type());
                endwhile;

                // Previous/next page navigation
                the_posts_navigation(array(
                    'prev_text' => __('← Older thoughts', 'valon'),
                    'next_text' => __('Newer thoughts →', 'valon'),
                ));

            else :

                get_template_part('template-parts/content', 'none');

            endif;
            
        endif; ?>

    </div>
</main><!-- #primary -->

<?php
get_footer();
