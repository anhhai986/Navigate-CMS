<?php
function nvweb_object($ignoreEnabled=false, $ignorePermissions=false)
{
	global $website;
    global $DB;
	
	session_write_close();
	ob_end_clean();
	
	$item = new file();	
	
	header('Cache-Control: private');
	header('Pragma: private');		
	
	$type = $_REQUEST['type'];
	$id = $_REQUEST['id'];
	
	if(!empty($_REQUEST['id']))
	{
		if(is_numeric($id))
			$item->load($id);
		else
			$item->load($_REQUEST['id']);
	}
	
	if(empty($type) && !empty($item->type)) 
		$type = $item->type;

	// Check file access authorization
	$enabled = nvweb_object_enabled($item);
	if(!$enabled && !$ignorePermissions)
		$type = 'not_allowed';
	
	switch($type)
	{
		case 'not_allowed':
			header("HTTP/1.0 405 Method Not Allowed");
			break;

		case 'blank':
		case 'transparent':
			$path = NAVIGATE_PATH.'/img/transparent.gif';
			
			header('Content-Disposition: attachment; filename="transparent.gif"');
			header('Content-Type: image/gif');
			header('Content-Disposition: inline; filename="transparent.gif"');			
			header("Content-Transfer-Encoding: binary\n");
			
			$etag = base64_encode($path.filemtime($path));
			header('ETag: "'.$etag.'"');
			
			// check the browser cache and stop downloading again the file
			$cached = file::cacheHeaders(filemtime($path), $etag);			

			if(!$cached)
                readfile($path);
			break;
				
		case 'flag':
			if($_REQUEST['code']=='ca')
                $_REQUEST['code'] = 'catalonia';
				
			header('Content-Disposition: attachment; filename="'.$_REQUEST['code'].'.png"');
			header('Content-Type: image/png');
			header('Content-Disposition: inline; filename="'.$_REQUEST['code'].'.png"');			
			header("Content-Transfer-Encoding: binary\n");

			$path = NAVIGATE_PATH.'/img/icons/flags/'.$_REQUEST['code'].'.png';
            if(!file_exists($path))
                $path = NAVIGATE_PATH.'/img/transparent.gif';
			
			$etag = base64_encode($path.filemtime($path));
			header('ETag: "'.$etag.'"');
			
			// check the browser cache and stop downloading again the file
			$cached = file::cacheHeaders(filemtime($path), $etag);			

			if(!$cached)
                readfile($path);
			break;
	
		case 'image':
		case 'img':			
			if(!$item->enabled && !$ignoreEnabled) 
				nvweb_clean_exit();
			
			$path = $item->absolute_path();

			$etag_add = '';		
		
			// calculate aspect ratio if width or height are given...
			$width = intval(@$_REQUEST['width']) + 0;
			$height = intval(@$_REQUEST['height']) + 0;	
			
			$resizable = true;
			if($item->mime == 'image/gif')
				$resizable = !(file::is_animated_gif($path));
	
			if((!empty($width) || !empty($height)) && ($resizable || @$_REQUEST['force_resize']=='true'))
			{
				$border = ($_REQUEST['border']=='false'? false : true);
				$path = file::thumbnail($item, $width, $height, $border);
				$etag_add  = '-'.$width.'-'.$height.'-'.$border;
				$item->name = $width.'x'.$height.'-'.$item->name;
				$item->size = filesize($path);
				$item->mime = 'image/png';
			}
					
			$etag = base64_encode($item->id.'-'.$item->name.'-'.$item->date_added.'-'.filesize($path).'-'.filemtime($path).'-'.$item->permission.$etag_add);
			header('ETag: "'.$etag.'"');
			header('Content-type: '.$item->mime);
			header("Content-Length: ". $item->size);
			if(empty($_REQUEST['disposition'])) $_REQUEST['disposition'] = 'inline';
			header('Content-Disposition: '.$_REQUEST['disposition'].'; filename="'.$item->name.'"');						
			
			// check the browser cache and stop downloading again the file
			$cached = file::cacheHeaders(filemtime($path), $etag);			

			if(!$cached)
                readfile($path);
			
			break;
		
		case 'archive':
		case 'video':
		case 'file':
		default:		
			if(!$item->enabled && !$ignoreEnabled) nvweb_clean_exit();
			
			$path = NAVIGATE_PRIVATE.'/'.$website->id.'/files/'.$item->id;
			
			$etag_add = '';		
		
			$etag = base64_encode($item->id.'-'.$item->name.'-'.$item->date_added.'-'.filemtime($path).'-'.$item->permission.$etag_add);
						
			header('ETag: "'.$etag.'"');					
			header('Content-type: '.$item->mime);
			header("Content-Length: ". $item->size); 

			if(empty($_REQUEST['disposition'])) $_REQUEST['disposition'] = 'attachment';
			header('Content-Disposition: '.$_REQUEST['disposition'].'; filename="'.$item->name.'"');			
			
			// check the browser cache and stop downloading again the file
			$cached = file::cacheHeaders(filemtime($path), $etag);			

			if(!$cached) readfile($path);		
			break;
	}
	
	session_write_close();
	if($DB)
        $DB->disconnect();
	exit;
}
?>