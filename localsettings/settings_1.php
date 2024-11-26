<?php

/*************************
 * Local settings for League with ID = X, as per settings_X.php
 *************************/
preg_match('/settings_(.*?)\.php/', __FILE__, $match);
$get_lid = $match[1];
$settings['stylesheet'] = 1; 
$settings['lang']            = 'en-GB';

/*********************
 *   General
 *********************/
// Change the Title after the = sign.  Do not change things before the = sign.
$settings['banner_subtitle'] = 'New here?  Visit theNAF.net/Leagues for more information';
// Button text for league URL.
$settings['league_url_name'] = 'League Forum'; 
// Stylesheet for text etc. Currently stylesheet 1 is the only existing stylesheet, so don't change it!  
// Default language. Existing: en-GB, es-ES, de-DE, fr-FR, it-IT. 
// Default is true. Generate coach, team and player links on the front page?       
$settings['fp_links']        = true;
$settings['league_name']     = get_alt_col('league_prefs','f_lid',$get_lid,'league_name'); 
$settings['banner_title']    = get_alt_col('league_prefs','f_lid',$get_lid,'league_name');
// URL of league home page, if you have one. If not then leave this empty, that is = '' (two quotes only), which will disable the button.
$settings['league_url']      = get_alt_col('league_prefs','f_lid',$get_lid,'forum_url');    
// The welcome text appears below the title.           
$settings['welcome']         = get_alt_col('league_prefs','f_lid',$get_lid,'welcome'); 
// The next text appears when you click the rules button.
$settings['rules']           = get_alt_col('league_prefs','f_lid',$get_lid,'rules'); 
$get_prime = get_alt_col('league_prefs','f_lid',$get_lid,'prime_tid');
$get_second = get_alt_col('league_prefs','f_lid',$get_lid,'second_tid');
// Keep the following the same.
$settings['tourlist_foldup_fin_divs'] = false; // Default is false. If true the division nodes in the tournament lists section will automatically be folded up if all child tournaments in that division are marked as finished.
$settings['tourlist_hide_nodes'] = array('league', 'division', 'tournament'); // Default is array('league', 'division', 'tournament'). In the section tournament lists these nodes will be hidden if their contents (children) are finished. Example: If 'division' is chosen here, and all tours in a given division are finished, then the division entry will be hidden.

/*********************
 *   Rules
 *********************/
// Please use the boolean values "true" and "false" wherever default values are boolean.
$rules['max_team_players']      = 16;       // Default is 16.
$rules['static_rerolls_prices'] = false;    // Default is "false". "true" forces re-roll prices to their un-doubled values.
$rules['player_refund']         = 0;        // Player sell value percentage. Default is 0 = 0%, 0.5 = 50%, and so on.
$rules['journeymen_limit']      = 11;       // Until a team can field this number of players, it may fill team positions with journeymen.
$rules['post_game_ff']          = false;    // Default is false. Allows teams to buy and drop fan factor even though their first game has been played.
$rules['initial_treasury']      = 1000000;  // Default is 1000000.
$rules['initial_rerolls']       = 0;        // Default is 0.
$rules['initial_fan_factor']    = 1;        // Default is 1.
$rules['initial_ass_coaches']   = 0;        // Default is 0.
$rules['initial_cheerleaders']  = 0;        // Default is 0.
// For the below limits, the following applies: -1 = unlimited. 0 = disabled.
$rules['max_rerolls']           = 8;       // Default is 8.
$rules['max_fan_factor']        = 9;        // Default is 9.
$rules['max_ini_fan_factor']      = 6;        // Default is 6.
$rules['max_ass_coaches']       = 6;       // Default is 6.
$rules['max_cheerleaders']       = 12;       // Default is 12.
// Allow/disallow teams of legend: 0 = enabled. 1 = disabled.
$rules['amazon'] 				= 1; 	//Amazon (teams of legend)
$rules['chorf'] 				= 0; 	//Chaos Dwarf (teams of legend)
$rules['helf'] 				= 0; 	//High Elf (teams of legend)
$rules['vamps'] 				= 1; 	//Vampires (teams of legend)
$rules['khemri'] 				= 0; 	//Tomb Kings (teams of legend)
$rules['slann'] 				= 0; 	//Slann (teams of legend)
// Allow/disallow dungeon bowl teams: 0 = enabled. 1 = disabled.
$rules['dungeon'] 				= 1; 	//Dungeon Bowl Teams
// Allow/disallow Mega-Stars: 0 = enabled. 1 = disabled.
$rules['megastars'] 				= 0; 	//Mega-Stars
// Additional League Points.
$rules['major_win_tds'] 		= 3; 	//Major Win for scoring how many TDs
$rules['major_win_pts'] 		= 1; 	//Bonus points for a Major Win
$rules['clean_sheet_pts'] 		= 1; 	//Bonus points for conceding 0 TDs (clean sheet)
$rules['major_beat_cas'] 		= 3; 	//Major Beating for scoring how many Casualties
$rules['major_beat_pts'] 		= 1; 	//Bonus points for a Major Beating
// Remove double backslashes in front of team number to enable team specific starting treasuries.
$rules['initial_team_treasury'] = array(	//	0			=>	1000000,	// Amazon (teams of legend)
											//	1			=>	1000000,	// Chaos Chosen
											//	2			=>	1000000,	// Chaos Dwarf
											//	3			=>	1000000,	// Dark Elf
											//	4			=>	1000000,	// Dwarf
											//	5			=>	1000000,	// Elf Union
											//	6			=>	1000000,	// Goblin
											//	7			=>	1000000,	// Halfling
											//	8			=>	1000000,	// High Elf
											//	9			=>	1000000,	// Human
											//	10			=>	1000000,	// Tomb Kings
											//	11			=>	1000000,	// Lizardman
											//	12			=>	1000000,	// Orc
											//	13			=>	1000000,	// Necromantic Horror
											//	14			=>	1000000,	// Norse
											//	15			=>	1000000,	// Nurgle
											//	16			=>	1000000,	// Ogre
											//	17			=>	1000000,	// Shambling Undead
											//	18			=>	1000000,	// Vampire
											//	19			=>	1000000,	// Skaven
											//	20			=>	1000000,	// Wood Elf
											//	21			=>	1000000,	// Chaos Renegades
											//	22			=>	1000000,	// Slann
											//	23			=>	1000000,	// Underworld Denizens
											//	24			=>	1000000,	// Old World Alliance
											//	25			=>	1000000,	// Snotling
											//	26			=>	1000000,	// Black Orc
											//	27			=>	1000000,	// Imperial Nobility
											//	28			=>	1000000,	// Khorne
											//	29			=>	1000000,	// Amazon
										);	

