<?php
/**
 * Stub class to access PHPmailer with htmlMimeMail interface
 *
 */

class htmlMimeMail
{
    var $mailer;

    var $smtp_params;
    var $image_types;

    function htmlMimeMail()
    {
        /**
        * Initialise some variables.
        */
        $this->mailer = new PHPMailer();
		$this->mailer->IsHTML(true);
		$this->mailer->IsSMTP();
		
        if (!empty($GLOBALS['HTTP_SERVER_VARS']['HTTP_HOST'])) {
            $helo = $GLOBALS['HTTP_SERVER_VARS']['HTTP_HOST'];
        } elseif (!empty($GLOBALS['HTTP_SERVER_VARS']['SERVER_NAME'])) {
            $helo = $GLOBALS['HTTP_SERVER_VARS']['SERVER_NAME'];
        } else {
            $helo = 'localhost';
        }

        $this->smtp_params['host'] = 'localhost';
        $this->smtp_params['port'] = 25;
        $this->smtp_params['helo'] = $helo;
        $this->smtp_params['auth'] = false;
        $this->smtp_params['user'] = '';
        $this->smtp_params['pass'] = '';
    }

    /**
    * Accessor to set the CRLF style
    */
    function setCrlf($crlf = "\n")
    {
		$this->mailer->LE = $crlf;
    }

    /**
    * Accessor to set the SMTP parameters
    */
    function setSMTPParams($host = null, $port = null, $helo = null, $auth = null, $user = null, $pass = null, $sec = '')
		{
        if (!is_null($host)) $this->smtp_params['host'] = $host;
        if (!is_null($port)) $this->smtp_params['port'] = $port;
        if (!is_null($helo)) $this->smtp_params['helo'] = $helo;
        if (!is_null($auth)) $this->smtp_params['auth'] = $auth;
        if (!is_null($user)) $this->smtp_params['user'] = $user;
        if (!is_null($pass)) $this->smtp_params['pass'] = $pass;
		
		$this->mailer->Mailer = 'smtp';
		$this->mailer->Host = $this->smtp_params['host'];
		$this->mailer->Port = $this->smtp_params['port'];
		$this->mailer->Helo = $this->smtp_params['helo'];
		if (isNull($sec) and $port==465) $sec='tls';
		$this->mailer->SMTPSecure = $sec;
		$this->mailer->SMTPAuth = $this->smtp_params['auth'];
		$this->mailer->Username = $this->smtp_params['user'];
		$this->mailer->Password = $this->smtp_params['pass'];
		}

    /**
    * Accessor function to set the text encoding
    */
    function setTextEncoding($encoding = '7bit')
    {
		$this->mailer->Encoding = $encoding;
    }

    /**
    * Accessor function to set the HTML encoding
    */
    function setHtmlEncoding($encoding = 'quoted-printable')
    {
		$this->mailer->Encoding = $encoding;
    }

    /**
    * Accessor function to set the text charset
    */
    function setTextCharset($charset = 'ISO-8859-1')
    {
		$this->mailer->CharSet = $charset;
    }

    /**
    * Accessor function to set the HTML charset
    */
    function setHtmlCharset($charset = 'ISO-8859-1')
    {
		$this->mailer->CharSet = $charset;
    }

    /**
    * Accessor function to set the header encoding charset
    */
    function setHeadCharset($charset = 'ISO-8859-1')
    {
		$this->mailer->CharSet = $charset;
    }

    /**
    * Accessor function to set the text wrap count
    */
    function setTextWrap($count = 998)
    {
		$this->mailer->WordWrap = $count;
    }

    /**
    * Accessor to set a header
    */
    function setHeader($name, $value)
    {
        $this->mailer->AddCustomHeader("$name: $value;");
   }

    /**
    * Accessor to add a Subject: header
    */
    function setSubject($subject)
    {
		$this->mailer->Subject=$subject;
    }

