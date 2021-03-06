<?php
// Register Custom Post Type
function api_purchase_post_type() {

	$labels = array(
		'name'                  => _x( 'API Purchase', 'Post Type General Name', 'text_domain' ),
		'singular_name'         => _x( 'API Purchase', 'Post Type Singular Name', 'text_domain' ),
		'menu_name'             => __( 'API Purchase', 'text_domain' ),
		'name_admin_bar'        => __( 'API Purchase', 'text_domain' ),
		'archives'              => __( 'Item Archives', 'text_domain' ),
		'attributes'            => __( 'Item Attributes', 'text_domain' ),
		'parent_item_colon'     => __( 'Parent Item:', 'text_domain' ),
		'all_items'             => __( 'All Items', 'text_domain' ),
		'add_new_item'          => __( 'Add New Item', 'text_domain' ),
		'add_new'               => __( 'Add New', 'text_domain' ),
		'new_item'              => __( 'New Item', 'text_domain' ),
		'edit_item'             => __( 'Edit Item', 'text_domain' ),
		'update_item'           => __( 'Update Item', 'text_domain' ),
		'view_item'             => __( 'View Item', 'text_domain' ),
		'view_items'            => __( 'View Items', 'text_domain' ),
		'search_items'          => __( 'Search Item', 'text_domain' ),
		'not_found'             => __( 'Not found', 'text_domain' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'text_domain' ),
		'featured_image'        => __( 'Featured Image', 'text_domain' ),
		'set_featured_image'    => __( 'Set featured image', 'text_domain' ),
		'remove_featured_image' => __( 'Remove featured image', 'text_domain' ),
		'use_featured_image'    => __( 'Use as featured image', 'text_domain' ),
		'insert_into_item'      => __( 'Insert into item', 'text_domain' ),
		'uploaded_to_this_item' => __( 'Uploaded to this item', 'text_domain' ),
		'items_list'            => __( 'Items list', 'text_domain' ),
		'items_list_navigation' => __( 'Items list navigation', 'text_domain' ),
		'filter_items_list'     => __( 'Filter items list', 'text_domain' ),
	);
	$args = array(
		'label'                 => __( 'API Purchase', 'text_domain' ),
		'description'           => __( 'API Purchase', 'text_domain' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor','custom-fields' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 5,
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => true,
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'page',
	);
	register_post_type( 'api_purchase', $args );

}
add_action( 'init', 'api_purchase_post_type', 0 );


function pw_edd_on_complete_purchase( $payment_id ) {
	file_get_contents('http://structuralengine.com/my-module/deduct_points_balance.php?id='.get_current_user_id().'&pt=10');
	$id = wp_insert_post(array('post_title'=>'Purchase history - '.get_current_user_id(), 'post_type'=>'api_purchase', 'post_content'=>' '));
	update_post_meta($id,'purchase_his_con',get_current_user_id());
	update_post_meta($id,'payment_id_e',$payment_id);
}
add_action( 'edd_complete_purchase', 'pw_edd_on_complete_purchase' );


function load_my_textdomain(){
	load_textdomain('additional-translate', ABSPATH . 'wp-content/languages/loco/themes/additional-translate-'.get_locale().'.mo');
}
add_action('after_setup_theme', 'load_my_textdomain');

function show_points_in_history($payment_id, $payment_meta) {
	global $wpdb;
	
	
	$page_details = 321; // ID of the page with the point details!
	
	
	$page_details = get_permalink($page_details);
	if(empty($page_details)) return '';
	
	$user_id = get_current_user_id();
	$payments = edd_get_users_purchases( get_current_user_id(), 20, true, 'any' );
	$points = null;
	foreach($payments as $index => $payment) {
		if($payment->ID == $payment_id) {
			if($index == 0) $query = "SELECT * FROM {$wpdb->prefix}edd_points WHERE `user_id` = '{$user_id}' AND TIMESTAMPDIFF(SECOND, `date`, '{$payment->post_date}') < 0 ORDER BY `date` DESC";
			elseif(empty($payments[$index + 1])) $query = "SELECT * FROM {$wpdb->prefix}edd_points WHERE `user_id` = '{$user_id}' AND TIMESTAMPDIFF(SECOND, `date`, '{$payment->post_date}') > 0 ORDER BY `date` DESC";
			else {
				$next_payment = $payments[$index + 1];
				$query = "SELECT * FROM {$wpdb->prefix}edd_points WHERE `user_id` = '{$user_id}' AND TIMESTAMPDIFF(SECOND, `date`, '{$payment->post_date}') > 0 AND TIMESTAMPDIFF(SECOND, `date`, '{$next_payment->post_date}') < 0 ORDER BY `date` DESC";
			}
			$points = $wpdb->get_results($query, ARRAY_A);
			break;
		}
	}
	if(!empty($points)) {
		$symbol = edd_currency_symbol(edd_get_currency());
		foreach($points as $point) {
			echo '<td class="edd_purchase_id minu-point-es">#-'.$point['point_id'].'</td>
				<td class="edd_purchase_date minu-point-es">'.date_i18n( get_option('date_format'), strtotime( $point['date'] ) ).'</td>
				<td class="edd_purchase_amount minu-point-es">
					<span class="edd_purchase_amount">'.$symbol.'-'.number_format($point['point_value'], 2).'</span>
				</td>
				<td class="edd_purchase_details minu-point-es">
					<a href="'.$page_details.'?user_id='.$user_id.'&point_id='.$point['point_id'].'&hash='.md5('MEg@'.$point['point_id'].'HA$h'.$user_id.'123').'">'.__( 'View Details and Downloads', 'easy-digital-downloads' ).'</a>
				</td>
			</tr>
			<tr class="edd_purchase_row">';
		}
	}
}
add_action( 'edd_purchase_history_row_start', 'show_points_in_history' );

function show_point_details(){
	global $wpdb, $l10n;
	
	if(empty($_GET['user_id']) || empty($_GET['point_id']) || empty($_GET['hash'])) return '';
	if($_GET['hash'] != md5('MEg@'.$_GET['point_id'].'HA$h'.$_GET['user_id'].'123')) return '';
	
	$user_id = $wpdb->_real_escape($_GET['user_id']);
	$point_id = $wpdb->_real_escape($_GET['point_id']);
	
	$points = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}edd_points WHERE `user_id` = '{$user_id}' AND `point_id` = '{$point_id}'", ARRAY_A);
	if(empty($points[0])) return '';
	$point = $points[0];
	$symbol = edd_currency_symbol(edd_get_currency());
	
	return '
	<table id="edd_purchase_receipt" class="edd-table">
		<thead>
			<tr>
				<th><strong>'.__( 'Point', 'additional-translate' ).':</strong></th>
				<th>'.$point['point_id'].'</th>
			</tr>
		</thead>

		<tbody>
			<tr>
				<td class="edd_receipt_payment_status"><strong>'.__( 'Payment Status', 'easy-digital-downloads' ).':</strong></td>
				<td class="edd_receipt_payment_status">'.__( 'Done', 'additional-translate' ).'</td>
			</tr>

			<tr>
				<td><strong>'.__( 'Payment Method', 'easy-digital-downloads' ).':</strong></td>
				<td>'.__( 'Get For Free', 'additional-translate' ).'</td>
			</tr>
			
			<tr>
				<td><strong>'.__( 'Date', 'easy-digital-downloads' ).':</strong></td>
				<td>'.date_i18n( get_option( 'date_format' ), strtotime( $point['date'] ) ).'</td>
			</tr>
			
			<tr>
				<td><strong>'.__( 'Total Price', 'easy-digital-downloads' ).':</strong></td>
				<td>'.$symbol.'-'.number_format($point['point_value'], 2).'</td>
			</tr>
		</tbody>
	</table>

	<h3>'.apply_filters( 'edd_payment_receipt_products_title', __( 'Products', 'easy-digital-downloads' ) ).'</h3>

	<table id="edd_purchase_receipt_products" class="edd-table">
		<thead>
			<th>'.__( 'Name', 'easy-digital-downloads' ).'</th>
			<th>'.__( 'Price', 'easy-digital-downloads' ).'</th>
		</thead>

		<tbody>
			<tr>
				<td>
					<div class="edd_purchase_receipt_product_name">
						'.__( 'Point Consumption For Service Use', 'additional-translate' ).'
					</div>
				</td>
				<td>
					'.$symbol.'-'.number_format($point['point_value'], 2).'
				</td>
			</tr>
		</tbody>
	</table>';
}
add_shortcode( 'show_point_details', 'show_point_details' );