/*********************
 *   Standings pages
 *********************/
$settings['standings']['length_players'] = 30;  // Number of entries on the general players standings table.
$settings['standings']['length_teams']   = 30;  // Number of entries on the general teams   standings table.
$settings['standings']['length_coaches'] = 30;  // Number of entries on the general coaches standings table.

/*********************
 *   Front page messageboard
 *********************/
$settings['fp_messageboard']['length']               = 10;    // Number of entries on the front page message board.
$settings['fp_messageboard']['show_team_news']       = true; // Default is true. Show team news on the front page message board.
$settings['fp_messageboard']['show_match_summaries'] = true; // Default is true. Show match summaries on the front page message board.

/*********************
 *   Front page boxes
 *********************/
/*
    The below settings define which boxes to show on the right side of the front page.
    Note, every box MUST have a UNIQUE 'box_ID' number.
    The box IDs are used to determine the order in which the boxes are shown on the front page.
    The box with 'box_ID' = 1 is shown at the top of the page, the box with 'box_ID' = 2 is displayed underneath it and so forth.
*/

/*********************
 *   Front page: tournament standings boxes
 *********************/
$settings['fp_standings'] = array(
	/* ID is the unique ID of the node (league, division or tournament) to be displayed
	 * BOX_ID must be unique and will be displayed in numerical order
	 * TYPE may be one of: 'league', 'division' or 'tournament'
	 * INFOCUS if true a random team from the standings will be selected and its top players displayed.
	 * HRS is the House Ranking System NUMBER to sort the table against.
	   Note, this is ignored for "type = tournament", since tours have an assigned HRS.
	   Also note that using HRSs with fields such as points (pts) for leagues/divisions standings makes no sense as they are tournament specific fields
	   (i.e. it makes no sense to sum the points for teams across different tours to get the teams' "league/division points", as the points definitions for tours may vary).
	   Note: this must be a existing and valid HRS number from the main settings.php file.
	 * TITLE is the Table title
	 * LENGTH is the number of entries in table
	 * FIELDS is an array dictating which columns are displayed in the table. Use the format:
	   "Displayed table column name" => "OBBLM field name"
	   For the OBBLM fields available see https://github.com/nicholasmr/obblm/wiki
	 */
	# This will display a standings box of the top 6 teams in the Prime Tournament
	array(
		'id'		=> 	$get_prime,
		'box_ID' 	=> 	1,
		'type' 		=> 	'tournament',
		'infocus' 	=> 	true,
		'HRS' 		=> 	get_alt_col('tours','tour_id',$get_prime,'rs'), 
		'title' 	=> 	get_alt_col('tours','tour_id',$get_prime,'name'),
		'length' 	=> 	40, 
		'fields' 	=> 	array(	'Name'	=> 'name',
								'PTS'  	=> 'pts',
								'TV'	=> 'tv',
								'CAS'	=> 'cas',
								'W'		=> 'won',
								'L'		=> 'lost',
								'D'		=> 'draw',
								'GF'	=> 'gf',
								'GA'	=> 'ga',
						),
	), 
	# This will display a standings box of the top 6 teams in the Secondary Tournament
    array(
		'id'		=> 	$get_second,
		'box_ID' 	=> 	9,
		'type' 		=> 	'tournament',
		'infocus' 	=> 	false,
		'HRS' 		=> 	get_alt_col('tours','tour_id',$get_second,'rs'), 
		'title' 	=> 	get_alt_col('tours','tour_id',$get_second,'name'),
		'length' 	=> 	40, 
		'fields' 	=> 	array(	'Name'	=> 'name',
								'PTS'  	=> 'pts',
								'TV'	=> 'tv',
								'CAS'	=> 'cas',
								'W'		=> 'won',
								'L'		=> 'lost',
								'D'		=> 'draw',
								'GF'	=> 'gf',
								'GA'	=> 'ga',
						),
    ),
	# This will display a standings box of the top WINNING STREAKS with ID = 4
    array(	'id'     	=> $get_prime,
			'box_ID' 	=> 4,
			'type'   	=> 'tournament', 
			'infocus' 	=> 	false,
			'HRS' 		=> 	get_alt_col('tours','tour_id',$get_prime,'rs'),
			'title'  	=> 'Top 3 - Longest Winning Streaks', 
			'length' 	=> 3, 	
			'fields' 	=> array('Team' => 'name', 'Won' => 'swon',),
    ),
);

