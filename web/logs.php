<?php
require_once 'init.php';
settingsIsOK();

if(isset($_GET['action']) ){

	if($_GET['action']=='sniproxy_access'){
		$logfile = '/var/log/sniproxy-access.log';
	}elseif($_GET['action']=='dnsmasq'){
		$logfile = '/var/log/sniproxy-error.log';
	}elseif($_GET['action']=='dnsmasq'){
		$logfile = '/var/log/dnsmasq-queries.log';
	}elseif($_GET['action']=='squid'){
		$logfile = '/var/log/squid/access.log';
	}else{
		$logfile = '/var/log/syslog';
	}

	$exeout = [];
	exec('/usr/bin/sudo /usr/bin/tail -n 300 ' . $logfile, $exeout, $return);
	if ($return == 0) {
		print implode("<br/>\n",$exeout);
	}else{
		echo 'Not able to access file ' . $logfile . "<br>\nReturn:";
		print_r($return);
	}
	exit;
}

require_once 'header.php';
?>
<h2>Logs</h2> 
<a href="logs.php?action=sniproxy_access" target="_blank" class="btn btn-info btn-block">SNIProxy access log</a>
<a href="logs.php?action=sniproxy_error" target="_blank" class="btn btn-info btn-block">SNIProxy error log</a>
<a href="logs.php?action=dnsmasq" target="_blank" class="btn btn-info btn-block">DNS query log</a>
<a href="logs.php?action=squid" target="_blank" class="btn btn-info btn-block">Squid access log</a>
<a href="logs.php?action=syslog" target="_blank"  class="btn btn-info btn-block">Sys log</a>
<?php require_once 'footer.php';?>
