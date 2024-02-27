<?php

class PDFMoz extends WPDF
	{
	var $mozPref;
	var $row;
	var $col;
	var $cw;
	var $ch;

	function PDFMoz($data, $unit='mm', $format='A4')
		{
		//Appel au constructeur parent
		$this->WPDF($data,$unit,$format);
		
		$this->mozPref = nvl($this->xml->getPathNodes('/PARAMS/MOZ',0), $this->pagePref);
		$this->nbrow=doubleval(nvl($this->mozPref->getAttr('ROWS'),nvl($this->pagePref->getAttr('ROWS'), 7)));
		$this->nbcol=doubleval(nvl($this->mozPref->getAttr('COLS'),nvl($this->pagePref->getAttr('COLS'), 5)));
		$this->row=0;
		$this->col=0;
		
		$this->paging($this->margins['top']);

		$nodes = $this->xml->getPathNodes('/ITEM');
		foreach($nodes as $item)
			$this->AddItem($item);
		}

	function paging($topmargin)
		{
		$this->margins['top'] = $topmargin;
		
		$this->cw=($this->w - $this->margins['left'] - $this->margins['right']) / $this->nbcol;
		$this->ch=($this->h -  $this->margins['top'] - $this->margins['bottom']) / $this->nbrow;
		
		$this->iw=$this->cw - 2*$this->padding;
		$this->ih=$this->ch - 3*$this->padding - $this->txth;
		}
					
	//En-tête
	function Header()
		{
		parent::Header();
		$this->paging($this->getY());
		}

	function AddItem($item)
		{
		$label = $item->extract($item->getPathNodes('/LABEL'),0);
		$info = $item->extract($item->getPathNodes('/INFO'),0);
		
		list($img, $width, $height, $imgstatus) = $this->getPicPath($item->getVal('IMG'));
		
		if ($this->col==0 and $this->row==0)
			$this->AddPage();

		if (($width/$this->iw) > ($height/$this->ih))
			{
			$rw = $this->iw;
			$rh = $height * $rw / $width;
			$ox = 0;
			$oy = ($this->ih - $rh)/2;
			}
		else
			{
			$rh = $this->ih;
			$rw = $width * $rh / $height;
			$oy = 0;
			$ox = ($this->iw - $rw)/2;
			}
		
		$this->Image($img, 
			($this->col * $this->cw) + $this->margins['left'] + $this->padding + $ox, 
			($this->row * $this->ch) + $this->margins['top'] + $this->padding + $oy, 
			$rw, $rh);
		
		if (!isNull($label))
			{
		    $this->SetFontStyle($this->itemPref->getPathNodes('/LABEL',0));
			$tw = $this->GetStringWidth($label);
			if ($tw>$this->iw)
				{
				while($this->GetStringWidth($label."...")>$this->iw)
					$label=substr($label,0,-1);
					
				$label = $label."...";
				}
					
			$tw = $this->GetStringWidth($label);
			$this->Text(
				$this->margins['left'] + ($this->col * $this->cw) + ($this->cw - $tw)/2, 
				$this->margins['top'] + ($this->row * $this->ch) + $this->ch - $this->padding, 
				$label);
			}
					
		/*
		if (!isNull($info))
			{
			$this->SetFontStyle($this->itemPref->getPathNodes('/INFO',0));
			$this->WriteHTML($info);
			$this->Ln($this->FontSize);
			}
		*/
			
		$border = $this->itemPref->getPathNodes('/BORDER',0);
		if ($border)
			{
			$this->SetLineWidth(nvl($border->getAttr('SIZE'), 0.25));
			$this->SetDrawColor(nvl($border->getAttr('COLOR'), '#000000'));
			$this->Rect(
				($this->col * $this->cw) + $this->margins['left'] + $this->padding/2,
				($this->row * $this->ch) + $this->margins['top'] + $this->padding/2,
				$this->cw-$this->padding, $this->ch-$this->padding);
			}
			
		if(($this->col+1)<$this->nbcol)
			$this->col++;
		else
			{
			$this->col = 0;
			if(($this->row+1)<$this->nbrow)
				$this->row++;
			else
				$this->row = 0;
			}
		}
	}
	
?>