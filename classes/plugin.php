<?php

/* 
 * The MIT License (MIT)
 * 
 * Copyright (c) 2014 Jonathon Byrd jonathonbyrd@gmail.com
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Shorthand;

defined('ABSPATH') or die('Direct access to files is not allowed.');

/**
 * 
 */
class Plugin {
	
	protected $_callbacks = array(
	    'install' => array(),
	    'deactivation' => array(),
	    'activation' => array(),
	    'upgrading' => array(),
	    'downgrading' => array(),
	);
	
	var $_file;
	
	/**
	 * Method hooks into the appropriate wordpress actions.
	 */
	function __construct( $file = false ) {
		
		if ($file) {
			$this->_file = $file;
		}
		
		// hooking our actions
		add_action( 'admin_init', array($this, 'admin_init') );
		
		add_action( 'wp_enqueue_scripts', array($this, 'wp_enqueue_scripts') );
		add_action( 'admin_enqueue_scripts', array($this, 'admin_enqueue_scripts') );
		add_action( 'login_enqueue_scripts', array($this, 'login_enqueue_scripts') );
		
		register_activation_hook( $this->_file, array($this, '_activate') );
		register_deactivation_hook( $this->_file, array($this, '_deactivate') );
		register_uninstall_hook( $this->_file, array($this, '_uninstall') );
		
		// Register all shorthand media
		$this->queue( array(
			'src'	=> 'inheritance.js',
			'deps'	=> array( 'jquery' )
			) );
		$this->queue( array(
			'src'	=> 'utilities.js',
			'deps'	=> array( 'inheritancejs' )
			) );
		
		// Shorthand Styles
		$this->queue( array(
			'src'	=> 'admin-styles.css',
			'admin'	=> true
			) );
	}
	
	/**
	 * Method is called upon the WordPress do_action(admin_init)
	 */
	public function admin_init() {
		// check to see if we're upgrading or downgrading this plugin
		$version = get_option($this->get_plugin_dir_name().'-version');
		if (!$version) {
			$this->_install();
			
		} elseif (version_compare( $this->_version, $version, '>' )) {
			$this->_upgrading($version);
			
		} elseif (version_compare( $this->_version, $version, '<' )) {
			$this->_downgrading($version);
		}
	}
	
	/**
	 * Method handles the sql properly, returning a well formed array
	 * 
	 * @param type $sql
	 */
	public function select( $sql ) {
		global $wpdb;
		return $wpdb->get_results($sql, ARRAY_A);
	}
	
	protected $_adminpages = array();
	
	/**
	 * Method creates an administrative page
	 * 
	 * @param type $args
	 * @return AdminPage Object
	 */
	public function admin( $args ) {
		
		require_once dirname(__file__).DS.'adminpage.php';

		$defaults = array(
		    'id'		=> $this->get_plugin_dir_name(),
		    'page_title'	=> false,
		    'menu_title'	=> false,
		    'parent_menu'	=> 'settings'
		);
		
		if (is_string($args)) {
			$name = $args;
			$args = array();
			$args['id'] = $name;
		}
		
		$args = wp_parse_args($args, $defaults);
		
		// making sure the other titles are set as well
		if (!$args['page_title']) {
			$args['page_title'] = $args['id'];
		}
		if (!$args['menu_title']) {
			$args['menu_title'] = $args['id'];
		}
		
		$args['name'] = $args['id'];
		
		// create the instance
		$this->_adminpages[$name] = AdminPage::getInstance(
			$args['name'], $args);
		
		// preload the other media for tabs
		if (!empty($args['tabs'])) {
			$this->enqueue_scripts_shorthand();
		}
		
		return $this->_adminpages[$name];
	}
	
	protected $_metaboxes = array();
	
