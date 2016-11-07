<?php

namespace aayala\reproWeb;

use Discogs;
use PHPixie\Database\Driver\PDO\Connection;
use GuzzleHttp\Command\Guzzle\GuzzleClient;

include 'lib/shoutcast.php';

class Motor {
	protected static $dbFolder;
	protected static $artistFolder;
	/**
	 * @var Connection
	 */
	protected static $bd;
	/**
	 * @var GuzzleClient
	 */
	protected static $client;
	/**
	 * @var array errores
	 */
	protected $err;
	/**
	 * @var Reproduccion
	 */
	private $played;
	/**
	 * @var Cancion
	 */
	private $currentSong;
	
	public function __construct(){
		self::$client = Discogs\ClientFactory::factory([
			'defaults' => [
				'headers' => [
					'User-Agent' => 'aayala-ReproWeb/0.1 +http://www.actuimagen.cl',
					'Authorization' =>'Discogs key=, secret=']
			]
		]);
		self::$client->getHttpClient()->getEmitter()->attach(new Discogs\Subscriber\ThrottleSubscriber());
		self::$dbFolder = dirname(__FILE__)."/db/";
		self::$artistFolder = dirname(__FILE__)."/img/artista/";
		self::iniciarBD('Reproductor.db');
		$this->currentSong = null;
		$this->played = null;
		$this->err = null;
	}
		
	public function getCurrentsong(){
		return $this->currentSong;
	}
	
	/**
	 * Obtiene la cancion que está sonando actualmente desde el servidor
	 * @return boolean true en caso de exito
	 */
	public function getCancionShoutcast() {
		//$s = new \Shoutcast("69.46.75.67", "80");
		$s = new \Shoutcast("167.114.119.122", "9972");
		
		if ( ! $s->server_online() ){
			$this->err['type'] = 'server';
			$this->err['string'] = 'Server offline';
			return false;
		}		
		else{
			if ( 0 == $s->get('STATION_STATUS') ){
				$this->err['type'] = 'transmition';
				$this->err['string'] = 'Transmition off';
				return false;
			}
			else{
				$current = $s->get('CURRENT_SONG');
				echo $current;
				//$current = 'clint eastwood - gorillaz';
				$current = explode("-", $current);
				if(count($current)>1){
					$currentCancion = trim($current[1]);
					$currentArtista = trim($current[0]);
					$artista = new Artista($currentArtista);
					$cancion = new Cancion($currentCancion, $artista);
					$this->setCurrentSong($cancion);
					return true;
				}
				$this->err['type'] = 'cancion';
				$this->err['string'] = 'Nombre de cancion invalido';
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Settea la canción que está sonando actualmente
	 * @param Cancion $currentSong
	 */
	public function setCurrentSong($currentSong) {
		if($this->currentSong == null){
			$this->currentSong = $currentSong;
			$this->played = new Reproduccion($currentSong);
			$this->played->guardar();
			$this->played->setUltimas();
		}
		else{
			if(strtolower($this->currentSong->getNombre()) != strtolower($currentSong->getNombre())){
				$nueva = new Reproduccion($currentSong);
				$anterior = $this->played;
				$this->played = $nueva;
				$this->played->guardar();
				if($this->played == null){
					$this->played->setUltimas();
				}
				else{
					$this->played->setAnterior($anterior);
				}
			}
		}		
		return $this;
	}	

	public function getPlayed() {
		return $this->played;
	}
	
	/**
	 * Setea la carpeta donde está almacenada la BD sqlite
	 * @param string $dbFolder
	 * @return \aayala\reproWeb\Motor
	 */
	public function setDbFolder($dbFolder) {
		$this->dbFolder = $dbFolder;
		return $this;
	}
	
	/**
	 * Settea la carpeta donde se almacenan las imagenes
	 * @param string $imgFolder
	 */
	public function setImgFolder($imgFolder) {
		$this->imgFolder = $imgFolder;
		return $this;
	}
	
	/**
	 * Settea la carpeta donde se almacenan las miniaturas de las imagenes
	 * @param string $thumbFolder
	 */
	public function setThumbFolder($thumbFolder) {
		$this->thumbFolder = $thumbFolder;
		return $this;
	}
	
	/**
	 * Obtiene y guarda una copia local de la imagen(url) indicado
	 * @param string $url
	 * @param string $saveto : ruta donde guardar la imagen
	 */
	public static function obtenerImagenUrl($url, $saveto){
		$ch = curl_init ($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_USERAGENT, "aayala-ReproWeb/0.1 +http://www.actuimagen.cl");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
		$raw=curl_exec($ch);
		curl_close ($ch);
		if(file_exists($saveto)){
			unlink($saveto);
		}
		$fp = fopen($saveto,'x');
		fwrite($fp, $raw);
		fclose($fp);		
		return true;
	}
	
	/**
	 * función para obtener el número más cercano al buscado
	 * @param integer $search numero buscado
	 * @param array $arr arreglo a comparar
	 */
	public static function getClosest($search, $arr) {
	   $closest = null;
	   foreach ($arr as $item) {
	      if ($closest === null || abs($search - $closest) > abs($item - $search)) {
	         $closest = $item;
	      }
	   }
	   return $closest;
	}
	
	/**
	 * inicializa las variables para trabajar con la bd de sqlite
	 */
	public static function iniciarBD($archivo){
		$slice = new \PHPixie\Slice();
		$database = new \PHPixie\Database($slice->arrayData(array(
				'default' => array(
						'driver' => 'pdo',
						'connection' => 'sqlite:'.self::$dbFolder.$archivo
				)
		)));
		self::$bd = $database->get('default');
	}
	
	public function getUltimasReproducidas($n = 5){
		return Reproduccion::getUltimas($n);
	}
	
	public function getErr(){
		return $this->err;
	}
	
	
}
