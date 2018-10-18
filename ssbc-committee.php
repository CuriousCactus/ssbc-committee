<?php
/*
 * Plugin Name:     SSBC - Committee
 * Plugin URI:      https://github.com/curiouscactus/ssbc-committee
 * Description:     Takes the users in this year's committee from the ssbc_committee table and puts them into a group with enhanced viewing permissions using the Groups plugin (https://wordpress.org/plugins/groups/). Makes a shortcode, [committee], which inserts a table and a JavaScript dropdown menu which allows viewing of present and past committees.
 * Author:          Lois Overvoorde
 * Author URI:      https://github.com/curiouscactus
 * Text Domain:     ssbc-committee
 * Version:         1.0.0
 */


// Exit if accessed directly

if( !defined( 'ABSPATH' ) ) exit;

// Load front-end shortcode

include( plugin_dir_path( __FILE__ ) . 'includes/shortcodes.php' );



//Get most recent year listed in the committee table (should be this year if it's up to date)

function get_committee_last_year(){

	global $wpdb;

	$cendyeara = $wpdb->get_results("SELECT DISTINCT `EndYear` FROM ssbc_committee ORDER BY `EndYear` DESC LIMIT 1");

	$cendyear = intval($cendyeara[0]->EndYear);
	
	return $cendyear;

}

//Get oldest year listed in the committee table

function get_committee_first_year(){

	global $wpdb;

	$cendyeara = $wpdb->get_results("SELECT DISTINCT `EndYear` FROM ssbc_committee ORDER BY `EndYear` ASC LIMIT 1");

	$cendyear = intval($cendyeara[0]->EndYear);
	
	return $cendyear;

}

//function to get an array of commitee members in the form $committee[position][name/email/ID]

function get_committee(){

	global $wpdb;
	
	//Get first and last years in the committee table

	$cendyearf = get_committee_first_year();
	$cendyearl = get_committee_last_year();

	//Get the array of committee members for this year

	$cresult = $wpdb->get_results("SELECT * FROM ssbc_committee");
	
	//Get the committee positions

	$cpresult = $wpdb->get_results("SELECT * FROM ssbc_committee_positions");

	//Make a mega array of all data
	
	$cendyear = $cendyearf;
	$committee = [];
	while ($cendyear <= $cendyearl) {
		foreach ( $cpresult as $cprow ) {
			$filled = false;
			foreach ( $cresult as $crow ) {
				if ($crow->EndYear == $cendyear && $crow->Position == $cprow->Position){
					$committee[$cendyear][$cprow->Position][name] = $crow->Name;
					$committee[$cendyear][$cprow->Position][email] = $cprow->Email;
					$committee[$cendyear][$cprow->Position][ID] = get_user_by('email', $crow->Email) -> ID; //gets IDs from main users table, not committee table
					$committee[$cendyear][$cprow->Position][avatar] = get_avatar($crow->Email);
					$committee[$cendyear][$cprow->Position][enc_email] = antispambot($cprow->Email);
					$filled = true;
				}else if($crow->EndYear == $cendyear && substr($crow->Position,0,-9) == $cprow->Position){
					$committee[$cendyear][$cprow->Position][resigned_name] = $crow->Name;
				}
			}
			if ($filled == false){
				$committee[$cendyear][$cprow->Position][name] = "";
				$committee[$cendyear][$cprow->Position][email] = "";
				$committee[$cendyear][$cprow->Position][ID] = "";
				$committee[$cendyear][$cprow->Position][avatar] = get_avatar();
				$committee[$cendyear][$cprow->Position][enc_email] = "";
			}
		}
		$cendyear++;
	}
	
	return $committee;

}





//function to update the committee group

function update_committee() {
	
  if ( class_exists('Groups_User_Group') && class_exists('Groups_Group')) {
  
  	//Get the committee group ID
  
  	$committee_group = Groups_Group::read_by_name('Committee');
  	
  	$group_ID = $committee_group->group_id;
  
  	//Remove old users from the committee group
  	
  	$old_committee_group = new Groups_Group( $group_ID );
  	
  	$old_committee = $old_committee_group->users; //the committee group held in the users table
  	
  	foreach ($old_committee as $member){
  
      Groups_User_Group::delete($member->ID, $group_ID );
		
	}

    //Add current users to the committee group
  
  	$committee = get_committee();
	$cendyear = get_committee_last_year();
  	
  	foreach ($committee[$cendyear] as $member){
		
    	Groups_User_Group::create( array( 'user_id' => $member[ID], 'group_id' => $group_ID ) );
		
	}
  } 
}

add_action('plugins_loaded', 'update_committee');