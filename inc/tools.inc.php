<?php
	// =================================================
	// ===================== FILES =====================
	// =================================================
	
	$_PATH_STD = array('/'=>'\\','\\'=>'/');
	
	function pathStd($path){
		global $_PATH_STD;
		return str_replace($_PATH_STD[DIRECTORY_SEPARATOR], DIRECTORY_SEPARATOR, $path);
	}
	
	function executeCommand($cmd)
		{
		//logdbg('CMD', $cmd);
//echo "System :\n$cmd\n";
		$ret = exec($cmd);
		//logdbg('CMD		RET', $ret);
		return $ret;
		}
		
	function execInBackground($cmd) {
		if (substr ( php_uname (), 0, 7 ) == "Windows") {
			pclose ( popen ( 'start /B "" '. $cmd, "r" ) );
		} else {
			exec ( $cmd . " > /dev/null &" );
		}
	}
			
	function mkdir_p($target) //thank's to saint at corenova.com
		{
		$target = str_replace('\\',DIRECTORY_SEPARATOR,$target);
		$target = str_replace('/',DIRECTORY_SEPARATOR,$target);
		if (is_dir($target)||empty($target)) return 1; // best case check first
		if (file_exists($target) && !is_dir($target)) return 0;
		if (mkdir_p(substr($target,0,strrpos($target,DIRECTORY_SEPARATOR))))
		  return mkdir($target); // crawl back up & create dir tree
		return 0;
		}

	function rmdir_p($dir, $keep_me = false)
		{
	    if(!$dh = @opendir($dir)) return;
	    while (false !== ($obj = readdir($dh)))
	        if(!isIn($obj,'.','..'))
				if (!@unlink($dir.DIRECTORY_SEPARATOR.$obj))
					rmdir_p($dir.DIRECTORY_SEPARATOR.$obj, false);

	    closedir($dh);
	    if (!$keep_me)
	        @rmdir($dir);
		}

	if (!function_exists("imProcess"))
		{
		if (!defined('gImagick')) define('gImagick',dirname(__FILE__).DIRECTORY_SEPARATOR.'imagick'.DIRECTORY_SEPARATOR);
		
		$_IMAGICK_EXTS = array(
				'3fr','aai','ai','art','arw','avs','bgr','bgra','bie','bmp','bmp2','bmp3','brf','cal','cals','canvas','caption',
				'cin','cip','clip','clipboard','cmyk','cmyka','cr2','crw','cur','cut','dcm','dcr','dcx','dds','dfont','djvu','dng',
				'dot','dps','dpx','dxt1','dxt5','emf','epdf','epi','eps','eps2','eps3','epsf','epsi','ept','ept2','ept3','erf','exr',
				'fax','fits','fpx','fractal','fts','g3','gif','gif87','gradient','gray','group4','gv','hald','hdr','histogram','hrz',
				'htm','html','icb','ico','icon','info','inline','ipl','isobrl','j2c','j2k','jbg','jbig','jng','jnx','jp2','jpc','jpeg',
				'jpg','jpt','json','k25','kdc','label','m2v','m4v','mac','map','mask','mat','matte','mef','miff','mng','mono',
				'mpc','mrw','msl','msvg','mtv','mvg','nef','nrw','null','orf','otb','otf','pal','palm','pam',
				'pango','pattern','pbm','pcd','pcds','pcl','pct','pcx','pdb','pdf','pef','pes','pfa','pfb','pfm','pgm','picon',
				'pict','pix','pjpeg','plasma','png','png00','png24','png32','png48','png64','png8','pnm','ppm','ps','ps2','ps3','psb',
				'psd','ptif','pwp','raf','ras','raw','rgb','rgba','rgbo','rgf','rla','rle','rmf','rw2','scr','sct','sfw','sgi','shtml',
				'sr2','srf','stegano','sun','svg','svgz','text','tga','thumbnail','tiff','tif','tiff64','tile','tim','ttc','ttf','txt',
				'ubrl','uil','uyvy','vda','vicar','vid','viff','vips','vst','wbmp','webp','wmf','wpg','x3f','xbm','xc','xcf',
				'xpm','xps','xv','ycbcr','ycbcra','yuv');

		// can create preview, no hd
		$_IMAGICK_DOC_EXTS = array(
				'cal','cals','caption',
				'clipboard','dfont',
				'dot','dps','dpx','epdf',
				'htm','html',
				'json','label',
				'mtv','mvg','null',
				'pdf','pdfa',
				'shtml',
				'text','thumbnail','ttf','txt',
				'xpm','xps','xv','ycbcr','ycbcra','yuv');
		
		// can create flv (libreoffice/jodconverter)
		// ffprobe -v error -select_streams v:0 -show_format -show_streams -of json d053113.mov
		// ffmpeg -i d00.webm -vf "thumbnail,scale='if(gt(a,1),200,-1)':'if(gt(a,1),-1,200)'" -frames:v 1 thumb.png
		// ffmpeg -i d00.webm -s 176x144 -ar 11025 d00.flv
		// ffmpeg -i d00.webm -s 352x288 -ar 22050 d00.flv
		$_FFMPEG_VID_EXT = array(
			"amr","asf","avi","dirac","dv","flac","flv","h261","h263","h264","m4v","mkv","webm",
				"mjpeg","mov","3gp","3g2","m4a","mj2","mp4","mpeg","mpg","mpegts","ogg",
				"rm","rtp","rtsp","swf","wav","wmv"
		);
		
		$_FFMPEG_AUD_EXT = array(
			"spdif","gif","mp3","oma","au","aiff","ac3","dts"
		);
	
		// can create pdf (libreoffice/jodconverter)
		$_READABLE_DOC_EXT = array(
				"swf","html","odt","sxw","doc","docx","rtf","wpd","txt","wiki","ods","sxc","xls","xlsx","csv","tsv","odp","sxi","ppt","pptx","odg","svg"
		);

		// do not store
		$_IGNORE_DOC_EXT = array(
				'txt', 'xml', 'log', 'bat', 'exe', 'com', 'php'
		);
		
		function imProcess($_f, $_dst, $_resize = null, $_popts = "-auto-orient") {
			if (! file_exists ( $_f ))
				return false;
			
			if (is_array ( $_resize ))
				$opts = $_resize;
			else {
				if (is_numeric ( $_resize ))
					$opts = array (
							'resize' => $_resize 
					);
				else
					$opts = array ();
				
				if ($_popts == "-auto-orient")
					$opts ['orient'] = 'auto';
					// elseif opts->match(/-($1) ($2)/)
					// $opts[$1]=$2;
				else
					$opts ['raw'] = $_popts;
			}
			
			if (array_get ( $opts, 'quality' ) == '')
				$opts ['quality'] = 80;
			
			$stropts = "";
			$_newsize = "";
			
			foreach ( $opts as $_o => $_v )
				switch ($_o) {
					case 'resize' :
						$_newsize = "-resize " . $opts ['resize'] . "x" . $opts ['resize'] . "^>";
						break;
					case 'orient' :
						if ($_v == 'auto')
							$stropts .= " -auto-orient";
						break;
					case 'raw' :
						$stropts .= " $_v";
						break;
					case 'profile' :
					case 'colorspace' :
						break;
					default :
						// case 'rotate':
						// case 'resize':
						$stropts .= " -$_o $_v";
						break;
				}
			
//echo "CMD:[".gImagick.'identify -format "%e#%m#%[colorspace]#%z#%n#%G#%b#%x" "'.$_f.'"'."]\n";
			$data = exec ( gImagick . 'identify -format "%e#%m#%[colorspace]#%z#%n#%G#%b#%x" "' . $_f . '"' );
			// logdbg(gImagick.'identify -format "%e#%m#%[colorspace]#%z#%n#%G#%b#%x" "'.$_f.'"',$data);
			// echo "[$data]\n";
			
			if (isNull ( $data ))
				return false;
			
			list ( $_f_ext, $_f_tp, $_f_co, $_f_bpp, $_f_nb, $_f_sz, $_f_wg, $_f_dpi ) = explode ( '#', $data );
			
			if ($_f_nb > 1) {
//echo "CMD2:[".gImagick.'identify -format "%e#%m#%[colorspace]#%z#%n#%G#%b#%x" "'.$_f.'"'."[0]]\n";
				$data = exec ( gImagick . 'identify -format "%e#%m#%[colorspace]#%z#%n#%G#%b#%x" "' . $_f . '"[0]' );
				list ( $_f_ext, $_f_tp, $_f_co, $_f_bpp, $_f_nb2, $_f_sz, $_f_wg, $_f_dpi ) = explode ( '#', $data );
			}
			
			$ext = strtolower ( $_f_ext );
			
			$_src_co = "";
			$_dst_co = "";
			if (! isIn ( $_f_co, 'RGB', 'sRGB' )) {
				if (isIn ( $_f_co, 'CMYK' )) {
					$_src_co = "-profile " . gImagick . "cmyk.icc";
					$_dst_co = "-profile " . gImagick . "rgb.icc";
				} else {
					$_src_co = "-colorspace $_f_co";
					$_dst_co = "-colorspace $_f_co";
				}
			}
			
			if (array_get ( $opts, 'colorspace' ) != '')
				$_dst_co = "-colorspace " . array_get ( $opts, 'colorspace', 'RGB' );
			
			if (array_get ( $opts, 'profile' ) != '')
				$_dst_co = "-profile " . gImagick . array_get ( $opts, 'profile', 'rgb' ) . ".icc";
			
			$_f_pg = '';
			if ($_f_nb > 1)
				$_f_pg = '[0]'; // isIn($ext,'pdf')
			
			$cmd = gImagick . 'convert -density ' . $_f_dpi . ' ' . $_src_co . ' "' . $_f . '"' . $_f_pg . ' ' . $_dst_co . ' ' . $stropts . ' -background white -flatten -alpha off ' . $_newsize . ' "' . $_dst . '"';
echo "\n #cmd:$cmd\n";
			exec ( $cmd );
			return true;
		}
		
		function vidProcess($_f, $_dst, $_resize = null, $_popts = null) {
			global $_FFMPEG_VID_EXT,$_FFMPEG_AUD_EXT;
			
			if (! file_exists ( $_f ))
				return false;
			
			if (is_array ( $_resize ))
				$opts = $_resize;
			else {
				if (is_numeric ( $_resize ))
					$opts = array (
							'resize' => $_resize 
					);
				else
					$opts = array ();
				
				if (!isNull($_popts))
					$opts ['raw'] = $_popts;
			}
			
			$stropts = "";
			$_newsize = "";
			
			foreach ( $opts as $_o => $_v )
				switch ($_o) {
					case 'resize' :
						if (isIn(fileext($_dst),$_FFMPEG_VID_EXT,$_FFMPEG_AUD_EXT))
							$_newsize = "-vf \"scale='if(gt(a,1)," . $opts ['resize'] . ",-1)':'if(gt(a,1),-1," . $opts ['resize'] . ")'\" -ar 22050 ";
						else
							$_newsize = "-vf \"thumbnail,scale='if(gt(a,1)," . $opts ['resize'] . ",-1)':'if(gt(a,1),-1," . $opts ['resize'] . ")'\" -frames:v 1 ";
						break;
					case 'orient' :
						break;
					case 'raw' :
						$stropts .= " $_v";
						break;
					case 'profile' :
					case 'colorspace' :
						break;
					default :
						// case 'rotate':
						// case 'resize':
						$stropts .= " -$_o $_v";
						break;
				}
			
			//$data = exec ( gImagick . 'ffprobe -v error -select_streams v:0 -show_format -show_streams -of json "' . $_f . '"' );
			// echo "[$data]\n";
			//if (isNull ( $data ))
			//	return false;
			
			$ext = fileext ( $_dst );
			
			$cmd = gImagick . 'ffmpeg -y -i "' . $_f . '" ' . $stropts . ' ' . $_newsize . ' "' . $_dst . '"';
echo "\n #cmd:$cmd\n";
			exec ( $cmd );
			return true;
		}
		
	}
	
	function fileProcess($src, $_f, $_resize = null){
		global $_IGNORE_DOC_EXT,$_READABLE_DOC_EXT,$_FFMPEG_VID_EXT,$_FFMPEG_AUD_EXT;
		$ext = fileext($src);
//echo "\n[MKDIR:".dirname($_f)."]\n";
		mkdir_p(dirname($_f));
		if ($ext==fileext($_f) and (isNull($_resize) or isIn($ext, $_IGNORE_DOC_EXT))){
			copy($src, $_f);
		}elseif (isIn($ext, $_FFMPEG_VID_EXT,$_FFMPEG_AUD_EXT)){
			vidProcess($src, $_f, $_resize);
		}else{
			imProcess($src, $_f, $_resize);
		}
	}
		
	function _filesize($file)
		{
			if (!file_exists($file)) return 0;
			//$size = filesize($file);
			//if ($size < 0)
			if (!(strtoupper(substr(PHP_OS, 0, 3)) == 'WIN'))
				$size = trim(`stat -c%s $file`);
			else
			{
				$fsobj = new COM("Scripting.FileSystemObject");
				$f = $fsobj->GetFile($file);
				$size = $f->Size;
			}
		
			return $size;
		}
		
	function fileext($file)
		{
		$chunks = pathinfo($file);
		return strtolower(array_get($chunks,"extension"));
		}

	if ( !function_exists('file_put_contents') && !defined('FILE_APPEND') )
		{
		define('FILE_APPEND', 1);
		function file_put_contents($n, $d, $flag = false)
			{
		    $mode = ($flag == FILE_APPEND || strtoupper($flag) == 'FILE_APPEND') ? 'a' : 'w';
		    $f = @fopen($n, $mode);
		    if ($f === false)
				{
		        return 0;
				}
			else
				{
		        if (is_array($d)) $d = implode($d);
		        $bytes_written = fwrite($f, $d);
		        fclose($f);
		        return $bytes_written;
			    }
			}
		}
		
	if ( !function_exists('sys_get_temp_dir') )
		{
		// Based on http://www.phpit.net/
		// article/creating-zip-tar-archives-dynamically-php/2/
		function sys_get_temp_dir()
		    {
			// Try to get from environment variable
			if ( !empty($_ENV['TMP']) )
		        {
				return realpath( $_ENV['TMP'] );
		        }
			else if ( !empty($_ENV['TMPDIR']) )
		        {
				return realpath( $_ENV['TMPDIR'] );
		        }
			else if ( !empty($_ENV['TEMP']) )
		        {
				return realpath( $_ENV['TEMP'] );
		        }

		        // Detect by creating a temporary file
			else
		        {
				// Try to use system's temporary directory
				// as random name shouldn't exist
				$temp_file = tempnam( md5(uniqid(rand(), TRUE)), '' );
				if ( $temp_file )
		            {
					$temp_dir = realpath( dirname($temp_file) );
					unlink( $temp_file );
					return $temp_dir;
		            }
				else
		            {
					return FALSE;
		            }
		        }
		    }
		}
	// =================================================
	// ==================== ARRAYS ====================
	// =================================================

	function &array_get($tbl, $key)
		{
		if (func_num_args()>2)
			$nvl = func_get_arg (2);
		else
			$nvl = "";
		
		if (isNull($nvl)) $nvl = "";
		if (is_array($tbl))
			if (array_key_exists($key, $tbl))
				return $tbl[$key];
			elseif (array_key_exists(strtoupper($key), $tbl))
				return $tbl[strtoupper($key)];
			elseif (array_key_exists(strtolower($key), $tbl))
				return $tbl[strtolower($key)];
		return $nvl;
		}
	
	function array_put(&$tbl, $val)
		{
		if (!isIn($val,$tbl)) $tbl[] = $val;
		}
	
	function isIn($needle)
		{
		$numargs = func_num_args();
		$arg_list = func_get_args();
		for ($i = 1; $i < $numargs; $i++)
			{
			if (is_array($arg_list[$i]))
				{
				if (in_array($needle, $arg_list[$i])) return true;
				}
			elseif ($needle == $arg_list[$i]) return true;
			}
		return false;
		}

	function array_xml($arr,$tag,$attr,$mtag="")
		{
		$ret = "";
		$tag = strtoupper($tag);
		if ($mtag=="")
			$mtag = $tag."S";
		else
			$mtag = strtoupper($mtag);
		$attr = strtoupper($attr);
		
		$ret .= "<$mtag>";
		foreach($arr as $key=>$val)
			{
			if (is_array($val)) $val = var_export($val, true);
			$ret .= "<$tag $attr=\"$key\">$val</$tag>";
			}
		$ret .= "</$mtag>";
		
		return $ret;
		}

	// =================================================
	// =================== DEV TOOLS ==================
	// =================================================

	function evalExpr($expr)
		{
		eval( "\$dst = $expr;" );
		return $dst;
		}
	
	//DEPRECATED : use call_user_func
	function callBack($fnc, $source, $param)
		{
		if (is_array($fnc))
			{
			if (is_string($fnc[0]))
				{
				global ${$fnc[0]};
				${$fnc[0]}->$fnc[1]($source, $param);
				}
			else
				$fnc[0]->$fnc[1]($source, $param);
			}
		else
			$fnc($source, $param);
		}

	function isNull(&$var)
		{
		return (!isset($var) or (empty($var) and $var !== 0));
		}
	
	function isTrue(&$var)
		{
		if (isNull($var)) return false;
		if (is_numeric($var)) return $var != 0;
		
		return (isIn(strtoupper($var),"YES","TRUE","ON","OK","ALLWAYS"));
		}
	
	function nvl(&$var, $val)
		{
		if (!isNull($var))
			return $var;
		return $val;
		}
		
	function cycle(&$var, $nb)
		{
		$var = ($var+1)%$nb;
		}
	
	// =================================================
	// =================== STRINGS TOOLS ==================
	// =================================================
		$_octet_multiplier = array("K"=>1024,"M"=>1048576,"G"=>1073741824);
		$_unit_multiplier = array("K"=>1000,"M"=>1000000,"G"=>1000000000);
		
	function humanSize($nbo, $u=null, $octets=true)
		{
		global $_octet_multiplier,$_unit_multiplier;
		$nboctet = $nbo;
		$units = $octets?$_octet_multiplier:$_unit_multiplier;

		if ($u) $nboctet *= $units[strtoupper($u)];
		
		if ($nboctet > (5*$units['G']) )
			return round($nboctet/$units['G'],2).'G';
		else if ($nboctet > 1.5*$units['G'])
			return round($nboctet/$units['M']).'M';
		else if ($nboctet > 5*$units['M'])
			return round($nboctet/$units['M'],2).'M';
		else if ($nboctet > 1.5*$units['M'])
			return round($nboctet/$units['K']).'K';
		else if ($nboctet > 5*$units['K'])
			return round($nboctet/$units['K'],2).'K';

		return "$nboctet";
		}

	function is_utf8($str)
		{
		$c=0; $b=0;
		$bits=0;
		$len=strlen($str);
		for($i=0; $i<$len; $i++)
			{
			$c=ord($str[$i]);
			if($c > 128)
				{
				if(($c >= 254)) return false;
				elseif($c >= 252) $bits=6;
				elseif($c >= 248) $bits=5;
				elseif($c >= 240) $bits=4;
				elseif($c >= 224) $bits=3;
				elseif($c >= 192) $bits=2;
				else return false;
				if(($i+$bits) > $len) return false;
				while($bits > 1)
					{
					$i++;
					$b=ord($str[$i]);
					if($b < 128 || $b > 191) return false;
					$bits--;
					}
				}
			}
		return true;
		}

	function castUtf($str,$toUTF=true)
		{
		if ($toUTF)
			{
			if (!is_utf8($str))
				return utf8_encode($str);
			}
		else
			{
			if (is_utf8($str))
				return utf8_decode($str);
			}
		return $str;
		}
		
	function str2bin($str)
		{
		$val= 0;
		$mul=1;
		for($i=0;$i<strlen($str);$i++,$mul*=2)
			if ($str{$i}=="1") $val+=$mul;
		return $val;
		}
		
	function getmicrotime(){
	   list($usec, $sec) = explode(" ",microtime());
	   return ((float)$usec + (float)($sec%120));
	   }
	   
	/**
	* @param    $hex string        6-digit hexadecimal color
	* @return    array            3 elements 'r', 'g', & 'b' = int color values
	* @desc Converts a 6 digit hexadecimal number into an array of
	*       3 integer values ('r'  => red value, 'g'  => green, 'b'  => blue)
	*/
    function hexcolor2intarray($hex) {
        if( eregi( "[#]?([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})", strtolower($hex), $ret ) )
        	return $ret;
        return array( 0,0,0 ); //black
		}

	/**
	 *  Log dans fichier debug.txt
	 */
	function logdbg($item="",$msg="")
		{
		$date = date("d/m/Y G.i:s");//, time()
		$fp=fopen(dirname(__FILE__)."/debug.txt","a");
		if ($fp)
			{
			fwrite($fp, "$date - $item \t: $msg \r\n");
			fclose($fp);
			}
		}
	
	class INI {
	    /**
	     *  WRITE
	     */
	    static function write($filename, $ini) {
	        $string = '';
	        foreach(array_keys($ini) as $key) {
	            $string .= '['.$key."]\n";
	            $string .= INI::write_get_string($ini[$key], '')."\n";
	        }
	        file_put_contents($filename, $string);
	    }
	  /**
	     *  write get string
	     */
	    static function write_get_string(& $ini, $prefix) {
	        $string = '';
	        ksort($ini);
	        foreach($ini as $key => $val) {
	            if (is_array($val)) {
	                $string .= INI::write_get_string($ini[$key], $prefix.$key.'.');
	            } else {
	                $string .= $prefix.$key.' = '.str_replace("\n", "\\\n", INI::set_value($val))."\n";
	            }
	        }
	        return $string;
	    }
	  /**
	     *  manage keys
	     */
	    static function set_value($val) {
	        if ($val === true) { return 'true'; }
	        else if ($val === false) { return 'false'; }
	        return $val;
	    }
	  /**
	     *  READ
	     */
	    static function read($filename) {
	        $ini = array();
	        $lines = file($filename);
	        $section = 'default';
	        $multi = '';
	        foreach($lines as $line) {
	            if (substr($line, 0, 1) !== ';') {
	                $line = str_replace("\r", "", str_replace("\n", "", $line));
	                if (preg_match('/^\[(.*)\]/', $line, $m)) {
	                    $section = $m[1];
	                } else if ($multi === '' && preg_match('/^([a-z0-9_.\[\]-]+)\s*=\s*(.*)$/i', $line, $m)) {
	                    $key = $m[1];
	                    $val = $m[2];
	//                    if (substr($val, -1) !== "\\") {
	                        $val = trim($val);
	                        INI::manage_keys($ini[$section], $key, $val);
	                        $multi = '';
	//                    } else {
	//                        $multi = substr($val, 0, -1)."\n";
	//                    }
	                } else if ($multi !== '') {
	//                    if (substr($line, -1) === "\\") {
	//                        $multi .= substr($line, 0, -1)."\n";
	//                    } else {
	                        INI::manage_keys($ini[$section], $key, $multi.$line);
	                        $multi = '';
	//                    }
	                }
	            }
	        }
	       
	        $buf = get_defined_constants(true);
print_r($buf['user']);
	        $consts = array();
	        foreach($buf['user'] as $key => $val) {
	            $consts['{'.$key.'}'] = $val;
	        }
	        array_walk_recursive($ini, array('INI', 'replace_consts'), $consts);
	        return $ini;
	    }
	  /**
	     *  manage keys
	     */
	    static function get_value($val) {
	        if (preg_match('/^-?[0-9]$/i', $val)) { return intval($val); }
	        else if (strtolower($val) === 'true') { return true; }
	        else if (strtolower($val) === 'false') { return false; }
	        else if (preg_match('/^"(.*)"$/i', $val, $m)) { return $m[1]; }
	        else if (preg_match('/^\'(.*)\'$/i', $val, $m)) { return $m[1]; }
	        return $val;
	    }

	    static function get($ini, $sect, $key)
			{
			$val='';
			$s = array_get($ini, $sect);
			if (!isNull($s))
				$val=INI::get_value(array_get($s, $key));
	        return $val;
			}
		
	    static function set(&$ini, $sect, $key, $val)
			{
			if (!array_key_exists($sect, $ini))
				$ini[$sect]=array();

			$ini[$sect][$key] = $val;
			}
		
	  /**
	     *  manage keys
	     */
	    static function get_key($val) {
	        if (preg_match('/^[0-9]$/i', $val)) { return intval($val); }
	        return $val;
	    }
	    /**
	     *  manage keys
	     */
	    static function manage_keys(& $ini, $key, $val) {
	        if (preg_match('/^([a-z0-9_-]+)\.(.*)$/i', $key, $m)) {
	            INI::manage_keys($ini[$m[1]], $m[2], $val);
	        } else if (preg_match('/^([a-z0-9_-]+)\[(.*)\]$/i', $key, $m)) {
	            if ($m[2] !== '') {
	                $ini[$m[1]][INI::get_key($m[2])] = INI::get_value($val);
	            } else {
	                $ini[$m[1]][] = INI::get_value($val);
	            }
	        } else {
	            $ini[INI::get_key($key)] = INI::get_value($val);
	        }
	    }
	    /**
	     *  replace utility
	     */
	    static function replace_consts(& $item, $key, $consts) {
	        if (is_string($item)) {
	            $item = strtr($item, $consts);
	        }
	    }
	}
	
	/* OTHER UTILS */
	function updateProgress(&$pb, $val)
		{
//echo "[U/P:$val]\n";
		if(isNull($pb)) return;
		@$pb->set_fraction($val);
		@$pb->set_text( intval($val*100).'%');
		while (Gtk::events_pending()) {Gtk::main_iteration();}
		}
	
	function runDialog(&$dialog)
		{
		if(isNull($dialog)) return;
		$dialog->show_all();
		$ret = $dialog->run();
		$dialog->destroy();
		return $ret;
		}
		
	function select_folder($ctrl = null, $label = "Select a folder")
		{
		$win=null;
        if($ctrl and $ctrl->window)
	        {
	        $win = $ctrl->get_toplevel();
	        }
        $dialog = new
			GtkFileChooserDialog
				(
				$label,
				$win,
				Gtk::FILE_CHOOSER_ACTION_SELECT_FOLDER,
				array(Gtk::STOCK_OK, Gtk::RESPONSE_OK, Gtk::STOCK_CANCEL,Gtk::RESPONSE_CANCEL),
				null
				);
        $selected_folder = '';
        if($ctrl)
	        {
	        $selected_folder = is_string($ctrl)?$ctrl:$ctrl->get_text();
	        $dialog->set_current_folder($selected_folder);
    	    }
        
        $dialog->show_all();
		$code = $dialog->run();
        if ($code == Gtk::RESPONSE_OK)
        	{
            $selected_folder = $dialog->get_filename();
            //echo "selected_file = $selected_file\n";
			if ($ctrl and !is_string($ctrl)) $ctrl->set_text($selected_folder);
        	}
		
		$dialog->destroy();
		
		return $selected_folder;
		}

	function newButton($title,$click)
		{
		$btn = new GtkButton($title);
		$btn->connect('clicked',$click);
		return $btn;
		}
	
	function jsonPrettyPrint( $json )
		{
			$result = '';
			$level = 0;
			$in_quotes = false;
			$in_escape = false;
			$ends_line_level = NULL;
			$json_length = strlen( $json );
		
			for( $i = 0; $i < $json_length; $i++ ) {
				$char = $json[$i];
				$new_line_level = NULL;
				$post = "";
				if( $ends_line_level !== NULL ) {
					$new_line_level = $ends_line_level;
					$ends_line_level = NULL;
				}
				if ( $in_escape ) {
					$in_escape = false;
				} else if( $char === '"' ) {
					$in_quotes = !$in_quotes;
				} else if( ! $in_quotes ) {
					switch( $char ) {
						case '}': case ']':
							$level--;
							$ends_line_level = NULL;
							$new_line_level = $level;
							break;
		
						case '{': case '[':
							$level++;
						case ',':
							$ends_line_level = $level;
							break;
		
						case ':':
							$post = " ";
							break;
		
						case " ": case "\t": case "\n": case "\r":
							$char = "";
							$ends_line_level = $new_line_level;
							$new_line_level = NULL;
							break;
					}
				} else if ( $char === '\\' ) {
					$in_escape = true;
				}
				if( $new_line_level !== NULL ) {
					$result .= "\n".str_repeat( "\t", $new_line_level );
				}
				$result .= $char.$post;
			}
		
			return $result;
		}
		