<?php

class PDFList extends WPDF
	{
	var $listPref;
	
	function PDFList($data, $unit='mm', $format='A4')
		{
//print_r($data);
		//Appel au constructeur parent
		$this->WPDF($data,$unit,$format);

		$this->listPref = nvl($this->xml->getPathNodes('/PARAMS/LIST',0), $this->itemPref);
		$iw = nvl($this->listPref->getAttr('IMGWIDTH'),$this->itemPref->getAttr('IMGWIDTH'));

		if (isNull($iw))
			$this->iw=$this->w / 4;
		else
			if (substr($iw,-1)=='%')
				$this->iw=$this->w*substr($iw,0,-1)/100;
			else
				$this->iw=$iw;
		
		$this->SetLeftMargin($this->margins['left'] + $this->iw + $this->padding);
		
		$this->AddPage(); // on considère la liste comme toujours "ouverte"
			// contrairement à la mosaique qui place les images à des places précises et 
			// crée les page à la première image de chaque page
			// on crée une page de liste si la nouvelle image "dépasse" de la page actuelle

		$nodes = $this->xml->getPathNodes('/ITEM');
		foreach($nodes as $item)
			$this->AddItem($item);
		}
		
	//En-tête
	function Header()
		{
		parent::Header();
		$this->SetLeftMargin($this->margins['left'] + $this->iw + $this->padding);
		}

	function AddItem($item)
		{
		$label = $item->getVal('LABEL');
		$info = $item->getVal('INFO');
		$body = $item->getVal('BODY');

		list($img, $width, $height, $imgstatus) = $this->getPicPath($item->getVal('IMG'));
		
		$cy = $this->getY();
		$cp = $this->PageNo();
		if($width)
			{
			if (($width/$this->iw) > ($height/$this->iw))
				{
				$rw = $this->iw;
				$rh = $height * $rw / $width;
				$ox = 0;
				}
			else
				{
				$rh = $this->iw;
				$rw = $width * $rh / $height;
				$ox = ($this->iw - $rw)/2;
				}
			
			if (($cy + $this->padding + $rh) > ($this->h - ($this->margins['bottom']) ) ) 
				{
				$this->AddPage();
				$cy = $this->getY();
				$cp = $this->PageNo();
				}
			
			$this->Image($img, $this->margins['left']+$ox, $cy + $this->padding, $rw, $rh);
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
		
		if($cp == $this->PageNo() and $this->getY()<($cy + $rh + 2*$this->padding) )
				$this->setY($cy + $rh + 2*$this->padding);
			
	//	$this->setY($this->getY()+$this->padding );
			
		$cy = $this->getY();	
		
		$border = $this->itemPref->getPathNodes('/BORDER',0);
		if ($border)
			{
			$this->SetLineWidth(nvl($border->getAttr('SIZE'), 0.25));
			$this->SetDrawColor(nvl($border->getAttr('COLOR'), '#000000'));
			$this->line($this->margins['left'],$cy,$this->w-$this->margins['right'],$cy);
			}
		}
	}
	
?>