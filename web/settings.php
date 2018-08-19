<?php
require_once 'init.php';
$db = new SQLite3(DATABASE_FILE);
if(!$db) {
   echo $db->lastErrorMsg();
   exit;
}

$ret = $db->query('SELECT * FROM settings');
while ( $row = $ret->fetchArray() ) {
    $settings[$row['name']]  = ['id'=>$row['id'],'value'=>$row['value']];
}
$db->close();

if($_SERVER['REQUEST_METHOD']  == 'POST'){
	$db = new SQLite3(DATABASE_FILE);
	if(!$db) {
	   echo $db->lastErrorMsg();
	   exit;
	}
	$stmtInsert = $db->prepare('INSERT INTO settings (name,value) VALUES(:name,:value)');
	$stmtUpdate = $db->prepare('UPDATE settings SET value=:value WHERE name LIKE :name');
	foreach($_POST as $name=>$value){
		$value = trim($value);
		if(isset($settings[$name])){
			$stmtUpdate->bindValue(':name', $name);
			$stmtUpdate->bindValue(':value',$value);
			$stmtUpdate->execute();
			$settings[$name]['value'] = $value;
		}else{
			$stmtInsert->bindValue(':name', $name);
			$stmtInsert->bindValue(':value',$value);
			$stmtInsert->execute();
		}
	}
    $db->close();
	
	UpdateDNSMasqConf();
	if(isset($settings['vpncountrycode'])){
		$exeout = [];
		exec('/usr/bin/sudo /usr/local/bin/openpyn -d '. $settings['vpncountrycode']['value'], $exeout, $return);
	}

}



$exeout = [];
exec('/bin/bash listinterfaces.sh', $exeout, $return);
$interfaces = [];
if ($return == 0) {
	foreach ($exeout as $row){
		$interface = explode(',',$row);
		$interfaces[trim($interface[0])] = trim($interface[1]);
	}
}else{
	echo '<h3>ERROR</h3>';
	print_r($return);
	exit;
}

$exeout = [];
exec('openpyn -l', $exeout, $return);

$vpncountries = [];
if ($return == 0) {
	foreach ($exeout as $row){
		$row = explode('      ',$row);
		$country = explode(':',$row[0]);
		$country = trim($country[1]);
		$countrycode = explode(':',$row[1]);
		$countrycode = trim($countrycode[1]);
		$vpncountries[trim($country)] = trim($countrycode);
	}
}else{
	echo '<h3>ERROR</h3>';
	print_r($return);
	exit;
}






require_once 'header.php';
?>
<h2>Settings</h2>
<form action="settings.php" method="post">
  <div class="form-group">
    <label for="interfaceinput">Choose An Interface</label>
    <select class="form-control" id="interfaceinput" name=ipaddress>
<?php foreach($interfaces as $intname => $intvalue){ ?>
<option value="<?php echo $intvalue;?>" <?php if(isset($settings['ipaddress']) && $settings['ipaddress']['value']==$intvalue){?>selected<?php }?>><?php echo $intname . ' - ' . $intvalue;?></option>
<?php } ?>
    </select>
	<small id="interfaceHelpBlock" class="form-text text-muted">
	  Static IP is needed, the IP of this interface will be your new DNS IP, make sure to change your DNS IP in your internet router to this IP.
	</small>
  </div>
  
<div class="form-group">
  <label for="vpncountryinput">VPN Country</label>
  <select class="form-control" id="vpncountryinput" name=vpncountrycode>
<?php foreach($vpncountries as $name => $value){ ?>
<option value="<?php echo $value;?>" <?php if(isset($settings['vpncountrycode']) && $settings['vpncountrycode']['value']==$value){?>selected<?php }?>><?php echo $name . ' (' . $value . ')';?></option>
<?php } ?>
  </select>
</div>
  
  
  <button type="submit" class="btn btn-primary mb-2">Submit</button>
</form>


<?php require_once 'footer.php';?>
