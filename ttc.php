<?php
$dircmd = '';
exec('dir C:\temp\impexp\batch\51C1AD5C.5060205@westvalley.fr',$dircmd);

echo "[".implode(' ',$dircmd)."]\n";
preg_match('/([0-9]+) (fichier\(s\)|file\(s\))[^a-z]+octets[^0-9]+([0-9]+) (r.p\(s\)|folder\(s\))/i', implode(' ',$dircmd), $chunks);
//print_r($chunks);

echo "----------------------\n";
echo "nb folds : ".($chunks[3]-2)."\n";
echo "nb files : ".$chunks[1]."\n";

echo "----------------------\n";

$fso = new COM ( 'scripting.filesystemobject' );
//$folder = $fso->getfolder( 'C:\temp\impexp\batch' );
$folder = $fso->getfolder( 'C:\temp\impexp\batch\51C1AD5C.5060205@westvalley.fr' );

echo "nb folds : ".$folder->subfolders->count()."\n";
echo "nb files : ".$folder->files->count()."\n";

echo "fold size : ".$folder->size." octets\n";

?>