/**
 * OnePress functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package OnePress
 */

if ( ! function_exists( 'onepress_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function onepress_setup() {
		/*
		 * Make theme available for translation.
		 * Translations can be filed in the /languages/ directory.
		 * If you're building a theme based on OnePress, use a find and replace
		 * to change 'onepress' to the name of your theme in all the template files.
		 */
		load_theme_textdomain( 'onepress', get_template_directory() . '/languages' );

		/*
		 * Add default posts and comments RSS feed links to head.
		 */
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );

		/**
		 * Excerpt for page
		 */
		add_post_type_support( 'page', 'excerpt' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );
		add_image_size( 'onepress-blog-small', 300, 150, true );
		add_image_size( 'onepress-small', 480, 300, true );
		add_image_size( 'onepress-medium', 640, 400, true );

		/*
		 * This theme uses wp_nav_menu() in one location.
		 */
		register_nav_menus( array(
			'primary'      => esc_html__( 'Primary Menu', 'onepress' ),
		) );

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support( 'html5', array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
		) );

		/*
		 * This theme styles the visual editor to resemble the theme style.
		 */
		add_editor_style( array( 'assets/css/editor-style.css', onepress_fonts_url() ) );

		/*
		 * WooCommerce support.
		 */
		add_theme_support( 'woocommerce' );

        /**
         * Add theme Support custom logo
         * @since WP 4.5
         * @sin 1.2.1
         */

        add_theme_support( 'custom-logo', array(
            'height'      => 36,
            'width'       => 160,
            'flex-height' => true,
            'flex-width'  => true,
            //'header-text' => array( 'site-title',  'site-description' ), //
        ) );


        // Recommend plugins
        add_theme_support( 'recommend-plugins', array(
            'contact-form-7' => array(
                'name' => esc_html__( 'Contact Form 7', 'onepress' ),
                'active_filename' => 'contact-form-7/wp-contact-form-7.php',
            ),
            'famethemes-demo-importer' => array(
                'name' => esc_html__( 'Famethemes Demo Importer', 'onepress' ),
                'active_filename' => 'famethemes-demo-importer/famethemes-demo-importer.php',
            ),
        ) );


        // Add theme support for selective refresh for widgets.
        add_theme_support( 'customize-selective-refresh-widgets' );

        // Add support for WooCommerce.
        add_theme_support( 'wc-product-gallery-zoom' );
        add_theme_support( 'wc-product-gallery-lightbox' );
        add_theme_support( 'wc-product-gallery-slider' );

	}
