<?php
	class ConfigServer extends GtkDialog
		{
		private $tabPanel;
		private $arrServersPage;
		private $nbServers;
		private $txtSmtpIp;
		private $txtSmtpPort;
		
	    function __construct($caller=null)
			{
	        parent::__construct('Configuration des serveurs',$caller,Gtk::DIALOG_MODAL,array(Gtk::STOCK_OK, Gtk::RESPONSE_OK));
			$this->arrServersPage = array();

			$this->tabPanel = $this->createTabPanel();
			$this->vbox->pack_start($this->tabPanel);
			
			global $cpanel;
			$this->nbServers = 0;
			foreach($cpanel->data->serversName as $serverName)
				$this->CreateNewServer($serverName);
			$this->tabPanel->set_current_page(min(1,$this->nbServers));
			$this->tabPanel->connect_after('switch-page',array($this,'tabPanel_switchpage'));
			
			$this->set_default_size(350,400);
			$this->show_all();
			}
			
		function tt()
			{
			$this->window->move_resize(0,0,300,600);
			$this->size_request(300,600);
			$this->show_all();
			}
			
		function createTabPanel()
			{
			$cs_nbkCfg = new GtkNotebook();
				$csTbl = new GtkTable(6,10);
				
				$btnNewServer = newButton('Nouveau Serveur', array($this,'btnNewServer_click'));
					$csTbl->attach($btnNewServer,1,9,4,5);
					$btnNewServer->child->modify_font(new PangoFontDescription("Arial Black Heavy 14"));
					
				$csTbl->attach(
					newButton('test', array($this,'tt')),
					1,9,5,6);
					
				/* */
				$csTbl->attach(new GtkLabel('SMTP Server Adress : '),0,3,1,2);
					$csTbl->attach($this->txtSmtpIp = new GtkEntry(''),4,10,1,2);
					//$this->initTrackTextChanges($this->txtSmtpIp);
					
				$csTbl->attach(new GtkLabel('SMTP Server Port : '),0,3,2,3);
					$csTbl->attach($this->txtSmtpPort = new GtkEntry(''),4,10,2,3);
					//$this->initTrackTextChanges($this->txtSmtpPort);
				/* */
					
				$csTbl->set_homogeneous(true);
				
				$cs_nbkCfg->append_page(
				    $csTbl,
				    new GtkLabel('Créer')
					);
					
					$cs_nbkCfg->set_name('csnbk');
					gtk::rc_parse_string(
						'style "nbk" {'
						//.'font="-*-arial-bold-r-normal--30-300-*-*-*-*-*-*" '
						.'bg[NORMAL] = "#ffffff" '
						.'fg[NORMAL] = "#000000" '
						.'base[NORMAL] = "#c0b0a0" '
						.'}'
						);
					gtk::rc_parse_string( 'widget "GtkDialog.*csnbk" style "nbk"');

			return $cs_nbkCfg;
			}
		
		public function ctrlEditServer_serverUpdated($source, $param)
			{
			global $cpanel;
			$serverPlace = $cpanel->data->serversId[$source->ServerLabel];
			
			$cpanel->data->serversConfig[$serverPlace]->DaemonWebService = $source->DaemonWebService;
			$cpanel->data->serversConfig[$serverPlace]->ImpexpFolder = $source->ImpexpFolder;
			$cpanel->data->serversConfig[$serverPlace]->MoreParams = $source->MoreParams;
			
			$cpanel->data->serversConfig[$serverPlace]->WorkFolder = $source->WorkFolder;
			
			$cpanel->data->serversConfig[$serverPlace]->Save();
			}
		
		function CreateNewServer($serverName)
			{
			global $cpanel;
			$serverPlace = 0;
			
			if ($this->nbServers>0)
				{
				$serverPlace = array_get($cpanel->data->serversId,$serverName);
				if (!isNull($serverPlace) and array_key_exists($serverPlace,$this->arrServersPage))
					{
					$this->tabPanel->set_current_page($this->arrServersPage[$serverPlace]);
					return;
					}
				}

			$this->nbServers++;
			$serverPlace = $cpanel->data->addServer($serverName);
			
			$ctrlEditServer = new EditServer();
			$ctrlEditServer->connect_custom('server_updated', array($this,'ctrlEditServer_serverUpdated'));
			$ctrlEditServer->ServerLabel = $serverName;
			$ctrlEditServer->Config = $cpanel->data->serversConfig[$serverPlace];
			
			$this->arrServersPage[$serverPlace] = $this->tabPanel->append_page($ctrlEditServer,new GtkLabel($serverName));
			$this->show_all();
			$this->tabPanel->set_current_page($this->arrServersPage[$serverPlace]);
			}
			
		function btnNewServer_click()
			{
			$this->askForNewServer();
			}
			
		function askForNewServer()
			{
			$newName = Gtk2_EntryDialog::get(
			    'Nom du nouveau serveur?',       //the message
			    'Serveur '.($this->nbServers+1)               //The default text
				);

			if (!isNull($newName)) {
				$this->CreateNewServer($newName);
				}
			}
			
		function tabPanel_switchpage($pnl, $par, $page)
			{
			if ($page==0)
				{
				//$this->askForNewServer();
				}
			}
		}
?>