<?php

define('WEBDIR',dirname(__FILE__));
define('DATABASE_FILE',WEBDIR . '/.database.db');

$UpstreamDNSProviders = [
	'NordVPN' => ['103.86.96.100','103.86.99.100'],
	'Google' => ['8.8.8.8','8.8.4.4'],
	'OpenDNS Home' => ['208.67.222.222','208.67.220.220'],
	'OpenDNS FamilyShield' => ['208.67.222.123','208.67.220.123'],
	'Level3' => ['4.2.2.1','4.2.2.2'],
	'Norton ConnectSafe - Security' => ['199.85.126.10','199.85.127.10'],
	'Norton ConnectSafe - Security + Pornography' => ['199.85.126.20','199.85.127.20'],
	'Norton ConnectSafe - Security + Pornography + Other' => ['199.85.126.30','199.85.127.30'],
	'SecureDNS' => ['8.26.56.26','8.20.247.20'],
	'DNS.WATCH' => ['84.200.69.80','84.200.70.40'],
	'Quad9' => ['9.9.9.9','149.112.112.112'],
	'CloudFlare' => ['1.1.1.1','1.0.0.1'],
];

if(!file_exists(DATABASE_FILE)) {
	createDATABASEFile();
}

function UpdateSquidConf($nameserver1=null,$nameserver2=null,$access_log=false){
	if (!filter_var($nameserver1, FILTER_VALIDATE_IP)) { return false; }
	if (!filter_var($nameserver2, FILTER_VALIDATE_IP)) { return false; }
	if(file_exists('/etc/squid/smartgw.conf')) {
		$content = file_get_contents(WEBDIR . '/template/squid-smartgw.conf');
		if($content){
			$content = str_replace('%nameserver1%',$nameserver1,$content);
			$content = str_replace('%nameserver2%',$nameserver2,$content);
			if($access_log){
				$content = str_replace('#access_log none#','',$content);
			}else{
				$content = str_replace('#access_log none#','access_log none',$content);
			}
			file_put_contents('/etc/squid/smartgw.conf',$content);
			restartSquid();
		}
	}

}
function UpdateDNSMasqConf($nameserver1=null,$nameserver2=null,$access_log=false){
	if (!filter_var($nameserver1, FILTER_VALIDATE_IP)) { return false; }
	if (!filter_var($nameserver2, FILTER_VALIDATE_IP)) { return false; }
	if(file_exists('/etc/dnsmasq.d/smartgw-global.conf')) {
		$content = file_get_contents(WEBDIR . '/template/dnsmasq-smartgw-global.conf');
		if($content){
			//exec("echo '' > /etc/dnsmasq.d/smartgw-global.conf", $exeout, $return);
			$content = str_replace('%nameserver1%',$nameserver1,$content);
			$content = str_replace('%nameserver2%',$nameserver2,$content);
			if($access_log){
				exec("egrep '^log-queries'  /etc/dnsmasq.d/*", $exeout, $return);
				if ($return != 0) {
					$content = str_replace('#log-queries#',"log-queries",$content);
					$content = str_replace('#log-facility#',"log-facility",$content);
					$content = str_replace('#log-async#',"log-async",$content);
				}
			}
			file_put_contents('/etc/dnsmasq.d/smartgw-global.conf',$content);
			UpdateDNSMasqDomains();
		}
	}
	$content = file_get_contents(WEBDIR . '/template/local-dnsmasq.conf');
	$content = str_replace('%nameserver1%',$nameserver1,$content);
	$content = str_replace('%nameserver2%',$nameserver2,$content);
	file_put_contents('/etc/dnsmasq.d/local-dnsmasq.conf',$content);
	restartLocalDNS();
	
}
function UpdateSNIProxyConf($nameserver1=null,$nameserver2=null,$access_log=false){
	if (!filter_var($nameserver1, FILTER_VALIDATE_IP)) { return false; }
	if (!filter_var($nameserver2, FILTER_VALIDATE_IP)) { return false; }
	if(file_exists('/etc/sniproxy.conf')) {
		$content = file_get_contents(WEBDIR . '/template/sniproxy.conf');
		if($content){
			$content = str_replace('%nameserver1%',$nameserver1,$content);
			$content = str_replace('%nameserver2%',$nameserver2,$content);			
			if($access_log){
				$content = str_replace('#access_log#',"access_log",$content);
				$content = str_replace('#error_log#',"error_log",$content);
			}
			file_put_contents('/etc/sniproxy.conf',$content);
			restartSNIProxy();
		}
	}
}

function createDATABASEFile(){
	$sqls = [];
	$sqls[] = 'CREATE TABLE `domains` ( `id` INTEGER PRIMARY KEY AUTOINCREMENT, `domain` TEXT UNIQUE )';
	$sqls[] = 'CREATE TABLE `settings` (`id` INTEGER PRIMARY KEY AUTOINCREMENT, `name`	TEXT UNIQUE, `value`	TEXT);';
	
	$db = new SQLite3(DATABASE_FILE);
	if(!$db) {
	   echo $db->lastErrorMsg();
	   exit;
	}
	foreach($sqls as $sql){
	$ret =  $db->exec($sql);
		if(!$ret) {
			echo $db->lastErrorMsg();
			exit;
		}
	}
	$db->close();
	header('Location: settings.php?s');
	exit;
}