endif;
add_action( 'after_setup_theme', 'onepress_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function onepress_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'onepress_content_width', 800 );
}
add_action( 'after_setup_theme', 'onepress_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function onepress_widgets_init() {
	register_sidebar( array(
		'name'          => esc_html__( 'Sidebar', 'onepress' ),
		'id'            => 'sidebar-1',
		'description'   => '',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );

    if ( class_exists( 'WooCommerce' ) ) {
        register_sidebar( array(
            'name'          => esc_html__( 'WooCommerce Sidebar', 'onepress' ),
            'id'            => 'sidebar-shop',
            'description'   => '',
            'before_widget' => '<aside id="%1$s" class="widget %2$s">',
            'after_widget'  => '</aside>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ) );
    }
    for ( $i = 1; $i<= 4; $i++ ) {
        register_sidebar(array(
            'name' => sprintf( __('Footer %s', 'onepress'), $i ),
            'id' => 'footer-'.$i,
            'description' => '',
            'before_widget' => '<aside id="%1$s" class="footer-widget widget %2$s">',
            'after_widget' => '</aside>',
            'before_title' => '<h2 class="widget-title">',
            'after_title' => '</h2>',
        ));
    }

}
add_action( 'widgets_init', 'onepress_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function onepress_scripts() {

    $theme = wp_get_theme( 'onepress' );
    $version = $theme->get( 'Version' );

    if ( ! get_theme_mod( 'onepress_disable_g_font' ) ) {
        wp_enqueue_style('onepress-fonts', onepress_fonts_url(), array(), $version);
    }

	wp_enqueue_style( 'onepress-animate', get_template_directory_uri() .'/assets/css/animate.min.css', array(), $version );
	wp_enqueue_style( 'onepress-fa', get_template_directory_uri() .'/assets/css/font-awesome.min.css', array(), '4.7.0' );
	wp_enqueue_style( 'onepress-bootstrap', get_template_directory_uri() .'/assets/css/bootstrap.min.css', false, $version );
	wp_enqueue_style( 'onepress-style', get_template_directory_uri().'/style.css' );

    $custom_css = onepress_custom_inline_style();
    wp_add_inline_style( 'onepress-style', $custom_css );

	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'onepress-js-plugins', get_template_directory_uri() . '/assets/js/plugins.js', array( 'jquery' ), $version, true );
	wp_enqueue_script( 'onepress-js-bootstrap', get_template_directory_uri() . '/assets/js/bootstrap.min.js', array(), $version, true );

    // Animation from settings.
    $onepress_js_settings = array(
        'onepress_disable_animation'     => get_theme_mod( 'onepress_animation_disable' ),
        'onepress_disable_sticky_header' => get_theme_mod( 'onepress_sticky_header_disable' ),
        'onepress_vertical_align_menu'   => get_theme_mod( 'onepress_vertical_align_menu' ),
        'hero_animation'   				 => get_theme_mod( 'onepress_hero_option_animation', 'flipInX' ),
        'hero_speed'   					 => intval( get_theme_mod( 'onepress_hero_option_speed', 5000 ) ),
        'hero_fade'   					 => intval( get_theme_mod( 'onepress_hero_slider_fade', 750 ) ),
        'hero_duration'   				 => intval( get_theme_mod( 'onepress_hero_slider_duration', 5000 ) ),
        'hero_disable_preload'   		 => get_theme_mod( 'onepress_hero_disable_preload', false ) ? true : false ,
        'is_home'   					 => '',
        'gallery_enable'   				 => '',
        'is_rtl' => is_rtl()
    );

    // Load gallery scripts
    $galley_disable  = get_theme_mod( 'onepress_gallery_disable' ) ==  1 ? true : false;
    $is_shop = false;
    if ( function_exists( 'is_woocommerce' ) ) {
        if ( is_woocommerce() ) {
            $is_shop = true;
        }
    }

    // Don't load scripts for woocommerce because it don't need.
    if ( ! $is_shop ) {
        if ( ! $galley_disable || is_customize_preview()) {
            $onepress_js_settings['gallery_enable'] = 1;
            $display = get_theme_mod('onepress_gallery_display', 'grid');
            if (!is_customize_preview()) {
                switch ($display) {
                    case 'masonry':
                        wp_enqueue_script('onepress-gallery-masonry', get_template_directory_uri() . '/assets/js/isotope.pkgd.min.js', array(), $version, true);
                        break;
                    case 'justified':
                        wp_enqueue_script('onepress-gallery-justified', get_template_directory_uri() . '/assets/js/jquery.justifiedGallery.min.js', array(), $version, true);
                        break;
                    case 'slider':
                    case 'carousel':
                        wp_enqueue_script('onepress-gallery-carousel', get_template_directory_uri() . '/assets/js/owl.carousel.min.js', array(), $version, true);
                        break;
                    default:
                        break;
                }
            } else {
                wp_enqueue_script('onepress-gallery-masonry', get_template_directory_uri() . '/assets/js/isotope.pkgd.min.js', array(), $version, true);
                wp_enqueue_script('onepress-gallery-justified', get_template_directory_uri() . '/assets/js/jquery.justifiedGallery.min.js', array(), $version, true);
                wp_enqueue_script('onepress-gallery-carousel', get_template_directory_uri() . '/assets/js/owl.carousel.min.js', array(), $version, true);
            }

        }
        wp_enqueue_style('onepress-gallery-lightgallery', get_template_directory_uri() . '/assets/css/lightgallery.css');
    }

	wp_enqueue_script( 'onepress-theme', get_template_directory_uri() . '/assets/js/theme.js', array(), $version, true );
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

    if ( is_front_page() && is_page_template( 'template-frontpage.php' ) ) {
        if ( get_theme_mod( 'onepress_header_scroll_logo' ) ) {
            $onepress_js_settings['is_home'] = 1;
        }
    }
	wp_localize_script( 'jquery', 'onepress_js_settings', $onepress_js_settings );

}
add_action( 'wp_enqueue_scripts', 'onepress_scripts' );


if ( ! function_exists( 'onepress_fonts_url' ) ) :
	/**
	 * Register default Google fonts
	 */
	function onepress_fonts_url() {
	    $fonts_url = '';

	 	/* Translators: If there are characters in your language that are not
	    * supported by Open Sans, translate this to 'off'. Do not translate
	    * into your own language.
	    */
	    $open_sans = _x( 'on', 'Open Sans font: on or off', 'onepress' );

	    /* Translators: If there are characters in your language that are not
	    * supported by Raleway, translate this to 'off'. Do not translate
	    * into your own language.
	    */
	    $raleway = _x( 'on', 'Raleway font: on or off', 'onepress' );

	    if ( 'off' !== $raleway || 'off' !== $open_sans ) {
	        $font_families = array();

	        if ( 'off' !== $raleway ) {
	            $font_families[] = 'Raleway:400,500,600,700,300,100,800,900';
	        }

	        if ( 'off' !== $open_sans ) {
	            $font_families[] = 'Open Sans:400,300,300italic,400italic,600,600italic,700,700italic';
	        }

	        $query_args = array(
	            'family' => urlencode( implode( '|', $font_families ) ),
	            'subset' => urlencode( 'latin,latin-ext' ),
	        );

	        $fonts_url = add_query_arg( $query_args, 'https://fonts.googleapis.com/css' );
	    }

	    return esc_url_raw( $fonts_url );
	}
endif;


if ( ! function_exists( 'onepress_register_required_plugins' ) ) :
	/**
	 * Register the required plugins for this theme.
	 *
	 * In this example, we register five plugins:
	 * - one included with the TGMPA library
	 * - two from an external source, one from an arbitrary source, one from a GitHub repository
	 * - two from the .org repo, where one demonstrates the use of the `is_callable` argument
	 *
	 * The variable passed to tgmpa_register_plugins() should be an array of plugin
	 * arrays.
	 *
	 * This function is hooked into tgmpa_init, which is fired within the
	 * TGM_Plugin_Activation class constructor.
	 */
	function onepress_register_required_plugins() {
		/*
		 * Array of plugin arrays. Required keys are name and slug.
		 * If the source is NOT from the .org repo, then source is also required.
		 */
		$plugins = array(
			array(
				'name'               => 'Contact Form 7', // The plugin name.
				'slug'               => 'contact-form-7', // The plugin slug (typically the folder name).
				'source'             => '', // The plugin source.
				'required'           => false, // If false, the plugin is only 'recommended' instead of required.
				'version'            => '4.2', // E.g. 1.0.0. If set, the active plugin must be this version or higher.
				'force_activation'   => false, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch.
				'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins.
				'external_url'       => '', // If set, overrides default API URL and points to an external URL.
			),
		);

		/*
		 * Array of configuration settings. Amend each line as needed.
		 *
		 * TGMPA will start providing localized text strings soon. If you already have translations of our standard
		 * strings available, please help us make TGMPA even better by giving us access to these translations or by
		 * sending in a pull-request with .po file(s) with the translations.
		 *
		 * Only uncomment the strings in the config array if you want to customize the strings.
		 */
		$config = array(
			'id'           => 'tgmpa',                 // Unique ID for hashing notices for multiple instances of TGMPA.
			'default_path' => '',                      // Default absolute path to bundled plugins.
			'menu'         => 'tgmpa-install-plugins', // Menu slug.
			'parent_slug'  => 'themes.php',            // Parent menu slug.
			'capability'   => 'edit_theme_options',    // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
			'has_notices'  => true,                    // Show admin notices or not.
			'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
			'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
			'is_automatic' => false,                   // Automatically activate plugins after installation or not.
			'message'      => '',                      // Message to output right before the plugins table.

			'strings'      => array(
				'page_title'                      => esc_html__( 'Install Required Plugins', 'onepress' ),
				'menu_title'                      => esc_html__( 'Install Plugins', 'onepress' ),
				'installing'                      => esc_html__( 'Installing Plugin: %s', 'onepress' ), // %s = plugin name.
				'oops'                            => esc_html__( 'Something went wrong with the plugin API.', 'onepress' ),
				'notice_can_install_required'     => _n_noop( 'This theme requires the following plugin: %1$s.', 'This theme requires the following plugins: %1$s.', 'onepress' ), // %1$s = plugin name(s).
				'notice_can_install_recommended'  => _n_noop( 'This theme recommends the following plugin: %1$s.', 'This theme recommends the following plugins: %1$s.', 'onepress' ), // %1$s = plugin name(s).
				'notice_cannot_install'           => _n_noop( 'Sorry, but you do not have the correct permissions to install the %1$s plugin.', 'Sorry, but you do not have the correct permissions to install the %1$s plugins.', 'onepress' ), // %1$s = plugin name(s).
				'notice_ask_to_update'            => _n_noop( 'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.', 'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.', 'onepress' ), // %1$s = plugin name(s).
				'notice_ask_to_update_maybe'      => _n_noop( 'There is an update available for: %1$s.', 'There are updates available for the following plugins: %1$s.', 'onepress' ), // %1$s = plugin name(s).
				'notice_cannot_update'            => _n_noop( 'Sorry, but you do not have the correct permissions to update the %1$s plugin.', 'Sorry, but you do not have the correct permissions to update the %1$s plugins.', 'onepress' ), // %1$s = plugin name(s).
				'notice_can_activate_required'    => _n_noop( 'The following required plugin is currently inactive: %1$s.', 'The following required plugins are currently inactive: %1$s.', 'onepress' ), // %1$s = plugin name(s).
				'notice_can_activate_recommended' => _n_noop( 'The following recommended plugin is currently inactive: %1$s.', 'The following recommended plugins are currently inactive: %1$s.', 'onepress' ), // %1$s = plugin name(s).
				'notice_cannot_activate'          => _n_noop( 'Sorry, but you do not have the correct permissions to activate the %1$s plugin.', 'Sorry, but you do not have the correct permissions to activate the %1$s plugins.', 'onepress' ), // %1$s = plugin name(s).
				'install_link'                    => _n_noop( 'Begin installing plugin', 'Begin installing plugins', 'onepress' ),
				'update_link' 					  => _n_noop( 'Begin updating plugin', 'Begin updating plugins', 'onepress' ),
				'activate_link'                   => _n_noop( 'Begin activating plugin', 'Begin activating plugins', 'onepress' ),
				'return'                          => esc_html__( 'Return to Required Plugins Installer', 'onepress' ),
				'plugin_activated'                => esc_html__( 'Plugin activated successfully.', 'onepress' ),
				'activated_successfully'          => esc_html__( 'The following plugin was activated successfully:', 'onepress' ),
				'plugin_already_active'           => esc_html__( 'No action taken. Plugin %1$s was already active.', 'onepress' ),  // %1$s = plugin name(s).
				'plugin_needs_higher_version'     => esc_html__( 'Plugin not activated. A higher version of %s is needed for this theme. Please update the plugin.', 'onepress' ),  // %1$s = plugin name(s).
				'complete'                        => esc_html__( 'All plugins installed and activated successfully. %1$s', 'onepress' ), // %s = dashboard link.
				'contact_admin'                   => esc_html__( 'Please contact the administrator of this site for help.', 'onepress' ),
				'nag_type'                        => 'updated', // Determines admin notice type - can only be 'updated', 'update-nag' or 'error'.
			),

		);

		tgmpa( $plugins, $config );
	}

endif;
add_action( 'tgmpa_register', 'onepress_register_required_plugins' );

require get_template_directory() . '/inc/sanitize.php';

/**
 * Custom Metabox  for this theme.
 */
require get_template_directory() . '/inc/metabox.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Custom functions that act independently of the theme templates.
 */
require get_template_directory() . '/inc/extras.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Add theme info page
 */
require get_template_directory() . '/inc/dashboard.php';
