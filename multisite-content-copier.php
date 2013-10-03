<?php
/*
Plugin Name: Multisite Content Copier
Plugin URI: 
Description: Does nothing at all
Author: igmoweb
Version:0.1
Author URI:
Text Domain: mcc
Network:true
*/

/**
 * The main class of the plugin
 */

class Multisite_Content_Copier {

	// The version slug for the DB
	public static $version_option_slug = 'multisite_content_copier_plugin_version';

	// Admin pages. THey could be accesed from other points
	// So they're statics
	static $network_main_menu_page;

	public function __construct() {

		$this->set_globals();

		if ( ! is_multisite() ) {
			add_action( 'all_admin_notices', array( &$this, 'display_not_multisite_notice' ) );
			return;
		}

		$this->includes();

		add_action( 'init', array( &$this, 'maybe_upgrade' ) );

		add_action( 'init', array( &$this, 'init_plugin' ) );

		add_action( 'plugins_loaded', array( &$this, 'load_text_domain' ) );

		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_styles' ) );

		// We don't use the activation hook here
		// As sometimes is not very helpful and
		// we would need to check stuff to install not only when
		// we activate the plugin
		register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ) );

		//add_action('init',array( &$this,'test'));

	}

	public function test() {
		switch_to_blog( 83 );
		
		$copier = new Multisite_Content_Copier_Page_Copier( 27, array( 'pages_ids' => 103, 'copy_images' => true ) );
		$copier->copy();
		restore_current_blog();
	}

	public function display_not_multisite_notice() {
		?>
			<div class="error"><p><?php _e( 'Multisite Content Copier is a plugin just for multisites, please deactivate it.', MULTISTE_CC_LANG_DOMAIN ); ?></p></div>
		<?php
	}

	public function enqueue_scripts() {
	}


	public function enqueue_styles() {
		wp_enqueue_style( 'origin-icons', MULTISTE_CC_ASSETS_URL . 'css/icons.css' );
	}



	/**
	 * Set the plugin constants
	 */
	private function set_globals() {

		// Basics
		define( 'MULTISTE_CC_VERSION', '0.1' );
		define( 'MULTISTE_CC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		define( 'MULTISTE_CC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		define( 'MULTISTE_CC_PLUGIN_FILE_DIR', plugin_dir_path( __FILE__ ) . 'multisite-content-copier.php' );

		// Language domain
		define( 'MULTISTE_CC_LANG_DOMAIN', 'mcc' );

		// URLs
		define( 'MULTISTE_CC_ASSETS_URL', MULTISTE_CC_PLUGIN_URL . 'assets/' );

		// Dirs
		define( 'MULTISTE_CC_ADMIN_DIR', MULTISTE_CC_PLUGIN_DIR . 'admin/' );
		define( 'MULTISTE_CC_FRONT_DIR', MULTISTE_CC_PLUGIN_DIR . 'front/' );
		define( 'MULTISTE_CC_MODEL_DIR', MULTISTE_CC_PLUGIN_DIR . 'model/' );
		define( 'MULTISTE_CC_INCLUDES_DIR', MULTISTE_CC_PLUGIN_DIR . 'inc/' );

	}

	/**
	 * Include files needed
	 */
	private function includes() {
		// Model
		require_once( MULTISTE_CC_MODEL_DIR . 'model.php' );
		require_once( MULTISTE_CC_MODEL_DIR . 'copier-model.php' );

		// Libraries
		require_once( MULTISTE_CC_INCLUDES_DIR . 'admin-page.php' );
		require_once( MULTISTE_CC_INCLUDES_DIR . 'errors-handler.php' );
		require_once( MULTISTE_CC_INCLUDES_DIR . 'helpers.php' );
		require_once( MULTISTE_CC_INCLUDES_DIR . 'upgrade.php' );

		// The copier
		require_once( MULTISTE_CC_INCLUDES_DIR . 'content-copier/content-copier.php' );
		require_once( MULTISTE_CC_INCLUDES_DIR . 'content-copier/content-copier-page.php' );

		// Settings Handler
		require_once( MULTISTE_CC_INCLUDES_DIR . 'settings-handler.php' );

		// Admin Pages
		require_once( MULTISTE_CC_ADMIN_DIR . 'pages/network-main-page.php' );
	}

	/**
	 * Upgrade the plugin when a new version is uploaded
	 */
	public function maybe_upgrade() {
		//$current_version = get_option( self::$version_option_slug );
//
		//if ( ! $current_version )
		//	$current_version = '0.2'; // This is the first version that includes some upgradings
//
		//// For the second version, we're just saving the version in DB
		//if ( version_compare( $current_version, '0.2', '<=' ) ) {
		//	require_once( MULTISTE_CC_INCLUDES_DIR . 'upgrade.php' );
		//	// Call upgrade functions here
		//}
//
		//// This is the third version (still not released)
		//if ( version_compare( $current_version, '0.3', '<' ) ) {
		//	require_once( MULTISTE_CC_INCLUDES_DIR . 'upgrade.php' );
		//	// Call upgrade functions here	
		//}
//
		//update_option( self::$version_option_slug, MULTISTE_CC_VERSION );
	}



	/**
	 * Actions executed when the plugin is deactivated
	 */
	public function deactivate() {
		$model = mcc_get_model();
		$model->deactivate_model();
		// HEY! Do not delete anything from DB here
		// You better use the uninstall functionality
	}

	/**
	 * Load the plugin text domain and MO files
	 * 
	 * These can be uploaded to the main WP Languages folder
	 * or the plugin one
	 */
	public function load_text_domain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), MULTISTE_CC_LANG_DOMAIN );

		load_textdomain( MULTISTE_CC_LANG_DOMAIN, WP_LANG_DIR . '/' . MULTISTE_CC_LANG_DOMAIN . '/' . MULTISTE_CC_LANG_DOMAIN . '-' . $locale . '.mo' );
		load_plugin_textdomain( MULTISTE_CC_LANG_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}

	/**
	 * Initialize the plugin
	 */
	public function init_plugin() {

		// A network menu
		$args = array(
			'menu_title' => __( 'Content Copier', MULTISTE_CC_LANG_DOMAIN ),
			'page_title' => __( 'Multisite Content Copier', MULTISTE_CC_LANG_DOMAIN ),
			'network_menu' => true,
			'screen_icon_slug' => 'bbu'
		);
		self::$network_main_menu_page = new Multisite_Content_Copier_Network_Main_Menu( 'mcc_network_page', 'manage_network', $args );
	}

}

new Multisite_Content_Copier();