<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>Smart Gateway</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
<link href="assets/app.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark p-0 pl-2">
  <a class="navbar-brand" href="https://github.com/mrahmadt/SmartGW">Smart Gateway</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav mr-auto">
          <li class="nav-item"><a class="nav-link" href="add-domain.php">Add Domain</a></li>
          <li class="nav-item"><a class="nav-link" href="domains.php">Domains</a></li>
          <li class="nav-item"><a class="nav-link" href="index.php">Status</a></li>
		  
      </ul>
  </div>
</nav>

<div class="container-fluid">
<main role="main">
<?php if(isset($alert['success'])){?><br><div class="alert alert-success"><?php echo $alert['success']; ?></div><?php } ?>

<?php if(isset($alert['info'])){?><br><div class="alert alert-info"><?php echo $alert['info']; ?></div><?php } ?>

<?php if(isset($alert['warning'])){?><br><div class="alert alert-warning"><?php echo $alert['warning']; ?></div><?php } ?>

<?php if(isset($alert['danger'])){?><br><div class="alert alert-danger"><?php echo $alert['danger']; ?></div><?php } ?>
