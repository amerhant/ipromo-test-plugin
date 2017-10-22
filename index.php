<?php
/*
Plugin Name: Ipromo test plugin   
Description: Список артистов.
Version: 1.0
Author: Иван Клименко
*/
require __DIR__.'/lib/itp_class.php';
register_activation_hook( __FILE__, array( 'Itp', 'install' ) );
$itp = new Itp(); 

