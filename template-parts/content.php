<?php
/**
 * Template part for displaying posts
 *
 * @package Valon
 */
?>

<?php if (is_singular()) : ?>
    <!-- Single Post Layout -->
    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <header class="entry-header">
            <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
            <div class="entry-meta">
                <?php
                echo '<time class="entry-date" datetime="' . esc_attr(get_the_date('c')) . '">';
                echo get_the_date();
                echo '</time>';
                echo ' • ' . valon_reading_time();
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
    </article>

<?php else : ?>
    <!-- Blog List Layout (Naval-style) -->
    <article id="post-<?php the_ID(); ?>" <?php post_class('blog-post-card'); ?>>
        <div class="post-card-content">
            <h2 class="post-card-title">
                <a href="<?php echo esc_url(get_permalink()); ?>" rel="bookmark">
                    <?php the_title(); ?>
                </a>
            </h2>
            
            <div class="post-card-excerpt">
                <?php 
                // Get custom excerpt or create one
                if (has_excerpt()) {
                    echo wp_trim_words(get_the_excerpt(), 25, '...');
                } else {
                    echo wp_trim_words(get_the_content(), 25, '...');
                }
                ?>
            </div>
            
            <div class="post-card-meta">
                <time class="post-date" datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                    <?php echo get_the_date('M j, Y'); ?>
                </time>
                <span class="meta-separator">•</span>
                <span class="reading-time"><?php echo valon_reading_time(); ?></span>
                
                <?php
                // Categories
                $categories = get_the_category();
                if (!empty($categories)) {
                    echo '<span class="meta-separator">•</span>';
                    echo '<span class="post-category">';
                    echo '<a href="' . esc_url(get_category_link($categories[0]->term_id)) . '">' . esc_html($categories[0]->name) . '</a>';
                    echo '</span>';
                }
                ?>
            </div>
        </div>
    </article>
<?php endif; ?>
