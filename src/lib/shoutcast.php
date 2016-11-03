<?php
/***************************************************************************
Shoutcast Server Information Class

Developed by Cristián Pérez
Version 2.2

Release 15/09/2010

http://www.cristianperez.com/

Feel free to use this code without restrictions
****************************************************************************/

class Shoutcast
{
	
	/**
	 * @var string Server Host
	 **/
	private $server_host;
	
	
	
	/**
	 * @var string Server Port
	 **/
	private $server_port;
	
	
	
	/**
	 * @var integer Connection Time Out
	 **/
	private $conn_timeout;
	
	
	
	/**
	 * @var string Administrator Username
	 **/
	private $adm_username;
	
	
	
	/**
	 * @var string Administrator Password
	 **/
	private $adm_password;
	
	
	
	/**
	 * @var resource Connection Handler
	 **/
	private $fp;
	
	
	
	/**
	 * @var array User Vars
	 **/
	private $vars;
	
	
	
	/**
	 * @var bool Work in admin mode
	 **/
	private $admin_mode = false;
	
	
	
	/**
	 * Constructor
	 * 
	 * @access public
	 * @return void
	 * 
	 **/
	public function __construct ($server_host, $server_port, $adm_username = null, $adm_password = null, $conn_timeout = 10)
	{
		
		/* Check Data */
		if ( empty ($server_host) || empty ($server_port) )
			die ('Error: Server Host and Server Port are needed');
		
		/* Both are needed */
		if ( ! is_string ($adm_username) xor ! is_string ($adm_password) )
			die("Error: Please complete administrator username and password");
		
		/* If Admin mode */
		if ( is_string ($adm_username) && is_string ($adm_password) )
		{
			$this->adm_username = $adm_username;
			$this->adm_password = $adm_password;
			$this->admin_mode = true;
		}
		
		/* Set data */
		$this->server_host = $server_host;
		$this->server_port = $server_port;
		$this->conn_timeout = $conn_timeout;
		
		/* Connect to server */
		$this->trace_connection();
		
		if ( $this->server_online() )
			$this->parse_data();
	}
	
	
	
	/**
	 * Destruct
	 * 
	 * @access public
	 * @return void
	 *
	 * Close handler
	 * 
	 **/
	public function __destruct()
	{
		if ( is_resource ($this->fp) )
			fclose ($this->fp);
	}
	
	
	
	/**
	 * Trace Connection
	 * 
	 * @access private
	 * @return void
	 * 
	 **/
	private function trace_connection()
	{
		$this->fp = @fsockopen ($this->server_host, $this->server_port, $errno, $errstr, $this->conn_timeout);
	}
	
	
	
	/**
	 * Select the parser to use
	 * 
	 * @access private
	 * @return void
	 * 
	 **/
	private function parse_data()
	{
		if ( true === $this->admin_mode )
			$this->admin_parser();
		
		if ( false === $this->admin_mode )
			$this->simple_parser();
	}
	
	
	
	/**
	 * Parse simple data into vars
	 * 
	 * @access private
	 * @return void
	 * 
	 **/
	private function simple_parser()
	{
		fputs ($this->fp, "GET /7.html HTTP/1.0\r\nUser-Agent: SC Status (Mozilla Compatible)\r\n\r\n");
		
		$plain_txt = '';
		
		//Buffering data
		while ( ! feof ($this->fp) )
			$plain_txt .= @fgets( $this->fp, 1024 );
		
		preg_match ("/<body>(.*)<\/body>/", $plain_txt, $matches);
		
		$vars = explode (',', $matches[1], 7); //limit 7 (because there are 7 variables)
		
		//Save Data
		$this->vars['CURRENT_LISTENERS'] 	= $vars[0];
		$this->vars['STATION_STATUS'] 		= $vars[1];
		$this->vars['LISTENERS_PEAK'] 		= $vars[2];
		$this->vars['LISTENERS_LIMIT'] 		= $vars[3];
		$this->vars['UNIQUE_LISTENERS'] 	= $vars[4];
		$this->vars['BITRATE'] 			= $vars[5];
		$this->vars['CURRENT_SONG'] 		= $vars[6];
	}
	
	
	
