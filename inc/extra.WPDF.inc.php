<?php
require_once('WPDF/fpdf.php');

global $_WPDF_default_tagList;
$_WPDF_default_tagList = array(
					'ITEMS','PARAMS','HEADER','PAGE','BORDER','TEXT',
					'COMMENT','FRONTPAGE','ITEM','IMG','LABEL','INFO',
					'BODY','LIST','MOZ','FOOTER','ROW','COL'
					);

class WPDF extends FPDF
	{
	//PARSING
	var $tagList;

	//CONFIG
	var $defh = 3;
	var $txth;
	var $footh;
	var $margins = array('top'=>10,'left'=>5,'right'=>5,'bottom'=>25);
	var $htmlTxtSizes = array('H1'=>12,'H2'=>10,'H3'=>8,'H4'=>7,'H5'=>6,'H6'=>5,'default'=>5);
	var $htmlTxtStyles = array('H1'=>'b','H2'=>'b','H3'=>'b','H4'=>'b','H5'=>'bi','H6'=>'i','default'=>'');
	var $htmlTxtAlign = array('H1'=>'C','H2'=>'C','H3'=>'L','H4'=>'L','H5'=>'L','H6'=>'L','default'=>'L');
	var $padding = 1.5;
	
	var $curFontStyle;
	var $curRefFontStyle;

	//PARAM
	var $xml;
	var $titre;
	var $textPref;
	var $pagePref;
	var $headPref;
	var $footPref;
	var $itemPref;
	var $ExtpicPath;

	//WORKING
	var $B;
	var $I;
	var $U;
	var $colorT;
	var $colorB;
	var $colorI;
	var $colorU;
	var $HREF;

	var $iw = 0;
	var $ih = 0;

	function WPDF($data, $unit='', $format='')
		{
		global $_emptyXmlTree, $_WPDF_default_tagList;
//$data->prettyPrint();return
		
		if  (isNull($this->tagList))
			$this->tagList = $_WPDF_default_tagList;
			
		if (is_object($data))
			{
			$tree =& $data;
			if ($data->tree) $tree =& $data->tree;

			if($tree->nodeTags and count(array_diff($this->tagList,$tree->nodeTags))==0 ) 
				{
				// tous les tags nécessaires ont été gérés dans le parse d'origine
				$this->xml =& $data; // could be either an xmlTree or an xmlNode
				}
			else 
				{
				// il va falloir re-parser l'xml avec la bonne liste de tags
				$_data = $data->toXml();
				}
//$_data = $data->toXml();
//$this->xml = null;
			}
		else
			{
			$_data=$data;
			}

		if (!$this->xml)
			{
			$this->xml = new xmlTree($_data, $this->tagList);
			}

//$node = $tree =& $this->xml;
//if ($this->xml->tree) $tree =& $this->xml->tree;
//if ($this->xml->node) $node =& $this->xml->node;
//$tree->prettyPrint($node);return;
				
		//logdbg('XML',$this->xml->toXml());
		$this->textPref = nvl($this->xml->getPathNodes('/PARAMS/TEXT',0),$_emptyXmlTree);
		$this->pagePref = nvl($this->xml->getPathNodes('/PARAMS/PAGE',0),$_emptyXmlTree);
		$this->headPref = nvl($this->xml->getPathNodes('/PARAMS/HEADER',0),$_emptyXmlTree);
		$this->itemPref = nvl($this->xml->getPathNodes('/PARAMS/ITEMS',0),$_emptyXmlTree);
		$this->ExtpicPath = nvl($this->itemPref->getAttr('EXTPIC'),dirname(__FILE__).'/WPDF/extpics');
		$this->footPref = nvl(
			$this->xml->getPathNodes('/PARAMS/FOOTER',0),
			new xmlTree('<FOOTER><TEXT SIZE="2" /><ROW><COL CELL="2">Page {p}/{nbp}</COL></ROW></FOOTER>',
				Array('FOOTER','TEXT','ROW','COL'))
			);
		
		//Appel au constructeur parent
		$this->FPDF(
			$this->orientFlag($this->pagePref->getAttr('ORIENT')),
			nvl($unit,nvl($this->pagePref->getAttr('UNIT'),'mm')),
			nvl($format,nvl($this->pagePref->getAttr('FORMAT'),'A4'))
			);

		//Initialisation
		$this->B=0;
		$this->I=0;
		$this->U=0;
		$this->HREF='';

		$this->titre=nvl($this->xml->getAttr('NAME'),'Selection');
		
		$this->ComputeFooterHeight();
		$this->padding = doubleval(nvl($this->pagePref->getAttr('PADDING'), $this->padding));
		$this->margins['top'] = nvl($this->pagePref->getAttr('MARGINTOP'),$this->margins['top']);
		$this->margins['bottom'] = nvl($this->pagePref->getAttr('MARGINBOTTOM'),$this->margins['bottom'])+$this->footh;
		$this->margins['left'] = nvl($this->pagePref->getAttr('MARGINLEFT'),$this->margins['left']);
		$this->margins['right'] = nvl($this->pagePref->getAttr('MARGINRIGHT'),$this->margins['right']);
		
		$this->setSubject($this->titre);
		$this->setCreator("Web Photo Printer - WestValley - 2006-2010");
		
		$this->SetLeftMargin($this->margins['left'] ); // + $this->iw + $this->padding
		$this->SetRightMargin($this->margins['right']);
		$this->SetTopMargin($this->margins['top']);
		$this->SetAutoPageBreak(true, $this->margins['bottom']);
		
		$this->AliasNbPages('{nbp}');
		
		$frontpage = $this->xml->getPathNodes('/FRONTPAGE',0);
		if (!isNull($frontpage))
			{
			$this->AddPage();
			$txtstyle = nvl($this->xml->getPathNodes('/PARAMS/FRONTPAGE',0),nvl($this->headPref->getPathNodes('/TEXT',0),$_emptyXmlTree));
		    $this->SetFontStyle($txtstyle);
			$this->WriteAlignedHTML('<BR/><HR/><BR/>'.$frontpage->getText().'<BR/><HR/>', 'L', nvl($txtstyle->getAttr('SIZE'),5));//
			}
		
		}
		
	/* static */ 
	function factory($data, $unit='', $format='')
		{
		if (is_object($data)) 
			{
//$data->tree->prettyPrint($data->tree->root);return;
//echo"<pre>";print_r($data->tree);echo"</pre>";return;

			$xmlRawData = null;
			$xmlData = $data; // could be either an xmlTree or an xmlNode
			}
		else
			{
			global $_WPDF_default_tagList;
			
			$xmlRawData = $data; 
			$xmlData = new xmlTree($data,$_WPDF_default_tagList);
			}
		
		$pdfFormat = $xmlData->getAttr('FORMAT');
		switch(strtoupper($pdfFormat))
			{
		case 'WIDE':
			$pdf=new PDFWide($xmlData,$unit,$format);
			break;
		case 'LIST':
			$pdf=new PDFList($xmlData,$unit,$format);
			break;
		case 'MOZ':
			$pdf=new PDFMoz($xmlData,$unit,$format);
			break;
		default:
			$pdfMod = dirname(__FILE__).'/../../appl/business/mod.pdf.'.$pdfFormat.'.inc.php';
			if (file_exists($pdfMod))
				{
				include $pdfMod;
				$pdfClass = 'PDF'.ucfirst($pdfFormat);
				$pdf=new $pdfClass($xmlData, $xmlRawData,$unit,$format);
				}
			else
				{
				$pdf=new FPDF();
					$pdf->AddPage();
					$pdf->setFont('Arial','b',11);
					$pdf->SetTextColor(0);
					$pdf->Write(10,'Wrong pdf type ('.$pdfFormat.')');
					$pdf->Ln(10);
				}
			}
			
		return $pdf;
		}

	function orientFlag($val)
		{
		$orient = strtolower(nvl($val,'v'));
		$orient = str_replace('vertical','p',$orient);
		$orient = str_replace('v','p',$orient);
		$orient = str_replace('paysage','l',$orient);
		$orient = str_replace('horizontal','l',$orient);
		$orient = str_replace('h','l',$orient);
		return $orient;
		}

	function availWidth()
		{
		return $this->w-$this->margins['left'] -$this->margins['right'] ;
		}
		
	function getPicPath($img, $fallback=null)
		{
		$width = false;
		
		if (!isNull($img))
			{
			$imgext = fileext($img);
			$imgstatus = 'SOURCE';
			if (!isIn($imgext,'jpg','tiff')) // try the thumbnail
				{
				$imgstatus = 'THUMB';
				$img = substr($img,0,-strlen($imgext)-1).'s.jpg';
				}
			
			if (file_exists($img))
				list($width, $height, $type, $attr) = getimagesize($img);

			if($width===false)
				{
				$imgstatus = 'FALLBACK';
				$img = $fallback;
				
				if (file_exists($img))
					list($width, $height, $type, $attr) = getimagesize($img);
				}

			if($width===false)
				{
				$imgstatus = 'EXT';
				$img = $this->ExtpicPath.'/'.$imgext.'.jpg';
				list($width, $height, $type, $attr) = getimagesize($img);
				}
			}
		else
			{
			$imgstatus = 'EMPTY';
			$img = $this->headPref->getAttr('LOGO');
			list($width, $height, $type, $attr) = getimagesize($img);
			}
			
		if($width===false)
			{
			$imgstatus = 'LOGO';
			$img = $this->headPref->getAttr('LOGO');
			list($width, $height, $type, $attr) = getimagesize($img);
			}
		
		if($width===false)
			{
			$imgstatus = 'UNKNOWN';
			$img = $this->ExtpicPath.'/_.jpg';
			list($width, $height, $type, $attr) = getimagesize($img);
			}

		if($width===false)
			{
			$img = '';
			$imgstatus = 'ERROR';
			$height=false;
			}
			
		//echo "PIC([$img],[$width],[$height],[$imgstatus])\n";
		return array($img, $width, $height, $imgstatus);
		}
		
	function WriteHTML($html)
		{
		//Parseur HTML
		$html=str_replace("\n",' ',$html);
		$a=preg_split('/<(.*)(?:\/|)>/U',$html,-1,PREG_SPLIT_DELIM_CAPTURE);
		foreach($a as $i=>$e)
			{
			if($i%2==0)
				{
				//Texte
				if($this->HREF)
					$this->PutLink($this->HREF,$e);
				else
					$this->Write($this->FontSize,$e);
				}
			else
				{
				//Balise
				if($e{0}=='/')
					$this->CloseTag(strtoupper(substr($e,1)));
				else
					{
					//Extraction des attributs
					$a2=explode(' ',$e);
					$tag=strtoupper(array_shift($a2));
					$attr=array();
					foreach($a2 as $v)
						if(ereg('^([^=]*)=["\']?([^"\']*)["\']?$',$v,$a3))
							$attr[strtoupper($a3[1])]=$a3[2];
					$this->OpenTag($tag,$attr);
					}
				}
			}
		}

	function WriteAlignedHTML($html, $align='L', $refSize=4, $refStyle='')
		{
		//Parseur HTML
		$html=str_replace("\n",' ',$html);
		$a=preg_split('/<(.*)(?:\/|)>/U',$html,-1,PREG_SPLIT_DELIM_CAPTURE);
		$inSize = $this->FontSize;
		
		//d'abord, calculer la largeur de chaque ligne
		$lines = array();
		$style = array();
		$ln = 1;
		$lines[$ln]=0;
		$style[$ln]=array(
			'SIZE'=>$refSize,
			'STYLE'=>$refStyle, 
			'ALIGN'=>$align
			);
		
		foreach($a as $i=>$e)
			{
			if($i%2==0)
				{
				$this->FontSize = $style[$ln]['SIZE'];
				$this->SetFont('',strtoupper($style[$ln]['STYLE']),$this->FontSize*$this->k);
				//Texte
				if($this->HREF)
					{
					$this->SetStyle('U',true);
					$lines[$ln] += $this->GetStringWidth($e);
					$this->SetStyle('U',false);
					}
				else
					{
					$lines[$ln] += $this->GetStringWidth($e);
					}
				}
			else
				{
				//Balise
				if($e{0}=='/')
					if (isIn(strtoupper($e),'/H1','/H2','/H3','/H4','/H5','/H6'))
						{
						$ln++;
						$lines[$ln]=0;
						$style[$ln]=array(
							'SIZE'=>$refSize,
							'STYLE'=>$refStyle, 
							'ALIGN'=>$align
							);
						}
					else
						$this->CloseTag(strtoupper(substr($e,1)));
				else
					{
					//Extraction des attributs
					$a2=explode(' ',$e);
					$tag=strtoupper(array_shift($a2));
					
					if ($tag=='BR' or $tag=='HR')
						{
						$ln++;
						$lines[$ln]=0;
						$style[$ln]=$style[$ln-1];
						}
					elseif (isIn($tag,'H1','H2','H3','H4','H5','H6'))
						{
						//if ($lines[$ln]>0)
							{
							$ln++;
							$lines[$ln]=0;
							}

						$style[$ln]=array(
							'SIZE'=>$this->htmlTxtSizes[$tag]*$refSize/$this->htmlTxtSizes['default'],
							'STYLE'=>$this->htmlTxtStyles[$tag], 
							'ALIGN'=>$this->htmlTxtAlign[$tag]
							);
//$this->Write(16,"[SIZE:".$this->htmlTxtSizes[$tag]."/".$refSize."/".$this->htmlTxtSizes['default']."=>".$style[$ln]['SIZE']."]");
						}
					else
						{
						$attr=array();
						foreach($a2 as $v)
							if(ereg('^([^=]*)=["\']?([^"\']*)["\']?$',$v,$a3))
								$attr[strtoupper($a3[1])]=$a3[2];
						$this->OpenTag($tag,$attr);
						}
					}
				}
			}

		$ln = 1;
		
		$w = $this->w-$this->lMargin-$this->rMargin;
		
		$this->FontSize = $style[$ln]['SIZE'];
		$align = $style[$ln]['ALIGN'];
		$this->SetFont('',strtoupper($style[$ln]['STYLE']),$this->FontSize*$this->k);
		
		if ($align=='R') $d = $w - $lines[$ln]-(7/$this->k); //écarter à 7Pt du bord pour éviter un retour auto imprévu
		elseif ($align=='C') $d = ($w - $lines[$ln])/2;
		else $d = 0;//$this->cMargin;
		$this->SetX($this->lMargin+$d);
		$interligne = 1;
				
		foreach($a as $i=>$e)
			{
			if($i%2==0)
				{
				//Texte
				if($this->HREF)
					$this->PutLink($this->HREF,$e);
				else
					$this->Write($this->FontSize,$e);
				}
			else
				{
				//Balise
				if($e{0}=='/')
					if (isIn(strtoupper($e),'/H1','/H2','/H3','/H4','/H5','/H6'))
						{
						$ln++;
						$this->OpenTag('BR',array('LINESIZE'=>$interligne));
						$this->FontSize = $style[$ln]['SIZE'];
						$align = $style[$ln]['ALIGN'];
						//$this->SetTextColor($this->colorT);
						$this->SetFont('',strtoupper($style[$ln]['STYLE']),$this->FontSize*$this->k);
						
						if ($align=='R') $d = $w - $lines[$ln]-(7/$this->k); //écarter à 7Pt du bord pour éviter un retour auto imprévu
						elseif ($align=='C') $d = ($w - $lines[$ln])/2;
						else $d = 0;//$this->cMargin;
						$this->SetX($this->lMargin+$d);
						}
					else
						$this->CloseTag(strtoupper(substr($e,1)));
				else
					{
					//Extraction des attributs
					$a2=explode(' ',$e);
					$tag=strtoupper(array_shift($a2));
					$attr=array();
					foreach($a2 as $v)
						if(ereg('^([^=]*)=["\']?([^"\']*)["\']?$',$v,$a3))
							$attr[strtoupper($a3[1])]=$a3[2];
					$this->OpenTag($tag,$attr);
					if (isIn($tag,'BR','HR','H1','H2','H3','H4','H5','H6'))
						{
						if (isIn($tag,'H1','H2','H3','H4','H5','H6'))
							$interligne = array_get($attr,'LINESIZE',1);
						if (isIn($tag,'BR','HR'))
							$ln++;
						else
							{
							if ($lines[$ln]>0)
								{
								$this->OpenTag('BR',$attr);
								}
							$ln++;
							}
						$this->FontSize = $style[$ln]['SIZE'];
						$align = $style[$ln]['ALIGN'];
						//$this->SetTextColor($this->colorT);
						$this->SetFont('',strtoupper($style[$ln]['STYLE']),$this->FontSize*$this->k);
						
						if ($align=='R') $d = $w - $lines[$ln]-(7/$this->k); //écarter à 7Pt du bord pour éviter un retour auto imprévu
						elseif ($align=='C') $d = ($w - $lines[$ln])/2;
						else $d = 0;//$this->cMargin;
						$this->SetX($this->lMargin+$d);
						}
					}
				}
			}
		
		$this->FontSize = $inSize;
		}

	function OpenTag($tag,$attr)
	{
		//Balise ouvrante
		if($tag=='B' or $tag=='I' or $tag=='U')
			$this->SetStyle($tag,true);
		elseif($tag=='A')
			$this->HREF=$attr['HREF'];
		elseif($tag=='BR')
			$this->Ln($this->FontSize*array_get($attr,'LINESIZE',1));
		elseif($tag=='HR')
			{
			if ($this->FontSize==0) $this->FontSize=4;
			//$this->Ln($this->FontSize*1.5);
			$this->Ln($this->FontSize);
			$cy = $this->getY();
			
			$border = $this->itemPref->getPathNodes('/BORDER',0);
			if ($border)
				{
				$this->SetLineWidth(nvl($border->getAttr('SIZE'), 0.25));
				$color = hexcolor2intarray(nvl($border->getAttr('COLOR'), '#000000'));
				$this->SetDrawColor(($color[0]+255)/2,($color[1]+255)/2,($color[2]+255)/2);				
				$this->line($this->lMargin,$cy,$this->w-$this->rMargin,$cy);
				}
			$this->Ln($this->FontSize/2.);
			}
			
	}

	function CloseTag($tag)
	{
		//Balise fermante
		if($tag=='B' or $tag=='I' or $tag=='U')
			$this->SetStyle($tag,false);
		if($tag=='A')
			$this->HREF='';
	}

	function SetStyle($tag,$enable)
	{
		//Modifie le style et sélectionne la police correspondante
		$this->$tag+=($enable ? 1 : -1);
		$style='';
		foreach(array('B','I','U') as $s)
			if($this->$s>0)
				$style.=$s;
		$this->SetFont('',$style);
		
		$fld = "color".$tag;
		if($this->$tag>0)
			$this->SetTextColor($this->$fld);
		else
			{
			$this->SetTextColor($this->colorT);
			foreach(array('B','I','U') as $s)
				if($this->$s>0)
					{
					$fld = "color".$s;
					$this->SetTextColor($this->$fld);
					}
			}
	}

	//function PutLink($h, $URL,$txt)
	function PutLink($URL,$txt)
		{
		//Place un hyperlien
		$this->SetTextColor(0,0,255);
		$this->SetStyle('U',true);
		$this->Write($this->FontSize,$txt,$URL);
		$this->SetStyle('U',false);
		$this->SetTextColor(0);
		}

	function SetFontStyle($style, $refStyle = null)
		{
		global $_emptyXmlTree;
		$this->curFontStyle = $style;
		$this->curRefFontStyle = $refStyle;
		
		$s = $style;
		if (isNull($s)) $s = $_emptyXmlTree;
		
		$rs = $refStyle;
		if (isNull($rs)) $rs = $this->textPref;
		if (isNull($rs)) $rs = $_emptyXmlTree;

		$this->colorT = nvl(nvl($s->getAttr('COLOR'),$rs->getAttr('COLOR')),'#000000');
		$this->colorB = nvl(nvl(nvl($s->getAttr('COLORB'),$s->getAttr('COLOR')),$rs->getAttr('COLORB')),$this->colorT);
		$this->colorU = nvl(nvl(nvl($s->getAttr('COLORU'),$s->getAttr('COLOR')),$rs->getAttr('COLORU')),$this->colorT);
		$this->colorI = nvl(nvl(nvl($s->getAttr('COLORI'),$s->getAttr('COLOR')),$rs->getAttr('COLORI')),$this->colorT);
		
		$font = nvl(nvl($s->getAttr('FONT'),$rs->getAttr('FONT')),'Arial');
		
		$this->txth = doubleval(nvl(nvl($s->getAttr('SIZE'),$rs->getAttr('SIZE')),$this->defh));
		$size = $this->txth*$this->k;
		$this->txth *= 1.2;
		
		$styles = nvl($s->getAttr('STYLE'),$rs->getAttr('STYLE'));
		
		$this->SetTextColor($this->colorT);
	    $this->SetFont($font,strtoupper($styles),$size);
		}
	
	//En-tête
	function Header()
		{
		global $_emptyXmlTree;
		$cfs = $this->curFontStyle;
		$crfs = $this->curRefFontStyle;
		
		$logo = $this->headPref->getAttr('LOGO');
		$logoside = nvl($this->headPref->getAttr('LOGOSIDE'),'LEFT'); // G/D/L/R/LEFT/RIGHT
		
		$lw = 0;
		$lh = 0;
		$lo = 0;
		if ($logo and file_exists($logo))
			{
			list($width, $height, $type, $attr) = getimagesize($logo);
			$lw = $this->headPref->getAttr('SIZE');
			$lh = $lw * $height / $width;
			$lo = $lw;// + $this->padding;
			}
		else
			$logo='';
		
		$y1 = $this->getY();
		
	    //Titre
		$label = nvl($this->headPref->getPathNodes('/LABEL',0),$_emptyXmlTree);
	    $this->SetFontStyle($label);
		
		if (isIn($logoside,'G','L','LEFT'))
			{
			$this->SetLeftMargin($this->margins['left']+$lo + $this->padding);
			$this->SetRightMargin($this->margins['right']);
			$this->setX($this->margins['left']+$lo);
		    $this->Cell(0,$this->FontSize+$this->padding,$this->titre,0,2,strtoupper(nvl($label->getAttr('ALIGN'),'C')));
			}
		else
			{
			$this->SetLeftMargin($this->margins['left']);
			$this->SetRightMargin($this->margins['right']+$lo + $this->padding);
			$this->setX($this->margins['left']);
		    $this->Cell(0,$this->FontSize+$this->padding,$this->titre,0,2,strtoupper(nvl($label->getAttr('ALIGN'),'C')));
			}

	    //Commentaire
		$comment = $this->xml->getVal('COMMENT');
		if ($comment)
			{
			if (strpos($comment,'{')!==FALSE)
				$comment = $this->applyFields($comment);
			$txtstyle = $this->headPref->getPathNodes('/TEXT',0);
		    $this->SetFontStyle($txtstyle);
		    $this->WriteAlignedHTML($comment,strtoupper(nvl($txtstyle->getAttr('ALIGN'),'L')), $txtstyle->getAttr('SIZE'), $txtstyle->getAttr('STYLE'));
			$this->Ln($this->FontSize);
			}
			
		$this->SetLeftMargin($this->margins['left']);
		$this->SetRightMargin($this->margins['right']);
		
		$y2 = $this->getY();
		
		$dh = ($y2-$y1);
		
		if ($logo)
			{
			if ($lh>$dh)
				{
				$lh=$dh;
				$lw=$lh*$width/$height;
				}
			if (isIn($logoside,'G','L','LEFT'))
				$this->Image($logo, $this->margins['left']+($lo-$lw)/2, $y1 /* + ($y2-$y1-$lh)/2 */, $lw, $lh);
			else
				$this->Image($logo, $this->w-$this->margins['right']-$lo+($lo-$lw)/2, $y1 /* + ($y2-$y1-$lh)/2 */, $lw, $lh);
			
			$this->SetLineWidth(0.1);
			$this->SetDrawColor('#000000');
			}
		
		$border = $this->headPref->getPathNodes('/BORDER',0);
		if ($border)
			{
			$this->SetLineWidth(nvl($border->getAttr('SIZE'), 0.25));
			$this->SetDrawColor(nvl($border->getAttr('COLOR'), '#000000'));
			$this->Rect($this->margins['left'],$y1,$this->w-$this->margins['left']-$this->margins['right'], $y2-$y1);
			}
		
	    //Saut de ligne
	    $this->Ln($this->padding);
	    $this->SetFontStyle($cfs, $crfs);
		}

	function applyFields($txt)
		{
		// {nbp} est géré à la fin par FPDF
		$flds = array ();
		$flds['p']=$this->PageNo();
		$flds['t']=$this->titre;
		$flds['titre']=$this->titre;
		$flds['d']=date('d/m');
		$flds['h']=date('H:i');
		$flds['date']=date('d/m/Y');
		$flds['heure']=date('H:i:s');
		
		$ret = $txt;
		foreach($flds as $key=>$val)
			$ret = str_replace('{'.$key.'}',$val,$ret);

		return $ret;
		}
		
	//Pied de page
	function ComputeFooterHeight()
		{
		$footer = $this->footPref->getPathNodes('/TEXT',0);
		$this->SetFontStyle($footer);
		
		$nbrows = 0;
		$rowsNode = $this->footPref->getChildNodes('ROW');
		foreach($rowsNode as $row)
			{
			$rowH = nvl($row->getAttr('H'),1);
			$nbrows += $rowH;
			}
			
		$this->footh = $nbrows * $this->txth;
		}
		
	function Footer()
		{
		$cfs = $this->curFontStyle;
		$crfs = $this->curRefFontStyle;
		
		$footer = $this->footPref->getPathNodes('/TEXT',0);
		$this->SetFontStyle($footer);

		$nbrows = 0;
		$rows = array();
		$rowsNode = $this->footPref->getChildNodes('ROW');
		foreach($rowsNode as $row)
			{
			$rowH = nvl($row->getAttr('H'),1);
			$rows[$nbrows+1] = array();
			$rows[$nbrows+1]['H'] = $rowH;
			$rows[$nbrows+1]['V1'] = '';
			$rows[$nbrows+1]['V2'] = '';
			$rows[$nbrows+1]['V3'] = '';
			
			$cols = $row->getChildNodes('COL');
			$curc = 1;
			foreach($cols as $col)
				{
				$colc = $col->getAttr('CELL');
				$colc = nvl($colc ,"$curc");
				$c1 = substr($colc,0,1);
				$curc = min(substr($colc,strlen($colc)-1,1)+1,4);
				$rows[$nbrows+1]['L'.$c1]=$curc-$c1;
				if (($c1+$curc)<5)
					$rows[$nbrows+1]['A'.$c1]='L';
				elseif (($c1+$curc)>5)
					$rows[$nbrows+1]['A'.$c1]='R';
				else
					$rows[$nbrows+1]['A'.$c1]='C';

				
				$val = $col->getText();
				if (strpos($val,'{')!==FALSE)
					$val = $this->applyFields($val);
					
				$rows[$nbrows+1]['V'.$c1]=$val;
				}
			$nbrows += $rowH;
			}

//logdbg('FOOT',print_r($rows,true));
			
		$cw = ($this->w - $this->margins['left'] - $this->margins['right'])/3;
		$curr = 1;
		foreach($rows as $row=>$rowdata)
			{
			for($c=1;$c<=3;$c++)
				if(!isNull($rowdata['V'.$c]))
					{
					$this->SetLeftMargin($this->margins['left']+($c-1)*$cw);
					$this->SetRightMargin($this->margins['right']+(4-($c+$rowdata['L'.$c]))*$cw);
					//$this->SetRightMargin($this->margins['right']+(3-($c))*$cw);
					$this->SetXY(
						$this->margins['left']+($c-1)*$cw,
						-($this->margins['bottom']-($curr-1)*$this->txth)
						);
				    $this->WriteAlignedHTML($rowdata['V'.$c],$rowdata['A'.$c], $footer->getAttr('SIZE'), $footer->getAttr('STYLE'));
					}
			$curr += $rowdata['H'];
			}
			
	    $this->SetFontStyle($cfs, $crfs);
		}
	}
	
require_once('WPDF/class.PDFList.inc.php');
require_once('WPDF/class.PDFMoz.inc.php');
require_once('WPDF/class.PDFWide.inc.php');

?>