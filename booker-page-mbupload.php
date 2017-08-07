<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_mbupload
	{

		function match_request($request)
		{
			return ($request=='mbupload');
		}


		function process_request($request)
		{
			$url = '';
			$fileformat = '';
			$filename = '';
			$filesize = '';
			$filetype = '';

			// var_dump(dirname(__file__));

			$file = reset($_FILES);
			/*
			$fileName = $file['name'];
			$fileTmpLoc = $file['tmp_name']; // File in the PHP tmp folder
			$fileType = $file['type']; // The type of file it is
			$fileSize = $file['size']; // File size in bytes
			$fileErrorMsg = $file['error']; // 0 for false... and 1 for true
			*/

			// $_FILES should contain the image
			$imageformats = array('png','gif','jpeg','jpg');
			$disallowedformats = array('exe','bat');
			if(is_array($_FILES) && count($_FILES))
			{
				$filename = strtolower($file['name']);
				$fileformat = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

				$isimage = in_array($fileformat, $imageformats);
				
				// $filetype = $file['type']; // application/octet-stream
				$filesize_bytes = $file['size'];
				// if bigger than 100 kb display MB
				if($filesize_bytes>1024*100)
				{
					$filesize = number_format($filesize_bytes/1048576, 1, ',', '.').' MB'; // File size in MB
				}
				else {
					// display kb instead of MB
					$filesize = round($filesize_bytes/1024).' kb'; // File size in kb
				}

				$userid = qa_get_logged_in_userid();
				if(!isset($userid))
				{
					exit();
				}

				// each user gets his own folder
				$serverpath = qa_opt('booker_uploadpath');
				// make sure last char is a slash
				$serverpath = rtrim($serverpath, '/') . '/';
				// set path to upload folder including the userid as own folder
				$directory = $serverpath.$userid.'/';

				if(in_array($fileformat, $disallowedformats))
				{
					echo qa_lang('booker_lang/filetype_disallowed');
					exit();
				}

				// if(is_dir($directory) || mkdir($directory, fileperms(rtrim($directory, '/')) & 0777)) {
				if(is_dir($directory) || mkdir($directory, 0777))
				{

					// count number of files in folder
					$filecount = count(glob($directory . "*.*"))+1;

					// prepend timestamp to filename
					// $filename = time().'_'.$filename;
					// prepend number to file name
					$filename = str_pad($filecount, 4, '0', STR_PAD_LEFT).'_'.$filename;
					$uploadtarget = $directory.$filename;
					if(file_exists($uploadtarget))
					{
						// should never happen, user can only upload one file in one moment
						echo qa_lang('booker_lang/filename_exists');
						exit();
					}
					
					$localfilename = $file['tmp_name']; // temp path to source file, e.g. /srv/users/serverpilot/tmp/kvanto/phpZYrYyv
					
					$content = null;
					
					// if image and too big, resize it
					if($isimage)
					{
						/*
						$imagesize = @getimagesize($localfilename);

						if(is_array($imagesize)) 
						{
							$result['width'] = $imagesize[0];
							$result['height'] = $imagesize[1];
						}
						*/
						
						$imagemaxwidth = 1080;
						$imagemaxheight = 640;
						
						// if appropriate, get more accurate image size and apply constraints to it
						$content = file_get_contents($localfilename);
						require_once QA_INCLUDE_DIR.'util/image.php';

						if($isimage && qa_has_gd_image()) 
						{
							$image = @imagecreatefromstring($content);

							if(is_resource($image)) 
							{
								$width = imagesx($image);
								$height = imagesy($image);
								// error_log($width.' - '.$height);

								if (qa_image_constrain($width, $height, $imagemaxwidth, $imagemaxheight))
								{
									qa_gd_image_resize($image, $width, $height);

									if(is_resource($image)) 
									{
										$content = qa_gd_image_jpeg($image);
									}
								}

								// might have been lost
								if (is_resource($image))
								{
									imagedestroy($image);										
								}
							}
						}
					} // end $isimage
					
					// nothing changed with the image 
					if(is_null($content))
					{
						$content = $localfilename;
						// upload the user file, no changes done
						move_uploaded_file($content, $uploadtarget);
					}
					else
					{
						// store directly in the filesystem
						$fp = fopen($uploadtarget, "w");
						fwrite($fp, $content);
						fclose($fp);
					}

					// success: create and return link to file
					// echo q2apro_site_url().'bookbin/'.$userid.'/'.$filename;
					// each user gets his own folder
					$uploadurl = qa_opt('booker_uploadurl');
					// make sure last char is a slash
					$uploadurl = rtrim($uploadurl, '/') . '/';
					// path to file
					$fileurl = $uploadurl.$userid.'/'.$filename;

					// check for images
					if($isimage)
					{
						// return html to embed and display image
						echo '<img src="'.$fileurl.'" alt="image" /> ';
					}
					else
					{
						// return link to uploaded file
						echo '<a href="'.$fileurl.'" title="'.$fileformat.'-Dokument ('.$filesize.')">'.$filename.' ('.$filesize.')</a> ';
					}

					exit();
				}
				else
				{
					echo qa_lang('booker_lang/nouploadfolder');
					exit();
				}
			} // end if is_array

			exit();
		} // end process_request

	} // END class booker_page_mbupload


/*
	Omit PHP closing tag to help avoid accidental output
*/
