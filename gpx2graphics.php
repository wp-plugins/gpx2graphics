<?php
/*
Plugin Name: Gpx2Graphics
Plugin URI: http://janwillemeshuis.nl/jwe-new-media-solutions/wordpress-plugins/gpx2graphics-plugin/
Description: Create a Google Map, Elevation image or Speed image from your (Garmin) GpX files.
Version: 0.3
Author: Jan-Willem Eshuis
Author URI: http://janwillemeshuis.nl
License: GPL2
*/

class Gpx2GraphicsLoader {
	function Enable() {
		global $wpdb;
	    $table = $wpdb->prefix."gpx2graphics";
	    $structure = "CREATE TABLE $table (
	        id INT(9) NOT NULL AUTO_INCREMENT,
	        name VARCHAR(80) NOT NULL,
	        filename TEXT NOT NULL,
	        public_location TEXT NOT NULL,
		    map_width VARCHAR(4) NOT NULL,
		    map_height VARCHAR(4) NOT NULL,
		    elevation_width VARCHAR(4) NOT NULL,
		    elevation_height VARCHAR(4) NOT NULL,
		    speed_width VARCHAR(4) NOT NULL,
		    speed_height VARCHAR(4) NOT NULL,
			UNIQUE KEY id (id)
	    );";
	    $wpdb->query($structure);
	    
