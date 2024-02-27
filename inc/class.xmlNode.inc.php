<?php
	class xmlNode
		{
		var $name;
		var $attrs;
		var $childs;
		var $_parent;
		var $meta;
		var $tree;
		
		function xmlNode($name, $attribs = Array(), $parent = null)
			{
			$this->name=$name;
			$this->attrs = $attribs;
			$this->childs = Array();
			
			$this->tree = null;
			$this->_parent = null;
					
			if ($parent != null)
				if (($parent instanceof xmlTree))
				//if (is_a($parent,'xmlTree'))
					{
					$this->tree =& $parent;
					}
				else
					{
					$this->tree =& $parent->tree;
					$this->_parent =& $parent;
					}
			$this->meta = Array();
			}
			
		function prettyPrint($node = null)
			{
			$this->tree->prettyPrint(nvl($node,$this));
			}
			
		function formatBranch(&$node,$nenc)
			{
			$node->tree =& $this->tree;
			if ($this->tree->encoding != $nenc)
				{
				foreach($node->attrs as $k=>$v)
					if($nenc=='UTF-8')
						$node->attrs[$k]=utf8_decode($v);
					else
						$node->attrs[$k]=utf8_encode($v);
				}
			foreach($node->childs as $i=>$child)
				{
				list($type,$data) = $child;
				switch ($type)
					{
				case 'node':
				case 'tag':
					$this->formatBranch($data,$nenc);
					break;
				case 'text':
				case "?print":
				case "?str":
				case "?php":
				case "?echo":
					if($nenc=='UTF-8')
						$node->childs[$i][1]=utf8_decode($data);
					else
						$node->childs[$i][1]=utf8_encode($data);
					break;
					}
				}
			}
			
		function &addChild(&$node, $childType = null)
			{
			if (!is_object($node))
				$this->childs[] = Array(nvl($childType,'text'),"$node"); //force string scalar
			else
				{
				if (method_exists($node,'rootNode'))
					{
					$enc = strtoupper($node->encoding);
					$n2 =& $node->rootNode();
					}
				elseif ($node->tree)
					{
					$enc = strtoupper($node->tree->encoding);
					$n2 =& $node;
					}
				else
					{
					$enc = strtoupper($this->tree->encoding);
					$n2 =& $node;
					}
//echo "\n[n>".get_class($node)."]";
//echo "\n[n2>".get_class($n2)."]";
				
				$this->formatBranch($n2, $enc);
				//$n2->tree =& $this->tree;
				$n2->_parent =& $this;
				$this->childs[] = Array(nvl($childType,'node'),$n2); //keep object assuming it's a xmlNode
				}
			
			return $this->childs[count($this->childs)-1][1]; //should be $node
			}
		
		function hasChildNodes($tag = '', $attr = '', $attrval = '')
			{
			foreach($this->childs as $child)
				if($child[0]=='node' and (isNull($tag) or ($tag==$child[1]->name)) )
					if (isNull($attr) or (array_key_exists($attr, $child[1]->attrs) and (isNull($attrval) or $attrval== html_entity_decode($child[1]->attrs[$attr])) ) )
						return true;
			return false;
			}
		
		function getChildNodes($tag = '', $attr = '', $attrval = '')
			{
			$ret = Array();
			if (!isset($this->childs)) return $ret;
			foreach($this->childs as $k=>$child)
				if($child[0]=='node' and (isNull($tag) or ($tag==$child[1]->name)) )
					if (isNull($attr) or (array_key_exists($attr, $child[1]->attrs) and (isNull($attrval) or $attrval== html_entity_decode($child[1]->attrs[$attr])) ) )
						$ret[] =& $this->childs[$k][1];
			return $ret;
			}
			
		function findNodes($tag, $attr = '', $attrval = '')
			{
			if (isNull($tag) or ($tag==$this->name))
				if (isNull($attr) or (array_key_exists($attr, $this->attrs) and (isNull($attrval) or $attrval== html_entity_decode($this->attrs[$attr])) ) )
					return array(& $this);

			$ret = array();
			
			if ($this->childs)
				foreach($this->childs as $child)
					if($child[0]=='node')
						$ret = array_merge($ret, $child[1]->findNodes($tag, $attr, $attrval));
			
			return $ret;
			}
			
		function &getPathNodes($path,$idx=null)
			{
			$arbo=explode('/',$path);
			$subpath = array_shift($arbo);
			
			$curnodes = array();
			if ($subpath=='')
				{
				$curnodes[] =& $this;
				}
			else
				{
//echo "[start:$subpath] ";
				preg_match("%^([^\[\]]+)(?:\[([^\[\]=]+)=([^\[\]]*)\]|)$%",$subpath,$chunks);
				$curtag = array_get($chunks,1);
				$curattr = array_get($chunks,2);
				$curval = array_get($chunks,3);
				
				$curnodes = $this->findNodes($curtag,$curattr,$curval);
				}
			
			foreach($arbo as $subpath)
				{
				preg_match("%^([^\[\]]+)(?:\[([^\[\]=]+)=([^\[\]]*)\]|)$%",$subpath,$chunks);
				$curtag = array_get($chunks,1);
				$curattr = array_get($chunks,2);
				$curval = array_get($chunks,3);
				
				$nextnodes = array();
				foreach($curnodes as $cn)
					$nextnodes = array_merge($nextnodes, $cn->getChildNodes($curtag,$curattr,$curval));
				if (count($nextnodes)==0) return $nextnodes;
				$curnodes = $nextnodes;
				}
				
			if (isNull($idx))
				return $curnodes;
			else
				return array_get($curnodes,$idx);
			}

		function &getText($subtext=false)
			{
			$ret = '';
			foreach($this->childs as $child)
				if($child[0]=='text')
					$ret .= $child[1];
				elseif(is_object($child[1]) and $child[0]!='node')
					$ret .= '<'.$child[1]->name.$child[1]->getAttrStr().'>';
				elseif ($subtext and $child[0]=='node')
					$ret .= $child[1]->getText(true);

			return $ret;
			}
			
		static function castUtf($str,$toUTF)
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
		function toXml($forceUTF=false)
			{
			$ret = '';
			foreach($this->childs as $child)
				if($child[0]=='text')
					if (trim($child[1])!='')
						$ret .= '<![CDATA['.$this->castUtf($child[1],$forceUTF).']]>';
					else
						$ret .= $child[1];
				elseif(is_object($child[1]) and $child[0]!='node') // simple tag
					$ret .= '<'.$this->castUtf($child[1]->name.$child[1]->getAttrStr(),$forceUTF).'>';
				elseif ($child[0]=='node') // managed tag
					$ret .= $child[1]->toXml($forceUTF);

			if (isNull($ret))
				$ret = '<'.$this->castUtf($this->name.$this->getAttrStr(),$forceUTF).' />';
			else
				$ret = '<'.$this->castUtf($this->name.$this->getAttrStr(),$forceUTF).'>'.$ret.'</'.$this->castUtf($this->name,$forceUTF).'>';
				
			return $ret;
			}
		
		function setText($text)
			{
			$done = false;
			foreach($this->childs as $k=>$child)
				if($child[0]=='text')
					{
					if ($done)
						$this->childs[$k][1] = '';
					else
						{
						$this->childs[$k][1] = $text;
						$done=true;
						}
					}
			if (!$done) $this->addChild($text);
			}
		
		function &getAttrStr()
			{
			$ret = '';
			foreach($this->attrs as $key=>$val)
				$ret .= ' '.$key.'="'.str_replace('"','&quot;',str_replace('&','&amp;',html_entity_decode($val))).'"'; //htmlentities(
				
			return $ret;
			}
			
		function getAttrs()
			{
			return $this->attrs;
			}
			
		function &getJsonVal($attr, $def = NULL)
			{
			$ret = $this->getVal($attr);
			
			if (isNull($ret))
				{
				if (is_string($def)) $ret = $def;
				else return $def;
				}
			
			$ret = preg_replace("/([a-zA-Z0-9_]+?):/" , "\"$1\":", $ret);
			$ret = preg_replace("/:'([^']*)'[ ]*([,\}])/" , ":\"$1\"$2", $ret);
			$ret = json_decode($ret);
			if (isNull($ret))
				{
				if (is_string($def)) $ret = json_decode($def);
				else $ret = $def;
				}
			
			return $ret;
			}
			
		function &getVal($attr) {
			$ret = '';
			if (array_key_exists($attr, $this->attrs))
				{
				$ret = html_entity_decode($this->attrs[$attr]);
				return $ret;
				}
				
			$nodes = $this->getChildNodes($attr);
			if (count($nodes)>0) {
				$ret = $nodes[0]->getText();
				return $ret;
			}
			
			$nodes = $this->getChildNodes('FIELD','REF',$attr);
			if (count($nodes)>0) {
				$ret = $nodes[0]->getText();
				return $ret;
			}
			
		}
			
		function &getAttr($attr)
			{
			$ret = html_entity_decode(array_get($this->attrs,$attr));
			return $ret;
			}
			
		public function __get($name)
			{
			if (isset($this->$name)) return $this->$name;
			return $this->getVal($name);
			}

		function extract($nodeList, $idx, $attr='')
			{
			if (count($nodeList)==0) return '';
			$node = array_get($nodeList, $idx);
			
			if (isNull($node)) return '';
			
			if (isNull($attr))
				return $node->getText();
			else
				return $node->getAttr($attr);
			}

		function setAttr($attr, $val)
			{
			$this->attrs[$attr]=htmlentities($val);
			}

		function setMeta($key, $val)
			{
			$this->meta[$key]=$val;
			}
		
		function getMeta($key)
			{
			return array_get($this->meta,$key);
			}
			
		function getBoolMeta($key)
			{
			return isTrue(array_get($this->meta,$key));
			}
		}

?>