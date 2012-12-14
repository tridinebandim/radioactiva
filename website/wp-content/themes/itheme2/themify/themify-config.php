<?php

/***************************************************************************
 *
 * 	----------------------------------------------------------------------
 * 						DO NOT EDIT THIS FILE
 *	----------------------------------------------------------------------
 * 
 *  			Original built by Darcy Clarke. http://themify.me
 * 				Extended by Elio Rivero.
 *  				Copyright (C) 2010 Themify
 * 
 *	----------------------------------------------------------------------
 *
 ***************************************************************************/

	
/* 	Set Error Reporting
 ***************************************************************************/
	error_reporting(E_ERROR);
	
	
/* 	Global Vars
 ***************************************************************************/

	global $wpdb, $config, $data, $theme, $version, $notifications, $pagenow;

/*	Activate Theme
 ***************************************************************************/

	if(isset($_GET['activated']) && $pagenow == 'themes.php')
		header('Location: '.admin_url().'admin.php?page=themify&firsttime=true');

/* 	Theme Config
 ***************************************************************************/
	$version = '1.2.3';
	define( 'THEMIFY_VERSION', $version );
	$theme = wp_get_theme();

/* 	Data Config
 ***************************************************************************/
	$data = themify_get_data();
	
/*	Generate Config from XML
 ***************************************************************************/
	/**
	 *  @var String $the_config_file
	 */ 
	$the_config_file = (is_file(THEME_DIR.'/custom-config.xml'))? 'custom-config.xml' : 'theme-config.xml';
	$the_config_file = THEME_DIR . '/' . $the_config_file;
	
	$file = fopen($the_config_file, 'r');
	$config = fread($file, filesize($the_config_file));
	fclose($file);
	
	$config = themify_xml2array($config);
	$config = $config['config']['_c'];
		
	/*	Dynamic panel creation
	/**************************************************/
	
	$panels = $config['panel'];
	unset($config['panel']);
	if(is_array($panels)){
		foreach($panels as $panel){
			$config['panel'][strtolower($panel['_a']['title'])] = $panel['_c'];
		}
	}
	
/**
 * Load Shortcodes
 * @since 1.1.3
 */
require_once(THEME_DIR . '/themify/themify-shortcodes.php');

/**
 * Load Regenerate Thumbnails plugin if the corresponding class doesn't exist.
 * @since 1.1.5
 */
if(!class_exists('RegenerateThumbnails'))
	require_once(THEMIFY_DIR . '/regenerate-thumbnails/regenerate-thumbnails.php' );
 
/**
 * Remove featured image metabox
 * @since 1.1.5
 */
add_action('do_meta_boxes', 'themify_cpt_image_box');

/**
 * Themify - CSS Header
 */
add_action('wp_head', 'themify_get_css');

/**
 * Themify - Insert settings page link in WP Admin Bar
 * @since 1.1.2
 */
add_action('wp_before_admin_bar_render', 'themify_admin_bar');

/**
 * Add support for feeds on the site
 */
add_theme_support( 'automatic-feed-links' );

/**
 * Load Themify Hooks
 * @since 1.2.2
 */
require_once(THEMIFY_DIR . '/themify-hooks.php' );
	
/**
 * Admin Only code follows
 ******************************************************/
if( is_admin() ){
	
	/**
	 * Remove Themify themes from upgrade check
	 * @since 1.1.8
	 */
	add_filter( 'http_request_args', 'themify_hide_themes', 5, 2);
	
	if( current_user_can('manage_options') ){
		/**
	 	* Themify - Admin Menu
	 	*******************************************************/
		add_action('admin_menu', 'themify_admin_nav');
		
		/**
	 	* Themify Updater
	 	*******************************************************/
		require_once(THEME_DIR . '/themify/themify-updater.php');
	}
	
	/**
 	* Add buttons to TinyMCE
 	*******************************************************/
	require_once(THEMIFY_DIR . '/tinymce/class-themify-tinymce.php');
	add_action('init', create_function('', '$Themify_TinyMCE = new Themify_TinyMCE();'));
	
	/**
 	* Enqueue jQuery and other scripts
 	*******************************************************/
	add_action('admin_enqueue_scripts', 'themify_enqueue_scripts');
	
	/**
	 * Display additional ID column in categories list
	 * @since 1.1.8
	 */
	add_filter('manage_edit-category_columns', 'themify_custom_category_header', 10, 2);
	add_filter('manage_category_custom_column', 'themify_custom_category', 10, 3);
	
	/**
 	* Ajaxify admin
 	*******************************************************/
	require_once(THEMIFY_DIR . '/themify-wpajax.php');
}