    /**
    * Accessor to add a From: header
    */
    function setFrom($from)
    {
		$this->mailer->setFrom($from);
    }

    /**
    * Accessor to set the return path
    */
    function setReturnPath($return_path)
    {
        $this->mailer->Sender = $return_path;
    }

    /**
    * Accessor to add a Cc: header
    */
    function setCc($cc)
    {
		if (is_array($cc))
			$rcpts=explode(',',implode(',',$cc));
		else
			$rcpts=explode(',',$cc);
			
		foreach($rcpts as $rcpt)
			{
			if(preg_match('/([^<]*)<([^>]*)>/',$rcpt,$parts))
				{
				$address=$parts[2];
				$name=$parts[1];
				}
			else
				{
				$address=$rcpt;
				$name='';
				}
			$this->mailer->AddCC($address, $name);
			}
    }

    /**
    * Accessor to add a Bcc: header
    */
    function setBcc($bcc)
    {
		if (is_array($bcc))
			$rcpts=explode(',',implode(',',$bcc));
		else
			$rcpts=explode(',',$bcc);

		foreach($rcpts as $rcpt)
			{
			if(preg_match('/([^<]*)<([^>]*)>/',$rcpt,$parts))
				{
				$address=$parts[2];
				$name=$parts[1];
				}
			else
				{
				$address=$rcpt;
				$name='';
				}
			$this->mailer->AddBCC($address, $name);
			}
    }

    /**
    * Adds plain text. Use this function
    * when NOT sending html email
    */
    function setText($text = '')
    {
		$this->mailer->AltBody= $text;
    }

    /**
    * Adds a html part to the mail.
    * Also replaces image names with
    * content-id's.
    */
    function setHtml($html, $text = null, $images_dir = null)
		{
		if($text)
			$this->mailer->AltBody= $text;
		
		$this->mailer->MsgHTML($html, $images_dir?$images_dir:'');
	    }

    /**
    * Adds an image to the list of embedded
    * images.
    */
    function addHtmlImage($file, $name = '', $c_type='application/octet-stream')
		{
		$this->mailer->AddEmbeddedImage($file, $file, $name, 'base64', $c_type);
		}
    function _findHtmlImages($images_dir)
		{
		}

    /**
    * Adds a file to the list of attachments.
    */
    function addAttachment($file, $name = '', $c_type='application/octet-stream', $encoding = 'base64')
		{
		if (file_exists($file))
			$this->mailer->AddAttachment($file, $name, $encoding, $c_type);
		else
			$this->mailer->AddStringAttachment($file, $name, $encoding, $c_type);
		}

    /**
    * This function will read a file in
    * from a supplied filename and return
    * it. This can then be given as the first
    * argument of the the functions
    * add_html_image() or add_attachment().
    */
    function getFile($filename)
    {
	//return $filename;
        $return = '';
        if ($fp = fopen($filename, 'rb')) {
            while (!feof($fp)) {
                $return .= fread($fp, 1024);
            }
            fclose($fp);
//echo '['.$filename.':'.strlen($return).']';
            return $return;

        } else {
//echo '['.$filename.':none]';
            return false;
        }
    }

    /**
    * Sends the mail.
    *
    * @param  array  $recipients
    * @param  string $type OPTIONAL
    * @return mixed
    */
    function send($recipients, $type = 'mail') // $type ignored, forcing smtp
		{
		if (is_array($recipients))
			$rcpts=explode(',',implode(',',$recipients));
		else
			$rcpts=explode(',',$recipients);
		
		foreach($rcpts as $n=>$rcpt)
			{
//echo "[to:$rcpt]\n";
			if(preg_match('/([^<]*)<([^>]*)>/',$rcpt,$parts))
				{
				$address=$parts[2];
				$name=$parts[1];
				}
			else
				{
				$address=$rcpt;
				$name='';
				}
			$this->mailer->AddAddress($address, $name);
			}
		$this->mailer->Send();
		}

	} // End of class.
?>