/*********************
 *   Front page: leaders boxes
 *********************/
$settings['fp_leaders'] = array(
    /* ID is the unique ID of the node (league, division or tournament) to be displayed
	 * BOX_ID must be unique and will be displayed in numerical order
	 * TYPE may be one of: 'league', 'division' or 'tournament'
	 * TITLE is the Table title
	 * FIELD is the field to be ranked.
	   Please note: You can NOT make expressions out of leader fields e.g.: 'field' => 'cas+td'
	   For the OBBLM fields available see http://nicholasmr.dk/obblmwiki/index.php?title=Customization
	 * LENGTH is the number of entries in table
	 * SHOW_TEAM if set to true, will include the Team name in the table
	 */
    # This will display a 'Most CAS' player leaders box for the Divison of the Prime Tournament
    array(	'id'        => get_alt_col('tours','tour_id',$get_prime,'f_did'), # Node ID
			'box_ID'    => 6,
			'type'      => 'division',
			'title'     => 'Top 5 Players - Casualties',
			'field'     => 'cas',
			'length'    => 5,
			'show_team' => true,
    ),
    # This will display a 'Most TD' player leaders box for the Division of the Prime Tournament
    array(	'id'        => get_alt_col('tours','tour_id',$get_prime,'f_did'),
			'box_ID'    => 5,
			'type'      => 'division',
			'title'     => 'Top 5 Players - Touchdowns',
			'field'     => 'td',
			'length'    => 5,
			'show_team' => true,
    ),
);

/*********************
 *   Front page: event boxes
 *********************/
$settings['fp_events'] = array(
	/* ID is the unique ID of the node (league, division or tournament) to be displayed
	 * BOX_ID must be unique and will be displayed in numerical order
	 * TYPE may be one of: 'league', 'division' or 'tournament'
	 * TITLE is the Table title
	 * CONTENT is the event type to be displayed and must be one of the following:
	   dead   - recent dead players
	   sold   - recent sold players
	   hired  - recent hired players
	   skills - recent player skill picks
	 * LENGTH is the number of entries in table
	 */
    # This will display a list of the most recent killed players for the Prime Tournament
    array(	'id'        => $get_lid,
			'box_ID'    => 7,
			'type'      => 'league',
			'title'     => 'Recently Deceased Players',
			'content'   => 'dead',
			'length'    => 5,
    ),
	# This will display a list of the most recent skills gained for the Prime Tournament
	array(	'id'        => $get_lid,
			'box_ID'    => 8,
			'type'      => 'league',
			'title'     => 'Recent Player Development',
			'content'   => 'skills',
			'length'    => 5,
    ),
);

/*********************
 *   Front page: latest games boxes
 *********************/
$settings['fp_latestgames'] = array(
	/* ID is the unique ID of the node (league, division or tournament) to be displayed
	 * BOX_ID must be unique and will be displayed in numerical order
	 * TYPE may be one of: 'league', 'division' or 'tournament'
	 * TITLE is the Table title
	 * LENGTH is the number of entries in table
	 */
    # This will display a latest games box for the Prime Tournament
    array(
        'id'     => $get_lid,
        'box_ID' => 2,
        'type'   => 'league',
        'title'  => 'Recent Games',
        'length' => 5,
    ),
);
