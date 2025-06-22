<?php
/**
 * Template part for displaying results in search pages
 *
 * @package Valon
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header">
        <?php the_title(sprintf('<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url(get_permalink())), '</a></h2>'); ?>

        <div class="entry-meta">
            <?php
            echo '<time class="entry-date" datetime="' . esc_attr(get_the_date('c')) . '">';
            echo get_the_date();
            echo '</time>';
            
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

    <div class="entry-summary">
        <?php the_excerpt(); ?>
        <p><a href="<?php echo esc_url(get_permalink()); ?>" class="read-more"><?php esc_html_e('Continue reading →', 'valon'); ?></a></p>
    </div>
</article>

<div class="content-separator">∗ ∗ ∗</div>
