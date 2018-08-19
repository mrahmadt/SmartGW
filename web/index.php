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
      <tr class="psservice" data-name="openpyn">
        <td class="servicename">VPN</td>
        <td class="servicestatus"><img src="assets/loading.gif" class="smallicon"></td>
      </tr>
      <tr>
        <td>VPN Connection</td>
        <td id="vpnconnection"><img src="assets/loading.gif" class="smallicon"></td>
      </tr>
      <tr class="psservice" data-name="sniproxy">
        <td class="servicename">SNI Proxy</td>
        <td class="servicestatus"><img src="assets/loading.gif" class="smallicon"></td>
      </tr>
      <tr class="psservice" data-name="dns">
        <td class="servicename">DNS</td>
        <td class="servicestatus"><img src="assets/loading.gif" class="smallicon"></td>
      </tr>
      <tr class="psservice" data-name="squid">
        <td class="servicename">Squid</td>
        <td class="servicestatus"><img src="assets/loading.gif" class="smallicon"></td>
      </tr>
	  
      <tr class="psservice" data-name="pihole">
        <td><a href="https://pi-hole.net/" target=_new>Pi Hole</a></td>
        <td class="servicestatus"><img src="assets/loading.gif" class="smallicon"></td>
      </tr>

	  
    </tbody>
  </table>
</div>
<?php require_once 'footer.php';?>
<script>
$( document ).ready(function() {
	$( "tr.psservice" ).each(function( index ) {
		tr = $(this);
	  service = tr.attr('data-name');
	  url = 'ps.php?service=' + service; 
	  var jqxhr = $.getJSON(url, function(data) {
		  if(!data.error){
			if(data.service.status){
			  	$('tr.psservice[data-name='+data.service.name+'] td.servicestatus').html('<font color=green>UP</font>');
			}else{
				$('tr.psservice[data-name='+data.service.name+'] td.servicestatus').html('<font color=red>DOWN</font>');
			}
		  }else{
			  $('tr.psservice[data-name='+data.service.name+'] td.servicestatus').html('<font color=red>Error</font>');
		  }
	  }).fail(function() {
	      $('tr.psservice[data-name='+data.service.name+'] td.servicestatus').html('<font color=red>Error</font>');
	  });
	});
	
	
  var jqxhr = $.getJSON('vpnconnection.php', function(data) {
	  if(data.status){
	  	$('#vpnconnection').html('<font color=green>Connected</font><br/><small>'+data.location+' - '+ data.isp + ' ' + data.ip+'</small>');
	  }else{
	  	$('#vpnconnection').html('<font color=red>Not Connected</font>');
	  }
  }).fail(function() {
      $('#vpnconnection').html('<font color=red>Error</font>');
  });
});
</script>