	/**
	 * Method creates a metabox
	 * 
	 * @param type $args
	 * @return Metabox Object
	 */
	public function metabox( $id, $args = array() ) {
		
		require_once dirname(__file__).DS.'metabox.php';

		$defaults = array(
		    'id'		=> $this->get_plugin_dir_name(),
		    'page'		=> array('post','page'),
		    'priority'		=> 'high',
		    '_fields'		=> array(),
		    'view_args'		=> false,
		    'view'		=> false
		);
		
		$args = wp_parse_args($args, $defaults);
		
		// treat the provided parameter as if it's a function
		if ($args['view'] && is_callable($args['view'])) {
			ob_start();
			echo call_user_func($args['view'], $this, $args);
			$args['view'] = ob_get_clean();
			
		// treat the parameter as if it's a filename
		} elseif ($args['view']) {
			if (!file_exists($args['view'])) {
				$args['view'] = locate($args['view'], 
					$this->get_override_dirs('templates'));
			}
			if ($args['view_args'] && is_array($args['view_args'])) {
				foreach($args['view_args'] as $p => $v) {
					if (!is_callable($v)) continue;
					$args['view_args'][$p] = call_user_func($v);
				}
			}
			$args['view'] = get_template($args['view'], $args['view_args']);
		}
		
		// load the instance
		$this->_metaboxes[$id] = Metabox::getInstance(
			$id, $args);
		
		return $this->_metaboxes[$id];
	}
	
	protected $_cron = array();
	
	/**
	 * Method calls the cron class, which could easily be called directly.
	 * However, this class keeps track of the cron instance without further
	 * effort.
	 * 
	 * @SEE Cron
	 * 
	 * @param type $args
	 * @return Cron
	 */
	public function cron( $args ) {
		
		require_once dirname(__file__).DS.'cron.php';

		$defaults = array(
		    'handle'	=> create_guid(),
		    'callback'	=> 'hello_world',
		    'single'	=> true,
		    'schedule'	=> 60,
		    'time'	=> false,
		    'stop'	=> false,
		    'direct'	=> false,
		    'debug'	=> false
		);
		
		extract(wp_parse_args($args, $defaults));
		
		$this->_cron[$handle] = Cron::getInstance($handle, array(
			'callback'	=> $callback,
			'single'	=> $single,
			'schedule'	=> $schedule,
			'time'		=> $time,
			'stop'		=> $stop,
			'debug'		=> $debug
			));
		
		// make this callback available directly
		if ($direct) {
			add_action("wp_ajax_$handle", $callback);
			add_action("wp_ajax_nopriv_$handle", $callback);
			
			$debug_callback = array($this->_cron[$handle], 
						'force_debug');
			
			add_action("wp_ajax_$handle", $debug_callback);
			add_action("wp_ajax_nopriv_$handle", $debug_callback);
		}
		
		return $this->_cron[$handle];
	}
	
	/**
	 * Method returns a global instance of the Cron class
	 * 
	 * @param type $handle
	 * @return Cron
	 */
	public function get_cron( $handle ) {
		return isset($this->_cron[$handle]) ? $this->_cron[$handle]: null;
	}
	
	protected $_widgets = array();
	
	/**
	 * Method creates a new widget and stores the instance for later use
	 * 
	 * @param type $args
	 * @return Widget
	 */
	public function widget( $args ) {
		
		require_once dirname(__file__).DS.'widget.php';

		$defaults = array(
		    'name'	=> 'Unnamed Widget',
		    'description' => '',
		    'fields'	=> array(
			array(
			    'type' => 'notification'
			)
		    )
		);
		
		$args = wp_parse_args($args, $defaults);
		
		$this->_widgets[$name] = new Widget($args);
		
		return $this->_widgets[$name];
	}
	
	protected $_queue = array(
	    'styles' => array(),
	    'scripts' => array()
	);
	
	/**
	 * 
	 */
	private function enqueue_scripts_shorthand() {
		$this->queue( array(
			'src'	=> 'main.js',
			'deps'	=> array( 'utilitiesjs' ),
			'admin' => true
			) );
	}
	
	/**
	 * Methods are called by wordpress actions and enqueue our
	 * scripts accordingly
	 */
	public function login_enqueue_scripts() {
		$this->enqueue_scripts( 'login' );
		$this->enqueue_style( 'login' );
	}
	public function admin_enqueue_scripts() {
		$this->enqueue_scripts( 'admin' );
		$this->enqueue_style( 'admin' );
	}
	public function wp_enqueue_scripts() {
		$this->enqueue_scripts( 'front' );
		$this->enqueue_style( 'front' );
	}
	
