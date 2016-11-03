<?php
require __DIR__ . '/vendor/autoload.php';
use aayala\reproWeb\Motor;
use aayala\reproWeb\Artista;
use aayala\reproWeb\Cancion;

$motor = new Motor();
$artista = new Artista('alice in chains');
$cancion = new Cancion('man in the box', $artista);
$motor->setCurrentSong($cancion);
var_dump($motor->getPlayed());
echo 'ultimas';
var_dump($motor->getUltimasReproducidas());
$motor->getCurrentSong();





