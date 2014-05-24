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
 * Class simplifies the WordPress Cron system
 * 
 */
class Cron extends \Shorthand\Base
{
	/**
	 * HTML 'id' attribute of the edit screen section
	 * 
	 * @var string
	 */
	var $_id;
	
	/**
	 * The time to schedule the task
	 * 
	 * @var date
	 */
	var $time;
	
	/**
	 * The duration in minutes
	 * 
	 * @var string
	 */
	var $schedule = 60;
	
	/**
	 * The callback function
	 * 
	 * @var string
	 */
	var $callback;
	
	/**
	 * This option will only allow one cron to run at a time.
	 * 
	 * @var boolean
	 */
	var $single = true;
	
	/**
	 * Variable allows the admin to stop the cron from running in the future.
	 * 
	 * @var boolean
	 */
	var $stop = false;
	
	/**
	 * Debugging will kill the website and display a full list of 
	 * debugging information. Use an IP address here and the debugging will
	 * only display for that user
	 * 
	 * @var boolean|string
	 */
	var $debug = false;
	
	/**
	 * Constructor.
	 *
	 */
	function __construct( $options = array() )
	{
		// initializing
		$this->setProperties($options);
		
		// prevent getting locked up
		$this->unlock();
	
		// Hooky Hooky!
		add_action( $this->_id, array(&$this, 'callback') );
		add_filter( 'cron_schedules', array(&$this, 'filter_cron_schedules') );
	
		if (!$this->next_scheduled()) {
			wp_schedule_event( $this->time(), $this->_id, $this->_id, $options );
		}
	
		// prevent the cron from running
		if ($this->stop) {
			$this->unschedule_cron();
		}
		
		// do the debugging
		$this->debug();
	}
	
	/**
	 * Method will reset the option
	 * 
	 */
	function unlock()
	{
		// initializing
		$lasttime = $this->getOption($this->_id.'::running', false);
		
		if ($lasttime < strtotime('-1 hour'))
		{
			$lasttime = $this->setOption($this->_id.'::running', false);
		}
	}
	
	/**
	 * Allows us to dump the cron data on function call
	 */
	function force_debug() {
		$this->debug(true);
	}
	
	/**
	 * Method outputs a full debugging report
	 *
	 * @return boolean
	 */
	function debug( $force = false )
	{
		// dump if we're not debugging
		// ITS ALL OR NOTHING!
		if (!$this->debug && !$force) return;
		if ( defined('DOING_CRON') || isset($_GET['doing_wp_cron']) )
		{
			return;
		}
		
		echo '<pre>';
	
		$crons = _get_cron_array();
		if ( !is_array($crons) )
		{
			echo 'Cron array is empty. Your event is not scheduling.';
			die('</pre>');
		}
	
		// show the crons
		print_r($crons);
	
		if (!$local_time = wp_next_scheduled( $this->_id ))
		{
			echo 'Your event is not scheduling.';
			die('</pre>');
		}
		
		//check the DNS
		$domainparts = str_replace('http://','',get_option( 'siteurl' ));
		$domainparts = explode('/', $domainparts);
		$domain = $domainparts[0];
		
		if (function_exists('gethostbyname') 
		&& $_SERVER['SERVER_ADDR'] !== gethostbyname($domain)
		&& '127.0.0.1' != gethostbyname($domain))
		{
			echo '<br/>DNS does not match this server ('.gethostbyname($domain).')';
		}
		
		set_transient( 'doing_cron', $local_time );
		$cron_url = get_option( 'siteurl' ) . '/wp-cron.php?doing_wp_cron=' . $local_time;
		// show the cron url
		echo '<br/>'.$cron_url;
		
		$results = wp_remote_post( $cron_url, array('sslverify' => apply_filters('https_local_ssl_verify', true)) );
		echo '<br/>';
		print_r($results);
	
		die('</pre>');
	}
	
	/**
	 * The callback for this cron
	 */
	function callback()
	{
		// set the database as Running
		if (!$this->canRun()) return false;
		
		//just in case we crash
		register_shutdown_function(array(&$this, 'stoppedRunning'));
		
		ini_set('memory_limit','256M');
		ini_set('max_execution_time', 0);
		ignore_user_abort(true);
		
		$this->startRunning();
		
		//triggering the cron job
		if (is_callable($this->callback)) {
			call_user_func($this->callback, $this);
		}
		
		do_action('shorthand_cron_fired', $this);
		
		//cron is done
		$this->stoppedRunning();
		
		return true;
	}
	
	/**
	 * Method returns the relevant time
	 */
	function time()
	{
		if (null !== $this->time) {
			return $this->time;
		}
	
		return time();
	}
	
