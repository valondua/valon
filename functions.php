<?php
/**
 * Valon Theme Functions
 * 
 * @package Valon
 * @since 1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Set up theme defaults and register support for various WordPress features
 */
function valon_setup() {
    // Add theme support for various features
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
    
    // Add support for custom logo
    add_theme_support('custom-logo', array(
        'height'      => 100,
        'width'       => 400,
        'flex-height' => true,
        'flex-width'  => true,
    ));
    
    // Register navigation menus
    register_nav_menus(array(
        'primary' => esc_html__('Primary Menu', 'valon'),
    ));
    
    // Add support for selective refresh for widgets
    add_theme_support('customize-selective-refresh-widgets');
}
add_action('after_setup_theme', 'valon_setup');

/**
 * Enqueue scripts and styles
 */
function valon_scripts() {
    // Enqueue main stylesheet
    wp_enqueue_style('valon-style', get_stylesheet_uri(), array(), '1.1');
    
    // Enqueue Google Fonts
    wp_enqueue_style('valon-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:wght@400;500;600&display=swap', array(), null);
    
    // Enqueue header scroll script
    wp_enqueue_script('valon-header-scroll', get_template_directory_uri() . '/js/header-scroll.js', array(), '1.0', true);
    
    // Enqueue comment reply script on singular posts/pages
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
}
add_action('wp_enqueue_scripts', 'valon_scripts');

/**
 * Custom excerpt length
 */
function valon_excerpt_length($length) {
    return 40;
}
add_filter('excerpt_length', 'valon_excerpt_length');

/**
 * Custom excerpt more text
 */
function valon_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'valon_excerpt_more');

/**
 * Add custom body classes
 */
function valon_body_classes($classes) {
    // Add a class for the theme
    $classes[] = 'valon-theme';
    
    // Add class if no sidebar
    if (!is_active_sidebar('sidebar-1')) {
        $classes[] = 'no-sidebar';
    }
    
    return $classes;
}
add_filter('body_class', 'valon_body_classes');

/**
 * Register widget area
 */
function valon_widgets_init() {
    register_sidebar(array(
        'name'          => esc_html__('Sidebar', 'valon'),
        'id'            => 'sidebar-1',
        'description'   => esc_html__('Add widgets here.', 'valon'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
}
add_action('widgets_init', 'valon_widgets_init');

/**
 * Customizer additions
 */
function valon_customize_register($wp_customize) {
    // Add a setting for the site tagline
    $wp_customize->add_setting('valon_tagline', array(
        'default'           => 'A journey of consciousness and authentic expression',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    
    $wp_customize->add_control('valon_tagline', array(
        'label'   => __('Site Tagline', 'valon'),
        'section' => 'title_tagline',
        'type'    => 'text',
    ));
    
    // Add color options
    $wp_customize->add_section('valon_colors', array(
        'title'    => __('Valon Colors', 'valon'),
        'priority' => 30,
    ));
    
    $wp_customize->add_setting('valon_accent_color', array(
        'default'           => '#2c5aa0',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'valon_accent_color', array(
        'label'   => __('Accent Color', 'valon'),
        'section' => 'valon_colors',
    )));
}
add_action('customize_register', 'valon_customize_register');

/**
 * Output custom CSS for customizer options
 */
function valon_customizer_css() {
    $accent_color = get_theme_mod('valon_accent_color', '#2c5aa0');
    
    if ($accent_color !== '#2c5aa0') {
        echo '<style type="text/css">';
        echo 'a, .main-navigation a:hover, .pull-quote, blockquote { color: ' . esc_attr($accent_color) . '; }';
        echo 'blockquote { border-left-color: ' . esc_attr($accent_color) . '; }';
        echo 'blockquote::before { color: ' . esc_attr($accent_color) . '; }';
        echo '</style>';
    }
}
add_action('wp_head', 'valon_customizer_css');

/**
 * Filter content to add philosophical formatting
 */
function valon_content_filter($content) {
    // Wrap quotes in special styling
    $content = preg_replace('/^"([^"]+)"$/m', '<div class="pull-quote">"$1"</div>', $content);
    
    // Add content separators
    $content = str_replace('***', '<div class="content-separator">∗ ∗ ∗</div>', $content);
    
    return $content;
}
add_filter('the_content', 'valon_content_filter');

/**
 * Custom post navigation
 */
function valon_post_navigation() {
    the_post_navigation(array(
        'prev_text' => '<span class="nav-subtitle">' . esc_html__('Previous:', 'valon') . '</span> <span class="nav-title">%title</span>',
        'next_text' => '<span class="nav-subtitle">' . esc_html__('Next:', 'valon') . '</span> <span class="nav-title">%title</span>',
    ));
}

/**
 * Add reading time estimate
 */
function valon_reading_time($content = '') {
    if (empty($content)) {
        $content = get_the_content();
    }
    
    $word_count = str_word_count(strip_tags($content));
    $reading_time = ceil($word_count / 200); // Average reading speed
    
    return $reading_time . ' min read';
}

/**
 * Default menu fallback
 */
function valon_default_menu() {
    echo '<ul>';
    echo '<li><a href="' . esc_url(home_url('/')) . '">' . esc_html__('Home', 'valon') . '</a></li>';
    if (get_option('show_on_front') == 'page') {
        echo '<li><a href="' . esc_url(get_permalink(get_option('page_for_posts'))) . '">' . esc_html__('Blog', 'valon') . '</a></li>';
    }
    wp_list_pages(array(
        'title_li' => '',
        'depth' => 1,
        'number' => 5
    ));
    echo '</ul>';
}

/**
 * Security enhancements
 */
function valon_security() {
    // Remove WordPress version from head
    remove_action('wp_head', 'wp_generator');
    
    // Hide WP version from RSS feeds
    function valon_remove_version() {
        return '';
    }
    add_filter('the_generator', 'valon_remove_version');
}
add_action('init', 'valon_security');
