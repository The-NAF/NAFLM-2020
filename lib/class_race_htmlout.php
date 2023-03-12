<?php

class Race_HTMLOUT extends Race
{
	public static function profile($rid) {
		global $lng, $DEA, $stars, $specialruleididx;
		$race = new Race($rid);
		$roster = $DEA[$race->name];
		title($lng->getTrn('race/'.strtolower(str_replace(' ','', $race->name))));
		?>
		<!-- Following HTML from class_race_htmlout.php profile -->
		<center><img src="<?php echo RACE_ICONS.'/'.$roster['other']['icon'];?>" alt="Race icon"></center>
		<ul>
			<li><b><?php echo $lng->getTrn('common/format')?>:</b> 
			<?php 
			if ($roster['other']['format'] == 'BB') {
			echo "Blood Bowl";
			}
			elseif ($roster['other']['format'] == 'DB') {
			echo "Dungeon Bowl";
			}
			;?>
			</li>
			<?php if ($roster['other']['format'] == 'BB')  : ?>
			<li><b><?php echo $lng->getTrn('common/tier')?>:</b> <?php echo $roster['other']['tier'];?></li>
			<?php endif; ?>
			<li><b><?php echo $lng->getTrn('common/reroll')?>:</b> <?php echo $roster['other']['rr_cost']/1000;?>k</li>		
			<li><b><?php echo $lng->getTrn('common/maxbigguys')?>:</b> <?php echo $roster['other']['bigguy_qty'];?></li>
			<li><b><?php echo $lng->getTrn('common/specialrules')?>:</b> <?php echo specialsTrans($race->special_rules);?>
			<?php if (strlen($race->getraceFavruleoptions()) >= 1 ) {
				$fav_str = specialsTrans($race->getraceFavruleoptions());
				//separate OWC choice for norse
				if (preg_match('/Old World Classic, /',$fav_str)) { 
					$fav_str = preg_replace("/Old World Classic, /", "", $fav_str);
					$fav_str =  $fav_str.") OR Old World Classic";
				echo " (".$lng->getTrn('common/chooseone').$fav_str;	
				}
				else {
				echo " (".$lng->getTrn('common/chooseone').$fav_str.")";	
				}
			}				
			?></li>
		</ul><br>
		<?php
		//List available player positions for race
		$players = array();
		foreach ($roster['players'] as $player => $d) {
			$p = (object) array_merge(array('position' => $player), $d);
			$p->skills = implode(', ', skillsTrans($p->def));
			$p->N = implode('',$p->norm);
			$p->D = implode('',$p->doub);
			$p->position = $lng->getTrn("position/".strtolower(str_replace(' ','',$p->position)));
			$players[] = $p;
			if ($p->pa == 0) {       
				$p->pa = '-';
			}
			else {       
				$p->pa = $p->pa.'+';
			}
		}
		$fields = array(
			'qty'       => array('desc' => $lng->getTrn('common/maxqty')),
			'position'  => array('desc' => $lng->getTrn('common/pos')),
			'ma'        => array('desc' => $lng->getTrn('common/ma')),
			'st'        => array('desc' => $lng->getTrn('common/st')),
			'ag'        => array('desc' => $lng->getTrn('common/ag'), 'suffix' => '+'),
			'pa'        => array('desc' => $lng->getTrn('common/pa')), 
			'av'        => array('desc' => $lng->getTrn('common/av'), 'suffix' => '+'),
			'skills'    => array('desc' => $lng->getTrn('common/skills'), 'nosort' => true),
			'N'         => array('desc' => $lng->getTrn('common/normal'), 'nosort' => true),
			'D'         => array('desc' => $lng->getTrn('common/double'), 'nosort' => true),
			'av'        => array('desc' => $lng->getTrn('common/av'), 'suffix' => '+'),
			'skills'    => array('desc' => $lng->getTrn('common/skills'), 'nosort' => true),
			'N'         => array('desc' => $lng->getTrn('common/normal'), 'nosort' => true),
			'D'         => array('desc' => $lng->getTrn('common/double'), 'nosort' => true),
			'cost'      => array('desc' => $lng->getTrn('common/price'), 'kilo' => true, 'suffix' => 'k'),
		);
		HTMLOUT::sort_table(
			$lng->getTrn('common/roster'),
			urlcompile(T_URL_PROFILE,T_OBJ_RACE,$race->race_id,false,false),
			$players,
			$fields,
			sort_rule('race_page'),
			(isset($_GET['sortpl'])) ? array((($_GET['dirpl'] == 'a') ? '+' : '-') . $_GET['sortpl']) : array(),
			array('GETsuffix' => 'pl', 'noHelp' => true, 'doNr' => false)
		);
		//List available star players for race
		$racestars = array(); 
		foreach ($stars as $s => $d) {  
			if (in_array($race->race_id, $d['races'])) {
				$tmp = new Star($d['id']);
				$tmp->skills = skillsTrans($tmp->skills);    
				$tmp->special = specialsTrans($tmp->special);            
				$racestars[] = $tmp;
			}
			if ($tmp->pa == 0) {       
				$tmp->pa = '-';
			}
			if ($tmp->pa != '-' && $tmp->pa != '1+' && $tmp->pa != '2+' && $tmp->pa != '3+' && $tmp->pa != '4+' && $tmp->pa != '5+' && $tmp->pa != '6+') {       
				$tmp->pa = $tmp->pa.'+';
			}
		}
		$fields = array(
			'name'   => array('desc' => $lng->getTrn('common/star'), 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_STAR,false,false,false), 'field' => 'obj_id', 'value' => 'star_id')),
			'ma'     => array('desc' => $lng->getTrn('common/ma')),
			'st'     => array('desc' => $lng->getTrn('common/st')),
			'ag'     => array('desc' => $lng->getTrn('common/ag'), 'suffix' => '+'),
			'pa'     => array('desc' => $lng->getTrn('common/pa')),
			'av'     => array('desc' => $lng->getTrn('common/av'), 'suffix' => '+'),
			'skills' => array('desc' => $lng->getTrn('common/skills'), 'nosort' => true),
			'special' => array('desc' => $lng->getTrn('common/specialrules'), 'nosort' => true),
			'cost'   => array('desc' => $lng->getTrn('common/price'), 'kilo' => true, 'suffix' => 'k'),
		);
		HTMLOUT::sort_table(
			$lng->getTrn('common/availablestars'),
			urlcompile(T_URL_PROFILE,T_OBJ_RACE,$race->race_id,false,false),
			$racestars,
			$fields,
			sort_rule('star'),
			(isset($_GET['sort'])) ? array((($_GET['dir'] == 'a') ? '+' : '-') . $_GET['sort']) : array(),
			array('anchor' => 's2', 'doNr' => false, 'noHelp' => true)
		);
		// Teams of the chosen race.
		$url = urlcompile(T_URL_PROFILE,T_OBJ_RACE,$race->race_id,false,false);
		HTMLOUT::standings(STATS_TEAM,false,false,array('url' => $url, 'teams_from' => STATS_RACE, 'teams_from_id' => $race->race_id));
		echo '<br>';
		HTMLOUT::recentGames(STATS_RACE, $race->race_id, false, false, false, false, array('url' => $url, 'n' => MAX_RECENT_GAMES, 'GET_SS' => 'gp'));
	}

	public static function standings() {  
		global $lng;
		title($lng->getTrn('menu/statistics_menu/race_stn'));
		HTMLOUT::standings(STATS_RACE,false,false,array('url' => urlcompile(T_URL_STANDINGS,T_OBJ_RACE,false,false,false)));
	}
}