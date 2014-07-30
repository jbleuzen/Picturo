<?php

/*
// Override any of the default settings below:

$config['site_title'] = 'Pico';			// Site title
$config['base_url'] = ''; 				// Override base URL (e.g. http://example.com)
$config['theme'] = 'default'; 			// Set the theme (defaults to "default")
$config['date_format'] = 'jS M Y';		// Set the PHP date format
$config['twig_config'] = array(			// Twig settings
	'cache' => false,					// To enable Twig caching change this to CACHE_DIR
	'autoescape' => false,				// Autoescape Twig vars
	'debug' => false					// Enable Twig debug
);
$config['items_per_page'] = 15;

// Un-comment to make your gallery private

$config['private'] = true;
$config['private_pass']['admin'] = 'd033e22ae348aeb5660fc2140aec35850c4da997';

// To add a custom config setting:

$config['custom_setting'] = 'Hello'; 	// Can be accessed by {{ config.custom_setting }} in a theme

*/

$config['private'] = true;
$config['private_pass']['admin'] = 'd033e22ae348aeb5660fc2140aec35850c4da997';
$config['private_pass']['toto'] = 'd033e22ae348aeb5660fc2140aec35850c4da997';
