<?php
	class EditServer extends GtkVBox
		{
		private $memServerLabel;
		private $memDaemonMode;
		private $memDaemonWebService;
		private $memImpexpFolder;
		private $memWorkFolder;
		private $memSmtpIp;
		private $memSmtpPort;
		
		private $txtServerLabel;
		private $selDaemonModeFOLDER;
		private $selDaemonModeWEB;
		private $selDaemonModeDB;
		private $selDaemonMode;
		private $txtDaemonWebService;
		private $txtImpexpFolder;
		private $txtWorkFolder;
		
		private $txtMoreParams;
		
		private $btnSave;
		private $btnCancel;
		private $btnDelete;
		
		private $dirty;
		private $inAssign;

		/**************
		* SIGNALS     *
		**************/
		private $__sigprocs;
		public function connect_custom($signal, $proc) {
			if (!array_key_exists($signal, $this->__sigprocs))
				{
				$this->__sigprocs[$signal]=array();
				}
			$this->__sigprocs[$signal][]=$proc;
			}
		private function emit_custom($signal, $param='') {
			if (array_key_exists($signal, $this->__sigprocs))
				foreach($this->__sigprocs[$signal] as $proc)
					call_user_func_array($proc,array(&$this,&$param));
			}

		/**************
		* ATTRIBUTES  *
		**************/
		public function __get($prop) {
			switch ($prop)
				{
				case 'isDirty':
					return $dirty;
				case 'DaemonWebService':
				case 'ImpexpFolder':
				case 'ServerLabel':
				case 'WorkFolder':
					$fld = 'mem'.$prop;
					return $this->$fld;
				case 'MoreParams':
					$fld = 'mem'.$prop;
					return $this->$fld; // parse vals?
				}
			}
		public function __set($prop, $val) {
			switch ($prop)
				{
				case 'Config':
					$this->ImpexpFolder = $val->ImpexpFolder;
					$this->WorkFolder = $val->WorkFolder;
					$this->DaemonWebService = $val->DaemonWebService;
					$this->MoreParams = $val->MoreParams;
					break;
				case 'DaemonWebService':
				case 'ImpexpFolder':
				case 'ServerLabel':
				case 'WorkFolder':
				case 'MoreParams':
					$this->inAssign=true;
					$fld = 'mem'.$prop;
					$this->$fld = $val;
					$fld = 'txt'.$prop;
					if ($prop!='MoreParams')
						$this->$fld->set_text($val);
					else
						$this->$fld->set_buffer($val);
					$this->inAssign=false;
					break;
				}
			}
		private function Save() {
			//$this->memServerLabel = $this->txtServerLabel->get_text();
			$this->memDaemonWebService = $this->txtDaemonWebService->get_text();
			$this->memImpexpFolder = $this->txtImpexpFolder->get_text();
			
			$this->memWorkFolder = $this->txtWorkFolder->get_text();
			$this->memMoreParams = $this->txtMoreParams->get_buffer();
			$this->setClean();
			}
		private function Revert() {
			$this->txtServerLabel->set_text($this->memServerLabel);
			$this->txtDaemonWebService->set_text($this->memDaemonWebService);
			$this->txtImpexpFolder->set_text($this->memImpexpFolder);
			
			$this->txtWorkFolder->set_text($this->memWorkFolder);
			$this->txtMoreParams->set_buffer($this->memMoreParams);
			$this->setClean();
			}

		private function setDirty() {
			if (!$this->inAssign)
				{
				$this->btnCancel->set_sensitive(true);
				$this->btnSave->set_sensitive(true);
				$this->dirty=true;
				}
			}
		function setClean() {
			$this->btnCancel->set_sensitive(false);
			$this->btnSave->set_sensitive(false);
			$this->dirty=false;
			}
		
		/**************
		EVENTS
		**************/
		function txtChanged()
			{
			$this->setDirty();
			}
			
		function onToggleMode($radio, $val)
			{
			if (!$radio->get_active()) return;
			//echo "$val\n";
			
			$this->setDirty();
			}
			
		function folderWork_select($okbutton)
		{
		$filePrompt = $okbutton->get_toplevel();
    	$fileName = $filePrompt->get_filename();
		}

		function btnSave_click()
			{
			$this->Save();
			$this->emit_custom('server_updated');
			}
			
		function btnDelete_click()
			{
			$this->emit_custom('server_delete_requested');
			}
			
		function btnCancel_click()
			{
			$this->Revert();
			}
			
		/**************
		INIT
		**************/
	    function __construct()
			{
	        parent::__construct();
			$this->dirty = false;
			$this->inAssign = false;
			$this->__sigprocs=array();
			
			$this->pack_start($this->init_form(),false);
			$this->pack_start(new GtkLabel(''));
			$this->pack_start($this->init_buttbar(),false);
			}
		
		function initToolBtn($btn, $lbl, $pic)
			{
			if (USE_PICS)
				{
				$btncnt = new GtkHBox();
				$btncnt->pack_start(GtkImage::new_from_stock(
					$pic,
					Gtk::ICON_SIZE_MENU
				    ));
				$btncnt->pack_start(new Gtklabel($lbl));
				$btn->add($btncnt);
				}
			else
				$btn->add(new Gtklabel($lbl));
			}

		function initTrackTextChanges($ctrl)
			{
			$ctrl->connect('backspace', array($this,'txtChanged'));
			$ctrl->connect('cut-clipboard', array($this,'txtChanged'));
			$ctrl->connect('delete-from-cursor', array($this,'txtChanged'));
			$ctrl->connect('insert-at-cursor', array($this,'txtChanged'));
			$ctrl->connect('paste-clipboard', array($this,'txtChanged'));
			}
			
		function init_form()
			{
			$formTable = new Gtktable(13,4);
			$fti = 0;

				$formTable->set_homogeneous(true);
				$formTable->attach(new GtkLabel('Server label : '),0,1,$fti,$fti+1);
					$formTable->attach($this->txtServerLabel = new GtkEntry(''),1,4,$fti,$fti+1);
					$this->initTrackTextChanges($this->txtServerLabel);
				$fti++;
				
				$formTable->attach(new GtkLabel('Import folder : '),0,1,$fti,$fti+1);
					$cpLayout = new GtkHBox();
					$formTable->attach($cpLayout,1,4,$fti,$fti+1);
						$this->txtImpexpFolder = new GtkEntry();
						$btnfile = new GtkButton('...');
					
						$cpLayout->pack_start($this->txtImpexpFolder,true);
						$cpLayout->pack_start($btnfile,false);
					$btnfile->connect_simple('clicked', 'select_folder',$this->txtImpexpFolder);
				
					$this->initTrackTextChanges($this->txtImpexpFolder);
				$fti++;
					
				$formTable->attach(new GtkLabel('Web service root : '),0,1,$fti,$fti+1);
					$formTable->attach($this->txtDaemonWebService = new GtkEntry(''),1,4,$fti,$fti+1);
					$this->initTrackTextChanges($this->txtDaemonWebService);
				$fti++;
				
				$formTable->attach(new GtkLabel('Working folder : '),0,1,$fti,$fti+1);
					$cpLayout = new GtkHBox();
						$this->txtWorkFolder = new GtkEntry();
						$btnfile = new GtkButton('...');
					
						$cpLayout->pack_start($this->txtWorkFolder,true);
						$cpLayout->pack_start($btnfile,false);
					$formTable->attach($cpLayout,1,4,3,4);
					$btnfile->connect_simple('clicked', 'select_folder',$this->txtWorkFolder);
					$this->initTrackTextChanges($this->txtWorkFolder);
				$fti++;
					
				$formTable->attach(new GtkLabel('ImpExp mode : '),0,1,$fti,$fti+1);
					$this->selDaemonModeFOLDER = new GtkRadioButton(null, 'Par lot', true);
					$this->selDaemonModeFOLDER->connect('toggled', array($this,'onToggleMode'), 'BATCH');
					$formTable->attach($this->selDaemonModeFOLDER,1,2,$fti,$fti+1);
					$this->selDaemonModeWEB = new GtkRadioButton($this->selDaemonModeFOLDER, 'MaJ', true);
					$this->selDaemonModeWEB->connect('toggled', array($this,'onToggleMode'), 'UPDATE');
					$formTable->attach($this->selDaemonModeWEB,2,3,$fti,$fti+1);
					$this->selDaemonModeDB = new GtkRadioButton($this->selDaemonModeFOLDER, 'P.J.', true);
					$this->selDaemonModeDB->connect('toggled', array($this,'onToggleMode'), 'PJ');
					$formTable->attach($this->selDaemonModeDB,3,4,$fti,$fti+1);
				$fti++;
					
				$formTable->attach(new GtkLabel('Module params : '),0,1,$fti,$fti+1);
						$this->txtMoreParams = new GtkTextView();
						$this->txtMoreParams->set_wrap_mode(Gtk::WRAP_WORD);
					$formTable->attach($this->txtMoreParams,1,4,$fti,$fti+1);
					$this->initTrackTextChanges($this->txtMoreParams);
				$fti++;
					
					
			return $formTable;
			}
			
		function init_buttbar()
			{
			$buttBar = new GtkHButtonBox();

			$buttBar->set_spacing(4);
			$buttBar->set_layout(Gtk::BUTTONBOX_END);
			$this->btnSave = new GtkButton();
			$buttBar->pack_start(&$this->btnSave);
				$this->initToolBtn($this->btnSave, 'Save', Gtk::STOCK_SAVE);
				$this->btnSave->connect('clicked', array($this,'btnSave_click'));
			$this->btnCancel = new GtkButton();
			$buttBar->pack_start(&$this->btnCancel);
				$this->initToolBtn($this->btnCancel, 'Cancel', Gtk::STOCK_CANCEL);
				$this->btnCancel->connect('clicked', array($this,'btnCancel_click'));
			$this->btnDelete = new GtkButton();
			$buttBar->pack_start(&$this->btnDelete);
				$this->initToolBtn($this->btnDelete, 'Delete', Gtk::STOCK_DELETE);
				$this->btnDelete->connect('clicked', array($this,'btnDelete_click'));
				
			return $buttBar;
			}
		}

?>