<?php
class ControlPanel extends GtkWindow {
	private $btnQuit;
	private $processfolder;

	private $currentPic;
	private $picList;

	private $sels;
	private $tags;

	private $fso;
	public $data;
	
	/**
	 * ********************************************
	 * Procs
	 * ********************************************
	 */
	function folderNbFiles($f) {
		if (is_object ( $this->fso )) {
			$folder = $this->fso->getfolder ( $f );
			return $folder->files->count ();
		}
		$dircmd = '';
		exec ( 'dir ' . escapeshellarg ( $f ), $dircmd );
		preg_match ( '/([0-9]+) (fichier\(s\)|file\(s\))[^a-z]+octets[^0-9]+([0-9]+) (r.p\(s\)|folder\(s\))/i', implode ( ' ', $dircmd ), $chunks );
		
		return intval ( $chunks [1] );
	}
	function folderNbFolds($f) {
		if (is_object ( $this->fso )) {
			$folder = $this->fso->getfolder ( $f );
			return $folder->files->count ();
		}
		$dircmd = '';
		exec ( 'dir ' . escapeshellarg ( $f ), $dircmd );
		preg_match ( '/([0-9]+) (fichier\(s\)|file\(s\))[^a-z]+octets[^0-9]+([0-9]+) (r.p\(s\)|folder\(s\))/i', implode ( ' ', $dircmd ), $chunks );
		
		return intval ( $chunks [3] ) - 2; // . et ..
	}

	function traceErrorHandler($errno, $errmsg, $filename, $linenum, $vars) {
		if (isIn ( $errno, 2048, E_WARNING ))
			return true;
			// 2048 : date timezone settings
		
		$errortype = array (
				E_ERROR => 'Erreur',
				E_WARNING => 'Alerte',
				E_PARSE => 'Erreur d\'analyse',
				E_NOTICE => 'Note',
				E_CORE_ERROR => 'Core Error',
				E_CORE_WARNING => 'Core Warning',
				E_COMPILE_ERROR => 'Compile Error',
				E_COMPILE_WARNING => 'Compile Warning',
				E_USER_ERROR => 'Erreur spécifique',
				E_USER_WARNING => 'Alerte spécifique',
				E_USER_NOTICE => 'Note spécifique' 
		);
		
		// Var dumping levels
		$vd_levs = array (
				E_ERROR,
				E_WARNING 
		);
		
		if (! preg_match ( "/(mail|smtp)/i", $filename )) {
			echo_out ( "### " . array_get ( $errortype, $errno, "ERROR : $errno" ) . " - $errmsg\n" );
			echo_out ( "\t\tin $filename ($linenum)\n" );
		}
		/*
		 * if (in_array($errno, $vd_levs))
		 * {
		 * echo_out("\t\tVARS:\n");
		 * echo_out(print_r($vars,true));
		 * }
		 */
		return true;
	}
		
	/**
	 * ********************************************
	 * Events
	 * ********************************************
	 */
	
	/* Evenements des controles */
	function btnQuit_click() {
		$this->destroy ();
	}

	function initPicList() {
		if (!is_dir($this->processfolder)) { $this->destroy (); return;}

		mkdir_p($this->processfolder . DIRECTORY_SEPARATOR . 'corbeille');
		mkdir_p($this->processfolder . DIRECTORY_SEPARATOR . 'selection');

		if ($dh = opendir ( $this->processfolder )) {
			$this->picList = array();

			while ( ($file = readdir ( $dh )) !== false ) {
				$filepath = $this->processfolder . DIRECTORY_SEPARATOR . $file;
				if (is_dir($filepath)) {
					echo "selection : $file\n";
					$this->sels[] = $file;
					$this->selList->pack_start(new GtkLabel($file), false);
				} else {
					echo "file : $file (".fileext($filepath).")\n";
					if (isIn(fileext($filepath),'jpg','png'))
						$this->picList[] = $file;

				}
			}
			closedir ( $dh );

echo count($this->picList)." images\n";
		}
		
		$this->nbPics = count($this->picList);

		return;		

		rename ( $filepath, $workfile );

		mkdir_p ( $sc->WorkFolder . DIRECTORY_SEPARATOR . $taskRef );
		@unlink($sc->WorkFolder . DIRECTORY_SEPARATOR . $taskRef . DIRECTORY_SEPARATOR . 'ok.txt');
		@unlink($sc->WorkFolder . DIRECTORY_SEPARATOR . $taskRef . DIRECTORY_SEPARATOR . $taskRef . '.xml');
	}

