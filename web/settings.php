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

$dnsips = [];
if(isset($settings['dns1ip']['value'])){
	$dnsips[] = $settings['dns1ip']['value'];
}
if(isset($settings['dns2ip']['value'])){
	$dnsips[] = $settings['dns2ip']['value'];
}
$dnsips = implode(',',$dnsips);
if($dnsips==''){
	$dnsips = '103.86.96.100,103.86.99.100';
}


if($_SERVER['REQUEST_METHOD']  == 'POST'){
	
	$db = new SQLite3(DATABASE_FILE);
	if(!$db) {
	   echo $db->lastErrorMsg();
	   exit;
	}
	$stmtInsert = $db->prepare('INSERT INTO settings (name,value) VALUES(:name,:value)');
	$stmtUpdate = $db->prepare('UPDATE settings SET value=:value WHERE name LIKE :name');

	$myPOST = $_POST;
	$checkBoxes = ['disablesquidlog'=>0,'disablesniproxylog'=>0,'disablednsquerylog'=>0];

	foreach($checkBoxes as $name=>$value){
		if(!isset($_POST[$name])){
			$myPOST[$name] = $value;
		}
	}
	
	if(trim($myPOST['dns1ip']) == '') { $myPOST['dns1ip'] = '103.86.96.100'; }
	if(trim($myPOST['dns2ip']) == '') { $myPOST['dns2ip'] = '103.86.99.100'; }

	foreach($myPOST as $name=>$value){
		$value = trim($value);
		if(isset($settings[$name])){
			$stmtUpdate->bindValue(':name', $name);
			$stmtUpdate->bindValue(':value',$value);
			$stmtUpdate->execute();
			if($settings[$name]['value'] != $value){$changes[$name] = true;}else{$changes[$name] = false;}
		}else{
			$stmtInsert->bindValue(':name', $name);
			$stmtInsert->bindValue(':value',$value);
			$stmtInsert->execute();
			$changes[$name] = false;
		}
		$settings[$name]['value'] = $value;
	}	
    $db->close();

	$alert['info'] = null;
	if($changes['dns1ip'] || $changes['dns2ip'] || $changes['disablesquidlog'] ){
		$alert['info'] .= "<br>UpdateSquidConf " . $myPOST['dns1ip'] . ' , ' . $myPOST['dns2ip'] . ' , ' . $myPOST['disablesquidlog'];
		UpdateSquidConf($myPOST['dns1ip'],$myPOST['dns2ip'],$myPOST['disablesquidlog']);
	}
	
	if($changes['dns1ip'] || $changes['dns2ip'] || $changes['disablednsquerylog'] ){
		$alert['info'] .= "<br>UpdateDNSMasqConf " . $myPOST['dns1ip'] . ' , ' . $myPOST['dns2ip'] . ' , ' . $myPOST['disablednsquerylog'];
		UpdateDNSMasqConf($myPOST['dns1ip'],$myPOST['dns2ip'],$myPOST['disablednsquerylog']);
	}elseif($changes['ipaddress']){
		$alert['info'] .= "<br>UpdateDNSMasqDomains";
		UpdateDNSMasqDomains();
	}
	
	if($changes['dns1ip'] || $changes['dns2ip'] || $changes['disablesniproxylog'] ){
		$alert['info'] .= "<br>UpdateSNIProxyConf " . $myPOST['dns1ip'] . ' , ' . $myPOST['dns2ip'] . ' , ' . $myPOST['disablesniproxylog'];
		UpdateSNIProxyConf($myPOST['dns1ip'],$myPOST['dns2ip'],$myPOST['disablesniproxylog']);
	}

	if(isset($myPOST['restartvpn']) && $myPOST['restartvpn'] == "1"){
		if(isset($settings['vpncountrycode']) && $settings['vpncountrycode']['value']!=''){
			$exeout = [];
			$alert['info'] .= "<br>openpyn";
			exec('/usr/bin/sudo /usr/local/bin/openpyn -k');
			exec('/usr/bin/sudo /usr/local/bin/openpyn -d '. $settings['vpncountrycode']['value'], $exeout, $return);
		}
	}
	//header('Location: settings.php');
	//exit;
	$alert['success'] = 'Your settings have been saved.';
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
	//echo '<h3>ERROR @ listinterfaces.sh</h3>';
	//print_r($return);
	//exit;
	$alert['danger'] = 'Not able to get your network interfaces. ' . print_r($exeout,true);
}

$exeout = [];