		add_action('admin_menu', array('Gpx2GraphicsLoader', 'RegisterAdminPage'));
	}
		
	function RegisterAdminPage() {
		if (function_exists('add_menu_page')) {
			add_menu_page( __('Gpx2Graphics','gpx2graphics'),  
				__('Gpx2Graphics','gpx2files'), 
				'level_10',  
				Gpx2GraphicsLoader::GetBaseName(), 
				array('Gpx2GraphicsLoader','CallHtmlGpxFiles'),
				'div' 
			);
		}
	}
	
	function IsDeleteFile() {
		if (isset($_GET['delete'])) {
			global $wpdb;
			$sql = 'SELECT * FROM '.$wpdb->prefix.'gpx2graphics WHERE id='.$_GET['delete'];
			$row = $wpdb->get_row($sql);
			unlink($row->filename);
			$pieces = explode('/',$row->filename);
			$original = $pieces[count($pieces)-1];
			unlink(str_replace($original,'gpx2maps_'.$_GET['delete'].'.js',$row->filename));
			unlink(str_replace($original,'gpx2maps_elevation_'.$_GET['delete'].'.png',$row->filename));
			unlink(str_replace($original,'gpx2maps_speed_'.$_GET['delete'].'.png',$row->filename));
			$sql = 'DELETE FROM '.$wpdb->prefix.'gpx2graphics WHERE id='.$_GET['delete'];
			$wpdb->query($sql);
		}
	}
	
	function IsNewFileUpload() {
		global $wpdb;
		if (isset($_FILES['gpx_file'])) {
			$upload_dir = wp_upload_dir();
			$map_width = $_POST['map_width'];
			$map_height = $_POST['map_height'];
			$speed_width = $_POST['speed_width'];
			$speed_height = $_POST['speed_height'];
			$elevation_width = $_POST['elevation_width'];
			$elevation_height = $_POST['elevation_height'];
			$name = $_FILES['gpx_file']['name'];
			$filename = $upload_dir['path'] . '/'. $_FILES['gpx_file']['name'];
			$public_location = $upload_dir['url'];
			
			$data = array(
				'name' => $name,
				'filename' => $filename,
				'public_location' => $public_location,
				'map_width' => $map_width,
				'map_height' => $map_height,
				'elevation_width' => $elevation_width,
				'elevation_height' => $elevation_height,
				'speed_width' => $speed_width,
				'speed_height' => $speed_height
			);
			$wpdb->insert($wpdb->prefix.'gpx2graphics',$data);
			move_uploaded_file($_FILES['gpx_file']['tmp_name'],$filename);
			
			$id = $wpdb->insert_id;
			Gpx2GraphicsLoader::WriteMapsJsFileFromGpx($filename,$id,$data);
			unset($_GET['newfile']);
		}
	}
	
	function WriteMapsJsFileFromGpx($fileName,$id,$data) {
		
		require_once 'file.php';
		require_once 'graph.php';
		require_once 'point.php';
		$geo = new Gpx2GraphicsLoader_Geo_File($fileName);
		$geo->setTrackInfo();
		$distance = $geo->getTotalDistance();
		$pointList = $geo->getPointList();
		$centerMap = $geo->getCenter();
		$zoomLevel = $geo->getZoomLevel();
	
		$jsString = 'function initialize_'.$id.'() {' . chr(13);
	    $jsString .= 'var myLatlng = new google.maps.LatLng('.$centerMap.');' . chr(13);
	    $jsString .= 'var myOptions = {' . chr(13);
		$jsString .= 'zoom: '.$zoomLevel.',' . chr(13);
		$jsString .= 'center: myLatlng,' . chr(13);
		$jsString .= 'mapTypeId: google.maps.MapTypeId.ROADMAP' . chr(13);
		$jsString .= '}' . chr(13);
		$jsString .= 'var map_'.$id.' = new google.maps.Map(document.getElementById("map_canvas_' . $id . '"), myOptions);' . chr(13);

		$jsString .= 'var ridePlanCoordinates = [' . chr(13);
		for ($i=0;$i<count($pointList)-1;$i++) {
			$point = $pointList[$i];
			$jsString .= 'new google.maps.LatLng('.$point->getLat().','.$point->getLon().'),' . chr(13);
		}
		$point = $pointList[count($pointList)-1];
		$jsString .= 'new google.maps.LatLng('.$point->getLat().','.$point->getLon().')' . chr(13);
	    $jsString .= '];' . chr(13);
		$jsString .= 'var flightPath_'.$id.' = new google.maps.Polyline({' . chr(13);
	    $jsString .= 'path: ridePlanCoordinates,' . chr(13);
	    $jsString .= 'strokeColor: "#FF0000",' . chr(13);
	    $jsString .= 'strokeOpacity: 1.0,' . chr(13);
	    $jsString .= 'strokeWeight: 4' . chr(13);
	    $jsString .= '});' . chr(13);
		$jsString .= 'flightPath_'.$id.'.setMap(map_'.$id.');' . chr(13);
	  	$jsString .= '}' . chr(13);
	  
	  	$upload_dir = wp_upload_dir();
	  	$filename = $upload_dir['path'] . '/gpx2maps_'.$id.'.js';
		$fp = fopen($filename,'w');
		fwrite ($fp,$jsString);
		fclose($fp);
		
		$filename = $upload_dir['path'] . '/gpx2maps_elevation_'.$id.'.png';
		$geo->getElevationChart($data['elevation_width'],$data['elevation_height'],$filename);
		
		$filename = $upload_dir['path'] . '/gpx2maps_speed_'.$id.'.png';
		$geo->getSpeedChart($data['speed_width'],$data['speed_height'],$filename);
	}
	
	function CallHtmlGpxFiles() {
		Gpx2GraphicsLoader::IsDeleteFile();
		Gpx2GraphicsLoader::IsNewFileUpload();
	    echo '<div class="wrap">';

	    if ($_GET['newfile']==1) {
			echo '<h2>' . __( 'Gpx2Graphics - Add file', 'gpx2graphics_add_file' ) . '</h2>';
		    echo '<div id="html-upload-ui" style="display: block;">';
			echo '<form action="" enctype="multipart/form-data" method="post" >';
			echo '<input type="hidden" name="MAX_FILE_SIZE" value="10000000" />';
			
			echo '<fieldset>';
			echo '<table>';
			echo '<tr><td><label for="async-upload" class="">';
			echo __( 'Choose a file', 'gpx2graphics_choose_file' ).'</label>';
			echo '</td><td><input type="file" id="async-upload" name="gpx_file"></td></tr>';
			
			echo '<tr><td><label for="gpx-width" class="">';
			echo __( 'Maps Width', 'gpx2graphics_width' ).'</label>';
			echo '</td><td><input type="text" id="gpx-width" name="map_width" value="600"></td></tr>';
			
			echo '<tr><td><label for="gpx-height" class="">';
			echo __( 'Maps Height', 'gpx2graphics_height' ).'</label>';
			echo '</td><td><input type="text" id="gpx-height" name="map_height" value="400"></td></tr>';
			
			echo '<tr><td><label for="gpx-width-speed" class="">';
			echo __( 'Speed graphic Width', 'gpx2graphics_speed_width' ).'</label>';
			echo '</td><td><input type="text" id="gpx-width-speed" name="speed_width" value="600"></td></tr>';
			
			echo '<tr><td><label for="gpx-height-speed" class="">';
			echo __( 'Speed graphic Height', 'gpx2graphics_speed_height' ).'</label>';
			echo '</td><td><input type="text" id="gpx-height-speed" name="speed_height" value="300"></td></tr>';
			
			echo '<tr><td><label for="gpx-width-elevation" class="">';
			echo __( 'Speed graphic Width', 'gpx2graphics_speed_width' ).'</label>';
			echo '</td><td><input type="text" id="gpx-width-elevation" name="elevation_width" value="600"></td></tr>';
			
			echo '<tr><td><label for="gpx-height-elevation" class="">';
			echo __( 'Speed graphic Height', 'gpx2graphics_speed_height' ).'</label>';
			echo '</td><td><input type="text" id="gpx-height-elevation" name="elevation_height" value="300"></td></tr>';
			
			echo '<tr><td>&nbsp;</td><td><input type="submit" value="Uploaden" name="html-upload" class="button"></td></tr>';
			echo '</table>';
			echo '</fieldset>';
			
			echo '</form>';
			echo '</div>';
	    } else {
	    	global $wpdb;
	    	$sql = 'SELECT * FROM '.$wpdb->prefix.'gpx2graphics ORDER BY id';
	    	$items = $wpdb->get_results($sql);
			echo '<h2>' . __( 'Gpx2Graphics - All files', 'gpx2graphics_all_files' ) . '</h2>';
		    echo '<div class="clear"></div>';
		    echo '<a href="?page='.Gpx2GraphicsLoader::GetBaseName().'&newfile=1">Upload new file</a>';
		    echo '<table cellspacing="0" class="widefat fixed">';
		    echo '<thead>';
			echo '<tr>';
			echo '<th style="" class="manage-column column-date" id="date" scope="col">Maps Code</th>';
			echo '<th style="" class="manage-column column-media" id="media" scope="col">Bestand</th>';
			echo '<th style="" class="manage-column column-author" id="author" scope="col">Google Maps (w/h)</th>';
			echo '<th style="" class="manage-column column-author" id="author" scope="col">Elevation image (w/h)</th>';
			echo '<th style="" class="manage-column column-author" id="author" scope="col">Speed image (w/h)</th>';
			echo '<th style="" class="manage-column column-author" id="author" scope="col">#</th>';
			echo '</tr>';
			echo '</thead>';
			foreach ($items as $item) {
				echo '<tr>';
				echo '<td style="" class="manage-column column-date" id="date" scope="col">'.$item->id.'</th>';
				echo '<td style="" class="manage-column column-media" id="media" scope="col">'.$item->name.'</th>';
				echo '<td style="" class="manage-column column-author" id="author" scope="col">'.$item->map_width.'x'.$item->map_height.'</th>';
				echo '<td style="" class="manage-column column-author" id="author" scope="col">'.$item->elevation_width.'x'.$item->elevation_height.'</th>';
				echo '<td style="" class="manage-column column-author" id="author" scope="col">'.$item->speed_width.'x'.$item->speed_height.'</th>';
				echo '<td style="" class="manage-column column-author" id="author" scope="col"><a href="?page='.Gpx2GraphicsLoader::GetBaseName().'&delete='.$item->id.'">'.__( 'delete', 'gpx2graphics_delete').'</a></th>';
				echo '</tr>';	
			}
			echo '</table>';
			echo '</div>';
	    }			
	}

	function GetBaseName() {
		return plugin_basename(__FILE__);
	}
	
	
}

