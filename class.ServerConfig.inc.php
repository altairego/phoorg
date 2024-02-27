<?php

class ServerConfig
	{
	var $Label;
	
	var $ImpexpFolder;
	var $ImpexpWebService;
	var $ImpexpServiceLabel;
	var $MoreParams;
	var $AutoFields;
	
	var $WorkFolder;
	
	function forcePath($p)
		{
		if (!isNull($p))
			{
			mkdir_p($p);
			return realpath($p);
			}
			
		return $p;
		}
	
	function ServerConfig($serverName)
		{
		global $settings;

		$this->Label = $serverName;
		
		$this->ImpexpFolder = $this->forcePath(INI::get($settings,$serverName,'ImpexpFolder'));
		$this->ImpexpServiceLabel = INI::get($settings,$serverName,'WebService');
		$this->ImpexpWebService = 
			'http://'.INI::get($settings,$serverName,'WebServer').
			'/'.$this->ImpexpServiceLabel.'/appl/business/in.service.Impexp.inc.php'
			;
		
		$this->WorkFolder = $this->forcePath(INI::get($settings,$serverName,'WorkFolder'));
		
		$autofields = INI::get($settings,$serverName,'AutoFields');
//echo "[$serverName] AutoFields=".json_encode(json_decode($autofields))."\n";

		$this->AutoFields = json_decode(nvl(INI::get($settings,$serverName,'AutoFields'),'null'));
		$this->MoreParams = $settings[$serverName];
		}
		
	function Save()
		{
		global $settings;

		if (!isNull($this->ImpexpFolder))
			$this->ImpexpFolder = $this->forcePath($this->ImpexpFolder);
			
		if (!isNull($this->WorkFolder))
			$this->WorkFolder = $this->forcePath($this->WorkFolder);
		
		INI::set($settings,$this->Label,'ImpexpFolder',$this->ImpexpFolder);
		INI::set($settings,$this->Label,'WebService',$this->DaemonWebService);
		
		INI::set($settings,$this->Label,'WorkFolder',$this->WorkFolder);
		//INI::set($settings,$this->Label,'MoreParams',$this->MoreParams);
		
		INI::write(iniFile, $settings);
		}
		
	function Service($act,$params,$fmt='array')
		{
		// add call key
		$sessId = wvEncrypt($this->Label,$this->Label);
		if (!is_array($params))
			$params = array('data'=>$params);
		$params['act'] = $act;
		$jsondata = json_encode($params);
		$data = array('sessionId'=>$sessId,'data'=>wvEncrypt($jsondata,$sessId));

echo "\nCalling [".$this->ImpexpWebService."]\nDATA:\n";
		// use key 'http' even if you send the request to https://...
		$options = array(
				'http' => array(
						'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
						'method'  => 'POST',
						'content' => http_build_query($data),
				)
		);
		$context  = stream_context_create($options);
		$ret = nvl(file_get_contents($this->ImpexpWebService, false, $context),'null');
		
echo jsonPrettyPrint($jsondata)."\n[SERVICE-$act:$ret]\n";
		return json_decode($ret,($fmt=='array'));
		}

	}
	?>