	function displayPic() {
		/*$this->picDisplay->set_from_file(
			$this->processfolder . DIRECTORY_SEPARATOR . $this->picList[$this->currentPic]
		); /* */

		$_r = $this->picDisplay->get_allocation();

		$this->picDisplay->set_from_pixbuf(GdkPixbuf::new_from_file_at_size(
			$this->processfolder . DIRECTORY_SEPARATOR . $this->picList[$this->currentPic],
			$_r->width, $_r->height
		));
		$this->picLabel->set_text ( 
			' '.sprintf("%'.05d", $this->currentPic+1).' / '.$this->nbPics.' : '
			.$this->picList[$this->currentPic].' '
		);
	}
	
	function btnNext_click() {
		if ($this->currentPic+1 >= $this->nbPics)
			$this->currentPic = 0;
		else
			$this->currentPic++;

		$this->displayPic();
	}

	function btnPrev_click() {
		if ($this->currentPic-1 < 0)
			$this->currentPic = $this->nbPics-1;
		else
			$this->currentPic--;

		$this->displayPic();
	}

	/* ##################################################################################################################### */

	/**
	 * ********************************************
	 * APP INIT / WINDOW LOADING
	 * ********************************************
	 */
	function __construct($folder) {
		global $settings;

		$this->noevent = true;

		parent::__construct ();
		$old_error_handler = set_error_handler ( array (
				$this,
				'traceErrorHandler' 
		) );

		$this->processfolder = $folder;
		$this->currentPic = 0;
		$this->nbPics = 0;
		
		$this->window_init ();
		
		$this->fso = new COM ( 'scripting.filesystemobject' );
		
		$this->data = new Datas ();

echo "selList3:" . get_class($this->selList)."\n";

		$this->initPicList();
		$this->displayPic();

		$this->connect ( 'window-state-event', array ( //size-request
				$this,
				'win_event'
		));

		$this->noevent = false;
	}

	function setToolBtn($btn, $lbl, $pic) {
		if (USE_PICS) {
			$btn->child->set_from_stock ( $pic, Gtk::ICON_SIZE_MENU );
		} else
			$btn->child->set_label ( $lbl );
	}
	function initToolBtn($btn, $lbl, $pic) {
		if (USE_PICS) {
			$btn->add ( GtkImage::new_from_stock ( $pic, Gtk::ICON_SIZE_MENU ) );
		} else
			$btn->add ( new GtkLabel ( $lbl ) );
		
		$btn->set_relief ( 2 ); // Gtk::GTK_RELIEF_NONE);
	}

	function win_event($a, $b='!b', $c='!c') {
		if ($this->noevent) return;
echo "win_event:" . get_class($a)."\n";
		$this->noevent = true;
		$this->displayPic();
		$this->noevent = false;
	}

