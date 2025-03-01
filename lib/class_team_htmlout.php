<?php

define('T_HTML_TEAMS_PER_PAGE', 50);

class Team_HTMLOUT extends Team
{
	public static function dispList() {
		global $lng;
		/*
			NOTE: We do NOT show teams not having played any matches for nodes = {T_NODE_TOURNAMENT, T_NODE_DIVISION}.
		*/
		list($sel_node, $sel_node_id, $sel_state, $sel_race) = HTMLOUT::nodeSelector(array('race' => true, 'state' => true));
		$ALL_TIME = ($sel_node === false && $sel_node_id === false);
		$fields = '_RRP AS "team_id", owned_by_coach_id, f_race_id, teams.name AS "tname", f_cname, f_rname, tv, teams.rdy AS "rdy", teams.retired AS "retired"';
		$where = array();
		if ($sel_state == T_STATE_ACTIVE) $where[] = 'teams.rdy IS TRUE AND teams.retired IS FALSE';
		if ($sel_race != T_RACE_ALL) 	  $where[] = "teams.f_race_id = $sel_race";
		if ($sel_node == T_NODE_LEAGUE || $ALL_TIME) {
			if (!$ALL_TIME) {
				$where[] = "f_lid = $sel_node_id";
			}
			$where = (count($where) > 0) ? 'WHERE '.implode(' AND ', $where) : '';
			$queryCnt = "SELECT COUNT(*) FROM teams $where";
			$queryGet = 'SELECT '.preg_replace('/\_RRP/', 'team_id', $fields).' FROM teams '.$where.' ORDER BY tname ASC';
		} else {
			$q = "SELECT $fields FROM matches, teams, tours, divisions WHERE matches._RRP = teams.team_id AND matches.f_tour_id = tours.tour_id AND tours.f_did = divisions.did ";
			switch ($sel_node) {
				case false: break;
				case T_NODE_TOURNAMENT: $q .= "AND tours.tour_id = $sel_node_id";   break;
				case T_NODE_DIVISION:   $q .= "AND divisions.did = $sel_node_id";   break;
				case T_NODE_LEAGUE:     $q .= "AND divisions.f_lid = $sel_node_id"; break;
			}
			$q .= (count($where) > 0 ? ' AND ' : ' ').implode(' AND ', $where).' ';
			$_subt1 = '('.preg_replace('/\_RRP/', 'team1_id', $q).')';
			$_subt2 = '('.preg_replace('/\_RRP/', 'team2_id', $q).')';
			$queryCnt = "SELECT COUNT(*) FROM (($_subt1) UNION DISTINCT ($_subt2)) AS tmp";
			$queryGet = '('.$_subt1.') UNION DISTINCT ('.$_subt2.') ORDER BY tname ASC';
		}

		$result = mysql_query($queryCnt);
		list($cnt) = mysql_fetch_row($result);
		$pages = ($cnt == 0) ? 1 : ceil($cnt/T_HTML_TEAMS_PER_PAGE);
		global $DEA, $rules, $page;
		$page = (isset($_GET['page']) && $_GET['page'] <= $pages) ? $_GET['page'] : 1; # Page 1 is default, of course.
		$_url = "?section=teamlist&amp;";
		echo '<br><center><table>';
		echo '<tr><td>';
		echo $lng->getTrn('common/page').': '.implode(', ', array_map(create_function('$nr', 'global $page; return ($nr == $page) ? $nr : "<a href=\''.$_url.'page=$nr\'>$nr</a>";'), range(1,$pages)));
		echo '</td></td>';
		echo "<tr><td>".$lng->getTrn('common/teams').": $cnt</td></td>";
		echo '</table></center><br>';
		$queryGet .= ' LIMIT '.(($page-1)*T_HTML_TEAMS_PER_PAGE).', '.(($page)*T_HTML_TEAMS_PER_PAGE);

		$teams = array();
		$result = mysql_query($queryGet);
		while ($t = mysql_fetch_object($result)) {
			$img = new ImageSubSys(IMGTYPE_TEAMLOGO, $t->team_id);
			$t->logo = "<img border='0px' height='20' width='20' alt='Team race picture' src='".$img->getPath($t->f_race_id)."'>";
			$format = $t->format;
			$t->format = $lng->getTrn('common/'.strtolower($DEA[$t->f_rname]['other']['format']));
			$retired = $t->retired;
			$t->retired = ($t->retired) ? '<b>'.$lng->getTrn('common/yes').'</b>' : $lng->getTrn('common/no');
			$t->rdy = ($t->rdy && !$retired) ? '<font color="green">'.$lng->getTrn('common/yes').'</font>' : '<font color="red">'.$lng->getTrn('common/no').'</font>';
			$t->f_rname = $lng->getTrn('race/'.strtolower(str_replace(' ','', $t->f_rname)));
			$teams[] = $t;
		}
		if ($rules['dungeon'] == 0 || $rules['sevens'] == 0) {
			$fields = array(
				'logo'    => array('desc' => 'Logo', 'nosort' => true, 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_TEAM,false,false,false), 'field' => 'obj_id', 'value' => 'team_id'), 'nosort' => true),
				'tname'   => array('desc' => $lng->getTrn('common/name'), 'nosort' => true, 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_TEAM,false,false,false), 'field' => 'obj_id', 'value' => 'team_id')),
				'f_cname' => array('desc' => $lng->getTrn('common/coach'), 'nosort' => true, 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_COACH,false,false,false), 'field' => 'obj_id', 'value' => 'owned_by_coach_id')),
				'format'  => array('desc' => $lng->getTrn('common/format'), 'nosort' => true),
				'rdy'     => array('desc' => $lng->getTrn('common/ready'), 'nosort' => true),
				'retired' => array('desc' => $lng->getTrn('common/retired'), 'nosort' => true),
				'f_rname' => array('desc' => $lng->getTrn('common/race'), 'nosort' => true, 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_RACE,false,false,false), 'field' => 'obj_id', 'value' => 'f_race_id')),
				'tv'      => array('desc' => 'TV', 'nosort' => true, 'kilo' => true, 'suffix' => 'k'),
		);
		} else {
			$fields = array(
				'logo'    => array('desc' => 'Logo', 'nosort' => true, 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_TEAM,false,false,false), 'field' => 'obj_id', 'value' => 'team_id'), 'nosort' => true),
				'tname'   => array('desc' => $lng->getTrn('common/name'), 'nosort' => true, 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_TEAM,false,false,false), 'field' => 'obj_id', 'value' => 'team_id')),
				'f_cname' => array('desc' => $lng->getTrn('common/coach'), 'nosort' => true, 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_COACH,false,false,false), 'field' => 'obj_id', 'value' => 'owned_by_coach_id')),
				'rdy'     => array('desc' => $lng->getTrn('common/ready'), 'nosort' => true),
				'retired' => array('desc' => $lng->getTrn('common/retired'), 'nosort' => true),
				'f_rname' => array('desc' => $lng->getTrn('common/race'), 'nosort' => true, 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_RACE,false,false,false), 'field' => 'obj_id', 'value' => 'f_race_id')),
				'tv'      => array('desc' => 'TV', 'nosort' => true, 'kilo' => true, 'suffix' => 'k'),
		);	
			
		}
		HTMLOUT::sort_table(
			$lng->getTrn('common/teams'),
			"index.php$_url",
			$teams,
			$fields,
			array(),
			array(),
			array('doNr' => false, 'noHelp' => true, 'noSRdisp' => true)
		);
	}

	public static function standings($node = false, $node_id = false) {
		global $lng, $settings;
		title($lng->getTrn('menu/statistics_menu/team_stn'));
		echo $lng->getTrn('common/notice_simul')."<br><br>\n";
		list($teams, $sortRule) = HTMLOUT::standings(STATS_TEAM,$node,$node_id,array('url' => urlcompile(T_URL_STANDINGS,T_OBJ_TEAM,false,false,false), 'hidemenu' => false, 'return_objects' => true));
		$fields = array(
			'name'         => array('desc' => $lng->getTrn('common/team'), 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_TEAM,false,false,false), 'field' => 'obj_id', 'value' => 'team_id')),
			'f_rname'      => array('desc' => $lng->getTrn('common/race'), 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_RACE,false,false,false), 'field' => 'obj_id', 'value' => 'f_race_id')),
			'f_cname'      => array('desc' => $lng->getTrn('common/coach'), 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_COACH,false,false,false), 'field' => 'obj_id', 'value' => 'owned_by_coach_id')),
			'rg_ff'        => array('desc' => 'Dedicated Fans'),
			'rerolls'      => array('desc' => 'RR'),
			'ass_coaches'  => array('desc' => 'Ass. coaches'),
			'cheerleaders' => array('desc' => 'Cheerleaders'),
			'treasury'     => array('desc' => 'Treasury', 'kilo' => true, 'suffix' => 'k'),
			'tv'           => array('desc' => 'TV', 'kilo' => true, 'suffix' => 'k'),
		);

		HTMLOUT::sort_table(
			$lng->getTrn('standings/team/tblTitle2'),
			urlcompile(T_URL_STANDINGS,T_OBJ_TEAM,false,false,false),
			$teams,
			$fields,
			$sortRule,
			array(),
			array('noHelp' => true, 'noSRdisp' => false, 'doNr' => false)
		);
	}

	public static function profile($tid) {
		global $coach, $settings, $rules;
		$t = new self($tid);
		setupGlobalVars(T_SETUP_GLOBAL_VARS__LOAD_LEAGUE_SETTINGS, array('lid' => $t->f_lid)); // Load correct $rules for league.

		/* Argument(s) passed to generating functions. */
		$ALLOW_EDIT = $t->allowEdit(); # Show team action boxes?
		$DETAILED   = (isset($_GET['detailed']) && $_GET['detailed'] == 1);# Detailed roster view?

		/* Team pages consist of the output of these generating functions. */
		$t->handleActions($ALLOW_EDIT); # Handles any actions/request sent.
		list($players, $players_backup) = $t->_loadPlayers($DETAILED); # Should come after handleActions().
		$t->_roster($ALLOW_EDIT, $DETAILED, $players);
		$players = $players_backup; # Restore the $players array (_roster() manipulates the passed $players array).
		$t->_menu($ALLOW_EDIT, $DETAILED);

		switch (isset($_GET['subsec']) ? $_GET['subsec'] : 'man') {
			case 'hhmerc': $t->_HHMerc($DETAILED); break;
			case 'hhstar': $t->_HHStar($DETAILED); break;
			case 'man': $t->_actionBoxes($ALLOW_EDIT, $players); break;
			case 'about': $t->_about($ALLOW_EDIT); break;
			case 'news': $t->_news($ALLOW_EDIT); break;
			case 'games': $t->_games(); break;
		}
		if (isset($_GET['subsec'])){
			?>
			<!-- Following HTML from ./lib/class_team_htmlout.php profile -->
			<script language="JavaScript" type="text/javascript">
			window.location = "#anc";
			</script>
			<?php
		}
	}

	public function handleActions($ALLOW_EDIT) {
		global $coach;
		$team = $this; // Copy. Used instead of $this for readability.
		// No request sent?
		if (!isset($_POST['type']) || !$ALLOW_EDIT) {
			return false;
		}
		// Handle request.
		if (get_magic_quotes_gpc()) {
			$_POST['name']     = stripslashes(isset($_POST['name'])  ? $_POST['name']  : '');
			$_POST['skill']    = stripslashes(isset($_POST['skill']) ? $_POST['skill'] : '');
			$_POST['thing']    = stripslashes(isset($_POST['thing']) ? $_POST['thing'] : '');
			$_POST['teamtext'] = stripslashes(isset($_POST['teamtext']) ? $_POST['teamtext'] : '');
			$_POST['txt']      = stripslashes(isset($_POST['txt']) ? $_POST['txt'] : '');
		}
		$p = (isset($_POST['player']) && $_POST['type'] != 'hire_player') ? new Player($_POST['player']) : null;
		switch ($_POST['type']) {
			case 'select_rule':      status($team->selectRule($_POST['rule'])); break;	
			case 'hire_player':
				list($exitStatus, $pid) = Player::create(
					array(
						'nr'        => $_POST['number'],
						'f_pos_id'  => $_POST['player'],
						'team_id'   => $team->team_id,
						'name'      => $_POST['name']
					),
					array(
						'JM' => isset($_POST['as_journeyman']) && $_POST['as_journeyman']
					)
				);
				status(!$exitStatus, $exitStatus ? Player::$T_CREATE_ERROR_MSGS[$exitStatus] : null);
				break;
			case 'hire_journeyman': status($p->hireJourneyman()); break;
			case 'fire_player':     status($p->sell()); break;
			case 'unbuy_player':    status($p->unbuy()); break;
			case 'rename_player':   status($p->rename($_POST['name'])); break;
			case 'renumber_player': status($p->renumber($_POST['number'])); break;
			case 'random_skill': 	
				$skill_type = $_POST['skill_type'];
				$skill_cat = $_POST['skill_cat'];
				status($p->randomSkill($skill_type,$skill_cat));
				break;
			case 'retire_player':   status($p->retirePlayer());
					SQLTriggers::run(T_SQLTRIG_TEAM_DPROPS, array('obj' => T_OBJ_TEAM, 'id' => $team->team_id));
					break;
			case 'rename_team':     status($team->rename($_POST['name'])); break;
			case 'buy_goods':       status($team->buy($_POST['thing'])); break;
			case 'drop_goods':      status($team->drop($_POST['thing'])); break;
			case 'ready_state':     status($team->setReady(isset($_POST['bool']))); break;
			case 'retire':          status(isset($_POST['bool']) && $team->setRetired(true)); break;
			case 'delete':          status(isset($_POST['bool']) && $team->delete()); break;
			case 'skill':
				$type = null;
				$skillcost = $_POST['skillcost'];
				list($skcost, $skcosttype) = explode('|', $skillcost);
				$p->setChoosableSkills();
				if     (in_array($_POST['skill'], $p->choosable_skills['norm'])) $type = 'N';
				elseif (in_array($_POST['skill'], $p->choosable_skills['doub'])) $type = 'D';
				else                                                             $type = 'C'; # Assume it's a characteristic.
				status($p->addSkill($type, ($type == 'C') ? (int) str_replace('ach_','',$_POST['skill']) : (int) $_POST['skill'], $skcost, $skcosttype));
				break;
			case 'teamtext': 	status($team->saveText($_POST['teamtext'])); break;
			case 'teamsponsor': status($team->saveSponsor($_POST['teamsponsor'])); break;
			case 'teamstadium': status($team->saveStadium($_POST['teamstadium'])); break;
			case 'news':     	status($team->writeNews($_POST['txt'])); break;
			case 'newsdel':  	status($team->deleteNews($_POST['news_id'])); break;
			case 'newsedit': 	status($team->editNews($_POST['news_id'], $_POST['txt'])); break;
			case 'pic':
				if ($_POST['add_del'] == 'add') {
					if ($_POST['pic_obj'] == IMGTYPE_TEAMSTADIUM) {
						list($status, $msg) = $team->saveStadiumPic(ImageSubSys::$defaultHTMLUploadName.'_stad');
						status($status, (!$status) ? $msg : '');
					}
					elseif ($_POST['pic_obj'] == IMGTYPE_TEAMLOGO) {
						list($status, $msg) = $team->saveLogo(ImageSubSys::$defaultHTMLUploadName.'_logo');
						status($status, (!$status) ? $msg : '');
					}
				} else {
					if ($_POST['pic_obj'] == IMGTYPE_TEAMSTADIUM)
						status($team->deleteStadiumPic());
					elseif ($_POST['pic_obj'] == IMGTYPE_TEAMLOGO)
						status($team->deleteLogo());
				}
				break;
		}

		// Administrator tools used?
		if ($coach->isNodeCommish(T_NODE_LEAGUE, $team->f_lid)) {
			switch ($_POST['type']) {
				case 'unhire_journeyman': status($p->unhireJourneyman()); break;
				case 'unsell_player':     status($p->unsell()); break;
				case 'unbuy_goods':       status($team->unbuy($_POST['thing'])); break;
				case 'bank':
					status($team->dtreasury($dtreas = ($_POST['sign'] == '+' ? 1 : -1) * $_POST['amount'] * 1000));
					if (Module::isRegistered('LogSubSys')) {
						Module::run('LogSubSys', array('createEntry', T_LOG_GOLDBANK, $coach->coach_id, "Coach '$coach->name' (ID=$coach->coach_id) added a treasury delta for team '$team->name' (ID=$team->team_id) of amount = $dtreas"));
					}
					SQLTriggers::run(T_SQLTRIG_TEAM_DPROPS, array('obj' => T_OBJ_TEAM, 'id' => $team->team_id));
					break;
				case 'spp':               status($p->dspp(($_POST['sign'] == '+' ? 1 : -1) * $_POST['amount'])); break;
				case 'dval':              status($p->dval(($_POST['sign'] == '+' ? 1 : -1) * $_POST['amount']*1000)); break;
				case 'extra_skills':
					$func = ($_POST['sign'] == '+') ? 'addSkill' : 'rmSkill';
					status($p->$func('E', $_POST['skill']));
					break;
				case 'ach_skills':
					$type = null;
					if     (in_array($_POST['skill'], $p->ach_nor_skills))  $type = 'N';
					elseif (in_array($_POST['skill'], $p->ach_dob_skills))  $type = 'D';
					else                                                    $type = 'C'; # Assume it's a characteristic.
					status($p->rmSkill($type, ($type == 'C') ? (int) str_replace('ach_','',$_POST['skill']) : (int) $_POST['skill']));
					break;
			   case 'ff':
					status($team->setff_bought($_POST['amount']));
					SQLTriggers::run(T_SQLTRIG_TEAM_DPROPS, array('obj' => T_OBJ_TEAM, 'id' => $team->team_id));
					break;
				case 'removeNiggle': status($p->removeNiggle()); break;
				case 'addniggle': status($p->addniggle()); break;
				case 'removeMNG': status($p->removeMNG()); 
					SQLTriggers::run(T_SQLTRIG_TEAM_DPROPS, array('obj' => T_OBJ_TEAM, 'id' => $team->team_id));
					break;
				case 'removenegastat': status($p->removenegastat($_POST['stat'])); break;
				case 'resetrule': status($team->resetRule()); break;
				case 'transferteam': status($team->setOwnership($_POST['coachname'])); break;
			}
		}
		$team->setStats(false,false,false); # Reload fields in case they changed after team actions made.
	}

	private function _loadPlayers($DETAILED) {
		/*
			Lets prepare the players for the roster.
		*/
		global $settings;
		$team = $this; // Copy. Used instead of $this for readability.
		$players = $players_org = array();
		$players_org = $team->getPlayers();
		// Make two copies: We will be overwriting $players later when the roster has been printed, so that the team actions boxes have the correct untempered player data to work with.
		foreach ($players_org as $p) {
			array_push($players, clone $p);
		}
		// Filter players depending on settings and view mode.
		$tmp_players = array();
		foreach ($players as $p) {
			if (!$DETAILED && ($p->is_dead || $p->is_sold)) {
				continue;
			}
			array_push($tmp_players, $p);
		}
		$players = $tmp_players;
		return array($players, $players_org);
	}

	private function _roster($ALLOW_EDIT, $DETAILED, $players) {
		global $rules, $settings, $lng, $skillididx, $coach, $DEA;
		$team = $this; // Copy. Used instead of $this for readability.

		/******************************
		 *   Make the players ready for roster printing.
		 ******************************/
		foreach ($players as $p) {
			/*
				Misc
			*/
			$p->name = preg_replace('/\s/', '&nbsp;', $p->name);
			$p->position = preg_replace('/\s/', '&nbsp;', $p->position);
			$p->info = '<i class="icon-info"></i>';
			$p->team_id = $team->team_id;
			/*
				Colors
			*/
			// Fictive player color fields used for creating player table.
			$p->HTMLfcolor = '#000000';
			$p->HTMLbcolor = COLOR_HTML_NORMAL;
			if     ($p->is_sold && $DETAILED)   $p->HTMLbcolor = COLOR_HTML_SOLD; # Sold has highest priority.
			elseif ($p->is_dead && $DETAILED)   $p->HTMLbcolor = COLOR_HTML_DEAD;
			elseif ($p->is_mng)                 $p->HTMLbcolor = COLOR_HTML_MNG;
			elseif ($p->is_retired)             $p->HTMLbcolor = COLOR_HTML_RETIRED;
			elseif ($p->is_journeyman_used)     $p->HTMLbcolor = COLOR_HTML_JOURNEY_USED;
			elseif ($p->is_journeyman)          $p->HTMLbcolor = COLOR_HTML_JOURNEY;
			elseif ($p->mayHaveNewSkill())      $p->HTMLbcolor = COLOR_HTML_NEWSKILL;
			elseif ($DETAILED)                  $p->HTMLbcolor = COLOR_HTML_READY;
			$p->skills   = '<small>'.$p->getSkillsStr(true).'</small>';
			$p->injs     = $p->getInjsStr(true);
			$p->position = "<table style='border-spacing:0px;'><tr><td><img align='left' src='$p->icon' alt='player avatar'></td><td>".$lng->getTrn("position/".strtolower($lng->FilterPosition($p->position)))."</td></tr></table>";
			if ($DETAILED) {
				$p->mv_cas = "$p->mv_bh/$p->mv_si/$p->mv_ki";
				$p->mv_spp = "$p->mv_spp/$p->extra_spp";
			}
			// Characteristic's colors
			foreach (array('ma', 'ag', 'pa', 'av', 'st') as $chr) {
				$sub = $p->$chr - $p->{"def_$chr"};
				$defchr = $p->{"def_$chr"};
				if  ($chr == 'ma' || $chr == 'av' || $chr == 'st' ) {
					if ($sub == 0) {
						// Nothing!
					}
					elseif ($sub == 1)  $p->{"${chr}_color"} = COLOR_HTML_CHR_EQP1;
					elseif ($sub > 1)   $p->{"${chr}_color"} = COLOR_HTML_CHR_GTP1;
					elseif ($sub == -1) $p->{"${chr}_color"} = COLOR_HTML_CHR_EQM1;
					elseif ($sub < -1)  $p->{"${chr}_color"} = COLOR_HTML_CHR_LTM1;
					if ($p->$chr != $p->{"${chr}_ua"}) {
						$p->{"${chr}_color"} = COLOR_HTML_CHR_BROKENLIMIT;
						$p->$chr = $p->{$chr.'_ua'}.' <i>('.$p->$chr.' eff.)</i>';
					}
				}
				else {
					if ($defchr > 0) {
						if ($sub == 0) {
							// Nothing!
						}
						elseif ($sub == 1)  $p->{"${chr}_color"} = COLOR_HTML_CHR_EQM1;
						elseif ($sub > 1)   $p->{"${chr}_color"} = COLOR_HTML_CHR_LTM1;
						elseif ($sub == -1) $p->{"${chr}_color"} = COLOR_HTML_CHR_EQP1;
						elseif ($sub < -1)  $p->{"${chr}_color"} = COLOR_HTML_CHR_GTP1;
						if ($p->$chr != $p->{"${chr}_ua"}) {
							$p->{"${chr}_color"} = COLOR_HTML_CHR_BROKENLIMIT;
							$p->$chr = $p->{$chr.'_ua'}.' <i>('.$p->$chr.' eff.)</i>';
						}	
					}
					else {
						if ($sub == 0) {
							// Nothing!
						}
						elseif ($sub == 7)  $p->{"${chr}_color"} = COLOR_HTML_CHR_EQM1;
						elseif ($sub > 7)   $p->{"${chr}_color"} = COLOR_HTML_CHR_LTM1;
						elseif ($sub == 6) $p->{"${chr}_color"} = COLOR_HTML_CHR_EQP1;
						elseif ($sub < 6)  $p->{"${chr}_color"} = COLOR_HTML_CHR_GTP1;
						if ($p->$chr != $p->{"${chr}_ua"}) {
							$p->{"${chr}_color"} = COLOR_HTML_CHR_BROKENLIMIT;
							$p->$chr = $p->{$chr.'_ua'}.' <i>(5 eff.)</i>';
						}	
					}
				}
			}
			/*
				New skills drop-down.
			*/
			$x = '';
			/*if ($ALLOW_EDIT && $p->mayHaveNewSkill()) {
				$x = ' &nbsp&nbsp&nbsp<a href="index.php?section=objhandler&type=1&obj=1&obj_id='.$p->player_id.'"><small>New Skill</small></a>';
			*/
			if ($rules['randomskillmanualentry'] == 1 && $DEA[$team->f_rname]['other']['format'] != 'SV') {
				if ($ALLOW_EDIT && $p->mayHaveNewSkill()) {
					$x = '<BR><small>&nbsp&nbsp&nbsp<u>Random skill available, see Team management box below</u></small>';
					}
				if ($DEA[$team->f_rname]['other']['format'] != 'SV') {
					if ($ALLOW_EDIT && $p->mayHaveNewChosenSkill()) {
					$x .= '<small><u> OR select a chosen skill and appropriate cost:</u></small><BR>';
					$x .= "<form method='POST'>\n";
					$x .= "<select name='skill'>\n";
					$x .= "<option selected value='999'>-- Select Skill --</option>\n";
					$x .= "<optgroup label='Primary skills'>\n";
					foreach ($p->choosable_skills['norm'] as $s) {
						if (($p->numberOfAchSkill() == 0 && $p->mv_spp > 5) || ($p->numberOfAchSkill() == 1 && $p->mv_spp > 7) || ($p->numberOfAchSkill() == 2 && $p->mv_spp > 11) || ($p->numberOfAchSkill() == 3 && $p->mv_spp > 15) || ($p->numberOfAchSkill() == 4 && $p->mv_spp > 19) || ($p->numberOfAchSkill() == 5 && $p->mv_spp > 29))	{ 					
							$x .= "<option value='$s'>".$skillididx[$s]."</option>\n";
						}
					}
					$x .= "</optgroup>\n";
					if (($p->numberOfAchSkill() == 0 && $p->mv_spp > 11) || ($p->numberOfAchSkill() == 1 && $p->mv_spp > 13) || ($p->numberOfAchSkill() == 2 && $p->mv_spp > 17) || ($p->numberOfAchSkill() == 3 && $p->mv_spp > 21) || ($p->numberOfAchSkill() == 4 && $p->mv_spp > 25) || ($p->numberOfAchSkill() == 5 && $p->mv_spp > 39))	{ 
						$x .= "<optgroup label='Secondary skills'>\n";
						foreach ($p->choosable_skills['doub'] as $s) {
							$x .= "<option value='$s'>".$skillididx[$s]."</option>\n";
						}
						$x .= "</optgroup>\n";
					}
					if (($p->numberOfAchSkill() == 0 && $p->mv_spp > 17) || ($p->numberOfAchSkill() == 1 && $p->mv_spp > 19) || ($p->numberOfAchSkill() == 2 && $p->mv_spp > 23) || ($p->numberOfAchSkill() == 3 && $p->mv_spp > 27) || ($p->numberOfAchSkill() == 4 && $p->mv_spp > 31) || ($p->numberOfAchSkill() == 5 && $p->mv_spp > 49))	{
						$x .= "<optgroup label='Characteristic improvement'>\n";
						foreach ($p->choosable_skills['chr'] as $s) {
							global $CHR_CONV;
							if  ($CHR_CONV[$s] == 'ma' || $CHR_CONV[$s] == 'av' || $CHR_CONV[$s] == 'st' ) {
								$x .= "<option value='ach_$s'>+ ".ucfirst($CHR_CONV[$s])."</option>\n";
							}
							else {
								$x .= "<option value='ach_$s'>- ".ucfirst($CHR_CONV[$s])."</option>\n";	
							}
						}
						$x .= "</optgroup>\n";
					}		
					$x .= "</select>\n";
					$x .= "<select name='skillcost'>\n";
					$x .= "<option selected value='99'>-- Select Skill Cost --</option>\n";
						# variable spp cost dependant on number of already achieved skills, options appear only if SPP available and if team is not sevens
						
						if ($p->numberOfAchSkill() == 0) { 
							if ($p->mv_spp > 5) { 
							$x .= "<option value='6|P'>6 SPP (Chosen Primary)</option>\n";
								if ($p->mv_spp > 11) { 
								$x .= "<option value='12|S'>12 SPP (Chosen Secondary)</option>\n";
									if ($p->mv_spp > 17) { 
									$x .= "<option value='18|X'>18 SPP (Random Stat Improvement)</option>\n";
									$x .= "<option value='18|S'>18 SPP (Chosen Secondary instead of Rolled Stat Improvement)</option>\n";
									}
								}
							}
						} elseif ($p->numberOfAchSkill() == 1) { 
							if ($p->mv_spp > 7) { 
							$x .= "<option value='8|P'>8 SPP (Chosen Primary)</option>\n";
								if ($p->mv_spp > 13) { 
								$x .= "<option value='14|S'>14 SPP (Chosen Secondary)</option>\n";
									if ($p->mv_spp > 19) { 
									$x .= "<option value='20|X'>20 SPP (Random Stat Improvement)</option>\n";
									$x .= "<option value='20|S'>20 SPP (Chosen Secondary instead of Rolled Stat Improvement)</option>\n";
									}
								}
							}
						} elseif ($p->numberOfAchSkill() == 2) { 
							if ($p->mv_spp > 11) { 
							$x .= "<option value='12|P'>12 SPP (Chosen Primary)</option>\n";
								if ($p->mv_spp > 17) { 
								$x .= "<option value='18|S'>18 SPP (Chosen Secondary)</option>\n";
									if ($p->mv_spp > 23) { 
									$x .= "<option value='24|X'>24 SPP (Random Stat Improvement)</option>\n";
									$x .= "<option value='24|S'>24 SPP (Chosen Secondary instead of Rolled Stat Improvement)</option>\n";
									}
								}
							}
						} elseif ($p->numberOfAchSkill() == 3) { 
							if ($p->mv_spp > 15) { 
							$x .= "<option value='16|P'>16 SPP (Chosen Primary)</option>\n";
								if ($p->mv_spp > 21) { 
								$x .= "<option value='22|S'>22 SPP (Chosen Secondary)</option>\n";
									if ($p->mv_spp > 27) { 
									$x .= "<option value='28|X'>28 SPP (Random Stat Improvement)</option>\n";
									$x .= "<option value='28|S'>28 SPP (Chosen Secondary instead of Rolled Stat Improvement)</option>\n";
									}
								}
							}
						} elseif ($p->numberOfAchSkill() == 4) { 
							if ($p->mv_spp > 19) { 
							$x .= "<option value='20|P'>20 SPP (Chosen Primary)</option>\n";
								if ($p->mv_spp > 25) { 
								$x .= "<option value='26|S'>26 SPP (Chosen Secondary)</option>\n";
									if ($p->mv_spp > 31) { 
									$x .= "<option value='32|X'>32 SPP (Random Stat Improvement)</option>\n";
									$x .= "<option value='32|S'>32 SPP (Chosen Secondary instead of Rolled Stat Improvement)</option>\n";
									}
								}
							}
						} elseif ($p->numberOfAchSkill() == 5) { 
							if ($p->mv_spp > 29) { 
							$x .= "<option value='30|P'>30 SPP (Chosen Primary)</option>\n";
								if ($p->mv_spp > 39) { 
								$x .= "<option value='40|S'>40 SPP (Chosen Secondary)</option>\n";
									if ($p->mv_spp > 49) { 
									$x .= "<option value='50|X'>50 SPP (Random Stat Improvement)</option>\n";
									$x .= "<option value='50|S'>50 SPP (Chosen Secondary instead of Rolled Stat Improvement)</option>\n";
									}
								}
							}
						} 
						
					$x .= '</select><input type="submit" name="button" value="OK" onClick="if(!confirm(\''.$lng->getTrn('common/confirm_box').'\')){return false;}">
					<input type="hidden" name="type" value="skill">
					<input type="hidden" name="player" value="'.$p->player_id.'">
					</form>
					</td>
					';
					}
				}
			} else {
				if ($ALLOW_EDIT && $p->mayHaveNewSkill()) {
					$x .= "<form method='POST'>\n";
					$x .= "<select name='skill'>\n";
					$x .= "<option selected value='999'>-- Select or Roll Skill --</option>\n";
					$x .= "<optgroup label='Primary skills'>\n";
					foreach ($p->choosable_skills['norm'] as $s) {
						$x .= "<option value='$s'>".$skillididx[$s]."</option>\n";
					}
					$x .= "</optgroup>\n";
					if (($p->numberOfAchSkill() == 0 && $p->mv_spp > 5) || ($p->numberOfAchSkill() == 1 && $p->mv_spp > 7) || ($p->numberOfAchSkill() == 2 && $p->mv_spp > 11) || ($p->numberOfAchSkill() == 3 && $p->mv_spp > 15) || ($p->numberOfAchSkill() == 4 && $p->mv_spp > 19) || ($p->numberOfAchSkill() == 5 && $p->mv_spp > 29))	{ 
						$x .= "<optgroup label='Secondary skills'>\n";
						foreach ($p->choosable_skills['doub'] as $s) {
							$x .= "<option value='$s'>".$skillididx[$s]."</option>\n";
						}
						$x .= "</optgroup>\n";
					}
					if (($p->numberOfAchSkill() == 0 && $p->mv_spp > 17) || ($p->numberOfAchSkill() == 1 && $p->mv_spp > 19) || ($p->numberOfAchSkill() == 2 && $p->mv_spp > 23) || ($p->numberOfAchSkill() == 3 && $p->mv_spp > 27) || ($p->numberOfAchSkill() == 4 && $p->mv_spp > 31) || ($p->numberOfAchSkill() == 5 && $p->mv_spp > 49))	{
						$x .= "<optgroup label='Characteristic improvement'>\n";
						foreach ($p->choosable_skills['chr'] as $s) {
							global $CHR_CONV;
							if  ($CHR_CONV[$s] == 'ma' || $CHR_CONV[$s] == 'av' || $CHR_CONV[$s] == 'st' ) {
								$x .= "<option value='ach_$s'>+ ".ucfirst($CHR_CONV[$s])."</option>\n";
							}
							else {
								$x .= "<option value='ach_$s'>- ".ucfirst($CHR_CONV[$s])."</option>\n";	
							}
						}
						$x .= "</optgroup>\n";
					}
					$x .= "</select>\n";
					$x .= "<select name='skillcost'>\n";
					$x .= "<option selected value='99'>-- Select Skill Cost --</option>\n";
						# variable spp cost dependant on number of already achieved skills, options appear only if SPP available
						if ($p->numberOfAchSkill() == 0) { 
						$x .= "<option value='3|R'>3 SPP (Random Primary)</option>\n";
							if ($p->mv_spp > 5) { 
							$x .= "<option value='6|P'>6 SPP (Chosen Primary/Random Secondary)</option>\n";
								if ($p->mv_spp > 11) { 
								$x .= "<option value='12|S'>12 SPP (Chosen Secondary)</option>\n";
									if ($p->mv_spp > 17) { 
									$x .= "<option value='18|X'>18 SPP (Random Stat Improvement)</option>\n";
									$x .= "<option value='18|S'>18 SPP (Chosen Secondary instead of Rolled Stat Improvement)</option>\n";
									}
								}
							}
						} elseif ($p->numberOfAchSkill() == 1) { 
						$x .= "<option value='4|R'>4 SPP (Random Primary)</option>\n";
							if ($p->mv_spp > 7) { 
							$x .= "<option value='8|P'>8 SPP (Chosen Primary/Random Secondary)</option>\n";
								if ($p->mv_spp > 13) { 
								$x .= "<option value='14|S'>14 SPP (Chosen Secondary)</option>\n";
									if ($p->mv_spp > 19) { 
									$x .= "<option value='20|X'>20 SPP (Random Stat Improvement)</option>\n";
									$x .= "<option value='20|S'>20 SPP (Chosen Secondary instead of Rolled Stat Improvement)</option>\n";
									}
								}
							}
						} elseif ($p->numberOfAchSkill() == 2) { 
						$x .= "<option value='6|R'>6 SPP (Random Primary)</option>\n";
							if ($p->mv_spp > 11) { 
							$x .= "<option value='12|P'>12 SPP (Chosen Primary/Random Secondary)</option>\n";
								if ($p->mv_spp > 17) { 
								$x .= "<option value='18|S'>18 SPP (Chosen Secondary)</option>\n";
									if ($p->mv_spp > 23) { 
									$x .= "<option value='24|X'>24 SPP (Random Stat Improvement)</option>\n";
									$x .= "<option value='24|S'>24 SPP (Chosen Secondary instead of Rolled Stat Improvement)</option>\n";
									}
								}
							}
						} elseif ($p->numberOfAchSkill() == 3) { 
						$x .= "<option value='8|R'>8 SPP (Random Primary)</option>\n";
							if ($p->mv_spp > 15) { 
							$x .= "<option value='16|P'>16 SPP (Chosen Primary/Random Secondary)</option>\n";
								if ($p->mv_spp > 21) { 
								$x .= "<option value='22|S'>22 SPP (Chosen Secondary)</option>\n";
									if ($p->mv_spp > 27) { 
									$x .= "<option value='28|X'>28 SPP (Random Stat Improvement)</option>\n";
									$x .= "<option value='28|S'>28 SPP (Chosen Secondary instead of Rolled Stat Improvement)</option>\n";
									}
								}
							}
						} elseif ($p->numberOfAchSkill() == 4) { 
						$x .= "<option value='10|R'>10 SPP (Random Primary)</option>\n";
							if ($p->mv_spp > 19) { 
							$x .= "<option value='20|P'>20 SPP (Chosen Primary/Random Secondary)</option>\n";
								if ($p->mv_spp > 25) { 
								$x .= "<option value='26|S'>26 SPP (Chosen Secondary)</option>\n";
									if ($p->mv_spp > 31) { 
									$x .= "<option value='32|X'>32 SPP (Random Stat Improvement)</option>\n";
									$x .= "<option value='32|S'>32 SPP (Chosen Secondary instead of Rolled Stat Improvement)</option>\n";
									}
								}
							}
						} elseif ($p->numberOfAchSkill() == 5) { 
						$x .= "<option value='15|R'>15 SPP (Random Primary)</option>\n";
							if ($p->mv_spp > 29) { 
							$x .= "<option value='30|P'>30 SPP (Chosen Primary/Random Secondary)</option>\n";
								if ($p->mv_spp > 39) { 
								$x .= "<option value='40|S'>40 SPP (Chosen Secondary)</option>\n";
									if ($p->mv_spp > 49) { 
									$x .= "<option value='50|X'>50 SPP (Random Stat Improvement)</option>\n";
									$x .= "<option value='50|S'>50 SPP (Chosen Secondary instead of Rolled Stat Improvement)</option>\n";
									}
								}
							}
						} 
					$x .= '
					</select>
					<input type="submit" name="button" value="OK" onClick="if(!confirm(\''.$lng->getTrn('common/confirm_box').'\')){return false;}">
					<input type="hidden" name="type" value="skill">
					<input type="hidden" name="player" value="'.$p->player_id.'">
					</form>
					</td>
					';
				}	
			}
			$p->skills .= $x;
			if ($p->pa == 0 || $p->pa >6) {       
				$p->pa = '-';
			}
			else {       
				$p->pa = $p->pa.'+';
			}
			$p->seasons = $p->getSeasons();
			if     ($p->is_sold)   				$p->rebuy = 'n/a';
			elseif ($p->is_dead)   				$p->rebuy = 'n/a';
			elseif ($p->is_journeyman_used)     $p->rebuy = 'n/a';
			elseif ($p->is_journeyman)          $p->rebuy = 'n/a';
			else								$p->rebuy = $p->getRebuy();
		}

		/* If enabled add stars and summed mercenaries entries to the roster */
		if ($DETAILED) {
			$stars = array();
			foreach (Star::getStars(STATS_TEAM, $team->team_id, false, false) as $s) {
				$s->name = preg_replace('/\s/', '&nbsp;', $s->name);
				$s->info = '<i class="icon-info"></i>';
				$s->player_id = $s->star_id;
				$s->team_id = $team->team_id;
				$s->nr = 0;
				$s->position = "<table style='border-spacing:0px;'><tr><td><i>Star&nbsp;player</i></td></tr></table>";
				$s->setSkills(true);
				$s->skills = '<small>'.$s->skills.'</small>';
				$s->injs = '';
				$s->value = 0;
				$s->mv_cas = "$s->mv_bh/$s->mv_si/$s->mv_ki";
				foreach ($s->getStats(T_OBJ_TEAM,$team->team_id) as $k => $v) {
					$s->$k = $v;
				}
				$s->is_dead = $s->is_sold = $s->is_mng = $s->is_journeyman = $s->is_retired = false;
				$s->HTMLbcolor = COLOR_HTML_STARMERC;
				$s->href = array('link' => urlcompile(T_URL_PROFILE,T_OBJ_STAR,false,false,false), 'field' => 'obj_id', 'value' => 'player_id'); # Like in below $fields def, but with T_OBJ_STAR instead.
				$s->seasons = 'n/a';
				$s->rebuy = 'n/a';
				array_push($stars, $s);
			}
			$players = array_merge($players, $stars);

			$smerc = (object) null;
			$smerc->mv_mvp = $smerc->mv_td = $smerc->mv_cp = $smerc->mv_deflct = $smerc->mv_intcpt = $smerc->mv_bh = $smerc->mv_si = $smerc->mv_ki = $smerc->skills = 0;
			foreach (Mercenary::getMercsHiredByTeam($team->team_id) as $merc) {
				$smerc->mv_mvp += $merc->mvp;
				$smerc->mv_td += $merc->td;
				$smerc->mv_cp += $merc->cp;
				$smerc->mv_deflct += $merc->deflct;
				$smerc->mv_intcpt += $merc->intcpt;
				$smerc->mv_bh += $merc->bh;
				$smerc->mv_si += $merc->si;
				$smerc->mv_ki += $merc->ki;
				$smerc->skills += $merc->skills;
			}
			$smerc->player_id = ID_MERCS;
			$smerc->team_id = $team->team_id;
			$smerc->nr = 0;
			$smerc->name = 'All&nbsp;mercenary&nbsp;hirings';
			$smerc->info = '<i class="icon-info"></i>';
			$smerc->position = "<i>Mercenaries</i>";
			$smerc->mv_cas = "$smerc->mv_bh/$smerc->mv_si/$smerc->mv_ki";
			$smerc->ma = '-';
			$smerc->st = '-';
			$smerc->ag = '-';
			$smerc->pa = '-';
			$smerc->av = '-';
			$smerc->skills = 'Total bought extra skills: '.$smerc->skills;
			$smerc->injs = '';
			$smerc->mv_spp = '-';
			$smerc->mv_misc = '-';
			$smerc->value = 0;
			$smerc->is_dead = $smerc->is_sold = $smerc->is_mng = $smerc->is_journeyman = $smerc->is_retired = false;
			$smerc->HTMLbcolor = COLOR_HTML_STARMERC;
			$smerc->seasons = 'n/a';
			$smerc->rebuy = 'n/a';
			array_push($players, $smerc);
		}
		/******************************
		 * Team players table
		 * ------------------
		 * Contains player information and menu(s) for skill choice.
		 ******************************/
		title($team->name . (($team->is_retired) ? ' <font color="red"> (Retired)</font>' : ''));
		$allowEdit = (isset($coach) && $coach)
			? $coach->isMyTeam($team->team_id) || $coach->mayManageObj(T_OBJ_TEAM, $team->team_id)
			: false;
		$fields = array(
			'nr'        => array('desc' => '#', 'editable' => 'updatePlayerNumber', 'javaScriptArgs' => array('team_id', 'player_id'), 'editableClass' => 'number', 'allowEdit' => $allowEdit),
			'name'      => array('desc' => $lng->getTrn('common/name'), 'editable' => 'updatePlayerName', 'javaScriptArgs' => array('team_id', 'player_id'), 'allowEdit' => $allowEdit),
			'info'      => array('desc' => '', 'nosort' => true, 'icon' => true, 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_PLAYER,false,false,false), 'field' => 'obj_id', 'value' => 'player_id')),
			'position'  => array('desc' => $lng->getTrn('common/pos'), 'nosort' => true),
			'ma'        => array('desc' => 'Ma'),
			'st'        => array('desc' => 'St'),
			'ag'        => array('desc' => 'Ag', 'suffix' => '+'),
			'pa'        => array('desc' => 'Pa'),	
			'av'        => array('desc' => 'Av', 'suffix' => '+'),
			'skills'    => array('desc' => $lng->getTrn('common/skills'), 'nosort' => true),
			'injs'      => array('desc' => $lng->getTrn('common/injs'), 'nosort' => true),
			'mv_cp'     => array('desc' => 'Cp'),
			'mv_td'     => array('desc' => 'Td'),
			'mv_deflct' => array('desc' => 'Def'),
			'mv_intcpt' => array('desc' => 'Int'),
			'mv_cas'    => array('desc' => ($DETAILED) ? 'BH/SI/Ki' : 'Cas', 'nosort' => ($DETAILED) ? true : false),
			'mv_mvp'    => array('desc' => 'MVP'),
			'mv_misc'   => array('desc' => 'Misc'),
			'mv_spp'    => array('desc' => ($DETAILED) ? 'SPP/extra' : 'SPP', 'nosort' => ($DETAILED) ? true : false),
			'value'     => array('desc' => $lng->getTrn('common/value'), 'kilo' => true, 'suffix' => 'k'),
		);
		$fieldsDetailed = array(
			'nr'        => array('desc' => '#', 'editable' => 'updatePlayerNumber', 'javaScriptArgs' => array('team_id', 'player_id'), 'editableClass' => 'number', 'allowEdit' => $allowEdit),
			'name'      => array('desc' => $lng->getTrn('common/name'), 'editable' => 'updatePlayerName', 'javaScriptArgs' => array('team_id', 'player_id'), 'allowEdit' => $allowEdit),
			'info'      => array('desc' => '', 'nosort' => true, 'icon' => true, 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_PLAYER,false,false,false), 'field' => 'obj_id', 'value' => 'player_id')),
			'position'  => array('desc' => $lng->getTrn('common/pos'), 'nosort' => true),
			'ma'        => array('desc' => 'Ma'),
			'st'        => array('desc' => 'St'),
			'ag'        => array('desc' => 'Ag', 'suffix' => '+'),
			'pa'        => array('desc' => 'Pa'),	
			'av'        => array('desc' => 'Av', 'suffix' => '+'),
			'skills'    => array('desc' => $lng->getTrn('common/skills'), 'nosort' => true),
			'injs'      => array('desc' => $lng->getTrn('common/injs'), 'nosort' => true),
			'mv_cp'     => array('desc' => 'Cp'),
			'mv_td'     => array('desc' => 'Td'),
			'mv_deflct' => array('desc' => 'Def'),
			'mv_intcpt' => array('desc' => 'Int'),
			'mv_cas'    => array('desc' => ($DETAILED) ? 'BH/SI/Ki' : 'Cas', 'nosort' => ($DETAILED) ? true : false),
			'mv_mvp'    => array('desc' => 'MVP'),
			'mv_misc'   => array('desc' => 'Misc'),
			'mv_spp'    => array('desc' => ($DETAILED) ? 'SPP/extra' : 'SPP', 'nosort' => ($DETAILED) ? true : false),
			'value'     => array('desc' => $lng->getTrn('common/value'), 'kilo' => true, 'suffix' => 'k'),
			'seasons'	=> array('desc' => 'Seasons', 'nosort' => true),
			'rebuy'		=> array('desc' => 'Rebuy', 'kilo' => true, 'suffix' => 'k', 'nosort' => true),
		);
		echo "<a href=".urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$this->team_id,false,false)."&amp;detailed=".(($DETAILED) ? 0 : 1).">".$lng->getTrn('profile/team/viewtoggle')."</a><br><br>\n";
		HTMLOUT::sort_table(
			$team->name.' roster',
			urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$team->team_id,false,false).(($DETAILED) ? '&amp;detailed=1' : '&amp;detailed=0'),
			$players,
			($DETAILED) ? $fieldsDetailed : $fields,
			($DETAILED) ? array('+is_dead', '+is_sold', '+is_mng', '+is_retired', '+is_journeyman', '+nr', '+name') : sort_rule('player'),
			(isset($_GET['sort'])) ? array((($_GET['dir'] == 'a') ? '+' : '-') . $_GET['sort']) : array(),
			array('color' => ($DETAILED) ? true : false, 'doNr' => false, 'noHelp' => true)
		);
		?>
		<!-- Following HTML is from class_team_htmlout.php _roster -->
		<table class="text">
			<tr>
				<td style="width: 100%;"> </td>
				<?php
				if ($DETAILED) {
					?>
					<td style="background-color: <?php echo COLOR_HTML_READY;   ?>;"><font color='black'><b>&nbsp;Ready&nbsp;</b></font></td>
					<td style="background-color: <?php echo COLOR_HTML_MNG;     ?>;"><font color='black'><b>&nbsp;MNG&nbsp;</b></font></td>
					<td style="background-color: <?php echo COLOR_HTML_RETIRED;     ?>;"><font color='black'><b>&nbsp;Retired&nbsp;</b></font></td>
					<td style="background-color: <?php echo COLOR_HTML_JOURNEY; ?>;"><font color='black'><b>&nbsp;Journey&nbsp;</b></font></td>
					<td style="background-color: <?php echo COLOR_HTML_JOURNEY_USED; ?>;"><font color='black'><b>&nbsp;Used&nbsp;journey&nbsp;</b></font></td>
					<td style="background-color: <?php echo COLOR_HTML_DEAD;    ?>;"><font color='black'><b>&nbsp;Dead&nbsp;</b></font></td>
					<td style="background-color: <?php echo COLOR_HTML_SOLD;    ?>;"><font color='black'><b>&nbsp;Sold&nbsp;</b></font></td>
					<td style="background-color: <?php echo COLOR_HTML_STARMERC;?>;"><font color='black'><b>&nbsp;Star/merc&nbsp;</b></font></td>
					<td style="background-color: <?php echo COLOR_HTML_NEWSKILL;?>;"><font color='black'><b>&nbsp;New&nbsp;skill&nbsp;</b></font></td>
					<?php
				}
				?>
			</tr>
		</table>
		<?php
	}

	private function _menu($ALLOW_EDIT, $DETAILED) {
		global $lng, $settings, $rules;
		$team = $this; // Copy. Used instead of $this for readability.
		$url = urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$this->team_id,false,false);
		?>
		<!-- Following HTML is from class_team_htmlout.php _menu -->
		<!-- Cyanide module team color style -->
		<style type="text/css">
		#cycolors {
		width: 128px;
		height: 112px;
		position: relative;
		background-image: url(images/cycolors.jpg);
		background-repeat: no-repeat;
		}

		#cycolors ul {
		margin: 0;
		padding: 0;
		padding-bottom:0px;padding-left:0px;padding-right:0px;padding-top:0px;
		list-style: none;
		}

		#cycolors a {
		position: absolute;
		width: 15px;
		height: 15px;
		text-indent: -1000em;
		padding-bottom:0px;padding-left:0px;padding-right:0px;padding-top:0px;
		border-bottom:0px;border-left:0px;border-top:0px;border-right:0px;
		}

		#cycolors a:hover { border: 1px solid #fff; }

		#cycolors .blue1 a {top: 0px;left: 0px;}
		#cycolors .cyan1 a {top: 0px;left: 16px;}
		#cycolors .green1 a {top: 0px;left: 32px;}
		#cycolors .yellow1 a {top: 0px;left: 48px;}
		#cycolors .red1 a {top: 0px;left: 64px;}
		#cycolors .magenta1 a {top: 0px;left: 80px;}
		#cycolors .purple1 a {top: 0px;left: 96px;}
		#cycolors .grey1 a {top: 0px;left: 112px;}

		#cycolors .blue2 a {top: 16px;left: 0px;}
		#cycolors .cyan2 a {top: 16px;left: 16px;}
		#cycolors .green2 a {top: 16px;left: 32px;}
		#cycolors .yellow2 a {top: 16px;left: 48px;}
		#cycolors .red2 a {top: 16px;left: 64px;}
		#cycolors .magenta2 a {top: 16px;left: 80px;}
		#cycolors .purple2 a {top: 16px;left: 96px;}
		#cycolors .grey2 a {top: 16px;left: 112px;}

		#cycolors .blue3 a {top: 32px;left: 0px;}
		#cycolors .cyan3 a {top: 32px;left: 16px;}
		#cycolors .green3 a {top: 32px;left: 32px;}
		#cycolors .yellow3 a {top: 32px;left: 48px;}
		#cycolors .red3 a {top: 32px;left: 64px;}
		#cycolors .magenta3 a {top: 32px;left: 80px;}
		#cycolors .purple3 a {top: 32px;left: 96px;}
		#cycolors .grey3 a {top: 32px;left: 112px;}

		#cycolors .blue4 a {top:48px;left: 0px;}
		#cycolors .cyan4 a {top:48px;left: 16px;}
		#cycolors .green4 a {top:48px;left: 32px;}
		#cycolors .yellow4 a {top:48px;left: 48px;}
		#cycolors .red4 a {top:48px;left: 64px;}
		#cycolors .magenta4 a {top:48px;left: 80px;}
		#cycolors .purple4 a {top:48px;left: 96px;}
		#cycolors .grey4 a {top:48px;left: 112px;}

		#cycolors .blue5 a {top:64px;left: 0px;}
		#cycolors .cyan5 a {top:64px;left: 16px;}
		#cycolors .green5 a {top:64px;left: 32px;}
		#cycolors .yellow5 a {top:64px;left: 48px;}
		#cycolors .red5 a {top:64px;left: 64px;}
		#cycolors .magenta5 a {top:64px;left: 80px;}
		#cycolors .purple5 a {top:64px;left: 96px;}
		#cycolors .grey5 a {top:64px;left: 112px;}

		#cycolors .blue6 a {top:80px;left: 0px;}
		#cycolors .cyan6 a {top:80px;left: 16px;}
		#cycolors .green6 a {top:80px;left: 32px;}
		#cycolors .yellow6 a {top:80px;left: 48px;}
		#cycolors .red6 a {top:80px;left: 64px;}
		#cycolors .magenta6 a {top:80px;left: 80px;}
		#cycolors .purple6 a {top:80px;left: 96px;}
		#cycolors .grey6 a {top:80px;left: 112px;}

		#cycolors .blue7 a {top:96px;left: 0px;}
		#cycolors .cyan7 a {top:96px;left: 16px;}
		#cycolors .green7 a {top:96px;left: 32px;}
		#cycolors .yellow7 a {top:96px;left: 48px;}
		#cycolors .red7 a {top:96px;left: 64px;}
		#cycolors .magenta7 a {top:96px;left: 80px;}
		#cycolors .purple7 a {top:96px;left: 96px;}
		#cycolors .grey7 a {top:96px;left: 112px;}
		</style>

		<ul class="css3menu1 topmenu" style="position:static; z-index:0;">
			<li class="topfirst"><a href="<?php echo $url.'&amp;subsec=man';?>"><?php echo $lng->getTrn('profile/team/tmanage');?></a></li>
			<li class="topmenu"><a href="<?php echo $url.'&amp;subsec=news';?>"><?php echo $lng->getTrn('profile/team/news');?></a></li>
			<li><a href="<?php echo $url.'&amp;subsec=about';?>"><?php echo $lng->getTrn('common/about');?></a></li>
			<li><a href="<?php echo $url.'&amp;subsec=games';?>"><?php echo $lng->getTrn('profile/team/games');?></a></li>
			<?php
			echo "<li><a href='${url}&amp;subsec=hhstar'>".$lng->getTrn('common/starhh')."</a></li>\n";
			echo "<li><a href='${url}&amp;subsec=hhmerc'>".$lng->getTrn('common/merchh')."</a></li>\n";
			
			$pdf    = (Module::isRegistered('PDFroster')) ? "handler.php?type=roster&amp;team_id=$this->team_id&amp;detailed=".($DETAILED ? '1' : '0') : '';
			$botocs = (Module::isRegistered('XML_BOTOCS') && $settings['leegmgr_botocs']) ? "handler.php?type=botocsxml&amp;teamid=$this->team_id" : '';
			$cyanide = (Module::isRegistered('XML_BOTOCS') && $settings['leegmgr_cyanide']) ? "handler.php?type=botocsxml&amp;teamid=$this->team_id&amp;cy" : '';
			if ($pdf || $botocs) {
			?>
			
			<?php
			}
			if (Module::isRegistered('IndcPage')) {
				echo "<li><a href='handler.php?type=inducements&amp;team_id=$team->team_id'>Inducements try-out</a></li>\n";
			}
			if (Module::isRegistered('SGraph')) {
				echo "<li><a href='handler.php?type=graph&amp;gtype=".SG_T_TEAM."&amp;id=$team->team_id''>Vis. stats</a></li>\n";
			}
			if (Module::isRegistered('Cemetery')) {
				echo "<li><a href='handler.php?type=cemetery&amp;tid=$team->team_id'>".$lng->getTrn('name', 'Cemetery')."</a></li>\n";
			}
			?>
			
			<li class="toplast"><a>Roster</a>
				<ul>
					<?php if ($pdf)    { ?><li class="subfirst"><a TARGET="_blank" href="<?php echo $pdf;?>">PDF</a></li> <?php } ?>
				</ul>
			</li>
		</ul>
		<br>
		<?php
	}

	private function _HHMerc($DETAILED)	{
		global $lng;
		$team = $this; // Copy. Used instead of $this for readability.
		title('<div class="team-management-title">' . $lng->getTrn('common/merchh') . '</div>');
		$mdat = array();
		foreach (Mercenary::getMercsHiredByTeam($team->team_id, false) as $merc) {
			$o = (object) array();
			$m = new Match($merc->match_id);
			$o->date_played = $m->date_played;
			$o->opponent = ($m->team1_id == $team->team_id) ? $m->team1_name : $m->team2_name;
			foreach (array('match_id', 'skills', 'misc', 'cp', 'td', 'deflct', 'intcpt', 'bh', 'ki', 'si') as $f) {
				$o->$f = $merc->$f;
			}
			$o->cas = $o->bh+$o->ki+$o->si;
			$o->match = '[view]';
			$o->tour = get_alt_col('tours', 'tour_id', $m->f_tour_id, 'name');
			$o->score = "$m->team1_score - $m->team2_score";
			$o->result = matchresult_icon(
				(
				($m->team1_id == $team->team_id && $m->team1_score > $m->team2_score) ||
				($m->team2_id == $team->team_id && $m->team1_score < $m->team2_score)
				)
					? 'W'
					: (($m->team1_score == $m->team2_score) ? 'D' : 'L')
			);

			array_push($mdat, $o);
		}
		$fields = array(
			'date_played'   => array('desc' => $lng->getTrn('common/dateplayed')),
			'tour'          => array('desc' => $lng->getTrn('common/tournament')),
			'opponent'      => array('desc' => $lng->getTrn('common/opponent')),
			'skills' => array('desc' => $lng->getTrn('common/skills')),
			'cp'     => array('desc' => 'Cp'),
			'td'     => array('desc' => 'Td'),
			'deflct' => array('desc' => 'Def'),
			'intcpt' => array('desc' => 'Int'),
			'cas'    => array('desc' => 'Cas'),
			'bh'     => array('desc' => 'BH'),
			'si'     => array('desc' => 'Si'),
			'ki'     => array('desc' => 'Ki'),
			'misc'    => array('desc' => 'Misc SPP'),
			'score'  => array('desc' => $lng->getTrn('common/score'), 'nosort' => true),
			'result' => array('desc' => $lng->getTrn('common/result'), 'nosort' => true),
			'match'  => array('desc' => $lng->getTrn('common/match'), 'href' => array('link' => 'index.php?section=matches&amp;type=report', 'field' => 'mid', 'value' => 'match_id'), 'nosort' => true),
		);
		HTMLOUT::sort_table(
			"<a name='tp_mhhanc'>".$lng->getTrn('common/merchh')."</a>",
			urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$team->team_id,false,false).'&amp;subsec=hhmerc'.(($DETAILED) ? '&amp;detailed=1' : '&amp;detailed=0'),
			$mdat,
			$fields,
			sort_rule('star_HH'),
			(isset($_GET['sorttp_mhh'])) ? array((($_GET['dirtp_mhh'] == 'a') ? '+' : '-') . $_GET['sorttp_mhh']) : array(),
			array('GETsuffix' => 'tp_mhh', 'doNr' => false,)
		);
	}

	private function _HHStar($DETAILED) {
		global $lng;
		$team = $this; // Copy. Used instead of $this for readability.
		title('<div class="team-management-title">' . $lng->getTrn('common/starhh') . '</div>');
		Star_HTMLOUT::starHireHistory(STATS_TEAM, $team->team_id, false, false, false, array(
			'url' => urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$team->team_id,false,false).'&amp;subsec=hhstar'.(($DETAILED) ? '&amp;detailed=1' : '&amp;detailed=0'),
			'GET_SS' => 'tp_shh',)
		);
	}

	public static function teamManagementBox($teamId) {
		$team = new self($teamId);
		$ALLOW_EDIT = $team->allowEdit(); # Show team action boxes?
		$DETAILED = (isset($_GET['detailed']) && $_GET['detailed'] == 1);# Detailed roster view?
		$team->handleActions($ALLOW_EDIT); # Handles any actions/request sent.
		list($players, $players_backup) = $team->_loadPlayers($DETAILED); # Should come after handleActions().
		$team->_teamManagementBox($players, $team);
	}

	private function _actionBoxes($ALLOW_EDIT, $players) {
		/******************************
		 * Team management
		 * ---------------
		 * Here we are able to view team stats and manage the team, depending on visitor's privileges.
		 ******************************/
		global $lng, $rules, $settings, $skillarray, $coach, $DEA, $CHR_CONV, $stars, $specialruleididx;
		global $leagues, $divisions;
		global $racesHasNecromancer, $racesNoApothecary;
		global $T_ALLOWED_PLAYER_NR;	
		$team = $this; // Copy. Used instead of $this for readability.	
		$raceid = $team->f_race_id;	
		$race = new Race($raceid);	
		// Set list of coaches that a team can be transfered to	(excludes current team coach)	
		$teamcoach = $team->owned_by_coach_id;	
		$queryGet = 'SELECT coaches.coach_id AS "coach_id", coaches.name AS "cname" FROM coaches WHERE coaches.retired IS FALSE and coaches.coach_id != '.$teamcoach.' ORDER BY cname ASC';
		$coaches = array();
		$result = mysql_query($queryGet);
		while ($c = mysql_fetch_object($result)) {
			$coaches[] = $c;
		}
		$JMP_ANC = (isset($_POST['menu_tmanage']) || isset($_POST['menu_admintools'])); # Jump condition MUST be set here due to _POST variables being changed later.
		?>
		<!-- Following HTML is from class_team_htmlout.php _actionBoxes -->
		<a name="aanc"></a>
		<div class="boxTeamPage">
			<div class="boxTitle<?php echo T_HTMLBOX_INFO;?>"><?php echo $lng->getTrn('profile/team/box_info/title');?></div>
			<div class="boxBody">
				<table width="100%">
					<tr>
						<td><?php echo $lng->getTrn('common/coach');?></td>
						<td><a href="<?php echo urlcompile(T_URL_PROFILE,T_OBJ_COACH,$team->owned_by_coach_id,false,false);?>"><?php echo $team->f_cname; ?></a></td>
					</tr>
					<tr>
						<td><?php echo $lng->getTrn('common/race');?></td>
						<td><a href="<?php echo urlcompile(T_URL_PROFILE,T_OBJ_RACE,$team->f_race_id,false,false);?>"><?php echo $lng->getTrn('race/'.strtolower(str_replace(' ','', $team->f_rname))); ?></a></td>
					</tr>
					<tr>
						<td><?php echo $lng->getTrn('common/teamspecialrules');?></td>
						<td><?php echo $race->special_rules;?></td>
					</tr>
					<?php 
					if (strlen($team->getFavruleoptions()) != 0) {
						echo '<tr><td>';
						echo $lng->getTrn('common/teamfavspecialrules');
						echo '</td><td>';
						if (strlen($team->getFavrulechosen()) >= 1) { 
							$fr_text = 'profile/team/box_tm/favrule/r'.$team->getFavrulechosen();
							echo $lng->getTrn($fr_text);
						} else {
							echo '<i>'.$lng->getTrn('common/notyetselected').'</i>';
						}
						echo '</td></tr>';
					}?>
					<tr>
						<td><?php echo $lng->getTrn('common/league');?></td>
						<td><?php if (isset($leagues[$team->f_lid])) {
							echo "<a href=\"";
							echo urlcompile(T_URL_STANDINGS,T_OBJ_TEAM,false,T_NODE_LEAGUE,$team->f_lid);
							echo "\">" . $leagues[$team->f_lid]['lname'] . "</a>";
						} else {
							echo '<i>'.$lng->getTrn('common/none').'</i>';
						} ?></td>
					</tr>
					<?php
					if ($team->f_did != self::T_NO_DIVISION_TIE) {
						?>
						<tr>
							<td><?php echo $lng->getTrn('common/division');?></td>
							<td><?php if (isset($divisions[$team->f_did])) {
							echo "<a href=\"";
							echo urlcompile(T_URL_STANDINGS,T_OBJ_TEAM,false,T_NODE_DIVISION,$team->f_did);
							echo "\">" . $divisions[$team->f_did]['dname'] . "</a>";
						} else {
							echo '<i>'.$lng->getTrn('common/none').'</i>';
						} ?></td>
						</tr>
						<?php
					}
					?>
					<tr>
						<td><?php echo $lng->getTrn('common/ready');?></td>
						<td><?php echo ($team->rdy) ? $lng->getTrn('common/yes') : $lng->getTrn('common/no'); ?></td>
					</tr>
					<tr>
						<td>TV</td>
						<td><?php echo $team->tv/1000 . 'k'; ?></td>
					</tr>
					<tr>
						 <td><?php echo $lng->getTrn('matches/report/treas')?></td>
						<td><?php echo $team->treasury/1000 . 'k'; ?></td>
					</tr>
					<tr>
					<?php
					if (in_array($team->f_race_id, $racesHasNecromancer)) {
						?>
						<td>Necromancer</td>
						<td><?php echo $lng->getTrn('common/yes');?></td>
						<?php
					}
					if (!in_array($team->f_race_id, $racesNoApothecary)) {
						echo "<td>".$lng->getTrn('common/apothecary')."</td>\n";
						echo "<td>" . ($team->apothecary ? $lng->getTrn('common/yes') : $lng->getTrn('common/no')) . "</td>\n";
					}
					?>
					</tr>
					<tr>
						<td><?php echo $lng->getTrn('common/reroll')?></td>
						<td><?php echo $team->rerolls; ?></td>
					</tr>
					<tr>
						<td><?php echo $lng->getTrn('matches/report/ff')?></td>
						<td><?php echo $team->rg_ff; ?></td>
					</tr>
					<tr>
						<td><?php echo $lng->getTrn('common/ass_coach')?></td>
						<td><?php echo $team->ass_coaches; ?></td>
					</tr>
					<tr>
						<td><?php echo $lng->getTrn('common/cheerleader')?></td>
						<td><?php echo $team->cheerleaders; ?></td>
					</tr>
					<tr>
						<td colspan=2><hr></td>
					</tr>
					<tr>
						<td><?php echo $lng->getTrn('common/played');?></td>
						<td><?php echo $team->mv_played; ?></td>
					</tr>
					<tr>
						<td>WIN%</td>
						<td><?php echo sprintf("%1.1f", $team->rg_win_pct).'%'; ?></td>
					</tr>
					<tr>
						<td>ELO</td>
						<td><?php echo (($team->rg_elo) ? sprintf("%1.2f", $team->rg_elo) : '<i>N/A</i>'); ?></td>
					</tr>
					<tr>
						<td>W/L/D</td>
						<td><?php echo "$team->mv_won/$team->mv_lost/$team->mv_draw"; ?></td>
					</tr>
					<tr>
						<td>W/L/D <?php echo $lng->getTrn('common/streaks');?></td>
						<td><?php echo "$team->rg_swon/$team->rg_slost/$team->rg_sdraw"; ?></td>
					</tr>
					<tr>
						<td><?php echo $lng->getTrn('common/wontours');?></td>
						<td><?php echo $team->wt_cnt; ?></td>
					</tr>
					<tr>
						<td><?php echo $lng->getTrn('profile/team/box_info/ltour');?></td>
						<td><?php echo Tour::getTourUrl($team->getLatestTour()); ?></td>
					</tr>
					<tr valign="top">
						<td><?php echo $lng->getTrn('common/playedtours');?></td>
						<td><small><?php $tours = $team->getToursPlayedIn(false);
						if (empty($tours)) {
							echo $lng->getTrn('common/none');
						} else {
							$first = true;
							foreach($tours as $tour) {
								if ($first) {
									$first = false;
								} else {
									echo ", ";
								}
								echo $tour->getUrl();
							}
						} ?></small></td>
					</tr>
					<?php
					if (Module::isRegistered('Prize')) {
						?>
						<tr valign="top">
							<td><?php echo $lng->getTrn('name', 'Prize');?></td>
							<td><small><?php echo Module::run('Prize', array('getPrizesString', T_OBJ_TEAM, $team->team_id));?></small></td>
						</tr>
						<?php
					}
					if (Module::isRegistered('FamousTeams')) {
						?>
						<tr>
							<td><?php echo $lng->getTrn('isfamous', 'FamousTeams');?></td>
							<td><?php echo (Module::run('FamousTeams', array('isInFT', $team->team_id))) ? '<b><font color="green">Yes</font></b>' : 'No';?></td>
						</tr>
						<?php
					}
					?>
				</table>
			</div>
		</div>

		<?php
		if ($ALLOW_EDIT) {
			$this->_teamManagementBox($players, $team);
			if ($coach->isNodeCommish(T_NODE_LEAGUE, $team->f_lid)) {
				?>
				<!-- Following HTML is from class_team_htmlout.php _actionBoxes -->
				<div class="boxTeamPage">
					<div class="boxTitle<?php echo T_HTMLBOX_ADMIN;?>"><?php echo $lng->getTrn('profile/team/box_admin/title');?></div>
					<div class="boxBody">
						<?php
						$base = 'profile/team';
						$admin_tools = array(
							'unhire_journeyman' => $lng->getTrn($base.'/box_admin/unhire_journeyman'),
							'unsell_player'     => $lng->getTrn($base.'/box_admin/unsell_player'),
							'unbuy_goods'       => $lng->getTrn($base.'/box_admin/unbuy_goods'),
							'bank'              => $lng->getTrn($base.'/box_admin/bank'),
							'spp'               => $lng->getTrn($base.'/box_admin/spp'),
							'dval'              => $lng->getTrn($base.'/box_admin/dval'),
							'extra_skills'      => $lng->getTrn($base.'/box_admin/extra_skills'),
							'ach_skills'        => $lng->getTrn($base.'/box_admin/ach_skills'),
							'ff'                => $lng->getTrn($base.'/box_admin/ff'),
							'resetrule'         => $lng->getTrn($base.'/box_admin/resetrule'),
							'transferteam'      => $lng->getTrn($base.'/box_admin/transferteam'),
							'removeNiggle'      => $lng->getTrn($base.'/box_admin/removeNiggle'),
							'addniggle'      	=> $lng->getTrn($base.'/box_admin/addniggle'),
							'removeMNG'      	=> $lng->getTrn($base.'/box_admin/removeMNG'),
							'removenegastat'    => $lng->getTrn($base.'/box_admin/removenegastat'),
						);
						// Set default choice.
						if (!isset($_POST['menu_admintools'])) {
							reset($admin_tools);
							$_POST['menu_admintools'] = key($admin_tools);
						}
						// If action is already chosen, then make it the default selected.
						if (isset($_POST['type']) && array_key_exists($_POST['type'], $admin_tools)) {
							$_POST['menu_admintools'] = $_POST['type'];
						}
						?>
						<form method="POST" name="menu_admintools_form">
							<select name="menu_admintools" onchange="document.menu_admintools_form.submit();">
						<?php
						foreach ($admin_tools as $opt => $desc)
						if ($opt != 'removeNiggle' && $opt != 'addniggle' && $opt != 'removeMNG' && $opt != 'removenegastat') {
									echo "<option value='$opt'" . ($_POST['menu_admintools'] == $opt ? 'SELECTED' : '') . ">$desc</option>";
						}
						?>
						<OPTGROUP LABEL='Redraft actions'>
						<?php
						foreach ($admin_tools as $opt => $desc)
						if ($opt == 'removeNiggle' || $opt == 'addniggle' || $opt == 'removeMNG' || $opt == 'removenegastat') {
									echo "<option value='$opt'" . ($_POST['menu_admintools'] == $opt ? 'SELECTED' : '') . ">$desc</option>";
						}
						?>
						</OPTGROUP>
						</select>
						<!-- <input type="submit" name="admintools" value="OK"> -->
						</form>

						<br><i><?php echo $lng->getTrn('common/desc');?>:</i><br><br>
						<form name='form_admintools' method='POST'>
							<?php
							$DISABLE = false;
							switch ($_POST['menu_admintools']) {
								/***************
								 * Un-hire journeymen
								 **************/
								case 'unhire_journeyman':
									echo $lng->getTrn('profile/team/box_admin/desc/unhire_journeyman');
									?>
									<hr><br>
									<?php echo $lng->getTrn('common/player');?>:<br>
									<select name="player">
									<?php
									$DISABLE = true;
									foreach ($players as $p) {
										if ($p->is_sold || $p->is_dead || $p->is_journeyman || $p->qty != 16)
											continue;

										echo "<option value='$p->player_id'>$p->nr $p->name</option>\n";
										$DISABLE = false;
									}
									?>
									</select>
									<input type="hidden" name="type" value="unhire_journeyman">
									<?php
									break;
								/***************
								 * Un-sell player
								 **************/
								case 'unsell_player':
									echo $lng->getTrn('profile/team/box_admin/desc/unsell_player');
									?>
									<hr><br>
									<?php echo $lng->getTrn('common/player');?>:<br>
									<select name="player">
									<?php
									$DISABLE = true;
									foreach ($players as $p) {
										if ($p->is_sold) {
												echo "<option value='$p->player_id'>$p->nr $p->name</option>\n";
												$DISABLE = false;
										}
									}
									?>
									</select>
									<input type="hidden" name="type" value="unsell_player">
									<?php
									break;
								/***************
								 * Un-buy team goods
								 **************/
								case 'unbuy_goods':
									echo $lng->getTrn('profile/team/box_admin/desc/unbuy_goods');
									?>
									<hr><br>
									<select name="thing">
									<?php
									$DISABLE = true;
										foreach ($team->getGoods() as $name => $details) {
										if ($team->$name > 0) { # Only allow to un-buy those things which we already have some of.
											echo "<option value='$name'>$details[item]</option>\n";
											$DISABLE = false;
										}
									}
									?>
									</select>
									<input type="hidden" name="type" value="unbuy_goods">
									<?php
									break;
								/***************
								 * Gold bank
								 **************/
								case 'bank':
									echo $lng->getTrn('profile/team/box_admin/desc/bank');
									?>
									<hr><br>
									&Delta; team treasury:<br>
									<input type="radio" CHECKED name="sign" value="+">+
									<input type="radio" name="sign" value="-">-
									<input type='text' name="amount" maxlength=5 size=5>k
									<input type="hidden" name="type" value="bank">
									<?php
									break;
								/***************
								 * Manage Fan Factor
								***************/
								case 'ff':
									echo $lng->getTrn('profile/team/box_admin/desc/ff');
									?>
									<hr><br>
									Bought dedicated fans + Match dedicated fans = Total<br>
									<input type='text' name="amount" value="<?php echo $team->ff_bought.'" maxlength=2 size=1 style="text-align: right">+'.($team->rg_ff-$team->ff_bought).'='.$team->rg_ff ?>
									<input type="hidden" name="type" value="ff">
									<?php
									break;
								/***************
								 * Reset selected team special rule
								***************/
								case 'resetrule':
									echo $lng->getTrn('profile/team/box_admin/desc/resetrule');
									?>
									<input type="hidden" name="type" value="resetrule">
									<?php
									break;
								/***************
								 * Transfer team to another coach
								***************/
								case 'transferteam':
									echo $lng->getTrn('profile/team/box_admin/desc/transferteam');
									?>
									<hr><br>
									<?php echo $lng->getTrn('common/coach');?>:<br>
									<select name="coachname">
									<?php
									$DISABLE = true;
									foreach ($coaches as $c) {
										echo "<option value='$c->coach_id'>$c->cname</option>\n";
										$DISABLE = false;
									}
									?>
									</select>
									<input type="hidden" name="type" value="transferteam">
									<?php
									break;
								/***************
								 * Manage extra SPP
								 **************/
								case 'spp':
									echo $lng->getTrn('profile/team/box_admin/desc/spp');
									?>
									<hr><br>
									<?php echo $lng->getTrn('common/player');?>:<br>
									<select name="player">
									<?php
									$DISABLE = true;
									//objsort($players, array('+is_dead', '+name'));
									foreach ($players as $p) {
										if (!$p->is_sold) {
											echo "<option value='$p->player_id'".(($p->is_dead) ? ' style="background-color:'.COLOR_HTML_DEAD.';"' : '').">$p->nr $p->name</option>";
											$DISABLE = false;
										}
									}
									objsort($players, array('+nr'));
									?>
									</select>
									<br><br>
									<input type="radio" CHECKED name="sign" value="+">+
									<input type="radio" name="sign" value="-">-
									<input type='text' name='amount' maxlength="5" size="5"> &Delta; SPP
									<input type="hidden" name="type" value="spp">
									<?php
									break;
								/***************
								 * Manage extra player value
								 **************/
								case 'dval':
									echo $lng->getTrn('profile/team/box_admin/desc/dval');
									?>
									<hr><br>
									<?php echo $lng->getTrn('common/player');?>:<br>
									<select name="player">
									<?php
									$DISABLE = true;
									//objsort($players, array('+is_dead', '+name'));
									foreach ($players as $p) {
										if (!$p->is_sold) {
											echo "<option value='$p->player_id'".(($p->is_dead) ? ' style="background-color:'.COLOR_HTML_DEAD.';"' : '').">$p->nr $p->name (current extra = ".($p->extra_val/1000)."k)</option>";
											$DISABLE = false;
										}
									}
									objsort($players, array('+nr'));
									?>
									</select>
									<br><br>
									Set extra value to<br>
									<input type="radio" CHECKED name="sign" value="+">+
									<input type="radio" name="sign" value="-">-
									<input type='text' name='amount' maxlength="10" size="6">k
									<input type="hidden" name="type" value="dval">
									<?php
									break;
								/***************
								 * Manage extra skills
								 **************/
								case 'extra_skills':
									echo $lng->getTrn('profile/team/box_admin/desc/extra_skills');
									?>
									<hr><br>
									<?php echo $lng->getTrn('common/player');?>:<br>
									<select name="player">
									<?php
									$DISABLE = true;
									foreach ($players as $p) {
										if (!$p->is_sold && !$p->is_dead) {
											echo "<option value='$p->player_id'>$p->nr $p->name</option>";
											$DISABLE = false;
										}
									}
									?>
									</select>
									<br><br>
									Skill:<br>
									<select name="skill">
									<?php
									foreach ($skillarray as $cat => $skills) {
										echo "<OPTGROUP LABEL='$cat'>";
										foreach ($skills as $id => $skill) {
											echo "<option value='$id'>$skill</option>";
										}
										echo "</OPTGROUP>";
									}
									?>
									</select>
									<br><br>
									Action (add/remove)<br>
									<input type="radio" CHECKED name="sign" value="+">+
									<input type="radio" name="sign" value="-">-
									<input type="hidden" name="type" value="extra_skills">
									<?php
									break;
								/***************
								 * Remove achived skills
								 **************/
								case 'ach_skills':
									echo $lng->getTrn('profile/team/box_admin/desc/ach_skills');
									?>
									<hr><br>
									<?php echo $lng->getTrn('common/player');?>:<br>
									<select name="player">
									<?php
									$DISABLE = true;
									foreach ($players as $p) {
										if (!$p->is_dead && !$p->is_sold) {
											echo "<option value='$p->player_id'>$p->nr $p->name</option>\n";
											$DISABLE = false;
										}
									}
									?>
									</select>
									<br><br>
									Skill<br>
									<select name="skill">
									<?php
									foreach ($skillarray as $cat => $skills) {
										echo "<OPTGROUP LABEL='$cat'>";
										foreach ($skills as $id => $skill) {
											echo "<option value='$id'>$skill</option>";
										}
										echo "</OPTGROUP>";
									}
									echo "<optgroup label='Characteristic improvement'>\n";
									foreach ($CHR_CONV as $key => $name) {
										if  ($name == 'ma' || $name == 'av' || $name == 'st' ) {
										echo "<option value='ach_$key'>+ ".ucfirst($name)."</option>\n";
										}
										else {
										echo "<option value='ach_$key'>- ".ucfirst($name)."</option>\n";	
										}
									}
									echo "</optgroup>\n";
									?>
									</select>
									<input type="hidden" name="type" value="ach_skills">
									<?php
									break;
								/***************
								 * Remove niggling injuries
								 **************/
								case 'removeNiggle':
									echo $lng->getTrn('profile/team/box_admin/desc/removeNiggle');
									?>
									<hr><br>
									<?php echo $lng->getTrn('common/player');?>:<br>
									<select name="player">
									<?php
									$DISABLE = true;
									foreach ($players as $p) {
										if ($p->is_sold || $p->is_dead || $p->is_journeyman || $p->inj_ni == 0)
											continue;

										echo "<option value='$p->player_id'>$p->nr $p->name</option>\n";
										$DISABLE = false;
									}
									?>
									</select>
									<input type="hidden" name="type" value="removeNiggle">
									<?php
									break;
								/***************
								 * Add niggling injury
								 **************/
								case 'addniggle':
									echo $lng->getTrn('profile/team/box_admin/desc/addniggle');
									?>
									<hr><br>
									<?php echo $lng->getTrn('common/player');?>:<br>
									<select name="player">
									<?php
									$DISABLE = true;
									foreach ($players as $p) {
										if ($p->is_sold || $p->is_dead || $p->is_journeyman)
											continue;

										echo "<option value='$p->player_id'>$p->nr $p->name</option>\n";
										$DISABLE = false;
									}
									?>
									</select>
									<input type="hidden" name="type" value="addniggle">
									<?php
									break;
								/***************
								 * Remove MNG Status
								 **************/
								case 'removeMNG':
									echo $lng->getTrn('profile/team/box_admin/desc/removeMNG');
									?>
									<hr><br>
									<?php echo $lng->getTrn('common/player');?>:<br>
									<select name="player">
									<?php
									$DISABLE = true;
									foreach ($players as $p) {
										if ($p->is_sold || $p->is_dead || $p->is_journeyman || $p->getStatus('-1') == 1)
											continue;
										echo "<option value='$p->player_id'>$p->nr $p->name"?>
										<?php 
										if ($p->getStatus('-1') == 2) {
											echo " (MNG)</option>\n";
										} elseif ($p->getStatus('-1') == 0) {
											echo " (RET)</option>\n";
										} 
										$DISABLE = false;
									}
									?>
									</select>
									<input type="hidden" name="type" value="removeMNG">
									<?php
									break;
								/***************
								 * Remove a stat injury
								 **************/
								case 'removenegastat':
									echo $lng->getTrn('profile/team/box_admin/desc/removenegastat');
									?>
									<hr><br>
									<?php echo $lng->getTrn('common/player');?>:<br>
									<select name="player">
									<?php
									$DISABLE = true;
									foreach ($players as $p) {
										if ($p->is_sold || $p->is_dead || $p->is_journeyman || $p->inj_ma + $p->inj_st + $p->inj_ag + $p->inj_pa + $p->inj_av == 0)
											continue;

										echo "<option value='$p->player_id'>$p->nr $p->name</option>\n";
										$DISABLE = false;
									}
									?>
									</select>
									<br><br>
									Stat injury<br>
									<select name="stat">
									<?php
									foreach ($players as $p) {
										if  ($p->inj_ma > 0) {
										echo "<option value='ma'>- MA</option>\n";
										}
										if  ($p->inj_st > 0) {
										echo "<option value='st'>- ST</option>\n";
										}
										if  ($p->inj_ag > 0) {
										echo "<option value='ag'>+ AG</option>\n";
										}
										if  ($p->inj_pa > 0) {
										echo "<option value='pa'>+ PA</option>\n";
										}
										if  ($p->inj_av > 0) {
										echo "<option value='av'>- AV</option>\n";
										}
									}
									?>
									</select>
									<input type="hidden" name="type" value="removenegastat">
									<?php
									break;
							}
							?>
							<br><br>
							<input type="submit" name="button" value="OK" <?php echo ($DISABLE ? 'DISABLED' : '');?> >
						</form>
					</div>
				</div>
				<?php
			}
		}
		?>
		<br>
		<div class="row"></div>
		<br>
		<?php
		if (!$settings['hide_ES_extensions']){
			?>
			<div class="row">
				<div class="boxWide">
					<div class="boxTitle<?php echo T_HTMLBOX_STATS;?>"><a href='javascript:void(0);' onClick="slideToggleFast('ES');"><b>[+/-]</b></a> &nbsp;<?php echo $lng->getTrn('common/extrastats'); ?></div>
					<div class="boxBody" id="ES" style='display:none;'>
						<?php
						HTMLOUT::generateEStable($this);
						?>
					</div>
				</div>
			</div>
			<?php
		}
		// If an team action was chosen, jump to actions HTML anchor.
		if ($JMP_ANC) {
			?>
			<script language="JavaScript" type="text/javascript">
			window.location = "#aanc";
			</script>
			<?php
		}
	}

	private function _teamManagementBox($players, $team) {
		global $lng, $rules, $DEA, $T_ALLOWED_PLAYER_NR;
		?>
		<!-- Following HTML is from class_team_htmlout.php _teamManagementBox -->
		<div class="boxTeamPage">
		<div class="boxTitle<?php echo T_HTMLBOX_COACH;?>"><?php echo $lng->getTrn('profile/team/box_tm/title') . ' - ' . $team->name;?></div>
		<div class="boxBody">
			<?php
			$base = 'profile/team';
			if ($rules['randomskillrolls'] == 1) {
				$tmanage = array(
					'select_rule'       => $lng->getTrn($base.'/box_tm/select_rule'),
					'hire_player'       => $lng->getTrn($base.'/box_tm/hire_player'),
					'hire_journeyman'   => $lng->getTrn($base.'/box_tm/hire_journeyman'),
					'fire_player'       => $lng->getTrn($base.'/box_tm/fire_player'),
					'unbuy_player'      => $lng->getTrn($base.'/box_tm/unbuy_player'),
					'rename_player'     => $lng->getTrn($base.'/box_tm/rename_player'),
					'renumber_player'   => $lng->getTrn($base.'/box_tm/renumber_player'),
					'retire_player'   	=> $lng->getTrn($base.'/box_tm/retire_player'),
					'rename_team'       => $lng->getTrn($base.'/box_tm/rename_team'),
					'buy_goods'         => $lng->getTrn($base.'/box_tm/buy_goods'),
					'drop_goods'        => $lng->getTrn($base.'/box_tm/drop_goods'),
					'ready_state'       => $lng->getTrn($base.'/box_tm/ready_state'),
					'retire'            => $lng->getTrn($base.'/box_tm/retire'),
					'delete'            => $lng->getTrn($base.'/box_tm/delete'),
				);
			} else {
				$tmanage = array(
					'select_rule'       => $lng->getTrn($base.'/box_tm/select_rule'),
					'hire_player'       => $lng->getTrn($base.'/box_tm/hire_player'),
					'hire_journeyman'   => $lng->getTrn($base.'/box_tm/hire_journeyman'),
					'fire_player'       => $lng->getTrn($base.'/box_tm/fire_player'),
					'unbuy_player'      => $lng->getTrn($base.'/box_tm/unbuy_player'),
					'rename_player'     => $lng->getTrn($base.'/box_tm/rename_player'),
					'renumber_player'   => $lng->getTrn($base.'/box_tm/renumber_player'),				
					'random_skill'   	=> $lng->getTrn($base.'/box_tm/random_skill'),
					'retire_player'   	=> $lng->getTrn($base.'/box_tm/retire_player'),
					'rename_team'       => $lng->getTrn($base.'/box_tm/rename_team'),
					'buy_goods'         => $lng->getTrn($base.'/box_tm/buy_goods'),
					'drop_goods'        => $lng->getTrn($base.'/box_tm/drop_goods'),
					'ready_state'       => $lng->getTrn($base.'/box_tm/ready_state'),
					'retire'            => $lng->getTrn($base.'/box_tm/retire'),
					'delete'            => $lng->getTrn($base.'/box_tm/delete'),
				);				
			}
			# If a favoured of ... rule has already been selected OR if it does not apply, hide option
			if (strlen($team->getFavrulechosen()) >= 1 || strlen($team->getFavruleoptions()) == 0  ) { 
			unset($tmanage['select_rule']);
			}
			# If one of these are selected from the menu, a JavaScript confirm prompt is displayed before submitting.
			# Note: Don't add "hire_player" here - players may be un-bought if not having played any games.
			$tmange_confirm = array('hire_journeyman', 'fire_player', 'buy_goods', 'drop_goods','retire_player','select_rule','random_skill');
			// Set default choice.
			if (!isset($_POST['menu_tmanage'])) {
				reset($tmanage);
				$_POST['menu_tmanage'] = key($tmanage);
			}
			// If action is already chosen, then make it the default selected.
			if (isset($_POST['type']) && array_key_exists($_POST['type'], $tmanage)) {
				$_POST['menu_tmanage'] = $_POST['type'];
			}
			?>
			<form method="POST" name="menu_tmanage_form">
				<select name="menu_tmanage" onchange="document.menu_tmanage_form.submit();">
					<?php
					foreach ($tmanage as $opt => $desc)
						echo "<option value='$opt'" . ($_POST['menu_tmanage'] == $opt ? 'SELECTED' : '') . ">$desc</option>";
					?>
				</select>
				<!-- <input type="submit" name="tmanage" value="OK"> -->
			</form>
			<br><i><?php echo $lng->getTrn('common/desc');?>:</i><br><br>
			<form name="form_tmanage" method="POST" enctype="multipart/form-data">
			<?php
			$DISABLE = false;
			switch ($_POST['menu_tmanage']) {
				/**************
				 * Select teams special rule
				 **************/
				case 'select_rule':				
					echo $lng->getTrn('profile/team/box_tm/desc/select_rule');
					?>
					<hr><br>
					<?php echo $lng->getTrn('common/specialrules');?>:<br>
					<select name="rule">
					<?php
                    $optionsArr = explode(",", $team->getFavruleoptions()); 
					foreach ($optionsArr as $opt) {
					$opt_text = 'profile/team/box_tm/favrule/r'.$opt;
						echo "<option value='$opt'>".$lng->getTrn($opt_text)."</option>\n";
					}
					?>
					</select>
					<input type="hidden" name="type" value="select_rule">
					<?php
					break;
				/**************
				 * Hire player
				 **************/
				case 'hire_player':
					echo $lng->getTrn('profile/team/box_tm/desc/hire_player');
					?>
					<hr><br>
					<?php echo $lng->getTrn('common/player');?>:<br>
					<select name='player'>
					<?php
					$active_players = array_filter($players, create_function('$p', "return (\$p->is_sold || \$p->is_dead || \$p->is_mng || \$p->is_retired) ? false : true;"));
					$DISABLE = true;
					foreach ($DEA[$team->f_rname]['players'] as $pos => $details) {
						// Show players on the select list if buyable, or if player is a potential journeyman AND team has not reached journeymen limit. Also Checking for big guy limits via isMaxBigGuys and dungeon bowl positional limits via isPlayerBuyable
						if ($DEA[$team->f_rname]['other']['format'] =='SV') {
							if ($team->isMaxBigGuys()) {
								if (($team->isPlayerBuyable($details['pos_id']) && $team->treasury >= $details['cost'] && $details['is_bigguy'] == 0) ||
									(($details['qty'] == 11) && count($active_players) < $rules['journeymen_limit_sevens'])) {
									echo "<option value='$details[pos_id]'>" . $details['cost']/1000 . "k | ".$lng->GetTrn('position/'.strtolower($lng->FilterPosition($pos)))."</option>\n";
									$DISABLE = false;
								}
							}
							else {
								if (($team->isPlayerBuyable($details['pos_id']) && $team->treasury >= $details['cost']) ||
									(($details['qty'] == 11) && count($active_players) < $rules['journeymen_limit_sevens'])) {
									echo "<option value='$details[pos_id]'>" . $details['cost']/1000 . "k | ".$lng->GetTrn('position/'.strtolower($lng->FilterPosition($pos)))."</option>\n";
									$DISABLE = false;
								}
							}
						} else {
							if ($team->isMaxBigGuys()) {
								if (($team->isPlayerBuyable($details['pos_id']) && $team->treasury >= $details['cost'] && $details['is_bigguy'] == 0) ||
									(($details['qty'] == 16 || $details['qty'] == 12) && count($active_players) < $rules['journeymen_limit'])) {
									echo "<option value='$details[pos_id]'>" . $details['cost']/1000 . "k | ".$lng->GetTrn('position/'.strtolower($lng->FilterPosition($pos)))."</option>\n";
									$DISABLE = false;
								}
							}
							else {
								if (($team->isPlayerBuyable($details['pos_id']) && $team->treasury >= $details['cost']) ||
									(($details['qty'] == 16 || $details['qty'] == 12) && count($active_players) < $rules['journeymen_limit'])) {
									echo "<option value='$details[pos_id]'>" . $details['cost']/1000 . "k | ".$lng->GetTrn('position/'.strtolower($lng->FilterPosition($pos)))."</option>\n";
									$DISABLE = false;
								}
							}
						}
					}
					echo "</select>\n";
					?>
					<br><br>
					<?php echo $lng->getTrn('common/number');?>:<br>
					<select name="number">
					<?php
					foreach ($T_ALLOWED_PLAYER_NR as $i) {
						foreach ($players as $p) {
							if ($p->nr == $i && !$p->is_sold && !$p->is_dead)
								continue 2;
						}
						echo "<option value='$i'>$i</option>\n";
					}
					?>
					</select>
					<br><br>
					<?php echo $lng->GetTrn('common/journeyman')?> ? <input type="checkbox" name="as_journeyman" value="1">
					<br><br>
					<?php echo $lng->getTrn('common/name');?>:<br>
					<input type="text" name="name">
					<input type="hidden" name="type" value="hire_player">
					<?php
					break;
				/**************
				 * Hire journeymen
				 **************/
				case 'hire_journeyman':
					echo $lng->getTrn('profile/team/box_tm/desc/hire_journeyman');
					?>
					<hr><br>
					<?php echo $lng->getTrn('common/player');?>:<br>
					<select name="player">
					<?php
					$DISABLE = true;
					foreach ($players as $p) {
						$price = $p->value;
						if (!$p->is_journeyman || $p->is_sold || $p->is_dead ||
							$team->treasury < $price || !$team->isPlayerBuyable($p->f_pos_id) || $team->isFull()) {
							continue;
						}

						echo "<option value='$p->player_id'>$p->nr $p->name | " . $price/1000 . " k</option>\n";
						$DISABLE = false;
					}
					?>
					</select>
					<input type="hidden" name="type" value="hire_journeyman">
					<?php
					break;
				/**************
				 * Fire player
				 **************/
				case 'fire_player':
					echo $lng->getTrn('profile/team/box_tm/desc/fire_player').' '.$rules['player_refund']*100 . "%.\n";
					?>
					<hr><br>
					<?php echo $lng->getTrn('common/player');?>:<br>
					<select name="player">
					<?php
					$DISABLE = true;
					foreach ($players as $p) {
						if ($p->is_dead || $p->is_sold)
							continue;

						echo "<option value='$p->player_id'>" . ($rules['player_refund'] ? (($p->value/1000)*$rules['player_refund'])."k refund | " : "") . "$p->nr $p->name</option>\n";
						$DISABLE = false;
					}
					?>
					</select>
					<input type="hidden" name="type" value="fire_player">
					<?php
					break;
				/***************
				 * Un-buy player
				 **************/
				case 'unbuy_player':
					echo $lng->getTrn('profile/team/box_tm/desc/unbuy_player');
					?>
					<hr><br>
					<?php echo $lng->getTrn('common/player');?>:<br>
					<select name="player">
					<?php
					$DISABLE = true;
					foreach ($players as $p) {
						if ($p->is_unbuyable() && !$p->is_sold) {
								echo "<option value='$p->player_id'>$p->nr $p->name</option>\n";
								$DISABLE = false;
						}
					}
					?>
					</select>
					<input type="hidden" name="type" value="unbuy_player">
					<?php
					break;
				/**************
				 * Rename player
				 **************/
				case 'rename_player':
					echo $lng->getTrn('profile/team/box_tm/desc/rename_player');
					?>
					<hr><br>
					<?php echo $lng->getTrn('common/player');?>:<br>
					<select name="player">
					<?php
					$DISABLE = true;
					foreach ($players as $p) {
						unset($color);
						if ($p->is_dead)
							$color = COLOR_HTML_DEAD;
						elseif ($p->is_sold)
							$color = COLOR_HTML_SOLD;

						echo "<option value='$p->player_id' ".(isset($color) ? "style='background-color: $color;'" : '').">$p->nr $p->name</option>\n";
						$DISABLE = false;
					}
					?>
					</select>
					<br><br>
					<?php echo $lng->getTrn('common/name');?>:<br>
					<input type='text' name='name' maxlength=50 size=20>
					<input type="hidden" name="type" value="rename_player">
					<?php
					break;
				/**************
				 * Renumber player
				 **************/
				case 'renumber_player':
					echo $lng->getTrn('profile/team/box_tm/desc/renumber_player');
					?>
					<hr><br>
					<?php echo $lng->getTrn('common/player');?>:<br>
					<select name="player">
					<?php
					$DISABLE = true;
					foreach ($players as $p) {
						unset($color);
						if ($p->is_dead)
							$color = COLOR_HTML_DEAD;
						elseif ($p->is_sold)
							$color = COLOR_HTML_SOLD;

						echo "<option value='$p->player_id' ".(isset($color) ? "style='background-color: $color;'" : '').">$p->nr $p->name</option>\n";
						$DISABLE = false;
					}
					?>
					</select>
					<br><br>
					<?php echo $lng->getTrn('common/number');?>:<br>
					<select name="number">
					<?php
					foreach ($T_ALLOWED_PLAYER_NR as $i) {
						echo "<option value='$i'>$i</option>\n";
					}
					?>
					</select>
					<input type="hidden" name="type" value="renumber_player">
					<?php
					break;
				/**************
				 * New Random Skill
				 **************/
				case 'random_skill':
					echo $lng->getTrn('profile/team/box_tm/desc/random_skill');
					?>
					<hr><br>
					<?php echo $lng->getTrn('common/player');?>:<br>
					<select name="player">
					<?php
					$DISABLE = true;
					foreach ($players as $p) {
						if ($p->is_dead || $p->is_sold || $p->is_retired) 
						continue;
						if (($p->numberOfAchSkill() == 0 && $p->mv_spp >2) || ($p->numberOfAchSkill() == 1 && $p->mv_spp >3) || ($p->numberOfAchSkill() == 2 && $p->mv_spp >5) || ($p->numberOfAchSkill() == 3 && $p->mv_spp >7) || ($p->numberOfAchSkill() == 4 && $p->mv_spp >9) || ($p->numberOfAchSkill() == 5 && $p->mv_spp >14))
						echo "<option value='$p->player_id'>$p->nr $p->name ($p->mv_spp SPP)</option>\n";
						$DISABLE = false;
					}
					?>
					</select>
					<br><br>
					<?php echo $lng->getTrn('common/skill_type');?>:<br>
					<select name="skill_type">
					<option value='P'>Primary</option>
					<option value='S'>Secondary</option>
					</select>
					<br><br>
					<?php echo $lng->getTrn('common/skill_cat');?>:<br>
					<select name="skill_cat">
					<option value='G'>General</option>
					<option value='A'>Agility</option>
					<option value='S'>Strength</option>
					<option value='P'>Passing</option>
					<option value='M'>Mutation</option>
					</select>
					<input type="hidden" name="type" value="random_skill">
					<?php
					break;
					
				/**************
				 * Temporary retire player
				 **************/
				case 'retire_player':
					echo $lng->getTrn('profile/team/box_tm/desc/retire_player');
					?>
					<hr><br>
					<?php echo $lng->getTrn('common/player');?>:<br>
					<select name="player">
					<?php
					$DISABLE = true;
					foreach ($players as $p) {
						if ($p->is_dead || $p->is_sold || $p->is_retired || !$p->can_retire)
							continue;

						echo "<option value='$p->player_id'>$p->nr $p->name</option>\n";
						$DISABLE = false;
					}
					?>
					</select>
					<input type="hidden" name="type" value="retire_player">
					<?php
					break;
				/**************
				 * Rename team
				 **************/
				case 'rename_team':
					echo $lng->getTrn('profile/team/box_tm/desc/rename_team');
					?>
					<hr><br>
					<?php echo $lng->getTrn('common/name');?>:<br>
					<input type='text' name='name' maxlength='50' size='20'>
					<input type="hidden" name="type" value="rename_team">
					<?php
					break;
				/**************
				 * Buy team goods
				 **************/
				case 'buy_goods':
					echo $lng->getTrn('profile/team/box_tm/desc/buy_goods');
					$goods_temp = $team->getGoods();
					if ($DEA[$team->f_rname]['other']['rr_cost'] != $goods_temp['rerolls']['cost'] && $DEA[$team->f_rname]['other']['format'] != 'SV') {
						echo $lng->getTrn('profile/team/box_tm/desc/buy_goods_warn');
					}
					?>
					<hr><br>
					<?php echo $lng->getTrn('profile/team/box_tm/fdescs/thing');?>:<br>
					<select name="thing">
					<?php
					$DISABLE = true;
					foreach ($team->getGoods() as $name => $details) {
						if ($name == 'ff_bought' && !$team->mayBuyFF())
							continue;
						if ($DEA[$team->f_rname]['other']['format'] =='SV' && $name == 'rerolls' && !$team->mayBuyRR())
							continue;
						if (($team->$name < $details['max'] || $details['max'] == -1) && $team->treasury >= $details['cost']) {
							echo "<option value='$name'>" . $details['cost']/1000 . "k | $details[item]</option>\n";
							$DISABLE = false;
						}
					}
					?>
					</select>
					<input type="hidden" name="type" value="buy_goods">
					<?php
					break;
				/**************
				 * Let go (drop) of team goods
				 **************/
				case 'drop_goods':
					echo $lng->getTrn('profile/team/box_tm/desc/drop_goods');
					?>
					<hr><br>
					<?php echo $lng->getTrn('profile/team/box_tm/fdescs/thing');?>:<br>
					<select name="thing">
					<?php
					$DISABLE = true;
					foreach ($team->getGoods() as $name => $details) {
						if ($name == 'ff_bought' && !$team->mayBuyFF())
							continue;
						if ($name == 'rerolls' && !$team->mayDropRR())
							continue;
						if ($team->$name > 0) {
							echo "<option value='$name'>$details[item]</option>\n";
							$DISABLE = false;
						}
					}
					?>
					</select>
					<input type="hidden" name="type" value="drop_goods">
					<?php
					break;
				/**************
				 * Set ready state
				 **************/
				case 'ready_state':
					echo $lng->getTrn('profile/team/box_tm/desc/ready_state');
					?>
					<hr><br>
					<?php echo $lng->getTrn('profile/team/box_tm/fdescs/teamready');?>
					<input type="checkbox" name="bool" value="1" <?php echo ($team->rdy) ? 'CHECKED' : '';?>>
					<input type="hidden" name="type" value="ready_state">
					<?php
					break;
				/***************
				 * Retire
				 **************/
				case 'retire':
					echo $lng->getTrn('profile/team/box_tm/desc/retire');
					?>
					<hr><br>
					<?php echo $lng->getTrn('profile/team/box_tm/fdescs/retire');?>
					<input type="checkbox" name="bool" value="1">
					<input type="hidden" name="type" value="retire">
					<?php
					break;
				/***************
				 * Delete
				 **************/
				case 'delete':
					echo $lng->getTrn('profile/team/box_tm/desc/delete');
					if (!$this->isDeletable()) {
						$DISABLE = true;
					}
					?>
					<hr><br>
					<?php echo $lng->getTrn('profile/team/box_tm/fdescs/suredeleteteam');?>
					<input type="checkbox" name="bool" value="1" <?php echo ($DISABLE) ? 'DISABLED' : '';?>>
					<input type="hidden" name="type" value="delete">
					<?php
					break;
				}
				?>
				<br><br>
				<input type="submit" name="button" value="OK" <?php echo ($DISABLE ? 'DISABLED' : '');?>
					<?php if (in_array($_POST['menu_tmanage'], $tmange_confirm)) {echo "onClick=\"if(!confirm('".$lng->getTrn('common/confirm_box')."')){return false;}\"";}?>
				>
				<?php if(Mobile::isMobile()) {
					echo '<a href="' . getFormAction('') . '">' . $lng->getTrn('common/back') . '</a>';
				} ?>
			</form>
		</div>
	</div>
	<?php
	}

	private function _about($ALLOW_EDIT) {
		global $lng;
		$team = $this; // Copy. Used instead of $this for readability.
		title('<div class="team-management-title">' . $lng->getTrn('common/about') . '</div>');
		?>
		<!-- Following HTML is from class_team_htmlout.php _about -->
		<table class='common'>

			<tr class='commonhead'>
				<td><b><?php echo $lng->getTrn('profile/team/logo');?></b></td>
				<td><b><?php echo $lng->getTrn('profile/team/stad');?></b></td>
				<td><b><?php echo $lng->getTrn('common/about');?></b></td>
			</tr>
			<tr>
				<td>
					<?php
					ImageSubSys::makeBox(IMGTYPE_TEAMLOGO, $team->team_id, $ALLOW_EDIT, '_logo');
					?>
				</td>
				<td>
					<?php
					ImageSubSys::makeBox(IMGTYPE_TEAMSTADIUM, $team->team_id, $ALLOW_EDIT, '_stad');
					?>
				</td>
				<td valign='top'  rowspan="3" style='width: 100%;'>
					<?php
					$txt = $team->getText();
					if (empty($txt)) {
						$txt = $lng->getTrn('common/nobody');
					}

					if ($ALLOW_EDIT) {
						?>
						<form method='POST'>
							<textarea name='teamtext' rows='15' style='width: 100%;'><?php echo $txt;?></textarea>
							<br><br>
							<input type="hidden" name="type" value="teamtext">
							<center>
							<input type="submit" name='Save' value='<?php echo $lng->getTrn('common/save');?>'>
							</center>
						</form>
						<?php
					}
					else {
						echo '<p>'.fmtprint($txt)."</p>\n";
					}
					?>
				</td>
			</tr>
			<tr class='commonhead'>
				<td><b><?php echo $lng->getTrn('profile/team/sponsor');?></b></td>
				<td><b><?php echo $lng->getTrn('profile/team/stadium');?></b></td>			
			</tr>
			<tr>
				<td valign='top'>
					<?php
					$txt = $team->getSponsor();
					if (empty($txt)) {
						$txt = $lng->getTrn('profile/team/nosponsor');
					}

					if ($ALLOW_EDIT) {
						?>
						<form method='POST'>
							<input type="text"  name='teamsponsor' value='<?php echo $txt;?>'></text>
							<br><br>
							<input type="hidden" name="type" value="teamsponsor">
							<input type="submit" name='Save' value='<?php echo $lng->getTrn('common/save');?>'>
						</form>
						<?php
					}
					else {
						echo '<p>'.fmtprint($txt)."</p>\n";
					}
					?>
				</td>
				<td valign='top'>
					<?php
					$txt = $team->getStadium();
					if (empty($txt)) {
						$txt = $lng->getTrn('profile/team/nostadium');
					}

					if ($ALLOW_EDIT) {
						?>
						<form method='POST'>
							<input type="text" name='teamstadium' value='<?php echo $txt;?>'></text>
							<br><br>
							<input type="hidden" name="type" value="teamstadium">
							<input type="submit" name='Save' value='<?php echo $lng->getTrn('common/save');?>'>
						</form>
						<?php
					}
					else {
						echo '<p>'.fmtprint($txt)."</p>\n";
					}
					?>
				</td>
			</tr>
		</table>
		<?php
	}

	private function _news($ALLOW_EDIT) {
		global $lng;
		$team = $this; // Copy. Used instead of $this for readability.
		title('<div class="team-management-title">' . $lng->getTrn('profile/team/news') . '</div>');
		$news = $team->getNews(MAX_TNEWS);
		?>
		<!-- Following HTML is from class_team_htmlout.php _news -->
		<div class="row">
			<div class="boxWide">
				<div class="boxTitle<?php echo T_HTMLBOX_INFO;?>"><?php echo $lng->getTrn('profile/team/tnews');?></div>
				<div class="boxBody">
				<?php
				$news_2 = array();
				foreach ($news as $n) {
					$news_2[] = '<p>'.fmtprint($n->txt).
					'<div id="newsedit'.$n->news_id.'" style="display:none; clear:both;"><form method="POST">
						<textarea name="txt" cols="60" rows="4">'.$n->txt.'</textarea>
						<input type="hidden" name="type" value="newsedit">
						<input type="hidden" name="news_id" value="'.$n->news_id.'">
						<br><br>
						<input type="submit" value="'.$lng->getTrn('common/submit').'">
					</form></div>
					<div style="text-align: right;"><p style="display: inline;">'.textdate($n->date, true).
					(($ALLOW_EDIT)
						? '&nbsp;'.inlineform(array('type' => 'newsdel', 'news_id' => $n->news_id), "newsForm$n->news_id", $lng->getTrn('common/delete')).
							"&nbsp; <a href='javascript:void(0);' onClick=\"slideToggle('newsedit".$n->news_id."');\">".$lng->getTrn('common/edit')."</a>"
						: '')
					.'</p></div><br></p>';
				}
				echo implode("<hr>\n", $news_2);
				if (empty($news)) {
					echo '<i>'.$lng->getTrn('profile/team/nonews').'</i>';
				}
				if ($ALLOW_EDIT) {
					?>
					<hr>
					<br>
					<b><?php echo $lng->getTrn('profile/team/wnews');?></b>
					<form method="POST">
						<textarea name='txt' cols='60' rows='4'></textarea>
						<br><br>
						<input type="hidden" name="type" value="news">
						<input type='submit' value="<?php echo $lng->getTrn('common/submit');?>">
					</form>
					<?php
				}
				?>
				</div>
			</div>
		</div>
		<?php
	}

	private function _games() {
		global $lng;
		$team = $this; // Copy. Used instead of $this for readability.
		title('<div class="team-management-title">' . $lng->getTrn('profile/team/games') . '</div>');
		HTMLOUT::recentGames(T_OBJ_TEAM, $team->team_id, false, false, false, false, array('url' => urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$team->team_id,false,false).'&amp;subsec=games', 'n' => MAX_RECENT_GAMES, 'GET_SS' => 'gp'));
		echo "<br>";
		HTMLOUT::upcomingGames(T_OBJ_TEAM, $team->team_id, false, false, false, false, array('url' => urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$team->team_id,false,false).'&amp;subsec=games', 'n' => MAX_RECENT_GAMES, 'GET_SS' => 'ug'));
	}
}