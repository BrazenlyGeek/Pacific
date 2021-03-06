<?php
/**
 * Pacific initiation
 *
 * Initializes Pacific's features and includes all necessary files.
 *
 * @package Pacific
 */

# Prevent direct access to this file
if ( 1 == count( get_included_files() ) ) {
	header( 'HTTP/1.1 403 Forbidden' );
	return;
}

/**
 * File inclusions
 */
require get_template_directory() . '/inc/customize.php'; # Enables user customization via admin panel
require get_template_directory() . '/inc/hooks.php'; # Enables user customization via WordPress plugin API
require get_template_directory() . '/inc/template-tags.php'; # Contains functions that output HTML

/**
 * Set the max width for embedded content
 *
 * @link http://codex.wordpress.org/Content_Width
 */
if ( ! isset( $content_width ) ) {
	$content_width = 720;
}

if ( ! function_exists( 'pacific_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 */
	function pacific_setup() {
		# Localization
		load_theme_textdomain( 'pacific', get_template_directory() . '/languages' );

		# Enable WordPress theme features
		add_theme_support( 'automatic-feed-links' ); # @link http://codex.wordpress.org/Automatic_Feed_Links
		$custom_background_support = array(
			'default-color'          => '',
			'default-image'          => get_template_directory_uri() . '/assets/img/default-background.jpg',
			'wp-head-callback'       => '_custom_background_cb',
			'admin-head-callback'    => '',
			'admin-preview-callback' => '',
		);
		add_theme_support( 'custom-background', $custom_background_support ); # @link http://codex.wordpress.org/Custom_Backgrounds
		# @TODO Add `gallery` support to this; will require various CSS tweaks
		add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'caption' ) ); # @link http://codex.wordpress.org/Function_Reference/add_theme_support#HTML5
		add_theme_support( 'post-formats', array( 'aside', 'audio', 'chat', 'gallery', 'image', 'quote', 'status' ) ); # @link http://codex.wordpress.org/Post%20Formats
		add_theme_support( 'post-thumbnails' ); # @link http://codex.wordpress.org/Post%20Thumbnails
		add_theme_support( 'structured-post-formats', array( 'link', 'video' ) );
		add_theme_support( 'title-tag' ); # @link http://codex.wordpress.org/Title_Tag
		add_theme_support( 'tha-hooks', array( 'all' ) ); # @link https://github.com/zamoose/themehookalliance

		# Add style to the post editor for a more WYSIWYG experience
		add_editor_style( array( 'assets/css/editor-style.css' ) );

		# Pacific has one navigation menu; register it with WordPress
		register_nav_menu( 'primary', __( 'Navigation Menu', 'pacific' ) );

		# Add filters
		add_filter( 'comments_popup_link_attributes', function() { return ' itemprop="discussionUrl"'; } ); # schema.org property on comments links
		add_filter( 'current_theme_supports-tha_hooks', '__return_true' ); # Enables checking for THA hooks
		add_filter( 'style_loader_tag', 'pacific_filter_styles', 10, 2 ); # Filters style tags as needed
		add_filter( 'the_content_more_link', 'modify_read_more_link' ); # Enhances appearance of "Read more..." link
		add_filter( 'use_default_gallery_style', '__return_false' ); # Disable default WordPress gallery styling
		add_filter( 'get_comment_author_link', function( $link ) { return str_replace( 'class', 'itemprop=\'url\' class', $link ); }); # Add microdata to author link

		# Add actions
		add_action( 'pacific_html_before', 'pacific_doctype' ); # Outputs HTML doctype
		add_action( 'pacific_404_content', 'pacific_output_404_content' ); # Outputs a helpful message on 404 pages
		add_action( 'widgets_init', 'pacific_widgets_init' ); # Registers Pacific's sidebar
		add_action( 'wp_enqueue_scripts', 'pacific_scripts_styles' ); # Enqueue's Pacific's scripts & styles
		add_action( 'admin_enqueue_scripts', 'pacific_admin_styles', 10 ); # Enqueue Pacific's admin styles
		add_action( 'pacific_comment_author', 'pacific_output_comment_author', 10, 3 ); # Default comment author/gravatar display
		add_action( 'pacific_comment_metadata', 'pacific_output_comment_metadata', 10, 3 ); # Default comment metadata
		add_action( 'pacific_footer', 'pacific_credit_wordpress' ); # Add WordPress credit link

		# @pluginSupport Removes default styling and output from Subtitles (@link https://wordpress.org/plugins/subtitles/)
		if ( class_exists( 'Subtitles' ) &&  method_exists( 'Subtitles', 'subtitle_styling' ) ) {
			remove_action( 'wp_head', array( Subtitles::getInstance(), 'subtitle_styling' ) );
			remove_filter( 'the_title', array( Subtitles::getInstance(), 'the_subtitle' ), 10, 2 );
		}
	}
endif;
add_action( 'after_setup_theme', 'pacific_setup' );

