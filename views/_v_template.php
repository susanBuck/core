<!DOCTYPE html>
<html>
<head>
	<title><?php if(isset($title) echo $title; ?></title>

	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />	
	
	<!-- JS -->
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>
	<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/jquery-ui.min.js"></script>
				
	<!-- Controller Specific JS/CSS -->
	<?php if(isset($client_files) echo $client_files; ?>
	
</head>

<body>	

	<?php if(isset($content) echo $content; ?>

</body>
</html>