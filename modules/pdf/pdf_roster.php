<?php

/*
 *  Copyright (c) Daniel Straalman <email is protected> 2008-2009. All Rights Reserved.
 *
 *
 *  This file is part of OBBLM.
 *
 *  OBBLM is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  OBBLM is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/*
 * Author Daniel Straalman, 2009
 *
 * Note: Detailed view does not work, only regular view. Detailed view with player history (sold/dead) would never fit on an A4 paper.
 */
 
class PDFroster implements ModuleInterface
{

public static function getModuleAttributes()
{
    return array(
        'author'     => 'Daniel Straalman',
        'moduleName' => 'PDF roster',
        'date'       => '2008-2009',
        'setCanvas'  => false, 
    );
}

public static function getModuleTables()
{
    return array();
}
 
public static function getModuleUpgradeSQL()
{
    return array();
}
 
public static function triggerHandler($type, $argv){}

public static function main($argv)
{

global $pdf;
global $DEA;
global $skillarray;
global $rules;
global $inducements;
global $starpairs;
global $lng;

define("MARGINX", 20);
define("MARGINY", 20);
define("DEFLINECOLOR", '#000000');
define("HEADLINEBGCOLOR", '#c3c3c3');

// Custom settings for inducements.

define('MAX_STARS', 2);
define('MERC_EXTRA_COST', 30000);
define('MERC_EXTRA_SKILL_COST', 50000);

// Color codes.
define('COLOR_ROSTER_NORMAL',   COLOR_HTML_NORMAL);
define('COLOR_ROSTER_READY',    COLOR_HTML_READY);
define('COLOR_ROSTER_MNG',      COLOR_HTML_MNG);
define('COLOR_ROSTER_RETIRED',  COLOR_HTML_RETIRED);
define('COLOR_ROSTER_DEAD',     COLOR_HTML_DEAD);
define('COLOR_ROSTER_SOLD',     COLOR_HTML_SOLD);
define('COLOR_ROSTER_STARMERC', COLOR_HTML_STARMERC);
define('COLOR_ROSTER_JOURNEY',  COLOR_HTML_JOURNEY);
define('COLOR_ROSTER_JOURNEY_USED',  COLOR_HTML_JOURNEY_USED);
define('COLOR_ROSTER_NEWSKILL', COLOR_HTML_NEWSKILL);
//-----
define('COLOR_ROSTER_CHR_EQP1', COLOR_HTML_CHR_EQP1); // Characteristic equal plus one.
define('COLOR_ROSTER_CHR_GTP1', COLOR_HTML_CHR_GTP1); // Characteristic greater than plus one.
define('COLOR_ROSTER_CHR_EQM1', COLOR_HTML_CHR_EQM1); // Characteristic equal minus one.
define('COLOR_ROSTER_CHR_LTM1', COLOR_HTML_CHR_LTM1); // Characteristic less than minus one.

define('T_PDF_ROSTER_SET_EMPTY_ON_ZERO', true); # Prints cp, td etc. as '' (empty string) when field = 0.

$ind_cost=0;

//
// Most of team and player data is copy/pasted from teams.php
//

$team_id = $_GET['team_id'];
// Is team id valid?
if (!get_alt_col('teams', 'team_id', $team_id, 'team_id'))
    fatal("Invalid team ID.");

$team  = new Team($team_id);
$coach = isset($_SESSION['logged_in']) ? new Coach($_SESSION['coach_id']) : null;

setupGlobalVars(T_SETUP_GLOBAL_VARS__LOAD_LEAGUE_SETTINGS, array('lid' => $team->f_lid)); // Load correct $rules for league.

$players = $team->getPlayers();

$tmp_players = array();
foreach ($players as $p) {
    if ($p->is_dead || $p->is_sold)
        continue;
    array_push($tmp_players, $p);
}
$players = $tmp_players;

// Team specific data

$rerollcost = $DEA[$team->f_rname]['other']['rr_cost'];
$teamtier = $DEA[$team->f_rname]['other']['tier'];
$teamformat = $DEA[$team->f_rname]['other']['format'];

$pdf=new BB_PDF('L','pt','A4'); // Creating a new PDF doc. Landscape, scale=pixels, size A4
$pdf->SetAutoPageBreak(false, 20); // No auto page break to mess up layout

$pdf->SetAuthor('Daniel Straalman');
$pdf->SetCreator('OBBLM');
$pdf->SetTitle('PDF Roster for ' . utf8_decode($team->name));
$pdf->SetSubject('PDF Roster for ' . utf8_decode($team->name));

$pdf->AddFont('Tahoma','','tahoma.php');  // Adding regular font Tahoma which is in font dir
$pdf->AddFont('Tahoma','B','tahomabd.php');  // Adding Tahoma Bold

// Initial settings
$pdf->SetFont('Tahoma','B',14);
$pdf->AddPage();
$pdf->SetLineWidth(1.5);
$currentx = MARGINX;
$currenty = MARGINY;
$pdf->SetFillColorBB($pdf->hex2cmyk(HEADLINEBGCOLOR));
$pdf->RoundedRect($currentx, $currenty, 802, 20, 6, 'DF'); // Filled rectangle around Team headline
$pdf->SetDrawColorBB($pdf->hex2cmyk(DEFLINECOLOR));

// Text in headline
$pdf->SetXY($currentx+8,$currenty);
$pdf->Cell(300, 20, utf8_decode($team->name), 0, 0, 'L', false, '');
$pdf->SetFont('Tahoma','',12);
$pdf->Cell(30, 20, "Race:", 0, 0, 'R', false, '');
$pdf->Cell(120, 20, ($team->f_rname), 0, 0, 'L', false, '');
$pdf->Cell(30, 20, "Tier:", 0, 0, 'R', false, '');
$pdf->Cell(10, 20, $teamtier, 0, 0, 'L', false, '');
$pdf->Cell(300, 20, ("Head Coach: " . utf8_decode($team->f_cname)), 0, 0, 'R', false, '');

$currenty+=25;
$currentx+=6;
$pdf->SetXY($currentx,$currenty);

$pdf->SetFillColorBB($pdf->hex2cmyk(HEADLINEBGCOLOR));
$pdf->SetDrawColorBB($pdf->hex2cmyk(DEFLINECOLOR));
$pdf->SetFont('Tahoma','B',8);
$pdf->SetLineWidth(1.5);
$h = 14;

// Printing headline for player table
$pdf->Cell(17, $h, 'Nr', 1, 0, 'C', true, '');
$pdf->Cell(85, $h, 'Name', 1, 0, 'L', true, '');
$pdf->Cell(99, $h, 'Position', 1, 0, 'L', true, '');
$pdf->Cell(18, $h, 'MA', 1, 0, 'C', true, '');
$pdf->Cell(18, $h, 'ST', 1, 0, 'C', true, '');
$pdf->Cell(18, $h, 'AG', 1, 0, 'C', true, '');
$pdf->Cell(18, $h, 'PA', 1, 0, 'C', true, '');
$pdf->Cell(18, $h, 'AV', 1, 0, 'C', true, '');
$pdf->Cell(279, $h, 'Skills and Injuries', 1, 0, 'L', true, '');
$pdf->Cell(22, $h, 'MNG', 1, 0, 'C', true, '');
$pdf->Cell(19, $h, 'Cp', 1, 0, 'C', true, '');
$pdf->Cell(19, $h, 'Td', 1, 0, 'C', true, '');
$pdf->Cell(20, $h, 'Def', 1, 0, 'C', true, '');
$pdf->Cell(20, $h, 'Int', 1, 0, 'C', true, '');
$pdf->Cell(20, $h, 'Cas', 1, 0, 'C', true, '');
$pdf->Cell(21, $h, 'MVP', 1, 0, 'C', true, '');
$pdf->Cell(21, $h, 'Misc', 1, 0, 'C', true, '');
$pdf->Cell(21, $h, 'SPP', 1, 0, 'C', true, '');
$pdf->Cell(38, $h, 'Value', 1, 0, 'C', true, '');

$currenty+=17;

$pdf->SetXY($currentx,$currenty);
$pdf->SetFont('Tahoma', '', 8);
$h=15;  // Row/cell height for player table

//
// Printing player rows
//

$sum_spp=0;
$sum_pvalue=0;
$sum_p_missing_value=0;
$sum_avail_players=0;
$sum_players=0;
$sum_cp=0;
$sum_td=0;
$sum_def=0;
$sum_int=0;
$sum_cas=0;
$sum_mvp=0;
$sum_misc=0;
$i=0;

// Looping through the players and printing the rows
foreach ($players as $p) {
  $i++;
  $mng='';
  $ret='';
  
  // Journeymen
  if ($p->is_journeyman) {
    $p->position = 'Journeyman';
    $bgc=COLOR_ROSTER_JOURNEY;
    if ($p->is_journeyman_used) {
        $bgc=COLOR_ROSTER_JOURNEY_USED;
    }
  }
  else $bgc=COLOR_ROSTER_NORMAL;
  
  // Concatenate skills, upgrades and injuries
  $skillstr = $p->getSkillsStr(false);
  $injstr = $p->getInjsStr(false);
  if ($skillstr == '') {  // No skills
    if ($injstr != '') $skills_injuries=$injstr;  // Only injuries
    else $skills_injuries=''; // No skills nor injuries
  }
  else {
    if ($injstr != '') $skills_injuries=$skillstr . ', ' . $injstr;   // Skills and injuries separated with ', '
    else $skills_injuries=$skillstr;  // Only skills, no injuries
  }
  
  // Concatenate special rules
  //$specialstr = $p->getSpecialsStr(false);
  
  // Colorcoding new skills available
  if ($p->mayHaveNewSkill()) $bgc=COLOR_ROSTER_NEWSKILL;
  
  if (!($p->is_mng) && !($p->is_retired)) { 
    $sum_avail_players++;
    $inj="";
  } 
  else {
	  if ($p->is_retired) {
		$bgc=COLOR_ROSTER_RETIRED;
		$sum_p_missing_value+=$p->value;
		$inj="RET"; // For MNG column
		// Removing RET from skills and injuries
		$skills_injuries = str_replace(', Retired', '', $skills_injuries);
		$skills_injuries = str_replace('Retired', '', $skills_injuries);
		$skills_injuries = str_replace('  ', ' ', $skills_injuries);    // Maybe not needed after changes to rest of code?
	  }
	  else {
		$bgc=COLOR_ROSTER_MNG;
		$sum_p_missing_value+=$p->value;
		$inj="MNG"; // For MNG column
		// Removing MNG from skills and injuries
		$skills_injuries = str_replace(', MNG', '', $skills_injuries);
		$skills_injuries = str_replace('MNG', '', $skills_injuries);
		$skills_injuries = str_replace('  ', ' ', $skills_injuries);    // Maybe not needed after changes to rest of code?
		}
  }
  
  // Characteristic's colors, copied and modified from teams.php
  foreach (array('ma', 'ag', 'pa', 'av', 'st') as $chr) {
      $sub = $p->$chr - $p->{"def_$chr"};
	  $defchr = $p->{"def_$chr"};
	  if ($chr == 'ma' || $chr == 'av' || $chr == 'st') {
		  if ($sub == 0)  $p->{"${chr}_color"} = $bgc;
		  elseif ($sub >= 1)  $p->{"${chr}_color"} = COLOR_ROSTER_CHR_GTP1;
		  elseif ($sub <= -1) $p->{"${chr}_color"} = COLOR_ROSTER_CHR_LTM1;
	  }
	  else {
		  if ($defchr > 0) {
			  if ($sub == 0)  $p->{"${chr}_color"} = $bgc;
			  elseif ($sub >= 1)  $p->{"${chr}_color"} = COLOR_ROSTER_CHR_LTM1;
			  elseif ($sub <= -1) $p->{"${chr}_color"} = COLOR_ROSTER_CHR_GTP1; 
		  }
		  else { 
			  if ($sub == 0)  $p->{"${chr}_color"} = $bgc;
			  elseif ($sub >= 7)  $p->{"${chr}_color"} = COLOR_ROSTER_CHR_LTM1;
			  elseif ($sub <= 6) $p->{"${chr}_color"} = COLOR_ROSTER_CHR_GTP1; 
		  }
	  }
  }

  $pp = array('nr'=>$p->nr, 'name'=>utf8_decode($p->name), 'pos'=>$p->position, 'ma'=>$p->ma, 'st'=>$p->st, 'ag'=>$p->ag, 'pa'=>$p->pa, 'av'=>$p->av, 'skills'=>utf8_decode($skills_injuries), 'inj'=>$inj,
     'cp'=>$p->mv_cp, 'td'=>$p->mv_td, 'def'=>$p->mv_deflct,  'int'=>$p->mv_intcpt, 'cas'=>$p->mv_cas, 'mvp'=>$p->mv_mvp, 'misc'=>$p->mv_misc, 'spp'=>$p->mv_spp, 'value'=>$pdf->Mf($p->value));
  $sum_spp+=$p->mv_spp;
  $sum_pvalue+=$p->value;
  $sum_players++;
  $sum_cp+=$p->mv_cp;
  $sum_td+=$p->mv_td;
  $sum_def+=$p->mv_deflct;
  $sum_int+=$p->mv_intcpt;
  $sum_cas+=$p->mv_cas;
  $sum_mvp+=$p->mv_mvp;
  $sum_misc+=$p->mv_misc;
  
    if (T_PDF_ROSTER_SET_EMPTY_ON_ZERO) {
        foreach (array('cp','td','def','int','cas','mvp','misc','spp') as $f) {
            if ($pp[$f] == 0) {
                $pp[$f] = '';
            }
        }
    }
  
  // Printing player row
  $currenty+=$pdf->print_prow($pp, $currentx, $currenty, $h, $bgc, DEFLINECOLOR, 0.5, 8, $p->ma_color, $p->st_color, $p->ag_color, $p->pa_color, $p->av_color);
}

// Filling up with empty rows to max number of players
$pp = array('nr'=>'', 'name'=>'', 'pos'=>'', 'ma'=>'', 'st'=>'', 'ag'=>'', 'pa'=>'', 'av'=>'', 'skills'=>'', 'inj'=>'',
            'cp'=>'', 'td'=>'', 'def'=>'', 'int'=>'', 'cas'=>'', 'mvp'=>'', 'misc'=>'', 'spp'=>'', 'value'=>'');
$bgc = COLOR_ROSTER_NORMAL;
while ($i<$rules['max_team_players']) {
  $i++;
  $currenty += $pdf->print_prow($pp, $currentx, $currenty, $h, '#FFFFFF', '#000000', 0.5, 8, $bgc, $bgc, $bgc, $bgc, $bgc);
}

// Sums
$sum_pvalue -= $sum_p_missing_value;
$pdf->SetXY(($currentx=MARGINX+6+23), ($currenty+=4));
$pdf->print_box($currentx, $currenty, 140, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', false, 'R', 'Total number of players next game:');
$pdf->print_box($currentx+=140, $currenty, 30, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', false, 'R', $sum_avail_players . '/' . $sum_players);

$pdf->SetX($currentx=MARGINX+6+538);
$pdf->print_box($currentx, $currenty, 50, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', false, 'R', 'Totals (excl TV for MNG or RET players):');
$pdf->print_box($currentx+=54, $currenty, 19, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', false, 'C', $sum_cp);
$pdf->print_box($currentx+=19, $currenty, 19, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', false, 'C', $sum_td);
$pdf->print_box($currentx+=19, $currenty, 20, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', false, 'C', $sum_def);
$pdf->print_box($currentx+=20, $currenty, 20, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', false, 'C', $sum_int);
$pdf->print_box($currentx+=20, $currenty, 20, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', false, 'C', $sum_cas);
$pdf->print_box($currentx+=20, $currenty, 21, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', false, 'C', $sum_mvp);
$pdf->print_box($currentx+=21, $currenty, 21, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', false, 'C', $sum_misc);
$pdf->print_box($currentx+=21, $currenty, 21, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', false, 'C', $sum_spp);
$pdf->print_box($currentx+=21, $currenty, 38, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', false, 'R', $pdf->Mf($sum_pvalue));

// Stars and Mercs part of roster
$currentx=MARGINX+6+6;
$currenty+=$h+2;

// Draw rounded rectangle around stars and mercs
// This rectangle has flexible height depending on how high player table is
$pdf->SetLineWidth(0.6);
$pdf->RoundedRect(MARGINX+6, $currenty, 792, (560-$currenty-130), 5, 'D');

$pdf->SetXY($currentx, $currenty+=2);
$h=14;
$pdf->SetFont('Tahoma', 'B', 8);
$pdf->Cell(170, $h, 'Induced Stars and Mercenaries', 0, 0, 'L', true, '');
$pdf->Cell(18, $h, 'MA', 0, 0, 'C', true, '');
$pdf->Cell(18, $h, 'ST', 0, 0, 'C', true, '');
$pdf->Cell(18, $h, 'AG', 0, 0, 'C', true, '');
$pdf->Cell(18, $h, 'PA', 0, 0, 'C', true, '');
$pdf->Cell(18, $h, 'AV', 0, 0, 'C', true, '');
$pdf->Cell(260, $h, 'Skills', 0, 0, 'L', true, '');
$pdf->Cell(86, $h, 'Special Rules', 0, 0, 'L', true, '');
//$pdf->Cell(23, $h, 'MNG', 1, 0, 'C', true, ''); // No MNG stars/mercs. They heal. ;-)
$pdf->Cell(19, $h, 'Cp', 0, 0, 'C', true, '');
$pdf->Cell(19, $h, 'Td', 0, 0, 'C', true, '');
$pdf->Cell(20, $h, 'Def', 0, 0, 'C', true, '');
$pdf->Cell(20, $h, 'Int', 0, 0, 'C', true, '');
$pdf->Cell(20, $h, 'Cas', 0, 0, 'C', true, '');
//$pdf->Cell(21, $h, 'MVP', 0, 0, 'C', true, '');
$pdf->Cell(21, $h, 'Misc', 0, 0, 'C', true, '');
$pdf->Cell(21, $h, 'SPP', 0, 0, 'C', true, '');
$pdf->Cell(38, $h, 'Value', 0, 0, 'R', true, '');
$currenty+=14;
$pdf->SetXY($currentx, $currenty);
$h=13;

// Printing chosen stars and mercs 
$pdf->SetFont('Tahoma', '', 8);
$merc = array(0=>'No Merc');
$i=0;
if ($_POST) {
  foreach ($DEA[$team->f_rname]["players"] as $p => $m) {
    $i++;
    array_push($merc, $m);
    $pos[$i] = $p;
  }
  $postvars = array(); # initialize.
  foreach ($_POST as $postkey => $postvalue) {
    if ($postkey == "Submit") continue;
    if ($postvalue == "0") continue;
    if ($postvalue == "0k") continue;
    if ($postvalue == "-No Extra Skill-") continue;
    $postvars[str_replace('_', ' ',$postkey)] = $postvalue;
  }

  $star_array_tmp[0]=0;
  $merc_array_tmp[0]=0;
  while (list($key, $val) = each($postvars)) {
    if (strpos($key,'Star') !== false) { // if POST key is StarX
        array_push($star_array_tmp,$val);
      continue;
    }
    elseif ($key == 'Mercbudget') { $ind_mercbudget = (int) str_replace('k','000',$val); continue; }
	elseif ($key == 'Mercdetails') { $ind_mercdetails = utf8_decode($val); continue; }
    elseif (strpos($key,'Merc') !== false) {
      $merc_nr = preg_replace("/[^0-9]/","", $key);
      $merc_array_tmp[$merc_nr] = $pos[$val];
      if (isset($postvars["Extra$merc_nr"])) $extra_array_tmp[$merc_nr] = $postvars["Extra$merc_nr"];
      else $extra_array_tmp[$merc_nr] = '';
      continue;
    }
    elseif ($key == 'Card') { $ind_card = (int) str_replace('k','000',$val); continue; }
    elseif ($key == 'Sidebet') { $ind_bet = (int) str_replace('k','000',$val); continue; }
    elseif ($key == 'Part-time Assistant Coaches') { $ind_sac = (int) $val; continue; }
    elseif ($key == 'Temp Agency Cheerleaders') { $ind_tem = (int) $val; continue; }
    elseif ($key == 'Bloodweiser Kegs') { $ind_babes = (int) $val; continue; }
    elseif ($key == 'Bribes') { $ind_bribes = (int) $val; continue; }
    elseif ($key == 'Special Plays') { $ind_splays = (int) $val; continue; }
    elseif ($key == 'Extra Team Training') { $ind_rr = (int) $val; continue; }
    elseif ($key == 'Halfling Master Chef') { $ind_chef = (int) $val; continue; }
    elseif ($key == 'Team Mascot') { $ind_mascot = (int) $val; continue; }
    elseif ($key == 'Medicinal Unguent') { $ind_medung = (int) $val; continue; }
    elseif ($key == 'Halfling Hot Pot') { $ind_hhp = (int) $val; continue; }
    elseif ($key == 'Halfling Hot-Pot') { $ind_hhpb = (int) $val; continue; }
    elseif ($key == 'Bottles of Heady Brew') { $ind_bhb = (int) $val; continue; }
    elseif ($key == 'Master of Ballistics') { $ind_masball = (int) $val; continue; }
    elseif ($key == 'Master of Ballistic') { $ind_masballb = (int) $val; continue; }
    elseif ($key == 'Dwarfen Runesmith') { $ind_dwrs = (int) $val; continue; }
    elseif ($key == 'Norscan Seer') { $ind_seer = (int) $val; continue; }
    elseif ($key == 'Ancient Artefact') { $ind_artef = (int) $val; continue; }
    elseif ($key == 'Healing Spite') { $ind_spite = (int) $val; continue; }
    elseif ($key == 'Cavorting Nurglings') { $ind_cvn = (int) $val; continue; }
    elseif ($key == 'Waaagh! Drummer') { $ind_wdrum = (int) $val; continue; }
    elseif ($key == 'Mortuary Assistant') { $ind_igor = (int) $val; continue; }
    elseif ($key == 'Wandering Apothecaries') { $ind_apo = (int) $val; continue; }
    elseif ($key == 'Hireling Sports-Wizard') { $ind_wiz = (int) $val; continue; }
    elseif ($key == 'Horatio X Schottenheim') { $ind_hxs = (int) $val; continue; }
    elseif ($key == 'Wandering Haemomancer') { $ind_haem = (int) $val; continue; }
    elseif ($key == 'Chaos Dwarf Sorcerer') { $ind_cdsor = (int) $val; continue; }
    elseif ($key == 'Fink Da Fixer') { $ind_fdf = (int) $val; continue; }
    elseif ($key == 'Papa Skullbones') { $ind_psb = (int) $val; continue; }
    elseif ($key == 'Galandril Silverwater') { $ind_gsw = (int) $val; continue; }
    elseif ($key == 'Krot Shockwhisker') { $ind_ksw = (int) $val; continue; }
    elseif ($key == 'Ayleen Andar') { $ind_ayl = (int) $val; continue; }
    elseif ($key == 'Kari Coldsteel') { $ind_kcs = (int) $val; continue; }
    elseif ($key == 'Professor Fronkelheim') { $ind_fronk = (int) $val; continue; }
    elseif ($key == 'Mungo Spinecracker') { $ind_mungo = (int) $val; continue; }
    elseif ($key == 'Schielund Scharlitan') { $ind_schi = (int) $val; continue; }
    elseif ($key == 'Chaos Sorcerer') { $ind_csr = (int) $val; continue; }
    elseif ($key == 'Weather Mage') { $ind_wxm = (int) $val; continue; }
    elseif ($key == 'Druchii Sports Sorceress') { $ind_dss = (int) $val; continue; }
    elseif ($key == 'Plague Doctor') { $ind_pdr = (int) $val; continue; }
    elseif ($key == 'Asur High Mage') { $ind_ahim = (int) $val; continue; }
    elseif ($key == 'Horticulturalist of Nurgle') { $ind_phn = (int) $val; continue; }
    elseif ($key == 'Joseph Bugman, Dwarf Master Brewer') { $ind_dmb = (int) $val; continue; }
    elseif ($key == 'Sports Necrotheurge') { $ind_spn = (int) $val; continue; }
    elseif ($key == 'Slann Mage-Priest') { $ind_smp = (int) $val; continue; }
    elseif ($key == 'Riotous Rookies') { $ind_rok = (int) $val; continue; }
    elseif ($key == 'Ogre Firebelly') { $ind_fbl = (int) $val; continue; }
    elseif ($key == 'Wicked Witch') { $ind_wwit = (int) $val; continue; }
    elseif ($key == 'Warlock Engineer') { $ind_weng = (int) $val; continue; }
    elseif ($key == 'Night Goblin Shaman') { $ind_ngsha = (int) $val; continue; }
    elseif ($key == 'Biased Referee') { $ind_bref = (int) $val; continue; }
    elseif ($key == 'Biased Referee: Ranulf \'Red\' Hokuli') { $ind_ranu = (int) $val; continue; }
    elseif ($key == 'Biased Referee: Thoron Korensson') { $ind_thor = (int) $val; continue; }
    elseif ($key == 'Biased Referee: Jorm the Ogre') { $ind_jorm = (int) $val; continue; }
    elseif ($key == 'Biased Referee: The Trundlefoot Triplets: Bungo, Filibert and Jeph') { $ind_trund = (int) $val; continue; }
    elseif ($key == 'College Wizard') { $ind_colwiz = (int) $val; continue; }
    elseif ($key == 'Desperate Measures') { $ind_desmes = (int) $val; continue; }
	//elseif ($key == 'Cards') { $ind_card = (int) str_replace('k','000',$val); continue; }
  }

  // Printing stars first
  if (isset($star_array_tmp[1])) {
    unset($star_array_tmp[0]);
    foreach ($star_array_tmp as $sid) {
      $s = new Star($sid);
      $s->setSkills(true);
      $ss = array('name'=>utf8_decode($s->name), 'ma'=>$s->ma, 'st'=>$s->st, 'ag'=>$s->ag, 'pa'=>$s->pa, 'av'=>$s->av, 'skills'=>utf8_decode($s->skills),'special'=>utf8_decode(specialsTrans($s->special)),
            'cp'=>$s->mv_cp, 'td'=>$s->mv_td, 'def'=>$s->mv_deflct, 'int'=>$s->mv_intcpt, 'cas'=>$s->mv_cas, 'mvp'=>$s->mv_mvp, 'misc'=>$s->mv_misc, 'spp'=>$s->mv_spp, 'value'=>$pdf->Mf($s->cost));
      $currenty+=$pdf->print_srow($ss, $currentx, $currenty, $h, $bgc, DEFLINECOLOR, 0.5, 8);
      $ind_cost += $s->cost;
      if (array_key_exists($sid, $starpairs)) {
          // Parent Star selected
          $sid = $starpairs[$sid];
          $s = new Star($sid);
          $s->setSkills(true);
          $ss = array('name'=>utf8_decode($s->name), 'ma'=>$s->ma, 'st'=>$s->st, 'ag'=>$s->ag, 'pa'=>$s->pa, 'av'=>$s->av, 'skills'=>utf8_decode($s->skills),'special'=>utf8_decode(specialsTrans($s->special)),
          'cp'=>$s->mv_cp, 'td'=>$s->mv_td, 'def'=>$s->mv_deflct, 'int'=>$s->mv_intcpt, 'cas'=>$s->mv_cas, //'mvp'=>$s->mv_mvp, 
		  'misc'=>$s->mv_misc, 'spp'=>$s->mv_spp, 'value'=>$pdf->Mf($s->cost));
          $currenty+=$pdf->print_srow($ss, $currentx, $currenty, $h, $bgc, DEFLINECOLOR, 0.5, 8);
      }
	  //$sp = array('special'=>$lng->getTrn($s->specialdesc.'desc', 'PDFroster'));
	  //$sp = $lng->getTrn($s->specialdesc.'desc', 'PDFroster');
	  //$currenty+=$pdf->print_srow($sp, $currentx, $currenty, $h, $bgc, DEFLINECOLOR, 0.5, 8);
	  $sp = utf8_decode($lng->getTrn($s->specialdesc.'desc', 'PDFroster'));
	  $currenty+=$pdf->print_sprow($sp, $currentx, $currenty, $h, $bgc, DEFLINECOLOR, 0.5, 8);
    }
  }
  // Then Mercs
  if (is_array($merc_array_tmp)) {
    unset($merc[0]);
    $r=$team->f_rname;
    $i=0;
    unset($merc_array_tmp[0]);
    foreach ($merc_array_tmp as $mpos) {
      $i++;
      $m['name'] = 'Mercenary '.$mpos;
      $m['ma'] = $DEA[$r]['players'][$mpos]['ma'];
      $m['st'] = $DEA[$r]['players'][$mpos]['st'];
      $m['ag'] = $DEA[$r]['players'][$mpos]['ag'];
      $m['pa'] = $DEA[$r]['players'][$mpos]['pa'];
      $m['av'] = $DEA[$r]['players'][$mpos]['av'];
      $m['skillarr'] = $DEA[$r]['players']["$mpos"]['def'];
      if (!in_array(99, $m['skillarr'])) array_unshift($m['skillarr'], 99);	// Adding Loner unless already in array
      $m['skills'] = implode(', ', skillsTrans($m['skillarr']));
	  if ($m['name'] == 'Mercenary ') {
		$m['cost'] = $DEA[$r]['players'][$mpos]['cost'];
	  } 
	  else {
		$m['cost'] = $DEA[$r]['players'][$mpos]['cost'] + MERC_EXTRA_COST;
	  }
      if (isset($postvars["Extra$i"])) {
        $m['cost'] += MERC_EXTRA_SKILL_COST;
        $m['extra'] = $postvars["Extra$i"];
        
        if ($m['skills'] == '') $m['skills'] = $m['extra']; 
        else $m['skills'] = $m['skills'] . ', ' . $m['extra'];
      }
      $ss = array('name'=>utf8_decode($m['name']), 'ma'=>$m['ma'], 'st'=>$m['st'], 'ag'=>$m['ag'], 'pa'=>$m['pa'], 'av'=>$m['av'], 'skills'=>utf8_decode($m['skills']), 'special'=>'Mercenary',
            'cp'=>' ', 'td'=>' ', 'def'=>' ', 'int'=>' ', 'cas'=>' ', //'mvp'=>' ', 
			'misc'=>' ', 'spp'=>' ', 'value'=>$pdf->Mf($m['cost']));
      $currenty+=$pdf->print_srow($ss, $currentx, $currenty, $h, $bgc, DEFLINECOLOR, 0.5, 8);
      $ind_cost += $m['cost'];
    }
	// New Mercs text box
	if (isset($ind_mercdetails) and strlen($ind_mercdetails)>0) { 
	$pdf->SetXY($currentx, $currenty+=16);
	$pdf->SetFont('Tahoma', 'B', 8);
	$pdf->Cell(170, $h, 'DZ Mercenaries Details:', 0, 0, 'L', true, '');
	$pdf->SetFont('Tahoma', '', 8);
	$pdf->Multicell(600, $h, $ind_mercdetails, 0, 'L', true, '');
	}
  }
}
$h = 13;

// Printing lower part of roster
$currentx = MARGINX;
$currenty = 435;
$ind_count = 0;

// Checking if Wandering Apothecary should be replaced with Mortuary Assistant
$r=$team->f_rname;
if (($r == 'Nurgle') || ($r == 'Tomb Kings') || ($r == 'Necromantic Horror') || ($r == 'Shambling Undead') || ($r == 'Sevens Nurgle') || ($r == 'Sevens Tomb Kings') || ($r == 'Sevens Necromantic Horror') || ($r == 'Sevens Shambling Undead')) {
  $apo_igor = 'Mortuary Assistant (0-1):';
  unset($inducements['Wandering Apothecaries']);
  if (isset($ind_igor)) { 
    $ind_apo_igor_cost = $ind_igor*$inducements['Mortuary Assistant']['reduced_cost'];
    // $ind_cost += $ind_igor*$ind_apo_igor_cost; CAN REMOVE
    $ind_apo_igor = $ind_igor;
  }
  else { $ind_apo_igor = '__'; $ind_apo_igor_cost = $inducements['Mortuary Assistant']['cost']; }
}
else {
  $apo_igor = 'Wandering Apothecaries (0-2):';
  unset($inducements['Mortuary Assistant']);
  if (isset($ind_apo)) { 
    $ind_apo_igor_cost = $inducements['Wandering Apothecaries']['cost'];
    // $ind_cost += $ind_apo*$ind_apo_igor_cost; CAN REMOVE
    $ind_apo_igor = $ind_apo;
  }
  else { $ind_apo_igor = '__'; $ind_apo_igor_cost = $inducements['Wandering Apothecaries']['cost']; }
}
// Checking game data if cheaper Chef for Halflings
$chef_cost = $inducements['Halfling Master Chef'][(($r == 'Halfling'||$r == 'Sevens Halfling') ? 'reduced_cost' : 'cost')];
// Checking game data if cheaper bribes for Goblin, Snotling, Black Orc and Underworld
$bribe_cost = $inducements['Bribes'][(($r == 'Goblin'||$r == 'Snotling'||$r == 'Black Orc'||$r == 'Underworld Denizens'||$r == 'Sevens Goblin'||$r == 'Sevens Snotling'||$r == 'Sevens Black Orc'||$r == 'Sevens Underworld Denizens') ? 'reduced_cost' : 'cost')];
// Checking game data if cheaper biased referee for Goblin, Snotling, Black Orc and Underworld
$biasedref_cost = $inducements['Biased Referee'][(($r == 'Goblin'||$r == 'Snotling'||$r == 'Black Orc'||$r == 'Underworld Denizens'||$r == 'Sevens Goblin'||$r == 'Sevens Snotling'||$r == 'Sevens Black Orc'||$r == 'Sevens Underworld Denizens') ? 'reduced_cost' : 'cost')];
// Checking game data if cheaper Biased Referee: Jorm the Ogre for Goblin, Snotling, Black Orc and Underworld
$bjorm_cost = $inducements['Biased Referee: Jorm the Ogre'][(($r == 'Goblin'||$r == 'Snotling'||$r == 'Black Orc'||$r == 'Underworld Denizens'||$r == 'Sevens Goblin'||$r == 'Sevens Snotling'||$r == 'Sevens Black Orc'||$r == 'Sevens Underworld Denizens') ? 'reduced_cost' : 'cost')];
// Checking game data if cheaper Trundlefoot Triplets for Halflings
$btrundle_cost = $inducements['Biased Referee: The Trundlefoot Triplets: Bungo, Filibert and Jeph'][(($r == 'Halfling'||$r == 'Sevens Halfling') ? 'reduced_cost' : 'cost')];
// Checking game data if Extra Training is for a sevens team
$ett_cost = $inducements['Extra Team Training'][(($teamformat == 'SV') ? 'reduced_cost' : 'cost')];

//calculate inducement costs
if (isset($ind_igor)) { $ind_cost += $ind_igor*$inducements['Mortuary Assistant']['reduced_cost']; $ind_count += 1; }
if (isset($ind_apo)) { $ind_cost += $ind_apo*$inducements['Wandering Apothecaries']['cost']; $ind_count += 1; }
if (isset($ind_sac)) { $ind_cost += $ind_sac*$inducements['Part-time Assistant Coaches']['cost']; $ind_count += 1; }
if (isset($ind_tem)) { $ind_cost += $ind_tem*$inducements['Temp Agency Cheerleaders']['cost']; $ind_count += 1; }
if (isset($ind_babes)) { $ind_cost += $ind_babes*$inducements['Bloodweiser Kegs']['cost']; $ind_count += 1; }
if (isset($ind_splays)) { $ind_cost += $ind_splays*$inducements['Special Plays']['cost']; $ind_count += 1; }
if (isset($ind_bribes)) { $ind_cost += $ind_bribes*$bribe_cost; $ind_count += 1; }
if (isset($ind_card)) { $ind_cost += $ind_card; $ind_count += 1; }
if (isset($ind_bet)) { $ind_cost += $ind_bet; $ind_count += 1; }
if (isset($ind_mercbudget)) { $ind_cost += $ind_mercbudget; $ind_count += 1; }
if (isset($ind_rr)) { $ind_cost += $ind_rr*$ett_cost; $ind_count += 1; }
if (isset($ind_chef)) { $ind_cost += $ind_chef*$chef_cost; $ind_count += 1;}
if (isset($ind_mascot)) { $ind_cost += $ind_mascot*$inducements['Team Mascot']['cost']; $ind_count += 1; }
if (isset($ind_medung)) { $ind_cost += $ind_medung*$inducements['Medicinal Unguent']['cost']; $ind_count += 1; }
if (isset($ind_hhp)) { $ind_cost += $ind_hhp*$inducements['Halfling Hot Pot']['reduced_cost']; $ind_count += 1; }
if (isset($ind_hhpb)) { $ind_cost += $ind_hhpb*$inducements['Halfling Hot-Pot']['reduced_cost']; $ind_count += 1; }
if (isset($ind_bhb)) { $ind_cost += $ind_bhb*$inducements['Bottles of Heady Brew']['reduced_cost']; $ind_count += 1; }
if (isset($ind_masball)) { $ind_cost += $ind_masball*$inducements['Master of Ballistics']['reduced_cost']; $ind_count += 1; }
if (isset($ind_masballb)) { $ind_cost += $ind_masballb*$inducements['Master of Ballistic']['reduced_cost']; $ind_count += 1; }
if (isset($ind_dwrs)) { $ind_cost += $ind_dwrs*$inducements['Dwarfen Runesmith']['reduced_cost']; $ind_count += 1; }
if (isset($ind_seer)) { $ind_cost += $ind_seer*$inducements['Norscan Seer']['reduced_cost']; $ind_count += 1; }
if (isset($ind_artef)) { $ind_cost += $ind_artef*$inducements['Ancient Artefact']['reduced_cost']; $ind_count += 1; }
if (isset($ind_artef)) { $ind_cost += $ind_spitef*$inducements['Healing Spite']['reduced_cost']; $ind_count += 1; }
if (isset($ind_cvn)) { $ind_cost += $ind_cvn*$inducements['Cavorting Nurglings']['reduced_cost']; $ind_count += 1; }
if (isset($ind_wdrum)) { $ind_cost += $ind_wdrum*$inducements['Waaagh! Drummer']['reduced_cost']; $ind_count += 1; }
if (isset($ind_wiz)) { $ind_cost += $ind_wiz*$inducements['Hireling Sports-Wizard']['reduced_cost']; $ind_count += 1; }
if (isset($ind_hxs)) { $ind_cost += $ind_hxs*$inducements['Horatio X Schottenheim']['cost']; $ind_count += 1; }
if (isset($ind_haem)) { $ind_cost += $ind_haem*$inducements['Wandering Haemomancer']['cost']; $ind_count += 1; }
if (isset($ind_cdsor)) { $ind_cost += $ind_cdsor*$inducements['Chaos Dwarf Sorcerer']['cost']; $ind_count += 1; }
if (isset($ind_fdf)) { $ind_cost += $ind_fdf*$inducements['Fink Da Fixer']['reduced_cost']; $ind_count += 1; }
if (isset($ind_psb)) { $ind_cost += $ind_psb*$inducements['Papa Skullbones']['reduced_cost']; $ind_count += 1; }
if (isset($ind_gsw)) { $ind_cost += $ind_gsw*$inducements['Galandril Silverwater']['reduced_cost']; $ind_count += 1; }
if (isset($ind_ksw)) { $ind_cost += $ind_ksw*$inducements['Krot Shockwhisker']['reduced_cost']; $ind_count += 1; }
if (isset($ind_kcs)) { $ind_cost += $ind_kcs*$inducements['Kari Coldsteel']['reduced_cost']; $ind_count += 1; }
if (isset($ind_ayl)) { $ind_cost += $ind_ayl*$inducements['Ayleen Andar']['cost']; $ind_count += 1; }
if (isset($ind_fronk)) { $ind_cost += $ind_fronk*$inducements['Professor Fronkelheim']['reduced_cost']; $ind_count += 1; }
if (isset($ind_mungo)) { $ind_cost += $ind_mungo*$inducements['Mungo Spinecracker']['reduced_cost']; $ind_count += 1; }
if (isset($ind_schi)) { $ind_cost += $ind_schi*$inducements['Schielund Scharlitan']['cost']; $ind_count += 1; }
if (isset($ind_csr)) { $ind_cost += $ind_csr*$inducements['Chaos Sorcerer']['reduced_cost']; $ind_count += 1; }
if (isset($ind_wxm)) { $ind_cost += $ind_wxm*$inducements['Weather Mage']['cost']; $ind_count += 1; }
if (isset($ind_dss)) { $ind_cost += $ind_dss*$inducements['Druchii Sports Sorceress']['reduced_cost']; $ind_count += 1; }
if (isset($ind_pdr)) { $ind_cost += $ind_pdr*$inducements['Plague Doctor']['reduced_cost']; $ind_count += 1; }
if (isset($ind_ahim)) { $ind_cost += $ind_ahim*$inducements['Asur High Mage']['reduced_cost']; $ind_count += 1; }
if (isset($ind_phn)) { $ind_cost += $ind_phn*$inducements['Horticulturalist of Nurgle']['reduced_cost']; $ind_count += 1; }
if (isset($ind_dmb)) { $ind_cost += $ind_dmb*$inducements['Joseph Bugman, Dwarf Master Brewer']['cost']; $ind_count += 1; }
if (isset($ind_spn)) { $ind_cost += $ind_spn*$inducements['Sports Necrotheurge']['reduced_cost']; $ind_count += 1; }
if (isset($ind_smp)) { $ind_cost += $ind_smp*$inducements['Slann Mage-Priest']['reduced_cost']; $ind_count += 1; }
if (isset($ind_rok)) { $ind_cost += $ind_rok*$inducements['Riotous Rookies']['reduced_cost']; $ind_count += 1; }
if (isset($ind_fbl)) { $ind_cost += $ind_fbl*$inducements['Ogre Firebelly']['reduced_cost']; $ind_count += 1; }
if (isset($ind_wwit)) { $ind_cost += $ind_wwit*$inducements['Wicked Witch']['reduced_cost']; $ind_count += 1; }
if (isset($ind_weng)) { $ind_cost += $ind_weng*$inducements['Warlock Engineer']['reduced_cost']; $ind_count += 1; }
if (isset($ind_ngsha)) { $ind_cost += $ind_ngsha*$inducements['Night Goblin Shaman']['reduced_cost']; $ind_count += 1; }
if (isset($ind_bref)) { $ind_cost += $ind_bref*$biasedref_cost; $ind_count += 1;}
if (isset($ind_ranu)) { $ind_cost += $ind_ranu*$inducements['Biased Referee: Ranulf \'Red\' Hokuli']['reduced_cost']; $ind_count += 1; }
if (isset($ind_thor)) { $ind_cost += $ind_thor*$inducements['Biased Referee: Thoron Korensson']['reduced_cost']; $ind_count += 1; }
if (isset($ind_jorm)) { $ind_cost += $ind_jorm*$bjorm_cost; $ind_count += 1;}
if (isset($ind_trund)) { $ind_cost += $ind_trund*$btrundle_cost; $ind_count += 1;}
if (isset($ind_colwiz)) { $ind_cost += $ind_colwiz*$inducements['College Wizard']['reduced_cost']; $ind_count += 1; }
if (isset($ind_desmes)) { $ind_cost += $ind_desmes*$inducements['Desperate Measures']['reduced_cost']; $ind_count += 1; }

//print_box($x, $y, $w, $h, $bgcolor='#FFFFFF', $bordercolor='#000000', $linewidth=1, $borderstyle, $fontsize, $font, $bold=false, $align, $text)
$h = 13; // Height of cells

if ($ind_count > 0 ) {
  $pdf->print_box($currentx, $currenty, 170, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', true, 'R', 'Inducements ');
  $pdf->print_box(($currentx += 170), $currenty, 120, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', false, 'L', '(for next match)');
}
  $pdf->print_box(($currentx = 630), $currenty, 40, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', true, 'R', 'Team Goods'); // 156 to margin

$currentx = MARGINX;
$currenty = 435;
$ind_display_counter = 0;

  // print_inducements($x, $y, $h, $bgcol, $linecol, $fontsize, $ind_name, $ind_amount, $ind_value)
  if (isset($ind_babes)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Bloodweiser Kegs (0-2):', $ind_babes, $pdf->Mf($inducements['Bloodweiser Kegs']['cost']));}
  if (isset($ind_babes)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;} 
  if (isset($ind_bribes)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Bribes (0-3):', $ind_bribes, $pdf->Mf($bribe_cost));}
  if (isset($ind_bribes)) { $ind_display_counter += 1;}
  if (isset($ind_splays)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Special Plays (0-5):', $ind_splays, $pdf->Mf($inducements['Special Plays']['cost']));}
  if (isset($ind_splays)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;} 
  if (isset($ind_rr)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Extra Team Training (0-8):', $ind_rr, $pdf->Mf($ett_cost));}
  if (isset($ind_rr)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;} 
  if (isset($ind_sac)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Part-time Assistant Coaches (0-3):', $ind_sac, $pdf->Mf($inducements['Part-time Assistant Coaches']['cost']));}
  if (isset($ind_sac)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_tem)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Temp Agency Cheerleaders (0-4):', $ind_tem, $pdf->Mf($inducements['Temp Agency Cheerleaders']['cost']));}
  if (isset($ind_tem)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_chef)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Halfling Master Chef (0-1):', $ind_chef, $pdf->Mf($chef_cost));}
  if (isset($ind_chef)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_igor) or isset($ind_apo)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, $apo_igor, $ind_apo_igor, $pdf->Mf($ind_apo_igor_cost));}
  if (isset($ind_igor) or isset($ind_apo)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_mascot)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Team Mascot (0-1):', $ind_mascot, $pdf->Mf($inducements['Team Mascot']['reduced_cost']));}
  if (isset($ind_mascot)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_hhp)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Halfling Hot Pot (0-1):', $ind_hhp, $pdf->Mf($inducements['Halfling Hot Pot']['reduced_cost']));}
  if (isset($ind_hhp)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_hhpb)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Halfling Hot-Pot (0-1):', $ind_hhpb, $pdf->Mf($inducements['Halfling Hot-Pot']['reduced_cost']));}
  if (isset($ind_hhpb)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_bhb)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Bottles of Heady Brew (0-3):', $ind_bhb, $pdf->Mf($inducements['Bottles of Heady Brew']['reduced_cost']));}
  if (isset($ind_bhb)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_medung)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Medicinal Unguent (0-1):', $ind_medung, $pdf->Mf($inducements['Medicinal Unguent']['reduced_cost']));}
  if (isset($ind_medung)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_masball)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Master of Ballistics (0-1):', $ind_masball, $pdf->Mf($inducements['Master of Ballistics']['reduced_cost']));}
  if (isset($ind_masball)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_masballb)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8,'Master of Ballistics (0-1):', $ind_masballb, $pdf->Mf($inducements['Master of Ballistic']['reduced_cost']));}
  if (isset($ind_masballb)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_dwrs)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Dwarfen Runesmith (0-1):', $ind_dwrs, $pdf->Mf($inducements['Dwarfen Runesmith']['reduced_cost']));}
  if (isset($ind_dwrs)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_seer)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Norscan Seer (0-1):', $ind_seer, $pdf->Mf($inducements['Norscan Seer']['cost']));}
  if (isset($ind_seer)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_artef)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Ancient Artefact (0-1):', $ind_artef, $pdf->Mf($inducements['Ancient Artefact']['cost']));}
  if (isset($ind_artef)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_spite)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Healing Spite (0-1):', $ind_artef, $pdf->Mf($inducements['Healing Spite']['cost']));}
  if (isset($ind_spite)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_cvn)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Cavorting Nurglings (0-3):', $ind_cvn, $pdf->Mf($inducements['Cavorting Nurglings']['reduced_cost']));}
  if (isset($ind_cvn)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_wdrum)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Waaagh! Drummer (0-1):', $ind_wdrum, $pdf->Mf($inducements['Waaagh! Drummer']['reduced_cost']));}
  if (isset($ind_wdrum)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_wiz)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Hireling Sports-Wizard (0-1):', $ind_wiz, $pdf->Mf($inducements['Hireling Sports-Wizard']['cost']));}
  if (isset($ind_wiz)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_hxs)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Horatio X. Schottenheim (0-1):', $ind_hxs, $pdf->Mf($inducements['Horatio X Schottenheim']['cost']));}
  if (isset($ind_hxs)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_haem)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Wandering Haemomancer (0-1):', $ind_haem, $pdf->Mf($inducements['Wandering Haemomancer']['cost']));}
  if (isset($ind_haem)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_cdsor)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Chaos Dwarf Sorcerer (0-1):', $ind_cdsor, $pdf->Mf($inducements['Chaos Dwarf Sorcerer']['cost']));}
  if (isset($ind_cdsor)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_fdf)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Fink Da Fixer (0-1):', $ind_fdf, $pdf->Mf($inducements['Fink Da Fixer']['reduced_cost']));}
  if (isset($ind_fdf)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_psb)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Papa Skullbones (0-1):', $ind_rr, $pdf->Mf($inducements['Papa Skullbones']['reduced_cost']));}
  if (isset($ind_psb)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_gsw)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Galandril Silverwater (0-1):', $ind_psb, $pdf->Mf($inducements['Galandril Silverwater']['reduced_cost']));}
  if (isset($ind_gsw)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_ksw)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Krot Shockwhisker (0-1):', $ind_ksw, $pdf->Mf($inducements['Krot Shockwhisker']['reduced_cost']));}
  if (isset($ind_ksw)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_ayl)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Ayleen Andar (0-1):', $ind_ayl, $pdf->Mf($inducements['Ayleen Andar']['reduced_cost']));}
  if (isset($ind_ayl)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_kcs)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Kari Coldsteel (0-1):', $ind_kcs, $pdf->Mf($inducements['Kari Coldsteel']['reduced_cost']));}
  if (isset($ind_kcs)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_fronk)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Professor Fronkelheim (0-1):', $ind_fronk, $pdf->Mf($inducements['Professor Fronkelheim']['reduced_cost']));}
  if (isset($ind_fronk)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_mungo)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Mungo Spinecracker (0-1):', $ind_mungo, $pdf->Mf($inducements['Mungo Spinecracker']['reduced_cost']));}
  if (isset($ind_mungo)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_schi)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Schielund Scharlitan (0-1):', $ind_schi, $pdf->Mf($inducements['Schielund Scharlitan']['reduced_cost']));}
  if (isset($ind_schi)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_csr)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Chaos Sorcerer (0-1):', $ind_csr, $pdf->Mf($inducements['Chaos Sorcerer']['reduced_cost']));}
  if (isset($ind_csr)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_wxm)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Weather Mage (0-1):', $ind_wxm, $pdf->Mf($inducements['Weather Mage']['cost']));}
  if (isset($ind_wxm)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_dss)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Druchii Sports Sorceress (0-1):', $ind_dss, $pdf->Mf($inducements['Druchii Sports Sorceress']['reduced_cost']));}
  if (isset($ind_dss)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_pdr)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Plague Doctor (0-1):', $ind_pdr, $pdf->Mf($inducements['Plague Doctor']['reduced_cost']));}
  if (isset($ind_pdr)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_ahim)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Asur High Mage (0-1):', $ind_ahim, $pdf->Mf($inducements['Asur High Mage']['reduced_cost']));}
  if (isset($ind_ahim)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_phn)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Horticulturalist of Nurgle (0-1):', $ind_phn, $pdf->Mf($inducements['Horticulturalist of Nurgle']['reduced_cost']));}
  if (isset($ind_phn)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_dmb)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Joseph Bugman, Dwarf Master Brewer (0-1):', $ind_dmb, $pdf->Mf($inducements['Joseph Bugman, Dwarf Master Brewer']['reduced_cost']));}
  if (isset($ind_dmb)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_spn)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Sports Necrotheurge (0-1):', $ind_spn, $pdf->Mf($inducements['Sports Necrotheurge']['reduced_cost']));}
  if (isset($ind_spn)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_smp)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Slann Mage-Priest (0-1):', $ind_smp, $pdf->Mf($inducements['Slann Mage-Priest']['reduced_cost']));}
  if (isset($ind_smp)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_rok)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Riotous Rookies (0-1):', $ind_rok, $pdf->Mf($inducements['Riotous Rookies']['reduced_cost']));}
  if (isset($ind_rok)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_fbl)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Ogre Firebelly (0-1):', $ind_fbl, $pdf->Mf($inducements['Ogre Firebelly']['reduced_cost']));}
  if (isset($ind_fbl)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_wwit)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Wicked Witch (0-1):', $ind_wwit, $pdf->Mf($inducements['Wicked Witch']['reduced_cost']));}
  if (isset($ind_wwit)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_weng)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Warlock Engineer (0-1):', $ind_weng, $pdf->Mf($inducements['Warlock Engineer']['reduced_cost']));}
  if (isset($ind_weng)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_ngsha)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Night Goblin Shaman (0-1):', $ind_ngsha, $pdf->Mf($inducements['Night Goblin Shaman']['reduced_cost']));}
  if (isset($ind_ngsha)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_bref)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Biased Referee (0-1):', $ind_bref, $pdf->Mf($biasedref_cost));}
  if (isset($ind_bref)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_ranu)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Biased Referee: Ranulf \'Red\' Hokuli (0-1):', $ind_ranu, $pdf->Mf($inducements['Biased Referee: Ranulf \'Red\' Hokuli']['reduced_cost']));}
  if (isset($ind_ranu)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_thor)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Biased Referee: Thoron Korensson (0-1):', $ind_thor, $pdf->Mf($inducements['Biased Referee: Thoron Korensson']['reduced_cost']));}
  if (isset($ind_thor)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_jorm)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Biased Referee: Jorm the Ogre (0-1):', $ind_jorm, $pdf->Mf($bjorm_cost));}
  if (isset($ind_jorm)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_trund)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Biased Referee: The Trundlefoot Triplets (0-1):', $ind_trund, $pdf->Mf($btrundle_cost));}
  if (isset($ind_trund)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_colwiz)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'College Wizard (0-1):', $ind_colwiz, $pdf->Mf($inducements['College Wizard']['reduced_cost']));}
  if (isset($ind_colwiz)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_desmes)) { $pdf->print_inducements($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Desperate Measures (0-5):', $ind_desmes, $pdf->Mf($inducements['Desperate Measures']['reduced_cost']));}
  if (isset($ind_desmes)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_card)) { $pdf->print_inducements(MARGINX, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Card budget:', ' ', $pdf->Mf($ind_card));}
  if (isset($ind_card)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_bet)) { $pdf->print_inducements(MARGINX, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'Side Bet:', ' ', $pdf->Mf($ind_bet));}
  if (isset($ind_bet)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}
  if (isset($ind_mercbudget)) { $pdf->print_inducements(MARGINX, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 8, 'DZ Mercenaries Budget:', ' ', $pdf->Mf($ind_mercbudget));}
  if (isset($ind_mercbudget)) { $ind_display_counter += 1;}
  if ($ind_display_counter == 9 ) {$currentx += 250; $currenty = 435;}

$currenty=435;
$currentx=630;
// print_team_goods($x, $y, $h, $bgcol, $linecol, $perm_name, $perm_nr, $perm_value, $perm_total_value, $bold=false)
$pdf->print_team_goods($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 'Rerolls:', ($team->rerolls), $pdf->Mf($rerollcost), $pdf->Mf($team->rerolls * $rerollcost), false);
//$pdf->print_team_goods($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 'Dedicated Fans:', ($team->rg_ff), $pdf->Mf($rules['cost_fan_factor']), $pdf->Mf($team->rg_ff * $rules['cost_fan_factor']), false);
if ($teamformat <> 'SV') {
	$pdf->print_team_goods($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 'Assistant Coaches:', ($team->ass_coaches), $pdf->Mf($rules['cost_ass_coaches']), $pdf->Mf($team->ass_coaches * $rules['cost_ass_coaches']), false);
	$pdf->print_team_goods($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 'Cheerleaders:', ($team->cheerleaders), $pdf->Mf($rules['cost_cheerleaders']), $pdf->Mf($team->cheerleaders * $rules['cost_cheerleaders']), false);
	if ($r == 'Undead' || $r == 'Necromantic') // Swap Apothecary for Necromancer
	  $pdf->print_team_goods($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 'Necromancer:', 1, 0, 0, false);
	elseif ($r == 'Nurgle' || $r == 'Khemri')  // Remove Apothecary
	  $currenty+=$h;
	else  // Normal case
	  $pdf->print_team_goods($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 'Apothecary:', ($team->apothecary), $pdf->Mf($rules['cost_apothecary']), $pdf->Mf($team->apothecary * $rules['cost_apothecary']), false);
} else {
	$pdf->print_team_goods($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 'Assistant Coaches:', ($team->ass_coaches), $pdf->Mf($rules['cost_ass_coaches_sevens']), $pdf->Mf($team->ass_coaches * $rules['cost_ass_coaches_sevens']), false);
	$pdf->print_team_goods($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 'Cheerleaders:', ($team->cheerleaders), $pdf->Mf($rules['cost_cheerleaders_sevens']), $pdf->Mf($team->cheerleaders * $rules['cost_cheerleaders_sevens']), false);
	if ($r == 'Undead' || $r == 'Necromantic') // Swap Apothecary for Necromancer
	  $pdf->print_team_goods($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 'Necromancer:', 1, 0, 0, false);
	elseif ($r == 'Nurgle' || $r == 'Khemri')  // Remove Apothecary
	  $currenty+=$h;
	else  // Normal case
	  $pdf->print_team_goods($currentx, ($currenty+=$h), $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 'Apothecary:', ($team->apothecary), $pdf->Mf($rules['cost_apothecary_sevens']), $pdf->Mf($team->apothecary * $rules['cost_apothecary_sevens']), false);	
}
//dedicated fans
$pdf->print_box($currentx+=70, ($currenty+=$h), 40, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', false, 'R', 'Dedicated Fans:' );
$pdf->print_box($currentx+=40, ($currenty), 65, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', false, 'R', $pdf->Mf($team->rg_ff));
//treasury
$pdf->print_box($currentx-=40, ($currenty+=$h), 40, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', false, 'R', 'Treasury:' );
$pdf->print_box($currentx+=40, ($currenty), 65, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', false, 'R', $pdf->Mf($team->treasury));

// Team Value, Inducements Value, Match Value
$h=13;
$pdf->print_box($currentx-=40, ($currenty+=$h), 40, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', true, 'R', 'Team Value (incl MNGs/RET value):');
$pdf->print_box($currentx+=40, ($currenty), 65, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', true, 'R', $pdf->Mf($team->value + $sum_p_missing_value));
$pdf->print_box($currentx-=40, ($currenty+=$h), 40, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', true, 'R', 'Induced Value:');
$pdf->print_box($currentx+=40, ($currenty), 65, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', true, 'R', $pdf->Mf($ind_cost));
$pdf->print_box($currentx-=40, ($currenty+=$h), 40, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', true, 'R', 'Current Team Value:');
$pdf->print_box($currentx+=40, ($currenty), 65, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', true, 'R', $pdf->Mf($team->value + $ind_cost));

// Drawing a rectangle around inducements
$pdf->SetLineWidth(0.6);
$pdf->RoundedRect(MARGINX+6, 435, 792, 130, 5, 'D');

global $settings;
if ($settings['enable_pdf_logos']) {
    // Team logo
    // Comment out if you dont have GD 2.x installed, or if you dont want the logo in roster.
    // Not tested with anything except PNG images that comes with OBBLM.
    if ($ind_count < 10) {
    $img = new ImageSubSys(IMGTYPE_TEAMLOGO,$team->team_id);
    $pdf->Image($img->getPath(),375,462,100,100,'','',false,0);
    }
    // OBBLM text lower left corner as a pic - removed due issues with it appearing multiple places
    // $pdf->Image('modules/pdf/OBBLM_pdf_logo.png', MARGINX+12, 534, 60, 28, '', '', false, 0);
}
//display team sponsor and stadium if populated
$sponsortxt = $team->getSponsor();
$stadiumtxt = $team->getStadium();
if (strlen($sponsortxt) >0) {
$pdf->print_box($currentx=384, 436, 30, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', true, 'R', 'Sponsor:');
$pdf->print_box($currentx+=29, 436, 100, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', false, 'L', $sponsortxt);
}
if (strlen($stadiumtxt) >0) {
$pdf->print_box($currentx=384, 448, 30, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', true, 'R', 'Stadium:');
$pdf->print_box($currentx+=29, 448, 100, $h, COLOR_ROSTER_NORMAL, DEFLINECOLOR, 0, 0, 8, 'Tahoma', false, 'L', $stadiumtxt);
}

// Color legends
$pdf->SetFont('Tahoma', '', 8);
$currentx = MARGINX+16;
$currenty = 572;
$pdf->SetFillColorBB($pdf->hex2cmyk(COLOR_ROSTER_MNG));
$pdf->SetXY($currentx, $currenty);
$pdf->Rect($currentx, $currenty, 5, 5, 'DF');
$pdf->SetXY($currentx+=5, $currenty-=1);
$pdf->Cell(20, 8, 'MNG', 0, 0, 'L', false);
$pdf->SetFillColorBB($pdf->hex2cmyk(COLOR_ROSTER_JOURNEY));
$pdf->Rect($currentx+=22+5, $currenty+=1, 5, 5, 'DF');
$pdf->SetXY($currentx+=5, $currenty-=1);
$pdf->Cell(45, 8, 'Journeyman', 0, 0, 'L', false);
$pdf->SetFillColorBB($pdf->hex2cmyk(COLOR_ROSTER_JOURNEY_USED));
$pdf->Rect($currentx+=47+5, $currenty+=1, 5, 5, 'DF');
$pdf->SetXY($currentx+=5, $currenty-=1);
$pdf->Cell(45, 8, 'Used journeyman', 0, 0, 'L', false);
$pdf->SetFillColorBB($pdf->hex2cmyk(COLOR_ROSTER_NEWSKILL));
$pdf->Rect($currentx+=67+5, $currenty+=1, 5, 5, 'DF');
$pdf->SetXY($currentx+=5, $currenty-=1);
$pdf->Cell(70, 8, 'New skill available', 0, 0, 'L', false);
$pdf->SetFillColorBB($pdf->hex2cmyk(COLOR_ROSTER_CHR_GTP1));
$pdf->Rect($currentx+=70+5, $currenty+=1, 5, 5, 'DF');
$pdf->SetXY($currentx+=5, $currenty-=1);
$pdf->Cell(50, 8, 'Stat upgrade', 0, 0, 'L', false);
$pdf->SetFillColorBB($pdf->hex2cmyk(COLOR_ROSTER_CHR_LTM1));
$pdf->Rect($currentx+=50+5, $currenty+=1, 5, 5, 'DF');
$pdf->SetXY($currentx+=5, $currenty-=1);
$pdf->Cell(50, 8, 'Stat downgrade', 0, 0, 'L', false);
$pdf->SetFillColorBB($pdf->hex2cmyk(COLOR_ROSTER_RETIRED));
$pdf->Rect($currentx+=60+5, $currenty+=1, 5, 5, 'DF');
$pdf->SetXY($currentx+=5, $currenty-=1);
$pdf->Cell(50, 8, 'Retired', 0, 0, 'L', false);

$pdf->SetFont('Tahoma', '', 7);
$pdf->SetFillColorBB($pdf->hex2cmyk(COLOR_ROSTER_NORMAL));
$pdf->SetXY($currentx+160, $currenty+1);        
//$donate = "";
//$pdf->Multicell(300, 8, $donate, 0, 0, 'L', false);

// Output the PDF document
$pdf->Output(utf8_decode($team->name) . date(' Y-m-d') . '.pdf', 'I');

}
}