	function window_init() {
		$this->set_title ( 'PhOOrg - '.$this->processfolder );

		$this->connect ( 'destroy', 'destroy' );
		// $this->connect('destroy', array($this, 'btnQuit_click'));
		// $cpWindow->connect('delete-event', 'delete_event');

		$cpLayout = new GtkVBox ();
		$cpLayout->pack_start ( $this->init_toolbar (), false );
		
		$cpLayout->pack_start ( $wLayout = new GtkHBox(), true );
			$wLayout->pack_start ( $this->init_picplaceholder (), true );
			$wLayout->pack_start ( $this->init_catlist (), false );
echo "selList2:" . get_class($this->selList)."\n";

		$this->picDisplay->modify_bg ( Gtk::STATE_NORMAL, GdkColor::parse ( '#f09030' ) );

		/*
		 * Create a new tooltips object and use it to set a tooltip for the toolbar.
		 */
		$tt = new GtkTooltips ();
		$tt->set_delay ( 200 );
		$tt->set_tip ( $this->btnQuit, 'Quitter ImpExpDaemon', '' );
		$tt->enable ();
		
		/*
		 * Show the window and all its child widgets.
		 */
		$this->add ( $cpLayout );
		$this->set_default_size ( 750, 450 );
		$this->show_all ();
	}
	function init_toolbar() {
		$cpToolbar = new GtkHBox ();
		
		$cpToolbar->pack_start( $this->btnPrev = new GtkButton (), false );
		$this->initToolBtn ( $this->btnPrev, 'Next', Gtk::STOCK_MEDIA_PREVIOUS );
		$this->btnPrev->connect_simple ( 'clicked', array (
				$this,
				'btnPrev_click' 
		) );

		$cpToolbar->pack_start 
		    ( $this->btnNext = new GtkButton (), false );
		$this->initToolBtn ( $this->btnNext, 'Next', Gtk::STOCK_MEDIA_NEXT );
		$this->btnNext->connect_simple ( 'clicked', array (
				$this,
				'btnNext_click' 
		) );
		$this->btnNext->grab_focus ();

		$cpToolbar->pack_start ( new GtkVSeparator (), false );

		$cpToolbar->pack_start ( $this->picLabel = new GtkLabel ( "-" ), false );

		$cpToolbar->pack_start ( new GtkVSeparator (), false );

		$cpToolbar->pack_start ( $this->btnQuit = new GtkButton (), false );
		$this->initToolBtn ( $this->btnQuit, 'Quit', Gtk::STOCK_QUIT );
		$this->btnQuit->connect_simple ( 'clicked', array (
				$this,
				'btnQuit_click' 
		) );
		
		$cptb = new GtkToolbar ();
		$cptb->append_widget ( $cpToolbar, '', '' );
		
		return $cptb;
	}
	function init_picplaceholder() {
		return $this->picDisplay = new GtkImage(); //set_from_file();
	}
	function init_catlist() {
		$vpane = new GtkVPaned ();
		$vpane->set_border_width ( 2 );
		$vpane->set_size_request(250,1000);
		
		$vpane->add1 ( $this->init_sellist () );
echo "selList1:" . get_class($this->selList)."\n";
$this->selList->pack_start($tt = &new GtkLabel("Yoooo!!2"), false);
		$vpane->add2 ( $this->init_taglist () );
		//$vpane->set_position ( 200 );

		$vpane->modify_bg ( Gtk::STATE_NORMAL, GdkColor::parse ( '#3090f0' ) );

		return $vpane;
	}
	function init_sellist() {
		$swTL = new GtkScrolledWindow ();

		$this->selList = &new GtkVBox();
echo "selList0:" . get_class($this->selList)."\n";

$this->selList->pack_start($tt = &new GtkLabel("Yoooo!!"), false);
$tt->modify_bg ( Gtk::STATE_NORMAL, GdkColor::parse ( '#f09030' ) );
$tt->modify_fg ( Gtk::STATE_NORMAL, GdkColor::parse ( '#302010' ) );

		$swTL->add_with_viewport ( $this->selList );
		$swTL->set_policy ( Gtk::POLICY_NEVER, Gtk::POLICY_ALWAYS );

		$swTL->modify_bg ( Gtk::STATE_NORMAL, GdkColor::parse ( '#3090f0' ) );

		return $swTL;
	}
	function init_taglist() {
		$swTL = new GtkScrolledWindow ();

		$this->tagList = new GtkVBox();
		
		$swTL->add_with_viewport ( $this->tagList );
		$swTL->set_policy ( 1, 0 );

		$swTL->modify_bg ( Gtk::STATE_NORMAL, GdkColor::parse ( '#3090f0' ) );

		return $swTL;
	}
}
?>