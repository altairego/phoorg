<?php

class PDFWide extends WPDF
	{
	var $listPref;
	
	function PDFWide($data, $unit='mm', $format='A4')
		{
//print_r($data);
		//Appel au constructeur parent
		$this->WPDF($data,$unit,$format);

		$this->listPref = nvl($this->xml->getPathNodes('/PARAMS/LIST',0), $this->itemPref);
		$iw = nvl($this->listPref->getAttr('IMGWIDTH'),$this->itemPref->getAttr('IMGWIDTH'));

		if (isNull($iw))
			$this->iw=$this->availWidth();
		else
			if (substr($iw,-1)=='%')
				$this->iw=$this->availWidth()*substr($iw,0,-1)/100;
			else
				$this->iw=$iw;
		
//debug
//$this->iw=$this->availWidth();

		//$this->SetLeftMargin($this->margins['left'] + $this->iw + $this->padding);
		
		$nodes = $this->xml->getPathNodes('/ITEM');
		foreach($nodes as $item)
			$this->AddItem($item);
		}
		
	function AddItem($item)
		{
		$label = $item->getVal('LABEL');
		$info = $item->getVal('INFO');
		$body = $item->getVal('BODY');

		list($img, $width, $height, $imgstatus) = $this->getPicPath($item->getVal('IMG'));
		
		$this->AddPage(); // on commence toujours une fiche sur une nouvelle page

		$cy = $this->getY();
		$cp = $this->PageNo();
		if($width)
			{
			if ($imgstatus == 'SOURCE')
				$mw = $this->iw;
			else
				$mw = min($this->iw, $this->availWidth()/4);
			
			if (($width/$mw) > ($height/$mw))
				{
				$rw = $mw;
				$rh = $height * $rw / $width;
				}
			else
				{
				$rh = $mw;
				$rw = $width * $rh / $height;
				}
				
			$ox = ($this->availWidth() - $rw)/2;
			
			if (($cy + $this->padding + $rh) > ($this->h - ($this->margins['bottom']) ) ) 
				{
				$this->AddPage();
				$cy = $this->getY();
				$cp = $this->PageNo();
				}
			
			$this->Image($img, $this->margins['left']+$ox, $cy + $this->padding, $rw, $rh);
			
			$this->setY($cy + $this->padding + $rh);
			}
		else
			{
			$rw = $this->iw;
			$rh = 0;
			$ox = 0;
			}
		
		$this->Ln($this->padding);
	    //Commentaire
		if (!isNull($label))
			{
		    $this->SetFontStyle($this->itemPref->getPathNodes('/LABEL',0));
		    $this->WriteAlignedHTML($label,'L',$this->FontSize);
			$this->Ln($this->FontSize);
			}
			
		if (!isNull($info))
			{
		    $this->SetFontStyle($this->itemPref->getPathNodes('/INFO',0));
		    $this->WriteAlignedHTML($info,'L',$this->FontSize);
			$this->Ln($this->FontSize);
			}
			
		if (!isNull($body))
			{
		    $this->SetFontStyle($this->itemPref->getPathNodes('/BODY',0));
		    $this->WriteAlignedHTML($body,'L',$this->FontSize);
			$this->Ln($this->FontSize);
			}
		
		$this->Ln($this->padding);
			
		$cy = $this->getY();	
		
		/*		
		$border = $this->itemPref->getPathNodes('/BORDER',0);
		if ($border)
			{
			$this->SetLineWidth(nvl($border->getAttr('SIZE'), 0.25));
			$this->SetDrawColor(nvl($border->getAttr('COLOR'), '#000000'));
			$this->line($this->margins['left'],$cy,$this->w-$this->margins['right'],$cy);
			}
		*/
		}
	}

?>