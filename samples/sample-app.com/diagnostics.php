<style>
	body {
		font-family:Consolas;
	}

	.pass {
		color:green;
	}
	
	.fail {
		color:red;
	}
</style>

<a href='?phpinfo=true'>Run phpinfo()</a>
<?php if(isset($_GET['phpinfo'])): ?>
	<a href='diagnostics.php'>&larr; Go back</a>
	<?php die(phpinfo()); ?>
<?php endif; ?>
<br><br>

<?php
$ini = ini_get_all();

$app_path = realpath(dirname(__FILE__)).'/';;
$doc_root = $_SERVER['DOCUMENT_ROOT'].'/';

$environment = file_exists($doc_root."../environment.php");

$core = file_exists($doc_root."../core/");
?>

APP Path: <?php echo $app_path  ?>
<br>
Doc Root: <?php echo $doc_root; ?>
<br>
PHP Version: <?php echo phpversion(); ?>
<br><br>

<?php if($environment): ?>
	<div class='pass'>environment.php exists</div>
<?php else: ?>
	<div class='fail'>environment.php is missing</div>
<?php endif; ?>

<?php if($core): ?>
	<div class='pass'>core/ exists</div>
<?php else: ?>
	<div class='fail'>core/ is missing</div>
<?php endif; ?>



