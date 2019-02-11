<?php

// Require all needed files
require( 'shortcodes/shortcodes-to-blocks.php' );

define('IS_ADMIN', 'false');

gmw_enqueue_scripts();

 //include GMW main stylesheet
wp_enqueue_style( 'gmw-frontend', GMW_URL . '/assets/css/gmw.frontend.min.css', array(), GMW_VERSION );

 //Map script.
wp_register_script( 'gmw-map', GMW_URL . '/assets/js/gmw.map.min.js', $map_scripts, GMW_VERSION, true );