if(file_exists('/usr/local/bin/openpyn')) {
	exec('/usr/local/bin/openpyn -l', $exeout, $return);
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
		//echo '<h3>ERROR</h3><br> Please run <b>/usr/local/bin/openpyn --init</b> to initialise openpyn before you login to the web interface!';
		$alert['danger'] = 'Please run <b>/usr/local/bin/openpyn --init</b> to initialise openpyn before you login to the web interface!. ' . print_r($exeout,true);
		//exit;
	}
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
   <label for="UpstreamDNSProvidersInput">Upstream DNS Provider</label>
   <select class="form-control" id="UpstreamDNSProvidersInput" name=UpstreamDNSProvider>
 <?php foreach($UpstreamDNSProviders as $name => $value){ ?>
 <option data-dnsips="<?php echo implode(',',$value);?>" value="<?php echo $name;?>" <?php if(isset($settings['UpstreamDNSProvider']) && $settings['UpstreamDNSProvider']['value']==$name){?>selected<?php }?>><?php echo $name;?></option>
 <?php } ?>
 <option data-dnsips="<?php echo $dnsips;?>" value="custom" <?php if(isset($settings['UpstreamDNSProvider']) && $settings['UpstreamDNSProvider']['value']=='custom'){?>selected<?php }?>>Custom</option>
 
   </select>
   <small>Please <a href="https://github.com/mrahmadt/SmartGW/wiki/Upstream-DNS-Providers" target=_blank>click here</a> for more information</small> 
 </div>
 
 <div id="DNSIPsInput" <?php if(isset($settings['UpstreamDNSProvider']) && $settings['UpstreamDNSProvider']['value']!='custom'){?>style="display:none;"<?php }?>>
   <div class="form-group">
     <label for="dns1ip">Primary DNS Server:</label>
	 <input type="text" class="form-control" id="dns1ip" name="dns1ip" placeholder="103.86.96.100">
   </div>
   <div class="form-group">
     <label for="dns2ip">Secondary DNS Server:</label>
	 <input type="text" class="form-control" id="dns2ip" name="dns2ip" placeholder="103.86.99.100">
   </div>
 </div>
 
<div class="form-group">
  <label for="vpncountryinput">VPN Country</label>
  <select class="form-control" id="vpncountryinput" name=vpncountrycode>
<?php foreach($vpncountries as $name => $value){ ?>
<option value="<?php echo $value;?>" <?php if(isset($settings['vpncountrycode']) && $settings['vpncountrycode']['value']==$value){?>selected<?php }?>><?php echo $name . ' (' . $value . ')';?></option>
<?php } ?>
  </select>
</div>
<div class="form-group form-check">
<input type="checkbox" class="form-check-input" id="disableSquidLog" name="disablesquidlog" value="1" <?php if(isset($settings['disablesquidlog']) && $settings['disablesquidlog']['value']=="1"){?>checked<?php } ?>>
<label class="form-check-label" for="disableSquidLog">Disable Squid access log</label>
</div>
<div class="form-group form-check">
<input type="checkbox" class="form-check-input" id="disablesniproxylog" name="disablesniproxylog" value="1" <?php if(isset($settings['disablesniproxylog']) && $settings['disablesniproxylog']['value']=="1"){?>checked<?php } ?>>
<label class="form-check-label" for="disablesniproxylog">Disable SNIProxy access log</label>
</div>
<div class="form-group form-check">
<input type="checkbox" class="form-check-input" id="disablednsquerylog" name="disablednsquerylog" value="1" <?php if(isset($settings['disablednsquerylog']) && $settings['disablednsquerylog']['value']=="1"){?>checked<?php } ?>>
<label class="form-check-label" for="disablednsquerylog">Disable DNS query log</label>
</div>
<div class="form-group form-check">
<input type="checkbox" class="form-check-input" id="restartvpn" name="restartvpn" value="1">
<label class="form-check-label" for="restartvpn">Restart my VPN</label>
</div>
 
  
  <button type="submit" class="btn btn-primary mb-2">Submit</button>
</form>


<?php require_once 'footer.php';?>
<script>
$( document ).ready(function() {
	$( "#vpncountryinput" ).change(function() {
		$('#restartvpn').prop('checked', true);
	});
		$( "#UpstreamDNSProvidersInput" ).change(function() {
			selected = $("#UpstreamDNSProvidersInput option:selected");
			if(selected.text()=='Custom'){
				$('#DNSIPsInput').fadeIn();
			}else{
				$('#DNSIPsInput').fadeOut();
			}
			var attr = selected.attr('data-dnsips');
			if (typeof attr !== typeof undefined && attr !== false) {
				ips = attr.split(","); 
				if(ips.length>0){
					$('#dns1ip').val(ips[0]);
				}
				if(ips.length>1){
					$('#dns2ip').val(ips[1]);
				}
			}else{
				$('#dns1ip').val('');
				$('#dns2ip').val('');
			}
		}).change();
});
</script>

