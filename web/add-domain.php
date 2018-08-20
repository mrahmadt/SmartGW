<?php
require_once 'init.php';
settingsIsOK();
if(isset($_POST['domains']) && $_SERVER['REQUEST_METHOD']  == 'POST'){
	$domainsPost = explode("\n",$_POST['domains']);
	$domains = [];
	foreach($domainsPost as $domain){
		$domain = str_replace(['http://','https://'],'',$domain);
		if(substr($domain,-1) == '/'){
			$domain = substr($domain,0,-1);
		}
		$domain = trim($domain);
		if($domain!=''){
			$domains[] = $domain;
		}
	}
	
	$db = new SQLite3(DATABASE_FILE);
	if(!$db) {
	   echo $db->lastErrorMsg();
	   exit;
	}
	
	
	$stmt = $db->prepare('INSERT INTO domains (domain) VALUES(:domain)');

	foreach($domains as $domain){
		$stmt->bindValue(':domain', $domain);
		$stmt->execute();
		//$ret =  $db->exec("INSERT INTO domains (domain) VALUES('".addslashes($domain)."')");
	    //if(!$ret) {
	    //   echo $db->lastErrorMsg();
	    //}
	}
    $db->close();
	
	UpdateDNSMasqConf();
	
	header('Location: domains.php');
	exit;
}



if(isset($_GET['bulk']) && $_GET['bulk']=='1'){
	addbulkForm();	
}
addSimpleForm();


function addSimpleForm(){
require_once 'header.php'
?>
<h2>Add Domain <span class="float-right"> <a href="add-domain.php?bulk=1" class=" btn btn-primary btn-sm">Bulk</a></span></h2> 
<form action="add-domain.php" method="post">
  <div class="form-group">
    <label for="InputDomain">Domain</label>
    <input type="text" class="form-control" id="DomainFormControlInput" name="domains"  placeholder="youtube.com">
  </div> <button type="submit" class="btn btn-primary mb-2">Submit</button>
</form>
<?php
require_once 'footer.php';
exit;
}



function addbulkForm(){
require_once 'header.php'
?>
<h2>Add Domains <span class="float-right"><a href="add-domain.php" class=" btn btn-primary btn-sm">Simple</a></span></h2> 
<form action="add-domain.php" method="post">
  <div class="form-group">
    <label for="FormControlTextarea">Domain(s)</label>
    <textarea class="form-control" id="DomainFormControlTextarea" name="domains" rows="6"></textarea>
  </div>
    <button type="submit" class="btn btn-primary mb-2">Submit</button>
</form>
<?php
require_once 'footer.php';
exit;
}

