<?php

class Race
{
	/***************
	 * Properties 
	 ***************/
	public $race    = '';
	public $name    = ''; // = $this->race, used for conventional reasons.
	public $race_id = 0;

	/***************
	 * Methods 
	 ***************/
	function __construct($race_id) {
		global $raceididx;
		$this->race_id = $race_id;
		$this->race = $this->name = $raceididx[$this->race_id];
		$this->setStats(false,false,false);
		$this->setRaceSpecialRules(true);
	}

	public function setStats($node, $node_id, $set_avg = false) {
		foreach (Stats::getAllStats(T_OBJ_RACE, $this->race_id, $node, $node_id, $set_avg) as $key => $val)
			$this->$key = $val;
	}
	
	public function setRaceSpecialRules($makeString = false) {
        $query = "SELECT special_rules FROM races WHERE race_id = $this->race_id";
        $result = mysql_query($query);
        list($rspecialstr) = mysql_fetch_row($result);
        $this->special_rules = ($makeString) ? specialsTrans($rspecialstr) : (empty($rspecialstr) ? array() : explode(',', $rspecialstr));
    }
	
	public function getraceFavruleoptions() {
		$raceid = $this->race_id;
		$roptions = "";
        $query = "SELECT fav_rules FROM races WHERE race_id = $raceid";
        $result = mysql_query($query);
        if ($row = mysql_fetch_array($result)){
            $roptions = $row['fav_rules'];
        }
        return $roptions;
    }
	
	public function getGoods($double_RRs = false) {
		/**
		 * Returns buyable stuff for this race.
		 **/
		global $DEA, $rules, $racesNoApothecary, $lng;
		$rr_price = $DEA[$this->race]['other']['rr_cost'] * (($double_RRs) ? 2 : 1);
		$apoth = !in_array($this->race_id, $racesNoApothecary);
		if  ($DEA[$this->race]['other']['format'] <> 'SV') {
		return array(
				// MySQL column names as keys
				'apothecary'    => array('cost' => $rules['cost_apothecary'],   'max' => ($apoth ? 1 : 0),              'item' => $lng->GetTrn('common/apothecary')),
				'rerolls'       => array('cost' => $rr_price,                   'max' => $rules['max_rerolls'],         'item' => $lng->GetTrn('common/reroll')),
				'ff_bought'     => array('cost' => $rules['cost_fan_factor'],   'max' => $rules['max_fan_factor'],      'item' => $lng->GetTrn('matches/report/ff')),
				'ass_coaches'   => array('cost' => $rules['cost_ass_coaches'],  'max' => $rules['max_ass_coaches'],     'item' => $lng->GetTrn('common/ass_coach')),
				'cheerleaders'  => array('cost' => $rules['cost_cheerleaders'], 'max' => $rules['max_cheerleaders'],    'item' => $lng->GetTrn('common/cheerleader')),
		);
		} else {
		return array(
				// MySQL column names as keys
				'apothecary'    => array('cost' => $rules['cost_apothecary_sevens'],   'max' => ($apoth ? 1 : 0),              'item' => $lng->GetTrn('common/apothecary')),
				'rerolls'       => array('cost' => $rr_price,                   'max' => $rules['max_rerolls_sevens'],         'item' => $lng->GetTrn('common/reroll')),
				'ff_bought'     => array('cost' => $rules['cost_fan_factor_sevens'],   'max' => $rules['max_fan_factor'],      'item' => $lng->GetTrn('matches/report/ff')),
				'ass_coaches'   => array('cost' => $rules['cost_ass_coaches_sevens'],  'max' => $rules['max_ass_coaches_sevens'],     'item' => $lng->GetTrn('common/ass_coach')),
				'cheerleaders'  => array('cost' => $rules['cost_cheerleaders_sevens'], 'max' => $rules['max_cheerleaders_sevens'],    'item' => $lng->GetTrn('common/cheerleader')),
		);
		}
	}

	public static function exists($id) {
		global $raceididx;
		return in_array($id, array_keys($raceididx));
	}
}