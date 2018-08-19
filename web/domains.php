<?php
require_once 'init.php';
if(isset($_GET['delete']) && is_numeric($_GET['delete']) ){
	$db = new SQLite3(DATABASE_FILE);
	$ret =  $db->exec('DELETE FROM domains WHERE id=' . addslashes($_GET['delete']));
    $db->close();
	UpdateDNSMasqConf();
	exit;
}

$limit      = ( isset( $_GET['limit'] ) && is_numeric($_GET['limit']) ) ? $_GET['limit'] : 100;
$page       = ( isset( $_GET['page'] ) && is_numeric($_GET['page'])) ? $_GET['page'] : 1;
$links      = ( isset( $_GET['links'] ) && is_numeric($_GET['links'])) ? $_GET['links'] : 7;

$Paginator = new Paginator(DATABASE_FILE,'SELECT COUNT(id) AS total FROM domains');
$result = $Paginator->getData('SELECT * FROM domains ORDER BY id DESC',$limit,$page);

require_once 'header.php';
?>
<h2>Domains <span class="float-right"><a href="add-domain.php" class=" btn btn-primary btn-sm">Add</a> <a href="add-domain.php?bulk=1" class=" btn btn-primary btn-sm">Bulk</a></span></h2> 
<div class="table-responsive">
  <table class="table table-striped table-sm">
    <thead>
      <tr>
        <th>Domain</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
	<?php 
	foreach($result->data as $row){
	$domain = strip_tags($row['domain']);
	$id = $row['id'];
	?>
      <tr id="trDomain<?php echo $id;?>">
        <td><a href="http://<?php echo $domain;?>" target=_blank><?php echo $domain;?></a></td>
        <td><a href="#" data-id="<?php echo $id;?>" class="btn btn-danger btn-sm deleteDomain">Delete</a></td>
      </tr>
	<?php }?>
    </tbody>
  </table>
</div>
<small>Note: nordvpn.com and smartgw domains will be included by default.
<?php echo $Paginator->createLinks( $links, 'pagination justify-content-center' ); ?> 
<?php require_once 'footer.php';?>
<script>
$( document ).ready(function() {
	$('a.deleteDomain').click(function(event) {
		event.preventDefault();
		id = $(this).attr('data-id');
		$('#trDomain'+id).remove();
		var jqxhr = $.getJSON('domains.php?delete=' + id, function(data) {});
	});
});
</script>