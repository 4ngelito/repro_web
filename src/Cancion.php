<?php

namespace aayala\reproWeb;

class Cancion {
	/**
	 * @var string
	 */
	private $nombre;
	/**
	 * @var Artista $artista
	 */
	private $artista;
	
	public function __construct($nombre, $artista){
		$this->nombre = $nombre;
		$this->artista = $artista;
	}
	public function getNombre() {
		return $this->nombre;
	}
	public function setNombre($nombre) {
		$this->nombre = $nombre;
		return $this;
	}
	public function getArtista() {
		return $this->artista;
	}
	public function setArtista($artista) {
		$this->artista = $artista;
		return $this;
	}
	public function __toString(){
		return $this->nombre . ' - ' . $this->artista;
	}
	public function toArray(){
		return array('nombre' => $this->nombre,
				'artista' => $this->artista->toArray()
		);
	}
	
}