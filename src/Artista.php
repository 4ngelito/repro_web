<?php

namespace aayala\reproWeb;

class Artista extends Motor {
	/**
	 * @var string $nombre_Std
	 */
	public $nombre_std;
	/**
	 * @var integer
	 */
	private $id;
	/**
	 * @var string $nombre
	 */
	private $nombre;
	/**
	 * @var string $imgUrl
	 */
	private $imgUrl;
	/**
	 * @var boolean $registrado
	 */
	private $registrado;
	
	public function __construct($arg= null){
		$tipo = gettype($arg);
		$this->imgUrl = null;
		$this->registrado = false;
		if($arg!=null && $tipo == "integer" && $arg!=0){
			$this->buscar('id', $arg);
		}
		else if($arg!=null && $tipo == "string"){
			$this->buscar('nombre',$arg);
			if(!isset($this->id) && $this->id != 0){
				$this->buscar('nombre_std',$arg);
			}			
		}
	}
	
	/**
	 * buscar a artista dentro de la bd y en discogs según campo indicado
	 * @param string $tipo 'id', 'nombre' o 'nombre_std'
	 * @param mixed $val valor a buscar
	 */
	public function buscar($tipo, $val){
		switch ($tipo){
			case "id":
				$this->queryArtista('id', '=', $val);
			break;
			case "nombre":
				$this->queryArtista('nombre', 'like', '%'.$val.'%');
			break;
			case "nombre_std":
				$val = self::setnombre_std($val);
				$this->queryArtista('nombre_std', 'like', $val);
			break;
		}
		if(!$this->registrado){
			//genera la busqueda
			$response = parent::$client->search([
					'q' => $val,
					'type' => 'artist'
			]);
			var_dump($response);
			$resultado = $response['results'][0];
			$this->id = $resultado['id'];
			$this->nombre = $resultado['title'];
			$this->nombre_std = self::setnombre_std($this->nombre);
			$this->setImagen();
			$this->guardar();
				
			$this->setRegistrado();
			
		}
		return $this;
	}
	
	/**
	 * busca y descarga en la bd de discogs las imagenes y la settea al Artista
	 */
	public function setImagen(){
		if($this->imgUrl == null){
			//busca las imagenes del artista
			$artist = parent::$client->getArtist([
					'id' => $this->id
			]);
			$imagenes = $artist['images'];
			if(count($imagenes)>0){
				$imgArtista = null;
				$ratios = null;
				//busca la imagen que sea más cuadrada ratio 1:1
				foreach ($imagenes as $imagen){
					$prop1 = $imagen['width'] / $imagen['height'];
					$prop2 = $imagen['height'] / $imagen['width'];
					$resta = abs($prop1 - $prop2);
					$ratios[] = $resta;
					$imgArtista[strval($resta)] = $imagen;
				}
				$imgArtista = $imgArtista[strval(parent::getClosest(0, $ratios))];
					
				$imgUrl = $this->id.'-'.urlencode($this->nombre).'.jpg';
					
				parent::obtenerImagenUrl($imgArtista['uri'], parent::$artistFolder . $imgUrl);
				parent::obtenerImagenUrl($imgArtista['uri150'], parent::$artistFolder. '150x150/' . $imgUrl);
				
				$this->imgUrl = $imgUrl;
				return true;
			}
			return false;
		}
		return true;
	}
	
	public function getId() {
		return $this->id;
	}
	public function getNombre() {
		return $this->nombre;
	}
	public function setNombre($nombre) {
		$this->nombre = $nombre;
		return $this;
	}
	public function getImgUrl() {
		return $this->imgUrl;
	}
	/**
	 * genera nombre estandar del artista 
	 * @param string $nombre
	 */
	public static function setnombre_std($nombre){
		$nuevo = urlencode($nombre);
		return $nuevo;
	}
	
	public function getValores(){
		return array($this->id, $this->nombre, $this->nombre_std, $this->imgUrl, $this->registrado);
	}
	
	public function toArray(){
		return array('id' => $this->id,
				'nombre' => $this->nombre,
				'nombre_std' => $this->nombre_std,
				'imgUrl' => $this->imgUrl
		);
	}
	public function isRegistrado() {
		return $this->registrado;
	}
	public function setRegistrado() {
		$this->registrado = true;
		return $this;
	}
	public function __toString(){
		return $this->nombre;
	}
	
	/**
	 * busca al artista según columna y comparacion en la BD
	 * @param string $column columna
	 * @param string $cmp comparacion logica
	 * @param string $q valor
	 * @return boolean false en caso de fracaso
	 */
	private function queryArtista($column, $cmp, $q){
		//parent::iniciarBD('Artista.db');
		$sql = "CREATE TABLE IF NOT EXISTS `artista` (
			  `id` INTEGER NULL DEFAULT NULL,
			  `nombre` VARCHAR(150) NOT NULL DEFAULT 'NULL',
			  `nombre_std` VARCHAR(150) NOT NULL DEFAULT 'NULL',
			  `imgUrl` MEDIUMTEXT NULL DEFAULT NULL,
			  PRIMARY KEY (`id`)
			);";
		parent::$bd->execute($sql);
		$count = parent::$bd->countQuery()
			->table('artista')
			->where($column, $cmp, $q)
			->execute();
		if($count > 0){
			$query = parent::$bd->selectQuery();
			$res = $query->table('artista')
				->where($column, $cmp, $q)
				->execute();
			$r = $res->current();
			$this->id = (int)$r->id;
			$this->nombre = $r->nombre;
			$this->nombre_std = $r->nombre_std;
			if($r->imgUrl != null){
				$this->imgUrl = $r->imgUrl;
			}
			else{
				$this->setImagen();
			}
			$this->setRegistrado();
			return $this;
		}
		return false;
	}
	
	/**
	 * inserta en la bd el artista
	 * @return bool true en caso de exito
	 */
	private function guardar(){
		if(!$this->registrado){
			$query = parent::$bd->insertQuery()->table('artista');
			$query->data($this->toArray())->execute();
			return true;
		}
		return true;
	}
	
	/**
	 * Actualiza la informacion del artista en la bd
	 */
	private function actualizar(){
		if($this->registrado){
			$query = parent::$bd->updateQuery()->table('artista');
			$query->data($this->toArray())
			->where('id', $this->id)
			->execute();
			return true;
		}
		return false;
	}
	
}