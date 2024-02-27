<?php
	define("appVersion", '0.0.1');
	//001 : choose folder
	
	define("iniFile", 'app.ini');
	define ('USE_PICS', true);
	
	// libs
		// php (cmd)
		// gtk (ini)
		// imagick (def)
		// ghostscript (path) + fonts?
	
	define('gImagick', dirname(__FILE__).DIRECTORY_SEPARATOR.'imagick'.DIRECTORY_SEPARATOR);

	// seuil mémoire avant redémarrage en octets
	define('stdMemoryLimit', 10000000);
	
	function delete_event()	{ return false; }
	function destroy() { Gtk::main_quit(); }

	Gtk::rc_parse(dirname(__FILE__).'\runtime\share\themes\MS-Windows\gtk-2.0\gtkrc');

	echo "Rsrc loaded ("
		.(dirname(__FILE__).'\runtime\share\themes\MS-Windows\gtk-2.0\gtkrc')
		.") - Mem usage is: ", memory_get_usage(), "\n";
	include('inc/tools.inc.php');
	
//echo "CREATE : ".dirname(__FILE__).DIRECTORY_SEPARATOR."phplog.txt"."\n";
//file_put_contents(dirname(__FILE__).DIRECTORY_SEPARATOR."phplog.txt" ,"START\n");
	
	function echo_out()
		{
		$args = func_get_args();
		//file_put_contents(dirname(__FILE__).DIRECTORY_SEPARATOR."phplog.txt" ,implode(" ",$args),FILE_APPEND);
		//echo "** >> ".implode(" ",$args)." \r\n";
		echo implode(" ",$args);
		}
		
	echo "\r\n\r\n### Starting PhOOrg v".appVersion." - ".date('d/m/Y - H:i:s')." ###\r\n";

	/*
	require_once('class.ServerConfig.inc.php');
	require_once('class.ConfigServer.inc.php');
	require_once('class.Datas.inc.php');
	require_once('class.ControlPanel.inc.php');
	require_once('class.EditServer.inc.php');
	require_once('class.EntryDialog.inc.php');
	/* */

	function __autoload($class_name) {
		if (file_exists('class.' . $class_name . '.inc.php'))
			{
				include 'class.' . $class_name . '.inc.php';
				return;
			}
		if (file_exists('inc/class.' . $class_name . '.inc.php'))
			{
				include 'inc/class.' . $class_name . '.inc.php';
				return;
			}
		if (file_exists('inc/extra.' . $class_name . '.inc.php'))
			{
				include 'inc/extra.' . $class_name . '.inc.php';
				return;
			}
		debug_print_backtrace();
	}
	//echo "Includes loaded - Mem usage is: ", memory_get_usage(), "\n";
	

	$selfolder = select_folder('\\photos', "Dossier à traiter");

	if ($selfolder) {
		$cpanel = new ControlPanel($selfolder);
		/* Run the main loop. */
		//echo "Ihm loaded - Mem usage is: ", memory_get_usage(), "\n";
		Gtk::main();
	}
	
	echo "\r\nExiting - Mem usage is: ", memory_get_usage(), "\n";
	echo "\r\n### ".date('d/m/Y - H:i:s')." ###\r\n";
?>