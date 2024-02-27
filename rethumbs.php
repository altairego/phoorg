<?php
	$CLT= 'florette';
	$EXT= array("tif","wmv","mov","mpg","mpeg");
	
	$imgclient = '//194.150.243.74/imgclient/';
	$depot = $imgclient.'depot/dispatch/'.$CLT.'_UPLOAD_LOT/';
	
	// libs
		// php (cmd)
		// gtk (ini)
		// java (path)
		// imagick (def)
		// ghostscript (path) + fonts?
		// soffice (def)
		// jodconv (def)
	
	define('gImagick','d:\\imagemagick\\');
	define('gOffice',gImagick."libreofficeportable\\App\\libreoffice\\program\\");
	//define('gJodconv',gImagick."jodconv\\");
	define('gJodconv',"D:\\imagemagick\\jodconv\\");
	// techno de compression
	define("zipMethod", 'wvzip'); // system/php/wvzip
	define("mailMethod", 'PHPMailer'); // htmlMimeMail/PHPMailer
	
	//echo "Rsrc loaded - Mem usage is: ", memory_get_usage(), "\n";
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
		
	function Service($act,$params,$fmt='array')
		{
		// add call key
		global $CLT;
		$ImpexpWebService = "http://ww1.westvalley.fr/$CLT/appl/business/in.service.Impexp.inc.php";
		
		$sessId = wvEncrypt($CLT,$CLT);
		if (!is_array($params))
			$params = array('data'=>$params);
		$params['act'] = $act;
		$jsondata = json_encode($params);
		$data = array('sessionId'=>$sessId,'data'=>wvEncrypt($jsondata,$sessId));

echo "\nCalling [".$ImpexpWebService."]\n";
		// use key 'http' even if you send the request to https://...
		$options = array(
				'http' => array(
						'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
						'method'  => 'POST',
						'content' => http_build_query($data),
				)
		);
		$context  = stream_context_create($options);
		$ret = nvl(file_get_contents($ImpexpWebService, false, $context),'null');
		
echo "DATA:\n".jsonPrettyPrint($jsondata)."\n[SERVICE-$act:$ret]\n";
		return json_decode($ret,($fmt=='array'));
		}
	
	function __autoload($class_name) {
		if (file_exists('delegate/class.' . $class_name . '.inc.php'))
			{
			include 'delegate/class.' . $class_name . '.inc.php';
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
		//debug_print_backtrace();
	}
	
	echo "\r\n\r\n### Starting Re-Thumbs - ".date('d/m/Y - H:i:s')." ###\r\n";
	//echo "Includes loaded - Mem usage is: ", memory_get_usage(), "\n";

	$files = array();
	if ($CLT=='devwv')
		$basedir = $imgclient.'demowv';
	else
		$basedir = $imgclient.strtolower($CLT);
	
	$exceptDir = array("img","db","icons","log","images_sources","recettes","temp");
	$exceptExt = array("txt","jpg","png","gif","log","ico");
	
	$baselen = strlen($basedir)+1;
	$dirs = array($basedir);
	
	
	while(count($dirs))
		{
		$dir = array_shift($dirs);
		$directory = dir( $dir );
//echo "DIR:$dir\n";
		$reldir = substr($dir,$baselen);
		while ( FALSE !== ( $readdirectory = $directory->read() ) ) {
			if ( isIn($readdirectory,'.','..',$exceptDir,'Thumbs.db') ) {
				continue;
			}
			if ( isIn(fileext($readdirectory),$exceptExt) ) {
				continue;
			}
			
			$PathDir = $dir . '/' . $readdirectory;
			if ( is_dir( $PathDir ) ) {
				array_push($dirs,$PathDir);
				continue;
			}
			
			$_ext = fileext($readdirectory);
			$_ref = basename($readdirectory,'.'.$_ext);
			$PathTh = $dir . '/' . $_ref . 's.jpg';
			
			if ( isIn($_ext,$EXT) and !file_exists($PathTh)) {

echo "FOUND FILE:$PathDir\n";
echo "NO THUMB:$PathTh\n";
// get medRef[REF=basename($file)]
				$medData = Service('findmed',array('crit'=>$_ref),'obj');
				if ($medData->done) {
					$medref = $medData->status;
//echo "FOUND ITEM:$medref\n";
					// create iedir = medRef
					$iedir = $depot.$medref;
//echo "CREATE DIR:$iedir\n";
					mkdir_p($iedir);
					// copy file=>iedir
//echo "COPY FILE:$PathDir=>$iedir/$readdirectory\n";
					copy($PathDir,$iedir.'/'.$readdirectory);
					// create medRef.xml[act=update]
					file_put_contents($iedir.'/'.$medref.'.xml', 
						'<ITEM SRC="RETHUMB"    CREATOR="'.$CLT.'" CONTACT="westline@westvalley.fr" ID="'.$medref.'" ACTION="UPDATE" TPL=""></ITEM>'
						);
					// create ok.txt
					file_put_contents($iedir.'/ok.txt', $medref);
				//return;
				}
					
				/*
				$size = _filesize($PathDir);
				$files[strtolower($reldir . '/' . $readdirectory)] = array('relpath'=>$reldir . '/' . $readdirectory,'path'=>$PathDir,'size'=>$size);
				fileProcess(
					$PathDir, 
					thumbPath($PathDir), 
					$size
					); /* */
			}
//echo "found [$reldir/$readdirectory]\n";
		}
 
		$directory->close();
		}

	echo "\r\n### END ".date('d/m/Y - H:i:s')." ###\r\n";
?>