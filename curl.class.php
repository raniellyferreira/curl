<?php
/**
 * Class to handle cURL requests
 * 
 * @author Cyril Mazur  www.cyrilmazur.com	twitter.com/CyrilMazur	facebook.com/CyrilMazur
 * 
 * Alterações por Ranielly Ferreira
 * v. 1.0.4
 * Criado em 29/11/2012
 * Última Modificação: 29/04/2013
 */
class curl {
	/**
	 * Contains the vars to send by POST
	 * @var array
	 */
	private $postVars;
	
	/**
	 * cURL handler
	 * @var ressource
	 */
	private $ch;
	
	/**
	 * The headers to send
	 * @var string
	 */
    private $headers;
	
	/**
	 * The number of the current channel
	 * @var int
	 */
	private $n;
	
	/**
	 * The resulted text
	 * @var string
	 */
	private $r_text;
	
	/**
	 * The resulted headers
	 * @var string
	 */
	private $r_headers;
	
	/**
	 * The resulted cookies
	 * @var string
	 */
	private $r_cookies = '';
	
	public $use_cookie_file 	= FALSE;
	public $tmp_dir 			= 'tmp';
	public $return_header	 	= TRUE;
	
	/**
	 * Constructor
	 */
	public function __construct($array = array()) 
	{
		$this->load($array);
		
		putenv('PT=BR/Brazil');
		
		if($this->use_cookie_file)
		{
			if(is_dir($this->tmp_dir))
			{
				$ckfile = tempnam($this->tmp_dir, "CURLCOOKIE");
			} else
			{
				mkdir($this->tmp_dir,0777);
				$ckfile = tempnam($this->tmp_dir, "CURLCOOKIE");
			}
			
			$this->n			= $ckfile;
		}
		
		$headers				= array();
		$headers['agent']		= 'User-Agent: Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)';
		if($this->use_cookie_file AND isset($ckfile)) $headers['cookie']		= $ckfile;
		$headers['randDate']	= mktime(0, 0, 0, date('m'), date('d') - rand(3,26),date('Y'));
		
		$this->headers		= $headers;
		$this->postVars		= array();
		$this->ch			= curl_init();
	}
	
	public function load($array = array())
	{
		if((bool) ! $array)
		{
			return FALSE;
		}
		
		foreach($array as $k => $v)
		{
			if(isset($this->$k))
			{
				$this->$k = $v;
			}
		}
	}
	
	/**
	 * Add post vars
	 * @param string $name
	 * @param stringe $value
	 */
	public function addPostVar($name,$value = NULL) 
	{
		if(!is_array($name))
		{
			$this->postVars[$name] = $value;
		}
		
		if((bool) !$name)
		{
			return false;
		}
		
		foreach($name as $k => $v)
		{
			$this->addPostVar($k,$v);
		}
		return $this;
	}
	
	/**
	 * Execute the request and return the result
	 * @param string $url
	 * @return string
	 */
	public function exec($url,$cookie = NULL) 
	{
		
		// Set the options
		curl_setopt ($this->ch, CURLOPT_URL,$url);
		curl_setopt ($this->ch, CURLOPT_USERAGENT, $this->headers['agent']);
		curl_setopt ($this->ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt ($this->ch, CURLOPT_TIMEOUT, 0);
		curl_setopt ($this->ch, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt ($this->ch, CURLOPT_RETURNTRANSFER, 1);
		
		if($this->use_cookie_file) 
		{
			curl_setopt ($this->ch, CURLOPT_COOKIEJAR,  $this->headers['cookie']);
			curl_setopt ($this->ch, CURLOPT_COOKIEFILE,  $this->n);
		}
		
		if(!is_null($cookie)) curl_setopt ($this->ch, CURLOPT_COOKIE,  $cookie);
		
		if($this->return_header)
		{
			curl_setopt ($this->ch, CURLINFO_HEADER_OUT, true);
			curl_setopt ($this->ch, CURLOPT_HEADER, true);
		}
		
		// Send the POST vars
		if (sizeof($this->postVars) > 0) 
		{
			$postVars = '';
			foreach($this->postVars as $name => $value) 
			{
				$postVars .= $name.'='.$value.'&';
			}
			$postVars = rtrim($postVars,'&');
			
			curl_setopt ($this->ch, CURLOPT_POSTFIELDS, $postVars);
			curl_setopt ($this->ch, CURLOPT_POST, 1);
		}
		
		// Execute and retrieve the result
		$t = '';
		while ($t == '') 
		{
			$t = curl_exec($this->ch);
		}
		
		$this->r_text		= $t;
		$this->r_headers	= curl_getinfo($this->ch,CURLINFO_HEADER_OUT);
		$this->r_cookies	.= $this->GetCookies($t).';';
		return $this->r_text;
	}
	
	
	/**
	 * Fecha a atual cURL aberta
	 */
	public function close()
	{
		return curl_close($this->ch);
	}
	
	
	/**
	 * Return the resulted text
	 * @return string
	 */
	public function getResult() 
	{
		return $this->r_text;
	}
	
	/**
	 * Return the headers
	 *
	 * @return string
	 */
	public function getHeader() 
	{
		return $this->r_headers;
	}
	
	
	/**
	 * Return the cookies
	 *
	 * @return string
	 */
	public function cookies() 
	{
		return $this->r_cookies;
	}
	
	// The U option will make sure that it matches the first character
    // So that it won't grab other information about cookie such as expire, domain and etc
	public function GetCookies($content) 
	{
		preg_match_all('/Set-Cookie: (.*)(;|\r\n)/U', $content, $temp);
		$cookie = $temp [1];
		$cook = implode('; ', $cookie);
		return $cook;
	}
	
	public function clear()
	{
		$this->postVars = array();
		$this->r_cookies = NULL;
		$this->headers = array();
		$this->r_text = NULL;
		$this->r_headers = NULL;
		$this->n = NULL;
		$this->close();
		return $this;
	}
	
}
