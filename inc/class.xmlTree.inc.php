<?php
	include_once('class.xmlNode.inc.php');
	class xmlTree
		{
		var $file;

	    // for the parsing engine
	    var $parser;
		var $root;
		var $curNode;
		var $fp;
		
		var $encoding;
		
		var $nodeTags;
		
		// ## Struct management ##

		function isValid()
			{
			return $this->root != null;
			}
		
		function &rootNode()
			{
			return $this->root;
			}
		
		function &find($tag, $attr = '', $attrval = '')
			{
			return $this->findNode($this->root, $tag, $attr, $attrval);
			}
		
		function destroy($_n='--ROOT--')
			{
			if (!$_n) return;

			if (is_string($_n))
				{
				if($_n=='--ROOT--') 
					$node =& $this->root;
				else 
					$node =& $this->getPathNodes($_n,0);
				}
			else
				$node = $_n;
				
			if (!is_object($node)) return;
			
			$_p =& $node->_parent;

			if ($node->childs)
				{
				foreach($node->childs as $child)
					if($child[0]=='node')
						{
						$this->destroy($child[1]);
						unset($child[1]);
						}
				unset($node->childs);
				}
			
			
			if($_n=='--ROOT--')
				{
			unset($this->root);
			unset($this->curNode);
			unset($this->fp);
			unset($this->nodeTags);
				return null;
				}
			else
				{
				foreach($_p->childs as $i=>$child)
					if($child[0]=='node' and $child[1]===$node)
						array_splice($_p->childs,$i,1);
				
				unset($node);
				
				return $_p;
				}
			}
			
		function __destruct()
			{
			if(isset($this->root))
				$this->destroy();
			}
			
		function &findNode($node, $tag, $attr = '', $attrval = '')
			{
			if (isNull($tag) or ($tag==$node->name)) 
				if (isNull($attr) or (array_key_exists($attr, $node->attrs) and (isNull($attrval) or $attrval== html_entity_decode($node->attrs[$attr])) ) )
					return $node;

			if ($node->childs)
				foreach($node->childs as $child)
					if($child[0]=='node')
						{
						$ret =& $this->findNode($child[1], $tag, $attr, $attrval);
						if ($ret !== false) return $ret;
						}
			
			$status = false;
			return $status;
			}
			
		function &createPathNode($path)
			{
			$arbo=explode('/',$path);
			if(isNull($arbo[0]))
				array_shift($arbo);
			
			$curNode =& $this->root;

			foreach($arbo as $subpath)
				{
				preg_match("%^([^\[\]]+)(?:\[([^\[\]=]+)=([^\[\]]*)\]|)$%",$subpath,$chunks);
				$curtag = array_get($chunks,1);
				$curattr = array_get($chunks,2);
				$curval = array_get($chunks,3);
				
				$subnodes = $curNode->getChildNodes($curtag,$curattr,$curval);
				if(count($subnodes)==0)
					{
					$attribs = Array();
					if (!isNull($curattr))
						$attribs[$curattr] = htmlentities($curval);
					$curNode =& $curNode->addChild(new xmlNode($curtag, $attribs));
					}
				else
					$curNode =& $subnodes[0];
				}
				
			return $curNode;
			}
			
		function prettyPrint($node = null, $capture=false)
			{
			if ($capture) ob_start();
			echo '<table border="0" cellspacing="1" cellpadding="1" style="font-size: 8px;font-family: sans-serif;background-color: #ffffff; border: 1px solid black; color: black;">'."\n";

			$this->prettyPrintNode(nvl($node,$this->root));
			
			echo '</table>'."\n";
			if ($capture) return ob_get_clean();
			}

		function prettyPrintNode($node, $level = 0, $line=0)
			{
			$curLine = $line;
			if ($level==0)
				$this->prettyPrintNodeLine('root',
					'<b>'.$node->name.'</b> '.$node->getAttrStr(), 
					$level++, $curLine++);
					
					
			foreach($node->childs as $child)
					{
					list($type,$data) = $child;
					switch ($type)
						{
					case  'node':
					case 'tag':
						$info = '<b>'.$data->name.'</b> <i>'.$data->getAttrStr().'</i>';
						break;
					case  'text':
					case "?print":
					case "?str":
						$info = $data;
						break;
					case "?php":
					case "?echo":
						$info = $data.";";
						break;
						}

					$info = trim($info);
					if (!isNull($info))
						$this->prettyPrintNodeLine($type,$info, $level, $curLine++);
					if($child[0]=='node')
						$curLine = $this->prettyPrintNode($data, $level+1, $curLine);
					}
					
			return $curLine;
			}

		function prettyPrintNodeLine($type,$lib, $level, $line)
			{
			$color = Array(
				'root'=>'#20C040', 
				'node'=>'#A08040', 
				'text'=>'#4040A0', 
				'?str'=>'#4060A0', 
				'tag'=>'#A0A040', 
				'misc'=>'#A04040', 
				'?print'=>'#4060A0', 
				'?echo'=>'#4060A0', 
				'?php'=>'#4060A0'
				);
			
			echo '<tr'.(($line%2==1)?' style="background-color: #D0D0D0"':'').'>';
				for($i=0;$i<$level;$i++) echo '<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>';
				
				echo '<td style="font-weight: bold; text-align: center; width: 40px; background-color: '
					.nvl(array_get($color,$type),'#404040')
					.'; color: #ffffff;">&nbsp;'.$type.'&nbsp;</td>';
				
				echo '<td colspan="30" nowrap="nowrap">'.$lib.'</td>';
				
			echo '</tr>'."\n";
			}

		// ## xmlNode profile ##
		//acting as it's own root
			// ### /!\ : should evolve as xmlNode class ###
		function getChildNodes($tag = '', $attr = '', $attrval = '')
			{return $this->root->getChildNodes($tag, $attr, $attrval);}
		function &getText($subtext=false)
			{return $this->root->getText($subtext);}
		function &addChild(&$node, $childType = null)
			{return $this->root->addChild($node, $childType);}
		function hasChildNodes($tag = '', $attr = '', $attrval = '')
			{return $this->root->hasChildNodes($tag, $attr, $attrval);}
		function getAttrs()
			{return $this->root->getAttrs();}
		function &getAttrStr()
			{return $this->root->getAttrStr();}
		function &getAttr($attr)
			{return $this->root->getAttr($attr);}
		function setAttr($attr, $val)
			{return $this->root->setAttr($attr, $val);}
		function &getVal($attr)
			{return $this->root->getVal($attr);}
		function &getJsonVal($attr, $def = NULL)
			{return $this->root->getJsonVal($attr, $def);}
		function findNodes($node, $tag, $attr = '', $attrval = '')
			{return $this->root->findNodes($tag, $attr, $attrval);}			
		function &getPathNodes($path,$idx=null)
			{return $this->root->getPathNodes($path,$idx);}
		function extract($nodeList, $idx, $attr='')
			{return $this->root->extract($nodeList, $idx, $attr);}
		function toXml($forceUTF=false) //return xml text
			{return ($forceUTF?'<?xml version="1.0" encoding="utf-8" ?>':'<?xml version="1.0" encoding="iso-8859-1" ?>').$this->root->toXml($forceUTF);}
		
		public function __get($name)
			{
			if (isset($this->$name)) return $this->$name;
			if ($this->root)
				return $this->root->$name;
			return "";
			}
		
		// ## CONSTRUCTOR / XML PARSING ##

		/*
	        if (false) if (!xml_parse($this->parser,
                '<?xml version="1.0" encoding="iso-8859-1" ?><!DOCTYPE TEMPLATE ['
                    .'<!ENTITY amp "&#38;">'
                    .'<!ENTITY copy "&amp;copy;">'
                    .'<!ENTITY nbsp "&amp;nbsp;">'
                .']>'))
			{
	            logdbg(nvl($this->file,"XML Buffer"),sprintf("%s in standard header",
	                       xml_error_string(xml_get_error_code($this->parser)),
	                       xml_get_current_line_number($this->parser)));
						}
		*/
			
		function initParser($charset, $upperAttrs)
			{
//logdbg('initParser',$charset);
				
	        $this->parser = xml_parser_create($charset);
	        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, $upperAttrs);
			
	        xml_set_object($this->parser, $this);
	        xml_set_element_handler($this->parser, "startElement", "endElement");
	        xml_set_character_data_handler($this->parser, "characterData");
	        xml_set_processing_instruction_handler($this->parser, "PIHandler");
	        xml_set_default_handler($this->parser, "defaultHandler");
			xml_set_external_entity_ref_handler($this->parser, "externalEntityRefHandler");
			}
			
		function xmlTree($file, $nodeTags=array(), $upperAttrs=true)
			{
			$this->curNode = null;
			$this->root = null;
			if (isNull($file)) return;
			
			if(is_bool($nodeTags))
				{
				$upperAttrs=$nodeTags;
				$nodeTags=array();
				}
			$this->nodeTags = $nodeTags;
			
			$this->encoding = "iso-8859-1";
			
			if (strchr($file,'>')===false)
				{
				$this->file = $file;
				if (!($this->fp = @fopen($file, "r"))) 
					{
					die("could not open XML input : $file");
					}
					
				$data = fread($this->fp, 4096);
				$this->encoding = is_utf8($data)?'UTF-8':'iso-8859-1';
				
				if (strpos($data,'<?')!==0)
					{
					$this->initParser($this->encoding, $upperAttrs);
					
					if (!xml_parse($this->parser,
							'<?xml version="1.0" encoding="'.$this->encoding.'" ?>'
							))
						{
						logdbg(nvl($this->file,"XML Buffer"),sprintf("%s in standard header",
								   xml_error_string(xml_get_error_code($this->parser))
								   ));
						}
					}
				else
					{
					if (preg_match('/\<\?xml.* encoding\=\"([^\"]+)\".*\?\>/', $data, $_hdt))
						$this->encoding = $_hdt[1];
					$this->initParser($this->encoding, $upperAttrs);
					}
							
				while ($data)
					{
					//					if (!xml_parse($this->parser, str_replace("&","&amp;",$data), feof($this->fp))) 
					if (!xml_parse($this->parser, $data, feof($this->fp))) 
						{
						logdbg('XML error',sprintf("%s at line %d of file %s\n",
								xml_error_string(xml_get_error_code($this->parser)),
								xml_get_current_line_number($this->parser),
								$this->file
								));
						}
					$data = ""; //memory leak workaround
					$data = fread($this->fp, 4096);
					}
				@fclose($this->fp);
				//END
				}
			else
				{
				$this->file = '';
				$this->encoding = is_utf8($file)?'UTF-8':'iso-8859-1';
				if (strpos($file,'<?')!==0)
					{
					$this->initParser($this->encoding, $upperAttrs);
					if (!$_cod=xml_parse($this->parser,
							'<?xml version="1.0" encoding="'.$this->encoding.'" ?>'
							))
						{
						logdbg(nvl($file,"XML Buffer"),sprintf("%s in standard header",
								   "[$_cod]:".xml_error_string(xml_get_error_code($this->parser))
								   ));
						}
					}
				else
					{
					if (preg_match('/\<\?xml.* encoding\=\"([^\"]+)\".*\?\>/', $file, $_hdt))
						$this->encoding = $_hdt[1];
					$this->initParser($this->encoding, $upperAttrs);
					}
							
				if (!$_cod=xml_parse($this->parser, $file,
						//str_replace("&","&amp;",$file), 
						true))
					{
					//echo "<!-- \n".$file."\n -->";
					logdbg('XML error',sprintf("%s at line %d\n",
							"[$_cod]:".xml_error_string(xml_get_error_code($this->parser)),
							xml_get_current_line_number($this->parser)));
					logdbg('XML error source',"\n".$file);
					}
				}
			xml_parser_free($this->parser);
			unset($this->parser);
			}
			
		// start_element_handler ( resource parser, string name, array attribs )
		function startElement($parser, $name, $attrs)
			{
			$uname = strtoupper($name);
//echo "[$name(".implode(',',array_keys($attrs)).")]";
			foreach($attrs as $key => $val)
				{
				$val = str_replace("&lt;","<",str_replace("&gt;",">",str_replace("&quot;","\"",str_replace("&amp;","&",$val))));
				$attrs[$key] = htmlentities($val);
				}
			
			if (!isNull($this->curNode))
				{
				if (!isNull($this->nodeTags) and (isIn($uname, $this->nodeTags) or isIn(substr($uname,0,-6), $this->nodeTags)) and substr($uname,-6)=='-BEGIN')
					{
					$this->curNode =& $this->curNode->addChild(new xmlNode(substr($name,0,-6), $attrs));
					}
				elseif (!isNull($this->nodeTags) and (isIn($uname, $this->nodeTags) or isIn(substr($uname,0,-4), $this->nodeTags)) and substr($uname,-4)=='-END')
					{ // DO NOTHING
					}
				elseif (isNull($this->nodeTags) or isIn($uname, $this->nodeTags))
					{
					$this->curNode =& $this->curNode->addChild(new xmlNode($name, $attrs));
					}
				else
					{
					$this->curNode->addChild(new xmlNode($name, $attrs), 'tag');
					}
				}
			else
				{
				$this->root = new xmlNode($name, $attrs, $this);
				$this->curNode =& $this->root;
				}
			}

		// end_element_handler ( resource parser, string name )
		function endElement($parser, $name)
			{
			$uname = strtoupper($name);
			if (!isNull($this->curNode))
				if (!isNull($this->nodeTags) and (isIn($uname, $this->nodeTags, $this->root->name) or isIn(substr($uname,0,-6), $this->nodeTags)) and substr($uname,-6)=='-BEGIN')
					{ // DO NOTHING
					}
				elseif (!isNull($this->nodeTags) and (isIn($uname, $this->nodeTags, $this->root->name) or isIn(substr($uname,0,-4), $this->nodeTags)) and substr($uname,-4)=='-END')
					{
					$this->curNode = &$this->curNode->_parent;
					}
				elseif (isNull($this->nodeTags) or isIn($uname, $this->nodeTags, $this->root->name))
					{
					$this->curNode = &$this->curNode->_parent;
					}
				else
					{
					$this->curNode->addChild(new xmlNode('/'.$name, Array()), 'tag');
					}
			}

		// handler ( resource parser, string data )
		function characterData($parser, $data)
			{
			if (!isNull($this->curNode) and count(trim($data)))
				$this->curNode->addChild($data);
			}
			
		// handler ( resource parser, string target, string data )
		function PIHandler($parser, $target, $data)
			{
			if (!isNull($this->curNode))
				$this->curNode->addChild($data,'?'.$target);
			}

		function defaultHandler($parser, $data)
			{
// is this really usefull?
//			if (!isNull($this->curNode))
//				$this->curNode->addChild($data,'misc');
			}

		function externalEntityRefHandler(
				$parser, $openEntityNames, $base, $systemId,
				$publicId)
			{
			if ($systemId) 
				{
				$subTree = new xmlTree($systemId);
				$this->curNode->addChild($subTree->root);
				return true;
				}
			return false;
			}
	}

	?>