function CheckForGpx2Graphics($content) {
	global $wpdb;
	$hasMatches = preg_match_all("/\[gpx2graphics [a-z]* [0-9]*\]/", $content,$matches);
	foreach ($matches[0] as $match) {
		if (strlen($match)>3) {
			$pieces = explode(' ',$match);
			if (isset($pieces[1]) && $pieces[1]=='map') {
				if (isset($pieces[2])) {
					$gpsCode = trim(str_replace(']','',$pieces[2]));
					$sql = 'SELECT * FROM '.$wpdb->prefix.'gpx2graphics WHERE id='.$gpsCode;
		    		$row = $wpdb->get_row($sql);
		    		$html =  '<div id="map_canvas_'.$gpsCode.'" style="margin-bottom: 5px;border:1px solid #000; width: '.$row->map_width.'px; height: '.$row->map_height.'px;"></div>';
		    		$html .= '<script type="text/javascript" src="'.$row->public_location.'/gpx2maps_'.$gpsCode.'.js"></script>';
		    		$html .= '<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false&callback=initialize_'.$gpsCode.'&language=NL&region=NE"></script>';
				}
				$content = str_replace($match,$html,$content);
			}
			if (isset($pieces[1]) && $pieces[1]=='speed') {
				if (isset($pieces[2])) {
					$gpsCode = trim(str_replace(']','',$pieces[2]));
					$sql = 'SELECT * FROM '.$wpdb->prefix.'gpx2graphics WHERE id='.$gpsCode;
		    		$row = $wpdb->get_row($sql);
		    		$html = '<img src="'.$row->public_location.'/gpx2maps_speed_'.$gpsCode.'.png" style="border: 1px solid #000;" />';
				}
				$content = str_replace($match,$html,$content);
			}
			if (isset($pieces[1]) && $pieces[1]=='elevation') {
				if (isset($pieces[2])) {
					$gpsCode = trim(str_replace(']','',$pieces[2]));
					$sql = 'SELECT * FROM '.$wpdb->prefix.'gpx2graphics WHERE id='.$gpsCode;
		    		$row = $wpdb->get_row($sql);
		    		$html = '<img src="'.$row->public_location.'/gpx2maps_elevation_'.$gpsCode.'.png" style="border: 1px solid #000;" />';
				}
				$content = str_replace($match,$html,$content);
			} 
			
		}
	}
	return $content;
}

wp_enqueue_style('gpx2graphics', get_bloginfo('wpurl') . '/wp-content/plugins/Gpx2Graphics/style.css',false,'0.1','all');

add_action("init",array("Gpx2GraphicsLoader","Enable"),1000,0);
add_filter( "the_content", "CheckForGpx2Graphics" ) ?>
