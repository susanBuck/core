<?php

class Upload {

	public function __construct() {	
	}
		
	/*-------------------------------------------------------------------------------------------------
	Single file upload
	Make sure the folder you're saving to has write permissions
	Make sure php.ini has file_uploads = On
	Make sure the file size is less than upload_max_filesize
	Allowed_files is an array of extensions, no period...ex: array("jpg", "jpeg", "gif", "png")
	Don't pass extension with $new_file_name
	
	Example form:
	<form method='POST' enctype="multipart/form-data" action='/scratch/p_upload/'>
	
		<input type='file' name='whatever-you-want'>
		<input type='submit'>

	<form>
	
	Example process:
	Upload::upload($_FILES, "/uploads/", array("jpg", "jpeg", "gif", "png"), "foobar");
	-------------------------------------------------------------------------------------------------*/
	public static function upload($file_obj, $upload_dir, $allowed_files, $new_file_name = NULL) {		
		
		# Access first element in file_obj array (b/c we're dealing with single file uploads only)
		$key = key($file_obj);
		
		$original_file_name = $file_obj[$key]['name'];
		$temp_file          = $file_obj[$key]['tmp_name'];
		$upload_dir         = $upload_dir;
		
		# If new file name not given, use original file name
		if($new_file_name == NULL) $new_file_name = $original_file_name;
		
		$file_parts  = pathinfo($original_file_name);
		$target_file = getcwd().$upload_dir . $new_file_name . "." . $file_parts['extension'];
								
		# Validate the filetype
		if (in_array($file_parts['extension'], $allowed_files)) {
	
			# Save the file
				move_uploaded_file($temp_file,$target_file);
				return $new_file_name . "." . $file_parts['extension'];
	
		} else {
			return 'Invalid file type.';
		}
	
	}
		

} # end class

?>