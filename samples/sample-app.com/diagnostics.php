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

<?php
$apache_modules = apache_get_modules();
$mod_rewrite    = in_array('mod_rewrite',$apache_modules);

$ini = ini_get_all();

$app_path = realpath(dirname(__FILE__)).'/';;
$doc_root = $_SERVER['DOCUMENT_ROOT'].'/';

$environment = file_exists($doc_root."../environment.php");

$core = file_exists($doc_root."../core/");
?>


<?php if(isset($_GET['phpinfo'])): ?>
	<a href='diagnostics.php'>&larr; Go back</a>
	<?php phpinfo(); ?>
<?php endif; ?>

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

<?php if($mod_rewrite): ?>
	<div class='pass'>mod_rewrite is enabled</div>
<?php else: ?>
	<div class='fail'>mod_rewrite is not enabled</div>
<?php endif; ?>

<br><a href='?phpinfo=true'>Run phpinfo()</a>