	/**
	 * Method enqueues the scripts that have been preloaded
	 * 
	 * @param type $type
	 */
	private function enqueue_scripts( $type ) {
		foreach((array)$this->_queue['scripts'] as $script) {
			$func = 'wp_enqueue_script';
			if (!$script[$type]) {
				$func = 'wp_register_script';
			}
			
			// custom callback 
			if ($script['show_callback']
				&& call_user_func($script['show_callback'], $this)) {
				$func = 'wp_register_script';
			}
			
			if (strpos($script['src'], 'http') !== 0) {
				$script['src'] = locate($script['src'], 
					$this->get_script_dirs());
				$script['src'] = dir_to_url($script['src']);
			}
			
			$func(  $script['handle'], 
				$script['src'], 
				$script['deps'], 
				$script['ver'], 
				$script['in_footer']
				);
		}
	}
	
	/**
	 * Method enqueues the styles that have been preloaded
	 * 
	 * @param type $type
	 */
	private function enqueue_style( $type ) {
		foreach((array)$this->_queue['styles'] as $style) {
			if (!$style[$type]) continue;
			
			// custom callback 
			if ($style['show_callback']
				&& call_user_func($style['show_callback'], $this)) 
				continue;
			
			wp_enqueue_style( 
					$style['handle'], 
					$style['src'], 
					$style['deps'], 
					$style['ver'], 
					$style['media']
				);
		}
	}
	
	/**
	 * Method makes sure that all data saved is good data
	 * 
	 * @param type $args
	 */
	public function queue( $args = array() ) {
		
		$defaults = array(
			'handle'	=> false,
			'src'		=> false,
			'deps'		=> array(),
			'show_callback' => false,
			'admin'		=> false,
			'editor'	=> false,
			'front'		=> false,
			'login'		=> false,
			'in_footer'	=> false,
			'media'		=> 'all',
			'ver'		=> $this->_version
		);
		
		if (is_string($args)) {
			$src = $args;
			$args = array();
			$args['src'] = $src;
		}
		
		// build a handle if it doesn't exist
		if (!$args['handle']) {
			$args['handle'] = slug( $args['src'] );
		}
		
		if (isset($this->_queue['scripts'][ $args['handle'] ])) {
			$defaults = $this->_queue['scripts'][ $args['handle'] ];
		}
			
		$args = wp_parse_args($args, $defaults);
		if (!$args['src']) return false;
		
		// get the file extension
		$parts = explode('.', $args['src']);
		$ext = strtolower(array_pop($parts));
		$args['ext'] = $ext;
		
		// locate the file
		// add the script to the registry
		if ($ext == 'css' && !file_exists($args['src'])) {
			$args['src'] = locate($args['src'], 
				$this->get_style_dirs());
			$args['src'] = dir_to_url($args['src']);
			
			$this->_queue['styles'][ $args['handle'] ] = $args;
			
		} elseif ($ext == 'js' && !file_exists($args['src'])) {
			$this->_queue['scripts'][ $args['handle'] ] = $args;
			
		}
		
		return $args['handle'];
	}
	
	/**
	 * Method allows the developer to register an installation callback
	 * 
	 * @param type $callback
	 */
	public function set_install_callback( $callback ) {
		$this->_callbacks['install'][] = $callback;
	}
	
	/**
	 * 
	 * @return boolean
	 */
	private function _install() {
		// does the user have permission to do this?
		if (!current_user_can('activate_plugins')) return false;
		
		// Save the version to the database for later upgrade/downgrade
		// plugin calls
		if (!get_option($this->get_plugin_dir_name().'-version')) {
			add_option($this->get_plugin_dir_name().'-version', 
				$this->_version, false, true);
		}
		
		$param_arr = array();
		$param_arr['plugin'] = $this;
		
		foreach ((array)$this->callbacks['install'] as $callback) {
			if (!is_callable($callback)) continue;
			call_user_func_array($callback, $param_arr);
		}
		// fire our callback
		$this->on_install();
	}
	
