<?php
if(!isset($_GET['service'])) printOut(['error'=>true]);
$service = trim($_GET['service']);
if($service=='') printOut(['error'=>true]);

$output = ['error'=>false];
$process = null;
if($service == 'dns'){
	$process = 'dnsmasq';
}elseif($service == 'sniproxy'){
	$process = 'sniproxy';
}elseif($service == 'squid'){
	$process = 'squid';
}elseif($service == 'web'){
	$process = 'lighttpd';
}elseif($service == 'openpyn'){
	$process = 'openpyn';
}elseif($service == 'pihole'){
	$process = 'pihole-FTL';
}elseif($service == 'apache2'){
	$process = 'apache2';
}
  
if($process != null){
	exec('ps -ef|grep '.$process.'|grep -v grep', $exeout, $return);
	if ($return == 0) {
		$output['service'] = ['name'=>$service,'status'=>true,'output'=>$exeout];
	}else{
		$output['service'] = ['name'=>$service,'status'=>false,'output'=>$exeout];
	}
}else{
	$output = ['error'=>true];
}

printOut($output);

function printOut($output){
  header('Content-Type: application/json');
  print json_encode($output);
  exit;
}
