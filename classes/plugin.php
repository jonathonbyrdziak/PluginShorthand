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

class Plugin {
	
	/**
	 * Method hooks into the appropriate wordpress actions.
	 */
	function __construct() {
		
		//add_action wp_style_enqueue
		//add_action wp_script_enqueue
		
		//add_action plugin_activation, array($this, '_activate')
		//add_action plugin_deactivation, array($this, '_deactivate')
		
	}
	
	protected $_activation_callback = array();
	
	/**
	 * Method sets the callback to be called on activation
	 * 
	 * @param type $callback
	 */
	public function on_activation( $callback ) {
		$this->_activation_callback[] = $callback;
	}
	
	/**
	 * Method is called by wordpress when this plugin gets activated
	 */
	function _activate() {
		$param_arr = func_get_args();
		$param_arr['plugin_class'] = $this;
		
		foreach ($this->_activation_callback as $callback) {
			call_user_func_array($callback, $param_arr);
		}
	}
	
	protected $_deactivation_callback = array();
	
	/**
	 * Method sets the callback for when this plugin gets deactivated
	 * 
	 * @param type $callback
	 */
	public function on_deactivation( $callback ) {
		$this->_deactivation_callback[] = $callback;
	}
	
	/**
	 * Method is called by wordpress when this plugin gets activated
	 */
	function _deactivate() {
		$param_arr = func_get_args();
		$param_arr['plugin_class'] = $this;
		
		foreach ($this->_deactivation_callback as $callback) {
			call_user_func_array($callback, $param_arr);
		}
	}
	
	protected $_version = '1.0';
	
	/**
	 * Method sets the version number for this plugin
	 * 
	 * @param type $ver
	 * @return \Shorthand\Plugin
	 */
	function version( $ver = '1.0' ) {
		$this->_version = $ver;
		return $this;
	}
	
	/**
	 * Method saves the appropriate data 
	 * 
	 * @param type $args
	 */
	public function queue( $args = array() ) {
		
		$defaults = array(
			'handle'	=> false,
			'src'		=> false,
			'deps'		=> array(),
			'admin'		=> true,
			'login'		=> false,
			'in_footer'	=> false,
			'media'		=> 'all',
			'editor'	=> false,
			'ver'		=> $this->_version
		);
		
		extract(wp_parse_args($args, $defaults));
		
		// get the file extension
		$parts = explode('.', $src);
		$ext = strtolower(array_pop($parts));
		unset($parts);
		
		// build a handle if it doesn't exist
		if (!$handle) {
			$handle = \Shorthand\slug( $src );
		}
		
		// exception for editor styles
		$type = $editor ?'editor' :$ext;
		
		switch($type) {
			case 'js':
				$this->wp_enqueue_script($handle, $src, $deps, 
					$ver, $in_footer);
			break;
			case 'css':
				$this->wp_enqueue_style($handle, $src, 
					$deps, $ver, $media);
			break;
			case 'editor':
				// @TODO Figure out what parameters are accepted for wp_enqueue_media
				$this->wp_enqueue_media(array());
			break;
			default:
				throw new Exception("Cannot determine file "
					. "type: $src");
			break;
		}
		
		return $handle;
	}
	
	protected $_styles_editor = array();
	
	/**
	 * Method...
	 * 
	 * @param type $args
	 * @return type
	 */
	public function wp_enqueue_media( $args ) {
		
		$this->_styles_editor[$handle] = $args;
		
		return $handle;
	}
	
	protected $_styles = array();
	
	/**
	 * Method stays true to the wordpress declarations while this class
	 * handles the action hooks for the styles. This method is designed
	 * to be called directly, but it may be faster to use the shorthand
	 * 
	 * @see $this->queue()
	 * 
	 * @param type $handle
	 * @param type $src
	 * @param type $deps
	 * @param type $ver
	 * @param type $media
	 * @return string $handle
	 */
	public function wp_enqueue_style($handle,
					 $src,
					 $deps = array(),
					 $ver = false,
					 $media = 'all') {
		
		if (!file_exists($src)) {
			$src = \Shorthand\locate($src, $this->get_style_dirs());
		}
		
		$this->_styles[$handle] = array(
		    'handle'	=> $handle,
		    'src'	=> $src,
		    'deps'	=> $deps,
		    'ver'	=> $ver ?$ver :$this->_version,
		    'media'	=> $media
		);
		
		return $handle;
	}
	
	protected $_scripts = array();
	