	/**
	 * 
	 */
	private function on_install() {
		
	}
	
	/**
	 * Method allows the developer to register callbacks for the upgrade
	 * process
	 * 
	 * @param type $callback
	 */
	public function set_upgrading_callback( $callback ) {
		$this->callbacks['upgrading'][] = $callback;
	}
	
	/**
	 * Method fires all upgrading callbacks and corrects the version number
	 * 
	 * @param type $old_version
	 */
	private function _upgrading( $old_version ) {
		// does the user have permission to do this?
		if (!current_user_can('activate_plugins')) return false;
		
		// set to the new version
		update_option($this->get_plugin_dir_name().'-version', 
				$this->_version);
		
		$param_arr = array();
		$param_arr['plugin'] = $this;
		$param_arr['old_version'] = $old_version;
		
		foreach ((array)$this->callbacks['upgrading'] as $callback) {
			if (!is_callable($callback)) continue;
			call_user_func_array($callback, $param_arr);
		}
		// fire our callback
		$this->on_upgrade( $old_version );
	}
	
	/**
	 * 
	 * @param type $old_version
	 */
	private function on_upgrade( $old_version ) {
		
	}
	
	/**
	 * Method allows the developer to set specific callbacks for the 
	 * downgrading process.
	 * 
	 * @param type $callback
	 */
	public function set_downgrading_callback( $callback ) {
		$this->callbacks['downgrading'][] = $callback;
	}
	
	/**
	 * Method fires all downgrading callbacks and corrects the version number
	 * 
	 * @param type $old_version
	 */
	private function _downgrading( $old_version ) {
		// does the user have permission to do this?
		if (!current_user_can('activate_plugins')) return false;
		
		// set to the new version
		update_option($this->get_plugin_dir_name().'-version', 
				$this->_version);
		
		$param_arr = array();
		$param_arr['plugin'] = $this;
		$param_arr['old_version'] = $old_version;
		
		foreach ((array)$this->callbacks['downgrading'] as $callback) {
			if (!is_callable($callback)) continue;
			call_user_func_array($callback, $param_arr);
		}
		
		// fire in house callback
		$this->on_downgrade( $old_version );
	}
	
	/**
	 * 
	 * @param type $old_version
	 */
	private function on_downgrade( $old_version ) {
		
	}
	
	/**
	 * Method let's us know if the current user can activate or
	 * deactivate this plugin
	 * 
	 * @return boolean
	 */
	private function can_activate() {
		// does the user have permission to do this?
		if (!current_user_can('activate_plugins')) return false;
		
		// double checking that we're deactiving THIS plugin
		$plugin = isset($_REQUEST['plugin']) ? $_REQUEST['plugin'] : '';
		$parts = explode(DS, clean_path($plugin));
		$plugin = (isset($parts[0])) ?$parts[0] :'';
		if ($plugin != $this->get_plugin_dir_name()) return false;
		
		return true;
	}
	
	/**
	 * Method is called by wordpress when this plugin gets activated
	 */
	public function _activate() {
		if (!$this->can_activate()) return false;
		
		// call any preset activation callbacks
		$param_arr = array();
		$param_arr['plugin'] = $this;
		
		ob_start();
		foreach ((array)$this->callbacks['activation'] as $callback) {
			if (!is_callable($callback)) continue;
			call_user_func_array($callback, $param_arr);
		}
		
		// call our internal activation process
		$this->on_activation($param_arr);
		ob_clean();
	}
	
	/**
	 * Method sets the callback to be called on activation
	 * 
	 * @param type $callback 
	 */
	public function set_activation_callback( $callback ) {
		$this->callbacks['activation'][] = $callback;
	}
	
	/**
	 * Method is called on activation. This should be called be any overriding
	 * functions so that we can do our own activation processess.
	 * 
	 * @param type $args
	 */
	private function on_activation( $args ) {
		
	}
	
