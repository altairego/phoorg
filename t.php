<?php

$file = "geo-v\u00e9rif accent.jpg";
$file = "geo-v\\u00e9rif accent.jpg";

echo "\n[V1:".$file."]\n";
$file = (preg_replace('/\\\\u([\da-fA-F]{4})/', '&#x\1;', $file));
echo "\n[V2:".$file."]\n";
$file = (html_entity_decode($file));
echo "\n[V3:".$file."]\n";
$file = json_encode(utf8_encode($file));
echo "\n[V4:".$file."]\n";



$file = iconv("Windows-1252","UTF-8",$argv[1]);

	if ($dh = opendir ( "c:\\temp" )){
		while ( ($file = readdir ( $dh )) !== false ) {
			if (substr($file,-5)=='t.jpg'){
				//$file = iconv("Windows-1252","UTF-8",$file);
				$file = utf8_encode($file);
				
				echo "\n[V2:".$file."]\n";
				echo "[d1:".(json_decode('"'.$file.'"'))."]\n";
				echo "[d3:".utf8_decode(json_decode('"'.$file.'"'))."]\n";
			}
		}
	}

?>