function settingsIsOK(){
	$db = new SQLite3(DATABASE_FILE);
	if(!$db) {
	   echo $db->lastErrorMsg();
	   exit;
	}
	$ipaddress = $db->querySingle( "SELECT value FROM settings WHERE name LIKE 'ipaddress'");
	$db->close();
	if($ipaddress==''){
		header('Location: settings.php?init=1');
		exit;
	}
	return true;
}
function UpdateDNSMasqDomains($restartDNS = true){
	$db = new SQLite3(DATABASE_FILE);
	if(!$db) {
	   echo $db->lastErrorMsg();
	   exit;
	}
	$ipaddress = $db->querySingle( "SELECT value FROM settings WHERE name LIKE 'ipaddress'");
	if($ipaddress!=''){
		$dnsmasqconf = '';
	    $ret = $db->query( 'SELECT domain FROM domains' );
	    while ( $row = $ret->fetchArray() ) {
			$dnsmasqconf .= "address=/".$row['domain']."/$ipaddress\n";
	    }
		$db->close();
		file_put_contents('/etc/dnsmasq.d/smartgw.conf',$dnsmasqconf);
		if($restartDNS) {restartDNS();}
	}else{
		$db->close();
		return FALSE;
	}
}

function restartDNS(){
	exec('ps -ef|grep pihole-FTL|grep -v grep', $exeout, $return);
	if ($return == 0) {
		exec('/usr/bin/sudo /usr/sbin/service pihole-FTL restart', $exeout, $return);	
	}
	exec('ps -ef|grep dnsmasq|grep -v grep', $exeout, $return);
	if ($return == 0) {
		exec('/usr/bin/sudo /usr/sbin/service dnsmasq restart', $exeout, $return);	
	}
}
function restartLocalDNS(){
	exec('/usr/bin/sudo /usr/sbin/service localdnsmasq restart', $exeout, $return);	
}
function restartSquid(){
	exec('/usr/bin/sudo /usr/sbin/service squid restart', $exeout, $return);	
}
function restartSNIProxy(){
	exec('/usr/bin/sudo /usr/sbin/service sniproxy restart', $exeout, $return);	
}

class Paginator {
 
     private $_conn;
     private $_limit;
     private $_page;
     private $_query;
     private $_total;
	 private $_databasefile;

	public function __construct($database_file, $count_query ) {
		$this->_databasefile = $database_file;
		
		$this->_conn = new SQLite3($this->_databasefile);
		if(!$this->_conn) {
		   echo $this->_conn->lastErrorMsg();
		   exit;
		}
	    $ret = $this->_conn->querySingle( $count_query );
	    $this->_total = $ret;
		$this->_conn->close();
	}
	
	public function getData($query, $limit = 100, $page = 1 ) {


	    $this->_query 	= $query;
	    $this->_limit   = $limit;
	    $this->_page    = $page;
 
	    if ( $this->_limit == 'all' ) {
	        $query      = $this->_query;
	    } else {
	        $query      = $this->_query . " LIMIT " . ( ( $this->_page - 1 ) * $this->_limit ) . ", $this->_limit";
	    }
		
		$this->_conn = new SQLite3($this->_databasefile);
		if(!$this->_conn) {
		   echo $this->_conn->lastErrorMsg();
		   exit;
		}
		
	    $ret             = $this->_conn->query( $query );
 	    $results = [];
	    while ( $row = $ret->fetchArray() ) {
	        $results[]  = $row;
	    }
 	    $this->_conn->close();
		
	    $result         = new stdClass();
	    $result->page   = $this->_page;
	    $result->limit  = $this->_limit;
	    $result->total  = $this->_total;
	    $result->data   = $results;
 	   
	    return $result;
	}
	
	public function createLinks( $links, $list_class ) {
	    if ( $this->_limit == 'all' ) {
	        return '';
	    }
 
	    $last       = ceil( $this->_total / $this->_limit );
 
	    $start      = ( ( $this->_page - $links ) > 0 ) ? $this->_page - $links : 1;
	    $end        = ( ( $this->_page + $links ) < $last ) ? $this->_page + $links : $last;
 
	    $html       = '<ul class="' . $list_class . '">';
 
	    $class      = ( $this->_page == 1 ) ? "disabled" : "";
	    $html       .= '<li class="' . $class . ' page-item"><a class="page-link" href="?limit=' . $this->_limit . '&page=' . ( $this->_page - 1 ) . '">&laquo;</a></li>';
 
	    if ( $start > 1 ) {
	        $html   .= '<li class="page-item"><a class="page-link" href="?limit=' . $this->_limit . '&page=1">1</a></li>';
	        $html   .= '<li class="disabled"><span>...</span></li>';
	    }
 
	    for ( $i = $start ; $i <= $end; $i++ ) {
	        $class  = ( $this->_page == $i ) ? "active" : "";
	        $html   .= '<li class="' . $class . ' page-item"><a class="page-link" href="?limit=' . $this->_limit . '&page=' . $i . '">' . $i . '</a></li>';
	    }
 
	    if ( $end < $last ) {
	        $html   .= '<li class="disabled page-item"><span>...</span></li>';
	        $html   .= '<li class="page-item"><a class="page-link" href="?limit=' . $this->_limit . '&page=' . $last . '">' . $last . '</a></li>';
	    }
 
	    $class      = ( $this->_page == $last ) ? "disabled" : "";
	    $html       .= '<li class="' . $class . ' page-item"><a class="page-link" href="?limit=' . $this->_limit . '&page=' . ( $this->_page + 1 ) . '">&raquo;</a></li>';
 
	    $html       .= '</ul>';
 
	    return $html;
	}
}