/**
 * Enqueue JS and CSS for Themify settings page and meta boxes
 * @param String $page
 * @since 1.1.1
 *******************************************************/
function themify_enqueue_scripts($page){
	global $version;
	
	$post = get_post( $_GET['post'] );
	$typenow = $post->post_type;
	$pagenow = $_GET['page'];
	$types = themify_post_types();
	if( $page == 'post.php' || $page == 'post-new.php' ){
		wp_enqueue_script( 'meta-box-tabs', get_template_directory_uri() . '/themify/js/meta-box-tabs.js', array('jquery'), '1.0', true );	
	}
	if( $page == 'post.php' || $page == 'post-new.php' || $page == 'toplevel_page_themify' ){
		//Enqueue styles
		wp_enqueue_style( 'themify-ui',  THEMIFY_URI . '/css/themify-ui.css', array(), $version );
		if ( is_rtl() )
			wp_enqueue_style( 'themify-ui-rtl',  THEMIFY_URI . '/css/themify-ui-rtl.css', array(), $version );
		wp_enqueue_style( 'colorpicker', THEMIFY_URI . '/css/colorpicker.css' );
		
		//Enqueue scripts
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_script( 'plupload-all' );
		wp_enqueue_script( 'validate', THEMIFY_URI . '/js/jquery.validate.pack.js', array('jquery') );
		wp_enqueue_script( 'colorpicker-js', THEMIFY_URI . '/js/colorpicker.js', array('jquery') );
		if( in_array($typenow, $types) || 'themify' == $pagenow ){
			//Don't include Themify JavaScript if we're not in one of the Themify-managed pages
			wp_enqueue_script( 'themify-scripts', THEMIFY_URI . '/js/scripts.js', array('jquery'), $version );
			wp_enqueue_script( 'themify-plupload', THEMIFY_URI . '/js/plupload.js', array('jquery', 'themify-scripts'));
		}
	}
	//Inject variable values to scripts.js previously enqueued
	wp_localize_script('themify-scripts', 'themify_js_vars', array(
			'themify' 	=> THEMIFY_URI,
			'nonce' 	=> wp_create_nonce('ajax-nonce'),
			'admin_url' => admin_url( 'admin.php?page=themify' ),
			'ajax_url' 	=> admin_url( 'admin-ajax.php' ),
			'app_url'	=> get_template_directory_uri() . '/themify/',
			'theme_url'	=> get_template_directory_uri() . '/',
			'blog_url'	=> site_url() . '/'
		)
	);
	
	wp_localize_script('themify-scripts', 'themify_lang', array(
			'confirm_reset_styling'	=> __('Are you sure you want to reset your theme style?', 'themify'),
			'confirm_reset_settings' => __('Are you sure you want to reset your theme settings?', 'themify'),
			'check_backup' => __('Make sure to backup before upgrading. Files and settings may get lost or changed.', 'themify'),
			'confirm_delete_image' => __('Do you want to delete this image permanently?', 'themify'),
			'invalid_login' => __('Invalid username or password.<br/>Contact <a href="http://themify.me/contact">Themify</a> for login issues.', 'themify'),
			'enable_zip_upload' => sprintf(
				__('Go to your <a href="%s">Network Settings</a> to enable <strong>zip</strong> and <strong>txt</strong> extensions in <strong>Upload file types</strong> field.', 'themify'),
				esc_url(network_admin_url('/network/settings.php').'#upload_filetypes')
			),
			'filesize_error' => __('The file you are trying to upload exceeds the maximum file size allowed.', 'themify'),
			'filesize_error_fix' => sprintf(
				__('Go to your <a href="%s">Network Settings</a> and increase the value of the <strong>Max upload file size</strong>.', 'themify'),
				esc_url(network_admin_url('/network/settings.php').'#fileupload_maxk')
			)
		)
	);
}
/**
 * Add Themify Settings link to admin bar
 * @since 1.1.2
 */
function themify_admin_bar() {
	global $wp_admin_bar;
	if ( !is_super_admin() || !is_admin_bar_showing() )
		return;
	$wp_admin_bar->add_menu( array(
		'id' => 'themify-settings',
		'parent' => 'appearance',
		'title' => __( 'Themify Settings', 'themify' ),
		'href' => admin_url( 'admin.php?page=themify' )
	));
}
/**
 * Remove WordPress' Post Thumbnail metabox. This functionality is handled by Themify
 * @since 1.1.5
 */
function themify_cpt_image_box() {
	$types = themify_post_types();
	foreach( $types as $type )
		remove_meta_box( 'postimagediv', $type, 'side' );
	
}
?>
