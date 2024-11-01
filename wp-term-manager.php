<?php

/**
 * Plugin Name: WP Term Manager
 * Plugin URL: https://scree.it/wp-term-manager
 * Description: Clean up terms for easier administration.
 * Version: 1.0.2
 * Author: Landon Otis
 * Author URI: https://scree.it
 * Text Domain: wptm
 * Domain Path: languages
 * License:     GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */
class WPTM {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * @var string
	 */
	protected static $_version = '1.0.0';

	/**
	 * Only make one instance of self
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof self ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		add_action( 'plugins_loaded', array( $this, 'maybe_setup' ), - 9999 );

		register_uninstall_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), array(
			$this,
			'uninstall'
		) );
	}

	/**
	 * Includes
	 */
	protected function includes() {
		require_once( $this->get_plugin_dir() . 'vendor/autoload.php' );

		WPTM\Settings::get_instance();
	}

	/**
	 * Actions and Filters
	 */
	protected function actions() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/** Actions **************************************/

	/**
	 * Setup the plugin
	 */
	public function maybe_setup() {
		$this->includes();
		$this->actions();
	}

	/**
	 * Load the text domain
	 *
	 * @since  1.0.0
	 */
	public function load_textdomain() {
		// Set filter for plugin's languages directory
		$lang_dir = dirname( plugin_basename( $this->get_plugin_file() ) ) . '/languages/';
		$lang_dir = apply_filters( $this->get_id() . '_languages_directory', $lang_dir );

		// Traditional WordPress plugin locale filter

		$get_locale = get_locale();

		if ( function_exists( 'get_user_locale' ) ) {
			$get_locale = get_user_locale();
		}

		/**
		 * Defines the plugin language locale used.
		 *
		 * @var string $get_locale The locale to use. Uses get_user_locale()` in WordPress 4.7 or greater,
		 *                  otherwise uses `get_locale()`.
		 */
		$locale = apply_filters( 'plugin_locale', $get_locale, $this->get_id() );
		$mofile = sprintf( '%1$s-%2$s.mo', $this->get_id(), $locale );

		// Setup paths to current locale file
		$mofile_local  = $lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/' . $this->get_id() . '/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			load_textdomain( $this->get_id(), $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			load_textdomain( $this->get_id(), $mofile_local );
		} else {
			load_plugin_textdomain( $this->get_id(), false, $lang_dir );
		}
	}

	public function uninstall() {
		$settings = get_option( 'wptm_settings' );
		$option   = $settings['clean_db'];

		if ( 'on' != $option ) {
			return;
		}

		delete_option( 'wptm_settings' );
	}

	/** Helper Methods **************************************/

	/**
	 * Check required plugins
	 * @return bool
	 */
	protected function check_required_plugins() {
		return true;
	}

	/**
	 * Return the version of the plugin
	 *
	 * @return string
	 * @since  1.0.0
	 *
	 */
	public function get_version() {
		return self::$_version;
	}

	/**
	 * Returns the plugin name, localized
	 *
	 * @return string the plugin name
	 * @since 1.0.0
	 */
	public function get_plugin_name() {
		return __( 'WP Term Manager', 'wptm' );
	}

	/**
	 * Returns the plugin ID. Used in the textdomain
	 *
	 * @return string
	 * @since  1.0.0
	 *
	 */
	public function get_id() {
		return 'wptm';
	}

	/**
	 * Get the plugin directory path
	 *
	 * @return string
	 * @since  1.0.0
	 *
	 */
	public function get_plugin_dir() {
		return plugin_dir_path( $this->get_plugin_file() );
	}

	/**
	 * Get the plugin directory url
	 *
	 * @return string
	 * @since  1.0.0
	 *
	 */
	public function get_plugin_url() {
		return plugin_dir_url( $this->get_plugin_file() );
	}

	/**
	 * Get the plugin file
	 *
	 * @return string
	 * @since  1.0.0
	 *
	 */
	public function get_plugin_file() {
		return __FILE__;
	}

}

/**
 * @return instance of class
 */
function wptm() {
	return WPTM::get_instance();
}

wptm();