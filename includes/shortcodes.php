<?php

// Add Shortcode
function committee_shortcode($atts) {
	
	//get the committee mega-array
	$committee = get_committee();
	
	//get the most recent year in the committee database (hopefully this year if up to date)
	$cendyear = get_committee_last_year();
	
	//get the committee positions table, so you can loop through it in order and populate the table
	global $wpdb;
	$cpresult = $wpdb->get_results("SELECT * FROM ssbc_committee_positions ORDER BY `ID` ASC");
	
	//making a table row
	
	//header
	$tablecontents .= '"<table class=\"committee-table\"><tbody><tr><th>Position</th><th>Name</th><th>Photo</th></tr>';
	
	//contents of row
	foreach ( $cpresult as $cprow ) {
		
		if ($cprow->Position !== 'computing_2'){ //computing_2 was added so a second computing officer could view things as a committee member. This line removes them from the committee page.
		
			//the position label, as a mailto link to the committee email address
			$tablecontents .= '<tr><td><a href=\"mailto:" + committee[cendyear]["' . $cprow->Position . '"]["enc_email"] + "\">' . $cprow->Label . '</a></td>';
			
			//the name of the holder of the position at the end of the year
			$tablecontents .= '<td>" + committee[cendyear]["' . $cprow->Position . '"]["name"]';
			
			//inserts the name of the resigned holder of the position if it exists, using a ternary operator (condition ? if true : if false)
			$tablecontents .= ' + (committee[cendyear]["' . $cprow->Position . '"]["resigned_name"] ? " (previously " + committee[cendyear]["' . $cprow->Position . '"]["resigned_name"] + ")" : "") + "</td>';
			
			//the avatar of the holder of the position at the end of the year
			$tablecontents .= '<td>" + committee[cendyear]["' . $cprow->Position . '"]["avatar"] + "</td></tr>';
			
		}
	}
	
	//footer
	$tablecontents .= '</tbody></table>"';
	
	//the stuff that gets printed from the shortcode
	$output = '
	
        <select id="year_dropdown"></select>
		
		<div id="committee_div"></div>
		
		<script>
			
			jQuery(function() { //this fires when you load the page
			
				var defyear = ' . $cendyear . '; //get the current committee end year (to load as default)
				var select = document.getElementById("year_dropdown"); //get the dropdown which will be filled
				for(var i = 2012; i <= defyear; i++) { //loop through the years from 2011 to last one in table
					var option = document.createElement("option"); //create an option
					option.textContent = i-1 + "-" + i; //add the lable
					option.value = i; //add the value, which is the end year
					select.appendChild(option); //add the attributes to the option
				}
			
				jQuery("#year_dropdown").val(defyear); //change the dropdown to show the current committee end year
				
				var cendyear = jQuery("#year_dropdown").val(); //take the value of the dropdown as the committee end year to use for loading content
				var committee = ' . json_encode($committee) . '; //convert the committee array from php to javascript
				var tablecontents = ' . str_replace(array("\r", "\n", "\t"), '', $tablecontents) . '; //get table contents (no new lines)
				document.getElementById("committee_div").innerHTML = tablecontents; //fill committee_div with the contents
			});
			
			jQuery("#year_dropdown").change(function() { //this fires when you change the dropdown
			
				var cendyear = jQuery("#year_dropdown").val(); //take the value of the dropdown as the committee end year to use for loading content
				var committee = ' . json_encode($committee) . '; //convert the committee array from php to javascript
				var tablecontents = ' . str_replace(array("\r", "\n", "\t"), '', $tablecontents) . '; //get table contents (no new lines)
				document.getElementById("committee_div").innerHTML = tablecontents; //fill committee_div with the contents
			});
			
		</script>';

	return $output;
}

add_shortcode( 'committee', 'committee_shortcode' );

