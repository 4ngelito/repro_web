<?php

namespace aayala\reproWeb;

use aayala\reproWeb\Motor;

class Reproduccion extends Motor {
	/**
	 * @var Reproduccion
	 */
	private $anterior;
	/**
	 * @var Cancion
	 */
	private $cancion;
	/**
	 * @var integer timestamp
	 */
	private $hora;
	
	public function __construct($cancion, $hora = null){
		$this->anterior = null;
		$this->cancion = $cancion;
		if($hora != null){
			$this->hora = $hora;
		}
		else {
			$this->hora = time();
		}	
	}
	
	/**
	 * inserta en la bd como cancion reproducida
	 */
	public function guardar(){
		//parent::iniciarBD('Reproducidas.db');
		$sql = "CREATE TABLE IF NOT EXISTS `reproducidas` (
			  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
			  `cancion` VARCHAR(150) NOT NULL DEFAULT 'NULL',
			  `id_artista` INTEGER NOT NULL DEFAULT NULL,
			  `artista_img` TEXT NOT NULL DEFAULT 'NULL',
			  `hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
			);";
		parent::$bd->execute($sql);
		$count = parent::$bd->countQuery()
			->table('reproducidas')
			->execute();
		if($count > 0){
			$sql = "SELECT * FROM reproducidas
					ORDER BY hora DESC
					LIMIT 0, 1;";
			$res = parent::$bd->execute($sql);
			$r = $res->current();
			$artistaE = new Artista((int)$r->id_artista);
			$cancionE = new Cancion($r->cancion, $artistaE);
			
			if(strtolower($cancionE->getNombre()) != strtolower($this->cancion->getNombre())){
				$artista = $this->cancion->getArtista();
				$query = parent::$bd->insertQuery();
				$query->table('reproducidas')
					->data(array(
							'cancion' => $this->cancion->getNombre(),
							'id_artista' => $artista->getId(),
							'artista_img' => $artista->getImgUrl(),
							'hora' => $this->hora
					))
					->execute();
			}
		}
		else{
			$artista = $this->cancion->getArtista();
			$query = parent::$bd->insertQuery();
			$query->table('reproducidas')
				->data(array(
						'cancion' => $this->cancion->getNombre(),
						'id_artista' => $artista->getId(),
						'artista_img' => $artista->getImgUrl(),
						'hora' => $this->hora
				))
				->execute();
		}
	}
	
	/**
	 * settea las ultimas $n canciones reproducidas en anterior
	 * @param number $n
	 */
	public function setUltimas($n=5){
		//parent::iniciarBD('Reproducidas.db');
		$sql = "CREATE TABLE IF NOT EXISTS `reproducidas` (
			  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
			  `cancion` VARCHAR(150) NOT NULL DEFAULT 'NULL',
			  `id_artista` INTEGER NOT NULL DEFAULT NULL,
			  `artista_img` TEXT NOT NULL DEFAULT 'NULL',
			  `hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
			);";
		parent::$bd->execute($sql);
		$count = parent::$bd->countQuery()
			->table('reproducidas')
			->execute();
		if($count > 0){
			$sql = "SELECT * FROM reproducidas
					ORDER BY hora DESC
					LIMIT 0, {$n};";
			$res = parent::$bd->execute($sql);
			$act = $this;
			$primero = true;
			foreach($res as $r){
				if(!$primero){
					$artista = new Artista((int)$r->id_artista);
					$cancion = new Cancion($r->cancion, $artista);
					$hora = $r->hora;
					$ant = new Reproduccion($cancion, $hora);
					$act->setAnterior($ant);
					$act = $ant;
				}
				$primero = false;
			}
		}
		return $this;
	}
	
	/**
	 * obtiene las ultimas $n canciones reproducidas
	 * @param number $n
	 * @return array arreglo con lo ultimo reproducido
	 */
	public static function getUltimas($n=5){
		$ultimas = null;
		//parent::iniciarBD('Reproducidas.db');
		$sql = "CREATE TABLE IF NOT EXISTS `reproducidas` (
			  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
			  `cancion` VARCHAR(150) NOT NULL DEFAULT 'NULL',
			  `id_artista` INTEGER NOT NULL DEFAULT NULL,
			  `artista_img` TEXT NOT NULL DEFAULT 'NULL',
			  `hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
			);";
		parent::$bd->execute($sql);
		$count = parent::$bd->countQuery()
			->table('reproducidas')
			->execute();
		if($count > 0){
			$sql = "SELECT * FROM reproducidas
					ORDER BY hora DESC;";
			$res = parent::$bd->execute($sql);
			foreach($res as $r){
				$artista = new Artista((int)$r->id_artista);
				$cancion = new Cancion($r->cancion, $artista);
				$hora = $r->hora;
				$ultimas[] = array('cancion' => $cancion->toArray(),
						'hora' => date('G:i',$hora)
				);
			}
		}
		return $ultimas;
	}
	public function getAnterior() {
		return $this->anterior;
	}
	public function getCancion() {
		return $this->cancion;
	}
	public function getHora() {
		return $this->hora;
	}
	
	public function setAnterior($anterior){
		$this->anterior = null;
		$this->anterior = $anterior;
		return $this;
	}
	
}