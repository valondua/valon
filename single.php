<?php
/**
 * Template for displaying single posts
 *
 * @package Valon
 */

get_header(); ?>

<main id="primary" class="site-main">
    <div class="container">

        <?php
        while (have_posts()) :
            the_post();
        ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="article-header">
                <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                
                <div class="entry-meta">
                    <?php
                    echo '<time class="entry-date" datetime="' . esc_attr(get_the_date('c')) . '">';
                    echo get_the_date();
                    echo '</time>';
                    
                    // Add reading time
                    echo ' • ' . valon_reading_time();
                    
                    // Add categories
                    $categories = get_the_category();
                    if (!empty($categories)) {
                        echo ' • ';
                        foreach ($categories as $category) {
                            echo '<a href="' . esc_url(get_category_link($category->term_id)) . '">' . esc_html($category->name) . '</a>';
                            if ($category !== end($categories)) {
                                echo ', ';
                            }
                        }
                    }
                    ?>
                </div>
            </header>

            <div class="entry-content">
                <?php
                the_content(sprintf(
                    wp_kses(
                        /* translators: %s: Name of current post. Only visible to screen readers */
                        __('Continue reading<span class="screen-reader-text"> "%s"</span>', 'valon'),
                        array(
                            'span' => array(
                                'class' => array(),
                            ),
                        )
                    ),
                    get_the_title()
                ));

                wp_link_pages(array(
                    'before' => '<div class="page-links">' . esc_html__('Pages:', 'valon'),
                    'after'  => '</div>',
                ));
                ?>
            </div>

            <footer class="entry-footer">
                <?php
                // Tags
                $tags = get_the_tags();
                if ($tags) {
                    echo '<div class="entry-tags">';
                    echo '<span class="tags-label">' . esc_html__('Tags:', 'valon') . '</span> ';
                    foreach ($tags as $tag) {
                        echo '<a href="' . esc_url(get_tag_link($tag->term_id)) . '">#' . esc_html($tag->name) . '</a> ';
                    }
                    echo '</div>';
                }
                ?>
            </footer>
        </article>

        <?php
        // Post navigation
        valon_post_navigation();

        // If comments are open or we have at least one comment, load up the comment template.
        if (comments_open() || get_comments_number()) :
            comments_template();
        endif;

        endwhile; // End of the loop.
        ?>

    </div>
</main><!-- #primary -->

<?php
get_footer();