	/**
	 * Method stays true to the wordpress delcarations while this class
	 * handles the action hooks for the scripts. This method is designed
	 * to be called directly, but it may be faster to use the shorthand
	 * 
	 * @SEE $this->queue()
	 * 
	 * @param type $handle
	 * @param type $src
	 * @param type $deps
	 * @param type $ver
	 * @param type $in_footer
	 * @return string $handle
	 */
	public function wp_enqueue_script($handle, 
					  $src, 
					  $deps = array(), 
					  $ver = false,
					  $in_footer = false) {
		
		if (!file_exists($src)) {
			$src = \Shorthand\locate($src, $this->get_script_dirs());
		}
		
		$this->_scripts[$handle] = array(
		    'handle'	=> $handle,
		    'src'	=> $src,
		    'deps'	=> $deps,
		    'ver'	=> $ver ?$ver :$this->_version,
		    'in_footer'	=> $in_footer
		    );
		
		return $handle;
	}
	
	protected $_cron = array();
	
	/**
	 * Method calls the cron class, which could easily be called directly.
	 * However, this class keeps track of the cron instance without further
	 * effort.
	 * 
	 * @SEE \Shorthand\Cron
	 * 
	 * @param type $args
	 * @return \Shorthand\Cron
	 */
	public function cron( $args ) {
		
		require_once dirname(__file__).DS.'cron.php';

		$defaults = array(
		    'handle'	=> \Shorthand\create_guid(),
		    'callback'	=> '\Shorthand\hello_world',
		    'single'	=> true,
		    'schedule'	=> 60,
		    'time'	=> false,
		    'stop'	=> false,
		    'direct'	=> false,
		    'debug'	=> false
		);
		
		extract(wp_parse_args($args, $defaults));
		
		$this->_cron[$handle] = \Shorthand\Cron::getInstance($handle, array(
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
	 * Method returns a global instance of the \Shorthand\Cron class
	 * 
	 * @param type $handle
	 * @return \Shorthand\Cron
	 */
	public function get_cron( $handle ) {
		return isset($this->_cron[$handle]) ? $this->_cron[$handle]: null;
	}
	
	protected $_widgets = array();
	
	/**
	 * Method creates a new widget and stores the instance for later use
	 * 
	 * @param type $args
	 * @return \Shorthand\Widget
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
		
		$this->_widgets[$name] = new \Shorthand\Widget($args);
		
		return $this->_widgets[$name];
	}
	
	/**
	 * Method returns an instance of the widget
	 * 
	 * @param type $identifier
	 * @return \Shorthand\Widget
	 */
	public function get_widget( $identifier ) {
		// @TODO locate the widget outside of this static variable
		return isset($this->_widgets[$identifier]) 
		?$this->_widgets[$identifier] :null;
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
		return \Shorthand\clean_path( 
			WP_PLUGIN_DIR.DS.$this->get_plugin_dir_name() );
	}
	
	public function get_shorthand_dir() {
		return \Shorthand\clean_path( plugin_dir_path(__dir__) );
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
		
		// The shorthand directory
		$dirs[] = $this->get_shorthand_dir().DS.$subdirectory;
		
		// The plugin directory
		$dirs[] = $this->get_plugin_dir().DS.$subdirectory;
		
		// @TODO The Parent theme directory
		
		// The active theme directory
		$dirs[] =  \Shorthand\get_theme_path().DS.
				$this->get_plugin_dir_name().DS.$subdirectory;
		
		return $dirs;
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
	 * Method returns an array of overriding directories. This list is
	 * sorted by default first and overriding in last position.
	 * 
	 * @param string $subdirectory
	 * @return array
	 */
	public function get_override_urls( $subdirectory = '' ) {
		
		if ($subdirectory) {
			$subdirectory .= '/';
		}
		
		$dirs = array();
		
		// The shorthand directory
		$dirs[] = \Shorthand\clean_url( 
			plugin_dir_url(__file__).'/'.$subdirectory ).'/';
		
		// The plugin directory
		$dirs[] = \Shorthand\clean_url( 
			$this->get_plugin_url().'/'.$subdirectory ).'/';
		
		// @TODO The Parent theme directory
		
		// The active theme directory
		$dirs[] =  \Shorthand\get_theme_path().'/'.
				$this->get_plugin_dir_name().'/'.$subdirectory;
		
		return $dirs;
	}
	
	/**
	 * Method returns an array of directories that style files may be
	 * located in. This list is prioritized by overriding to default.
	 * 
	 * @return array
	 */
	public function get_style_dirs() {
		return $this->get_override_urls( 'style' );
	}
	
	/**
	 * Method returns an array of directories that javascript files may be
	 * located in. This list is prioritized by overriding to default.
	 * 
	 * @return array
	 */
	public function get_script_dirs() {
		return $this->get_override_urls( 'js' );
	}
	
}