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
 * Function strips all characters but a-z, A-Z, 0-9, underscores, and dashes
 * 
 * @param type $uncleanStr
 * @return string $cleanStr
 */
function slug($string) {
	//Unwanted:  {UPPERCASE} ; / ? : @ & = + $ , . ! ~ * ' ( )
	$string = strtolower($string);
	//Strip any unwanted characters
	$string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
	//Clean multiple dashes or whitespaces
	$string = preg_replace("/[\s-]+/", " ", $string);
	//Convert whitespaces and underscore to dash
	$string = preg_replace("/[\s_]/", "-", $string);
	return $string;
}

/**
 * Function is the default for various configurations. The intent is to allow 
 * the developer to first test that a configuration is triggering properly.
 */
function hello_world() {
	echo 'Hello World';
}

/**
 * Function locates a file/$needle located within one of the given directories/
 * $haystack
 * 
 * @param type $needle
 * @param type $haystack
 */
function locate( $needle, $haystack = array() ) {
	$fullpath = $needle;
	foreach ((array)$haystack as $directory) {
		if (!file_exists($directory.$needle)) continue;
		$fullpath = $directory.$needle;
		break;
	}
	return $fullpath;
}

/**
 * Create Global Unique Identifier
 * 
 * Method will activate only if sugar has not already activated this
 * same method. This method has been copied from the sugar files and
 * is used for cakphp database saving methods.
 * 
 * There is no format to these unique ID's other then that they are
 * globally unique and based on a microtime value
 * 
 * @return string //aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee
 */
function create_guid() {
	$microTime = microtime();
	list($a_dec, $a_sec) = explode(" ", $microTime);

	$dec_hex = sprintf("%x", $a_dec * 1000000);
	$sec_hex = sprintf("%x", $a_sec);

	\shorthand\ensure_length($dec_hex, 5);
	\shorthand\ensure_length($sec_hex, 6);

	$guid = "";
	$guid .= $dec_hex;
	$guid .= \shorthand\create_guid_section(3);
	$guid .= '-';
	$guid .= \shorthand\create_guid_section(4);
	$guid .= '-';
	$guid .= \shorthand\create_guid_section(4);
	$guid .= '-';
	$guid .= \shorthand\create_guid_section(4);
	$guid .= '-';
	$guid .= $sec_hex;
	$guid .= \shorthand\create_guid_section(6);

	return $guid;
}
function create_guid_section($characters) {
	$return = "";
	for ($i = 0; $i < $characters; $i++) {
		$return .= sprintf("%x", mt_rand(0, 15));
	}
	return $return;
}
function ensure_length(&$string, $length) {
	$strlen = strlen($string);
	if ($strlen < $length) {
		$string = str_pad($string, $length, "0");
	} else if ($strlen > $length) {
		$string = substr($string, 0, $length);
	}
}

/**
 * Method returns the directory path to the active theme
 * 
 */
function get_theme_folder() {
	$parts = explode('/', get_bloginfo('stylesheet_directory'));
	return array_pop($parts);
}
function get_theme_path() {
	return \Shorthand\clean_path( get_theme_root().
		DS.\Shorthand\get_theme_folder() );
}

function clean_path( $uncleanPath ) {
	$cleanPath = rtrim(str_replace(array('/','\\'),DS, $uncleanPath), DS);
	return $cleanPath;
}

function clean_url( $uncleanUrl ) {
	$cleanUrl = rtrim(str_replace('\\', '/', $uncleanUrl), '/');
	return $cleanUrl;
}

/**
 * Function replaces the directory path with a valid url path
 * 
 * @param type $dir
 */
function dir_to_url( $dir ) {
	return \Shorthand\clean_url(str_replace(\Shorthand\clean_path(ABSPATH),
		get_bloginfo('home'),
		$dir));
}