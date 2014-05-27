<?php
/**
 * Plugin Name: Plugin Shortcode Example
 * Plugin URI: http://widgetized.co/pluginshortcode
 * Description: This is an example file that you can use to initiate your plugin
 * Version: 1.0
 * Author: Jonathon Byrd
 * Author URI: http://widgetized.co
 * License: The MIT License (MIT)
 * 
 * Copyright (c) 2014 "Jonathon Byrd" <jonathonbyrd@gmail.com>
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

defined('ABSPATH') or die('Direct access to files is not allowed.');

// load the Plugin Shorthand Library from where ever you're located
require_once dirname(__file__).DIRECTORY_SEPARATOR.'shorthand'.
	DIRECTORY_SEPARATOR.'bootstrap.php';

// Instantiate the shorthand
$myplugin = new \Shorthand\Plugin(__file__);

// Set the version of your plugin
$myplugin->version( 1.1 );

$myplugin->queue( 'mychildclass.js' );
$myplugin->queue( 'styles.css' );

$shorthand = $myplugin->admin( array(
	'id' => 'Shorthand',
	'description'	=> 'Various data that is displayed and managed for settings and other configurations',
	'tabs' => array(
	    'first' => 'First',
	    'second' => 'Second',
	    'third' => 'Third'
	)
	) );

$myplugin->metabox('paypal', array(
	'title'		=> 'Paypal Information',
	'_object_types' => $shorthand,
	'priority'	=> 'high',
	'context'	=> 'first',
	'view'		=> 'data.php',
	'_fields' => array(
		array(
		    'name' => 'PayPal Email',
		    'id' => 'business',
		    'type' => 'text',
		    'class' => 'regular-text',
		    'desc' => "",
		),
		array(
		    'name' => 'Language',
		    'id' => 'lc',
		    'type' => 'country',
		    'default' => 'US',
		    'desc' => "<br/>The default language that users should see at paypal when they are redirected to the checkout page."
		),
		array(
		    'name' => 'Cancellation Page',
		    'id' => 'return',
		    'type' => 'pages',
		    'desc' => "If users back out of the checkout process, this is the page that they will be directed to."
		),
		array(
		    'name' => 'Currency Code',
		    'id' => 'currency_code',
		    'type' => 'currency',
		),
		array(
		    'name' => 'Test Mode',
		    'id' => '_test',
		    'type' => 'checkbox',
		    'options' => array(
			'testmode' => ''
		    ),
		    'desc' => "Click this box to activate the paypal test mode."
		),
	)
));
 

/**
 * API function makes it quick and painless to request a specific administrative option
 * from the admin settings page desired.
 * 
 * @param string $object_id
 * @param string $property
 * @param mixed $default
 * @param boolean $single
 */
function redrokk_admin_option( $object_id, $property, $default = false, $single = true )
{
	return redrokk_admin_class::getInstance($object_id)->getOption($property, $default, $single);
}




//var_dump( $myplugin->get_override_dirs() );die();

/*
$mywidget = $myplugin->widget( array(
	'name' => 'my widget',
	'description' => 'my description',
	'fields' => array(  )
	) );
*/


/*
	
add_metabox
add_post_type
add_post_status
add_admin_page
add_admin_menu
add_url_route
add_menu_item
add_cap
add_role
remove_cap
remove_role
wp_login_form
add_ping
plugin_url
plugin_dir
plugin_data
add_help

query
get_records
add_table
add_table_column
get_record

add_page_template

add_sidebar
setting
user
shortcode
support
add_body_class
loop(
	'callback'
	'query'
	'pagination'
	);
add_option
form(
	'fields'
	);
add_node
ajax(
	'no_priv' => false
	'callback'
	'json'
	);
get_request( 'property', 'default', 'type' );
bind_args
url

plugin_update
register
is_registered
registration_form

relationship( 'post_type', 'post_type', 'one-many' );



$myplugin->cron( array(
	//'callback' => 'my_callback',
	'handle' => 'tinker',
	'schedule' => 300,
	'direct'	=> true
	) );
 * 
 * 
 */