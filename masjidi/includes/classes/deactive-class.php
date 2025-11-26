<?php
/** @wordpress-plugin
 * Author:            Masjidal 
 * Author URI:        https://icfbayarea.com/
 */

namespace masjidi_namespace;

class MPSTI_Plugin_Deactivator {
	/* De-activate Class */
	public static function deactivate() {
		/* Delete Table And Post type*/
	global $wpdb;	
	
		delete_option( 'widget_mpsti_wpb_widget' );
		  	
	}
}