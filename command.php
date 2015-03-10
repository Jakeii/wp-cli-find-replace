<?php 

if ( !defined( 'WP_CLI' ) ) return;

class CLI_Find_Replace extends WP_CLI_Command {
		
	/**
	 * Find and replace in many places within wordpress, available are post content, excerpts, links, attachments, custom fields, guids.
	 * Based on 'Velvet Blues Update URLs' plugin by VelvetBlues.com
	 *
	 * ## OPTIONS
	 * 
	 * <find>
	 * : The string in find.
	 *
	 * <replace>
	 * : New string to replace.
	 *
	 * [<location>]
	 * : Locations to find and replace, defaults to just post content if none are specified.
	 *
	 * --all 
	 * : Overrides location args, will replace in all available locations.
	 *
	 * --all-but-guids
	 * : Overrides location args, will replace in all available locations except guids
	 *
	 * ## EXAMPLES
	 *
	 * wp replace http://oldurl.com/ http://newurl.com content excerpts custom
	 *
	 * wp replace http://oldurl.com/ http://newurl.com --all
	 *
	 * @synopsis <find> <replace> [<content>] [<excerpts>] [<links>] [<attachments>] [<custom>] [<guids>] [--all] [--all-but-guids]
	 * @when after_wp_load
	 */
	function __invoke( $args, $assoc_args ) {
		$oldurl = $args[0];
		$newurl = $args[1];
		
		$options = array();
		
		if( $assoc_args['all'] ) {
			$options = array( 'content', 'excerpts', 'links', 'attachments', 'custom', 'guids' );
		} else if( $assoc_args['all-but-guids'] ) {
			$options = array( 'content', 'excerpts', 'links', 'attachments', 'custom' );
		} else {
			$options = array_slice( $args, 2 );
		}
		
		$results = $this->update_urls( $options, $oldurl, $newurl );
		
		$empty = true;
				
		foreach($results as $result){
			$empty = ($result[0] != 0 || $empty == false)? false : true;
		}
		
		if( $empty ) {
			WP_CLI::warning('Something may have gone wrong.');
			WP_CLI::warning('No Replacements have been made.');
		} else {
			WP_CLI::success('Success! Your replacements have been updated.');
		}
		
		WP_CLI::line('');
		WP_CLI::line('Results:');
		
		foreach($results as $result){
			WP_CLI::line($result[0] . ' ' . $result[1]);
		}
		
		if($empty) {
			WP_CLI::line('');
			WP_CLI::line('Why do the results show 0 updated?');
			WP_CLI::line('This happens if the find string is incorrect OR if it is not found in the content. Double check it and try again.');
		}
	}
	
	function update_urls( $options, $oldurl, $newurl ) {	
		global $wpdb;
		
		$results = array();
		
		$queries = array(
			'content' =>		array("UPDATE $wpdb->posts SET post_content = replace(post_content, %s, %s)",  __('Content Items (Posts, Pages, Custom Post Types, Revisions)','velvet-blues-update-urls') ),
			'excerpts' =>		array("UPDATE $wpdb->posts SET post_excerpt = replace(post_excerpt, %s, %s)", __('Excerpts','velvet-blues-update-urls') ),
			'attachments' =>	array("UPDATE $wpdb->posts SET guid = replace(guid, %s, %s) WHERE post_type = 'attachment'",  __('Attachments','velvet-blues-update-urls') ),
			'links' =>			array("UPDATE $wpdb->links SET link_url = replace(link_url, %s, %s)", __('Links','velvet-blues-update-urls') ),
			'custom' =>			array("UPDATE $wpdb->postmeta SET meta_value = replace(meta_value, %s, %s)",  __('Custom Fields','velvet-blues-update-urls') ),
			'guids' =>			array("UPDATE $wpdb->posts SET guid = replace(guid, %s, %s)",  __('GUIDs','velvet-blues-update-urls') )
		);
		
		foreach( $options as $option ) {
			if( $option == 'custom' ) {
				$n = 0;
				$row_count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->postmeta" );
				$page_size = 10000;
				$pages = ceil( $row_count / $page_size );
				
				for( $page = 0; $page < $pages; $page++ ) {
					$current_row = 0;
					$start = $page * $page_size;
					$end = $start + $page_size;
					$pmquery = "SELECT * FROM $wpdb->postmeta WHERE meta_value <> ''";
					$items = $wpdb->get_results( $pmquery );
					foreach( $items as $item ){
						$value = $item->meta_value;
						
						if( trim($value) == '' ) {
							continue;
						}
						
						$edited = $this->unserialize_replace( $oldurl, $newurl, $value );

						if( $edited != $value ){
							$fix = $wpdb->query("UPDATE $wpdb->postmeta SET meta_value = '".$edited."' WHERE meta_id = ".$item->meta_id );
							
							if( $fix ) {
								$n++;
							}
						}
					}
				}
				$results[$option] = array($n, $queries[$option][1]);
			}
			else{
				$result = $wpdb->query( $wpdb->prepare( $queries[$option][0], $oldurl, $newurl) );
				$results[$option] = array($result, $queries[$option][1]);
			}
		}
		return $results;			
	}
	
	function unserialize_replace( $from = '', $to = '', $data = '', $serialised = false ) {
		try {
			if ( is_string( $data ) && ( $unserialized = @unserialize( $data ) ) !== false ) {
				$data = $this->unserialize_replace( $from, $to, $unserialized, true );
			}
			elseif ( is_array( $data ) ) {
				$_tmp = array( );
				foreach ( $data as $key => $value ) {
					$_tmp[ $key ] = $this->unserialize_replace( $from, $to, $value, false );
				}
				$data = $_tmp;
				unset( $_tmp );
			}
			else {
				if ( is_string( $data ) )
					$data = str_replace( $from, $to, $data );
			}
			if ( $serialised )
				return serialize( $data );
		} catch( Exception $error ) {
		}
		return $data;
	}
}

WP_CLI::add_command( 'replace', 'CLI_Find_Replace' );