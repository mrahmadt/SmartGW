<?php
require_once 'init.php';
require_once 'header.php';
?>
<h2>Status</h2>
<div class="table-responsive">
  <table class="table table-striped table-sm">
    <thead>
      <tr>
        <th>Service</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
    <tr>
        <td>DNS IP</td>
        <td><?echo getenv('SERVER_IP');?></td>
      </tr>
      <tr>
        <td>Pi-Hole</td>
        <td><a href="http://<?echo getenv('SERVER_IP');?>:8081/admin"><?echo getenv('SERVER_IP');?>:8081/admin</a></td>
      </tr>
      <tr>
        <td>VPN Connection</td>
        <td id="vpnconnection"><img src="assets/loading.gif" class="smallicon"></td>
      </tr>
    </tbody>
  </table>
</div>
<?php require_once 'footer.php';?>
<script>
$( document ).ready(function() {
  var jqxhr = $.getJSON('vpnconnection.php', function(data) {
	  if(data.status){
	  	$('#vpnconnection').html('<font color=green>Connected</font><br/><small>'+data.location+' - '+ data.isp + ' ' + data.ip+'</small>');
	  }else{
	  	$('#vpnconnection').html('<font color=red>Not Connected</font>');
	  }
  }).fail(function() {
      $('#vpnconnection').html('<font color=red>Error (add domain <b><i>nordvpn.com</i></b> to your dns in order for the status to work)</font>');
  });
});
</script>