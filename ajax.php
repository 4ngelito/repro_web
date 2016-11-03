<?php
//header('Content-Type: application/json');
require __DIR__ . '/vendor/autoload.php';
use aayala\reproWeb\Motor;

$r['status'] = 'error';
$r['content'] = 'Error en la Consulta.';
if(isset($_GET['q'])){
	$q = $_GET['q'];
}
if(isset($q)){
	$m = new Motor();
	if($q == 'en-vivo'){
		if($m->getCancionShoutcast()){
			$r['status'] = 'ok';
			$r['content'] = $m->getCurrentsong()->toArray();
		}
		else{
			$r['content'] = $m->getErr()['string'];
		}
	}
	else if ($q == 'reproducidos'){
		$ultimas = $m->getUltimasReproducidas();
		if(count($ultimas)>0){
			$r['status'] = 'ok';
			$r['content'] = $ultimas;
		}
	}
}
echo json_encode($r);


