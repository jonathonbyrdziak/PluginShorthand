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
function slug( $uncleanStr ) {


	return $cleanStr;
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
	foreach ($haystack as $directory) {
		if (!file_exists($directory.$needle)) continue;
		$fullpath = $directory.$needle;
		break;
	}
	return $fullpath;
}

/**
 * Function returns a well formed globally unique identifier
 */
function create_guid() {
	
}

/**
 * Method returns the directory path to the active theme
 * 
 */
function get_theme_path() {
	return \Shorthand\clean_path( get_theme_root() );
}

function clean_path( $uncleanPath ) {
	$cleanPath = rtrim(str_replace(array('/','\\'),DS, $uncleanPath), DS);
	return $cleanPath;
}

function clean_url( $uncleanUrl ) {
	$cleanUrl = rtrim($uncleanUrl, '/');
	return $cleanUrl;
}