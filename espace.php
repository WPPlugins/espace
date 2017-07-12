<?php
/*
Plugin Name: eSpace
Plugin URI: http://www.quirm.net/
Description: Adds a widget to the dashboard with simple webspace stats for the wp-content directory
Version: 1.0.1
Author: Rich Pedley
Author URI: http://www.quirm.net/

Copyright (c) 2008 Rich Pedley
Released under the GNU General Public License (GPL)
http://www.gnu.org/licenses/gpl.txt
*/
// get webspace used for wp-content directory

function eSpace_dashboard() {
	if ( !current_user_can( 'edit_posts' ) ) return;

	// Load up the localization file if we're using WordPress in a different language
	// Place it in this plugin's folder and name it "dashboard-espace-[value in wp-config].mo"
	load_plugin_textdomain( 'eSpace-dashboard', '/wp-content/plugins/eSpace-dashboard' );

		// Add the widget to the dashboard
		add_action( 'wp_dashboard_setup', 'espace_register_widget' );
		add_filter( 'wp_dashboard_widgets', 'espace_add_widget' );

}


// Register this widget -- we use a hook/function to make the widget a dashboard-only widget
function espace_register_widget() {
	wp_register_sidebar_widget( 'eSpace-dashboard', __( 'eSpace' ), 'espace_widget' );
	wp_register_widget_control( 'eSpace-dashboard', __( 'eSpace settings' ), 'espace_widget_control',
			array(), // leave an empty array here: oddity in widget code
			array('widget_id' => 'eSpace-dashboard', // Yes - again.  This is required: oddity in widget code
				'allowed'       => ''));
}
function espace_add_widget( $widgets ) {
	global $wp_registered_widgets;

	if ( !isset($wp_registered_widgets['eSpace-dashboard']) ) return $widgets;

	array_splice( $widgets, 2, 0, 'eSpace-dashboard' );

	return $widgets;
}

function espace_widget_control($args){
	$options = $newoptions = get_option('widget_recent_entries');
	if ( $_POST["dashboard-espace-amt-submit"] ) {
		$newoptions['eSpace-number'] = (int) $_POST["dashboard-espace-amt"];
	}
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('widget_recent_entries', $options);
		wp_flush_widget_recent_entries();
	}
	$title = attribute_escape($options['title']);
	if ( !$number = (int) $options['eSpace-number'] )
		$number = 100;
?>
<p><label for="dashboard-espace-amt"><?php _e('Allocated Space in Mb:'); ?> <input style="width: 4em;" id="dashboard-espace-amt" name="dashboard-espace-amt" type="text" value="<?php echo $number; ?>" /></label></p>
<input type="hidden" id="dashboard-espace-amt-submit" name="dashboard-espace-amt-submit" value="1" />
<?php

}
// Output the widget contents
function espace_widget($args) {
	// allowed space
	$options = get_option('widget_recent_entries');
	$allowed = $options['eSpace-number'];
	$allowed=$allowed*1024*1024;
	// warning style
	$warn = ' style="color:#c00;"';

	// alert style
	$alert = ' style="background:#c00;color:#fff;font-weight:bold;"';

	// units - MB, GB etc
	$units = ' MB';

	$root = dirname(__FILE__);
	$pos = strpos($root,'plugins');
	if($pos>0){
		$root= substr($root,0,$pos);
	}
	
	$arry=espace_getDirectorySize($root);
	$left = floatval($allowed-$arry['size']);
	if($left>0 && $left<5.1) $style=$warn;
	elseif($left<0) $style=$alert;
	else $style='';
	extract( $args, EXTR_SKIP );

	echo $before_widget;
	echo $before_title;
	echo $widget_name;
	echo $after_title;
	echo '<h4>'.__('Web/disk space usage stats for wp-content')."</h4>\n";
	//echo '<p>Root: '.$root.'</p>';//un comment this line to check the root path
	echo "<ul>\n";
	echo '<li>'.__('No. of files : ').$arry['count']."</li>\n";
	echo '<li>'.__('No. of directories : ').$arry['dircount']."</li>\n"; 
	echo '<li>'.__('Total web space allocated: ').espace_sizeFormat($allowed)."</li>\n";
	echo '<li>'.__('Total web space used: ').espace_sizeFormat($arry['size'])."</li>\n";
	echo '<li'.$style.'>'.__('Web space available: ').espace_sizeFormat($left)."</li>\n";
	echo "</ul>\n";
	echo $after_widget;
	
}
function espace_getDirectorySize($path) {
	$totalsize = 0;
	$totalcount = 0;
	$dircount = 0;
	if ($handle = opendir ($path)) {
		while (false !== ($file = readdir($handle))) {
			$nextpath = $path . '/' . $file;
			if ($file != '.' && $file != '..' && !is_link ($nextpath)) {
				if (is_dir ($nextpath)) {
					$dircount++;
					$result = espace_getDirectorySize($nextpath);
					$totalsize += $result['size'];
					$totalcount += $result['count'];
					$dircount += $result['dircount'];
				}
				elseif (is_file ($nextpath)) {
					$totalsize += filesize ($nextpath);
					$totalcount++;
				}
			}
		}
	}
	closedir ($handle);
	$total['size'] = $totalsize;
	$total['count'] = $totalcount;
	$total['dircount'] = $dircount;
	return $total;
}

function espace_sizeFormat($size) {
	if($size<1024)	return $size.__(" bytes");
	else if($size<(1024*1024)) {
		$size=round($size/1024,1);
		return $size.__(" KB");
	}
	else if($size<(1024*1024*1024)) {
		$size=round($size/(1024*1024),1);
		return $size.__(" MB");
	}
	else {
		$size=round($size/(1024*1024*1024),1);
		return $size.__(" GB");
	}
}

add_action( 'plugins_loaded', 'eSpace_dashboard');

?>