if ( ! function_exists( 'pacific_widgets_init' ) ) :
	/**
	 * Registers our sidebar with WordPress
	 */
	function pacific_widgets_init() {
		register_sidebar( array(
			'name'          => __( 'Main Widget Area', 'pacific' ),
			'id'            => 'sidebar-1',
			'description'   => __( 'Appears in the sidebar section of the site', 'pacific' ),
			'before_widget' => "\t\t\t\t\t" . '<aside id="%1$s" class="widget %2$s">' . "\n",
			'after_widget'  => "\t\t\t\t\t</aside>\n",
			'before_title'  => "\t\t\t\t\t\t<h3 class='widget-title'>",
			'after_title'   => "</h3>\n",
		) );
	}
endif;

if ( ! function_exists( 'pacific_scripts_styles' ) ) :
	/**
	 * Sets up necessary scripts and styles
	 */
	function pacific_scripts_styles() {
		global $wp_version;

		# Get the current version of Pacific, even if a child theme is being used
		$version = wp_get_theme( wp_get_theme()->template )->get( 'Version' );

		# When needed, enqueue comment-reply script
		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}

		# Minified versions of CSS & JS are used, unless a development constant is set
		if ( defined( 'WP_ENV' ) && 'development' === WP_ENV ) {
			$assets = array(
				'css' => '/assets/css/pacific.css',
				'js' => '/assets/js/pacific.js',
				'shiv' => '/assets/js/html5shiv.js',
			);
		} else {
			$assets = array(
				'css' => '/assets/css/pacific.min.css',
				'js' => '/assets/js/pacific.min.js',
				'shiv' => '/assets/js/html5shiv.min.js',
			);
		}

		wp_enqueue_style( 'pacific-fonts', pacific_fonts_url(), array(), null ); # Web fonts
		wp_enqueue_style( 'pacific-theme', get_template_directory_uri() . $assets['css'], array(), $version ); # Pacific's styling
		wp_enqueue_script( 'pacific-js', get_template_directory_uri() . $assets['js'], array( 'jquery' ), $version, true ); # Pacific's scripting
		wp_enqueue_style( 'pacific-style', get_stylesheet_uri() ); # Load main stylesheet, for child theme supports

		# If the `script_loader_tag` filter is unavailable, this script will be added via the `wp_head` hook
		if ( version_compare( '4.1', $wp_version, '<=' ) ) {
			wp_enqueue_script( 'html5shiv', get_template_directory_uri() . $assets['shiv'], array(), '3.7.3', false );
		}
	}
endif;

function pacific_admin_styles() {
	wp_enqueue_style( 'pacific_admin_stylesheet', get_template_directory_uri() . '/assets/css/admin-style.css', array(), '1.1', false );
}

# The following function uses a filter introduced in WP 4.1
if ( version_compare( '4.1', $wp_version, '<=' ) ) :
	if ( ! function_exists( 'pacific_filter_scripts' ) ) :
		/**
		 * Filters enqueued script output to better suit Pacific's needs
		 */
		function pacific_filter_scripts( $tag, $handle, $src ) {
			# Remove `type` attribute (unneeded in HTML5)
			$tag = str_replace( ' type=\'text/javascript\'', '', $tag );

			# Apply conditionals to html5shiv for legacy IE
			if ( 'html5shiv' === $handle ) {
				$tag = "<!--[if lt IE 9]>\n$tag<![endif]-->\n";
			}

			return $tag;
		}
	endif;
	add_filter( 'script_loader_tag', 'pacific_filter_scripts', 10, 3 );
else : # If the `script_loader_tag` filter is unavailable...
	/**
	 * Adds html5shiv the "old" way (WP < 4.1)
	 */
	function pacific_add_html5shiv() {
		echo "<!--[if lt IE 9]>\n<scr" . 'ipt src="' . esc_url( get_template_directory_uri() ) . '/assets/js/html5shiv.min.js"></scr' . "ipt>\n<![endif]-->"; # This is a hack to disguise adding the script without using WordPress' enqueue function
	}
	add_action( 'wp_head', 'pacific_add_html5shiv' );
endif;

if ( ! function_exists( 'pacific_filter_styles' ) ) :
	/**
	 * Filter enqueued style output to better suit HTML5
	 */
	function pacific_filter_styles( $tag, $handle ) {
		# Get rid of unnecessary `type` attribute
		$tag = str_replace( ' type=\'text/css\'', '', $tag );

		# Get rid of double-spaces
		$tag = str_replace( '  ', ' ', $tag );

		return $tag;
	}
endif;

/**
 * Enhances "Read more..." links with Bootstrap button styling
 */
function modify_read_more_link() {
	return '<a class="btn btn-default btn-sm" href="' . esc_url( get_permalink() ) . '">' . sprintf( __( 'Continue reading %s', 'pacific' ), '<i class="fa fa-angle-double-right"></i></a>' );
}