	/**
	 * Parse simple and admin data into vars
	 * 
	 * @access private
	 * @return void
	 * 
	 **/
	private function admin_parser()
	{
		fputs($this->fp, "GET /admin.cgi?mode=viewxml HTTP/1.0\r\n");
		fputs($this->fp, "User-Agent: Mozilla\r\n");
		fputs($this->fp, "Authorization: Basic " . base64_encode ($this->adm_username . ":" . $this->adm_password) . "\r\n");
		fputs($this->fp, "\r\n");
		
		$plain_txt = '';
		
		//Buffering data
		while ( ! feof ($this->fp) )
			$plain_txt .= @fgets( $this->fp, 1024 );
		
		preg_match ("/<SHOUTCASTSERVER>(.*)<\/SHOUTCASTSERVER>/", $plain_txt, $matches);
		
		$xml = @simplexml_load_string ($matches[0]);
		
		if ( ! is_object ($xml) )
		{
			$this->vars['STATION_STATUS'] = 0;
			return;
		}
		
		$data = self::simplexml_to_array($xml); //To array;
		
		//Save data: simple
		$this->vars['CURRENT_LISTENERS'] 	= $data['CURRENTLISTENERS'];
		$this->vars['STATION_STATUS'] 		= $data['STREAMSTATUS'];
		$this->vars['LISTENERS_PEAK'] 		= $data['PEAKLISTENERS'];
		$this->vars['LISTENERS_LIMIT'] 		= $data['MAXLISTENERS'];
		$this->vars['UNIQUE_LISTENERS'] 	= $data['REPORTEDLISTENERS'];
		$this->vars['BITRATE'] 			= $data['BITRATE'];
		$this->vars['CURRENT_SONG'] 		= $data['SONGTITLE'];
		
		//Save data: admin
		$this->vars['STATION_GENRE'] 		= $data['SERVERGENRE'];
		$this->vars['STATION_URL'] 			= $data['SERVERURL'];
		$this->vars['STATION_TITLE'] 		= $data['SERVERTITLE'];
		$this->vars['IRC'] 				= $data['IRC'];
		$this->vars['ICQ'] 				= $data['ICQ'];
		$this->vars['AIM'] 				= $data['AIM'];
		$this->vars['CONTENT_TYPE'] 		= $data['CONTENT'];
		$this->vars['SERVER_VERSION'] 		= $data['VERSION'];
		
		//Save song history
		if(isset($data['SONGHISTORY']['SONG']['TITLE'])) {
			$tmp_data = $data['SONGHISTORY'];
		} else {
			$tmp_data = $data['SONGHISTORY']['SONG'];
		}
		
		$song_history = array();
		foreach ( (array)$tmp_data as $song )
		{
			$song_history[] = array (
				"TIMESTAMP" => intval ($song['PLAYEDAT']), 
				"TITLE" => $song['TITLE']
			);
		}
		
		//Save listeners list
		if(isset($data['LISTENERS']['LISTENER']['HOSTNAME'])) {
			$tmp_data = $data['LISTENERS'];
		} else {
			$tmp_data = $data['LISTENERS']['LISTENER'];
		}
		
		$listeners = array();
		foreach ( (array)$tmp_data as $listener )
		{
			$listeners[] = array (
				"HOST" => $listener['HOSTNAME'], 
				"PLAYER" => $listener['USERAGENT'],
				"UNDER_RUNS" => $listener['UNDERRUNS'],
				"CONNECT_TIME" => $listener['CONNECTTIME'],
				"POINTER" => $listener['POINTER'],
				"UID" => $listener['UID']
			);
		}
		
		//here continue vars
		$this->vars['SONG_HISTORY'] 		= $song_history;
		$this->vars['LISTENERS'] 			= $listeners;
	}
	
	
	
	/**
	 * Check if server is offline or not!
	 * 
	 * @access public
	 * @return bool If server online
	 * 
	 **/
	public function server_online()
	{
		return is_resource ($this->fp);
	}
	
	
	
	/**
	 * Check is admin mode is actived
	 * 
	 * @access public
	 * @return bool If active mode actived
	 * 
	 **/
	public function admin_mode()
	{
		return $this->admin_mode;
	}
	
	
	
	/**
	 * Get a var value
	 * 
	 * @access public
	 * @return mixed
	 * 
	 **/
	public function get ($var_name)
	{
		if ( isset ($this->vars[$var_name]) )
			return $this->vars[$var_name];
		else
			return '';
	}

	public static function simplexml_to_array($object) {
		if(!is_object($object) && !is_array($object)) {
			return $object;
		}

		if(is_object($object)) {
			$object = get_object_vars($object);
		}
		
		if(count($object) === 0) {
			return '';
		}

		return array_map(array(__CLASS__, 'simplexml_to_array'), $object);
	}
	
}

?>