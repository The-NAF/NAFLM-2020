<?php

// DO NOT DELETE THIS FILE!!!
// Use this local settings file for leagues which have no local settings file.
$settings['banner_title']             = 'No settings file exists for the selected league';
$settings['banner_subtitle']          = 'No settings file exists for the selected league';
$settings['league_name']              = 'No settings file exists for the selected league';
$settings['league_url']               = '';
$settings['league_url_name']          = 'League URL disabled';
$settings['stylesheet']               = 1;
$settings['lang']                     = 'en-GB';
$settings['fp_links']                 = true;
$settings['welcome']                  = 'Could not find the local league settings file for the selected league at <i>localsettings/settings_&lt;LEAGUE ID&gt;.php</i>';
$settings['rules']                    = 'No settings file exists for the selected league';
$settings['tourlist_foldup_fin_divs'] = false;
$settings['tourlist_hide_nodes']      = array('league', 'division', 'tournament');

$rules['max_team_players']     		= 16;
$rules['max_team_players_sevens'] 	= 11; 
$rules['static_rerolls_prices']		= false;
$rules['player_refund']        		= 0;
$rules['journeymen_limit']     		= 11;
$rules['journeymen_limit_sevens'] 	= 7; 
$rules['post_game_ff']         		= false;
$rules['post_game_rr']         		= false;
		
$rules['initial_treasury']     		= 1000000;
$rules['initial_treasury_sevens'] 	= 600000;
$rules['initial_rerolls']      		= 0;
$rules['initial_fan_factor']   		= 1;
$rules['initial_ass_coaches']  		= 0;
$rules['initial_cheerleaders'] 		= 0;
		
$rules['max_rerolls']          		= 8;
$rules['max_rerolls_sevens']    	= 6;
$rules['max_fan_factor']       		= 7;
$rules['max_ini_fan_factor']   		= 5; 
$rules['max_ass_coaches']      		= 6;
$rules['max_cheerleaders']     		= 12; 
$rules['max_ass_coaches_sevens'] 	= 3;
$rules['max_cheerleaders_sevens'] 	= 6;
		
$rules['amazon'] 					= 1; 	//Amazon (teams of legend)
$rules['chorf'] 					= 1; 	//Chaos Dwarf (teams of legend)
$rules['helf'] 						= 0; 	//High Elf (teams of legend)
$rules['vamps'] 					= 1; 	//Vampires (teams of legend)
$rules['khemri'] 					= 0; 	//Tomb Kings (teams of legend)

$rules['sevens'] 					= 1; 	//Sevens Teams
$rules['dungeon'] 					= 1; 	//Dungeon Bowl Teams
$rules['randomskillrolls'] 			= 1; 	//Automated Random Skill Rolls
$rules['randomskillmanualentry'] 	= 0; 	//Manual Random Skill Rolls
$rules['megastars'] 				= 0; 	//Mega-Stars

$rules['major_win_tds'] 			= 0; 	//Major Win for scoring how many TDs
$rules['major_win_pts'] 			= 0; 	//Bonus points for a Major Win
$rules['clean_sheet_pts'] 			= 0; 	//Bonus points for conceding 0 TDs (clean sheet)
$rules['major_beat_cas'] 			= 0; 	//Major Beating for scoring how many Casualties
$rules['major_beat_pts'] 			= 0; 	//Bonus points for a Major Beating

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
											//	18			=>	1000000,	// Vampire (teams of legend)
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
											//	30			=>	1000000,	// Vampire
											//	34			=>	1000000,	// Gnomes
										);

$settings['standings']['length_players'] = 30;
$settings['standings']['length_teams']   = 30;
$settings['standings']['length_coaches'] = 30;

$settings['fp_messageboard']['length']               = 5;
$settings['fp_messageboard']['show_team_news']       = true;
$settings['fp_messageboard']['show_match_summaries'] = true;

$settings['fp_standings']   = array();
$settings['fp_leaders']     = array();
$settings['fp_events']      = array();
$settings['fp_latestgames'] = array();
