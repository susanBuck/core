<?php
/* 
Report information about our environment

Red    = Could cause damage with live data / live users...be very careful.
Orange = Not a huge danger, but operating a little differently than expected for local so be aware. 
Black  = Normal operating for local.

*/


if(!IN_PRODUCTION && !Utils::is_ajax()) {
	echo "<div onClick='this.style.display = \"none\";' style='cursor:pointer; position:fixed; font-family:consolas; z-index:999; background-color:yellow; padding:3px; bottom:0px; left:0px;'>";
			
		# TIME	
		if(Time::now() != time()) {
			$color = "orange"; # Not a huge danger, but good to draw attention to because it can cause confusing things to happen if you forget you're mimicking the time
		}
		else {
			$color = "black";
		}
		echo "&nbsp;&nbsp;<span style='color:".$color."'>".Time::display(Time::now())."</span>&nbsp;&nbsp;";
		
		
		# OUTGOING EMAIL
		if(ENABLE_OUTGOING_EMAIL) {
			$status = "TRUE";
			$color = "black"; 
		}
		else {
			$status = "FALSE";
			$color = "black";
		}

		echo "<span style='color:".$color."'>ENABLE_OUTGOING_EMAIL=".$status."</span>&nbsp;&nbsp;";
		
		# FAKEMAIL
		if(FAKEMAIL) {
			$status = "TRUE";
			$color = "black"; 
		}
		else {
			$status = "FALSE";
			$color = "black";
		}
		
		echo "<span style='color:".$color."'>FAKEMAIL=".$status."</span>&nbsp;&nbsp;";
		
		if(!FAKEMAIL && ENABLE_OUTGOING_EMAIL) {
			echo "<span style='color:red'>WARNING: SYSTEM CAN SEND EMAIL TO USERS</span>&nbsp;&nbsp;";		
		}
		
		# DATABASE
		if(REMOTE_DB) {
			$status = "TRUE";
			$color = "red"; // Danger! You're on the live DB!
		}
		else {
			$status = "FALSE";
			$color = "black";
		}

		echo "<span style='color:".$color."'>REMOTE_DB=".$status."</span>&nbsp;&nbsp;";

	echo "</div>";
}
	
	