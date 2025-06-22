<?php
/**
 * Template part for displaying posts
 *
 * @package Valon
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header">
        <?php
        if (is_singular()) :
            the_title('<h1 class="entry-title">', '</h1>');
        else :
            the_title('<h2 class="entry-title"><a href="' . esc_url(get_permalink()) . '" rel="bookmark">', '</a></h2>');
        endif;
        ?>

        <?php if (!is_singular()) : ?>
        <div class="entry-meta">
            <?php
            echo '<time class="entry-date" datetime="' . esc_attr(get_the_date('c')) . '">';
            echo get_the_date();
            echo '</time>';
            
            // Add reading time for excerpts
            echo ' • ' . valon_reading_time();
            ?>
        </div>
        <?php endif; ?>
    </header>

    <div class="entry-content">
        <?php
        if (is_singular()) {
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
        } else {
            the_excerpt();
            echo '<p><a href="' . esc_url(get_permalink()) . '" class="read-more">' . esc_html__('Continue reading →', 'valon') . '</a></p>';
        }

        if (is_singular()) {
            wp_link_pages(array(
                'before' => '<div class="page-links">' . esc_html__('Pages:', 'valon'),
                'after'  => '</div>',
            ));
        }
        ?>
    </div>

    <?php if (!is_singular()) : ?>
    <footer class="entry-footer">
        <div class="entry-meta">
            <?php
            // Categories
            $categories = get_the_category();
            if (!empty($categories)) {
                echo '<span class="categories">';
                foreach ($categories as $category) {
                    echo '<a href="' . esc_url(get_category_link($category->term_id)) . '">' . esc_html($category->name) . '</a>';
                    if ($category !== end($categories)) {
                        echo ', ';
                    }
                }
                echo '</span>';
            }
            ?>
        </div>
    </footer>
    <?php endif; ?>
</article>

<?php if (!is_singular()) : ?>
<div class="content-separator">∗ ∗ ∗</div>
<?php endif; ?>