	/**
	 * Method is designed to schedule the cron job
	 *
	 */
	function schedule_name()
	{
		return __("Shorthand-{$this->schedule}Minutes");
	}
	
	/**
	 * Add new filter that represents the minutes entered into schedule
	 *
	 * @param array $schedules
	 */
	function filter_cron_schedules( $schedules )
	{
		//adding our schedule to the array
		$schedules[ $this->_id ] = array(
				'interval' => 60 * $this->schedule,
				'display'  => $this->schedule_name(),
		);
	
		return $schedules;
	}
	
	/**
	 * Method knows whether or not the cron can run a second time
	 * 
	 * @return boolean
	 */
	function canRun()
	{
		if (!$this->single) return true;
		if (!$this->getOption($this->_id.'::running', false)) return true;
		return false;
	}
	
	/**
	 * Function declares that we are running a cron job
	 * 
	 */
	function startRunning()
	{
		// initilizing
		if ( empty( $_GET[ 'doing_wp_cron' ] ) ) {
			$doing_wp_cron = time();
		} else {
			$doing_wp_cron = $_GET[ 'doing_wp_cron' ];
		}
		
		$this->setOption($this->_id.'::running', $doing_wp_cron);
		return $doing_wp_cron;
	}
	
	/**
	 * Method registers that this cron has stopped running
	 * 
	 */
	function stoppedRunning()
	{
		$this->setOption($this->_id.'::running', false);
	}
	
	/**
	 * Method sets a value directly to the database
	 * 
	 * @param string $key
	 * @param mixed $value
	 */
	function setOption( $key, $value, $autoload = 'no' )
	{
		global $wpdb;
		
		if(!is_serialized( $value )) {
			$value = maybe_serialize($value);
		}
		
		if ($value === false) {
			$value = 'false';
		}
		
		if ($value === true) {
			$value = 'true';
		}
		
		$exists = $wpdb->get_row( "SELECT `option_id` FROM $wpdb->options "
			. " WHERE `option_name` = '$key' LIMIT 1" );
		
		// updating
		if ($exists)
		{
			$result = $wpdb->query( "UPDATE $wpdb->options "
				. " SET `option_value` = '$value' "
				. " WHERE `option_name` = '$key'" );
		}
		// adding
		else
		{
			$result = $wpdb->query( "INSERT INTO $wpdb->options "
				. " (`option_name`, `option_value`, `autoload`)"
				. " VALUES "
				. " ('$key', '$value', '$autoload')" );
		}
		return true;
	}
	
	/**
	 * Method gets a value from the database directly
	 * 
	 * @param string $key
	 * @param mixed $default
	 */
	function getOption( $key, $value = false )
	{
		global $wpdb;
		$result = $wpdb->get_row( "SELECT * FROM $wpdb->options "
			. " WHERE $wpdb->options.option_name  = '$key' LIMIT 1" );
		
		if ($result) {
			$value = $result->option_value;
		}
		
		if(is_serialized( $value )) {
			$value = maybe_unserialize($value);
		}
		
		if ($value === 'false') {
			$value = false;
		}
		
		if ($value === 'true') {
			$value = true;
		}
		
		return $value;
	}
	
	/**
	 * Unschedule all cron jobs associated with this hook
	 * 
	 * @return boolean
	 */
	function unschedule_cron()
	{
		while ( $timestamp = $this->next_scheduled() )
		{
			$crons = _get_cron_array();
			unset( $crons[$timestamp][$this->_id] );
			if ( empty($crons[$timestamp][$this->_id]) )
				unset( $crons[$timestamp][$this->_id] );
			if ( empty($crons[$timestamp]) )
				unset( $crons[$timestamp] );
			_set_cron_array( $crons );
		}
	}
	
	/**
	 * Get the next schedule cron 
	 * 
	 */
	function next_scheduled()
	{
		$crons = _get_cron_array();
		if ( empty($crons) )
			return false;
		foreach ( $crons as $timestamp => $cron ) {
			if ( isset( $cron[$this->_id] ) )
				return $timestamp;
		}
		return false;
	}
	
	/**
	 * 
	 * contains the current instance of this class
	 * @var object
	 */
	static $_instances = null;
	
	/**
	 * Method is called when we need to instantiate this class
	 * 
	 * @param array $options
	 */
	public static function getInstance( $_id, $options = array() )
	{
		if (!isset(self::$_instances[$_id]))
		{
			$options['_id'] = $_id;
			$class = get_class();
			self::$_instances[$_id] =& new $class($options);
		}
		return self::$_instances[$_id];
	}
}