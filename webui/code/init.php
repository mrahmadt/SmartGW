<?php

define('WEBDIR',dirname(__FILE__));
define('DATABASE_FILE',WEBDIR . '/database.db');

if(!file_exists(DATABASE_FILE)) {
	createDATABASEFile();
}

function createDATABASEFile(){
	$sqls = [];
	$sqls[] = 'CREATE TABLE `domains` ( `id` INTEGER PRIMARY KEY AUTOINCREMENT, `domain` TEXT UNIQUE, `ipaddress` TEXT  )';
	
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
	exit;
}
function UpdateDNSMasqDomains($restartDNS = true){
	$db = new SQLite3(DATABASE_FILE);
	if(!$db) {
	   echo $db->lastErrorMsg();
	   exit;
	}
	$dnsmasqconf = '';
	$ret = $db->query( 'SELECT * FROM domains' );
	while ( $row = $ret->fetchArray() ) {
		$dnsmasqconf .= "address=/".$row['domain']."/".$row['ipaddress']."\n";
	}
	$db->close();
	file_put_contents('/etc/dnsmasq.d/smartgw.conf',$dnsmasqconf);
	if($restartDNS) {restartDNS();}
}

function restartDNS(){
	/*exec('ps -ef|grep pihole-FTL|grep -v grep', $exeout, $return);
	if ($return == 0) {
		exec('/usr/bin/sudo /usr/sbin/service pihole-FTL restart', $exeout, $return);	
	}else{
		exec('ps -ef|grep dnsmasq| grep -v local-dnsmasq.conf | grep -v grep', $exeout, $return);
		if ($return == 0) {
			exec('/usr/bin/sudo /usr/sbin/service dnsmasq restart', $exeout, $return);	
		}
	}*/
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