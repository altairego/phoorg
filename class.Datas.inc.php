<?php
	class Datas
		{
		var $monitorFile;
		var $pauseFile;
			
		// TODO : filter members
		var $nbTasks;
		var $taskFiles;
		var $nbWaits;
		var $waitFiles;
		
		var $nbServers;
		var $serversName;
		var $serversId;
		var $serversConfig;
		
		public $settings;
		var $SmtpIp;
		var $SmtpPort;
	
		function Datas()
			{
			$this->nbTasks=0;
			$this->taskFiles=array();
			$this->nbWaits=0;
			$this->waitFiles=array();
			
			$this->settings = INI::read(iniFile);
			global $settings;
			$settings = $this->settings;
//print_r($settings);
				
			$this->monitorFile = INI::get($this->settings,'appl','MonitorFile');
			$this->pauseFile = INI::get($this->settings,'appl','PauseFile');
			$this->SmtpIp = INI::get($this->settings,'Smtp','Server');
			$this->SmtpPort = INI::get($this->settings,'Smtp','Port');
			//$this->nbServers = (int) nvl(INI::get($settings,'Servers','NbServers'),0);
			$this->nbServers = (int) INI::get($this->settings,'Servers','NbServers');
			$this->serversName=array();
			$this->serversConfig=array();
			
			for($i=1;$i<=$this->nbServers;$i++)
				{
				$this->serversName[$i]=INI::get($this->settings,'Servers','Server'.$i);
				$this->serversConfig[$i]=new ServerConfig($this->serversName[$i]);
				}
			$this->serversId = array_flip($this->serversName);
			}
			
		function writeServerList()
			{
			INI::set($this->settings,'Smtp','Server',$this->SmtpIp);
			INI::set($this->settings,'Smtp','Port',$this->SmtpPort);
			
			INI::set($this->settings,'Servers','NbServers',$this->nbServers);
			for($i=1;$i<=$this->nbServers;$i++)
				INI::set($this->settings,'Servers','Server'.$i,$this->serversName[$i]);
			INI::write(iniFile, $this->settings);
			}
			
		function addServer($name)
			{
			if ($this->nbServers>0)
				{
				$serverPlace=array_get($this->serversId,$name);
				if (!isNull($serverPlace))
					return $serverPlace;
				}

			$this->nbServers++;
			$this->serversName[$this->nbServers]=$name;
			$this->serversConfig[$this->nbServers]=new ServerConfig($name);
			$this->serversId = array_flip($this->serversName);
			$this->writeServerList();
			return $this->nbServers;
			}
		}

?>