	/**
	 * Method is called by wordpress when this plugin gets activated
	 */
	function _deactivate() {
		if (!$this->can_activate()) return false;
		
		// call any preset deactivation callbacks
		$param_arr = array();
		$param_arr['plugin'] = $this;
		
		ob_start();
		foreach ($this->_callbacks['deactivation'] as $callback) {
			if (!is_callable($callback)) continue;
			call_user_func_array($callback, $param_arr);
		}
		
		// call our internal deactivation process
		$this->on_deactivation($param_arr);
		ob_clean();
	}
	
	/**
	 * Method sets the callback for when this plugin gets deactivated
	 * 
	 * @param type $callback
	 */
	public function set_deactivation_callback( $callback ) {
		$this->callbacks['deactivation'][] = $callback;
	}
	
	/**
	 * Method is called on the deactivation of this plugin
	 * 
	 * @param type $args
	 */
	private function on_deactivation( $args ) {
		
	}
	
	/**
	 * Method will be called upon the deactivation of this plugin
	 */
	private function _uninstall() {
		//if uninstall not called from WordPress exit
		if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) exit();
		if ($this->_file != WP_UNINSTALL_PLUGIN) return false;
		
		// remove plugin specific options
		delete_option($this->get_plugin_dir_name().'-version');
		
		
	}

	protected $_version = '1.0';
	
	/**
	 * Method sets the version number for this plugin
	 * 
	 * @param type $ver
	 * @return Plugin
	 */
	function version( $ver = '1.0' ) {
		$this->_version = $ver;
		return $this;
	}
	
	/**
	 * Method returns an instance of the widget
	 * 
	 * @param type $identifier
	 * @return Widget
	 */
	public function get_widget( $identifier ) {
		// @TODO locate the widget outside of this static variable
		return isset($this->_widgets[$identifier]) 
		?$this->_widgets[$identifier] :null;
	}
	
	/**
	 * Method returns an array of overriding directories. This list is
	 * sorted by default first and overriding in last position.
	 * 
	 * @param string $subdirectory
	 * @return array
	 */
	public function get_override_dirs( $subdirectory = '' ) {
		
		if ($subdirectory) {
			$subdirectory .= DS;
		}
		
		$dirs = array();
		
		// The active theme directory
		$dirs[] =  get_theme_path().DS.
				$this->get_plugin_dir_name().DS.$subdirectory;
		
		// @TODO The Parent theme directory
		
		// The plugin directory
		$dirs[] = $this->get_plugin_dir().DS.$subdirectory;
		
		// The shorthand directory
		$dirs[] = $this->get_shorthand_dir().DS.$subdirectory;
		
		return $dirs;
	}
	
	/**
	 * Method returns the directories name that this plugin is within
	 * 
	 * @return string
	 */
	public function get_plugin_dir_name() {
		$parts = explode('/', plugin_basename(__file__));
		return $parts[0];
	}
	
	
	public function get_plugin_dir() {
		return clean_path( 
			WP_PLUGIN_DIR.DS.$this->get_plugin_dir_name() );
	}
	
	public function get_shorthand_dir() {
		return clean_path( plugin_dir_path(__dir__) );
	}
	
	/**
	 * Method returns an array of directory pathes for the templates.
	 * 
	 * @return array
	 */
	public function get_template_dirs() {
		return $this->get_override_dirs( 'templates' );
	}
	
	/**
	 * Method returns the URL for this plugin
	 */
	public function get_plugin_url() {
		return plugin_dir_url($this->get_plugin_dir()).
			$this->get_plugin_dir_name().'/';
	}
	
	/**
	 * Method returns the URL for this plugin
	 */
	public function get_shorthand_url() {
		return plugin_dir_url(__dir__);
	}
	
	/**
	 * Method returns an array of directories that style files may be
	 * located in. This list is prioritized by overriding to default.
	 * 
	 * @return array
	 */
	public function get_style_dirs() {
		return $this->get_override_dirs( 'css' );
	}
	
	/**
	 * Method returns an array of directories that javascript files may be
	 * located in. This list is prioritized by overriding to default.
	 * 
	 * @return array
	 */
	public function get_script_dirs() {
		return $this->get_override_dirs( 'js' );
	}
	
}