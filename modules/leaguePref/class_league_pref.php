<?php
/*
 *  Copyright (c) Ian Williams <email is protected> 2011. All Rights Reserved.
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
    This file is a template for modules.

    Note: the two terms functions and methods are used loosely in this documentation. They mean the same thing.

    How to USE a module once it's written:
    ---------------------------------
        Firstly you will need to register it in the modules/modsheader.php file.
        The existing entries and comments should be enough to figure out how to do that.
        Now, let's say that your module (as an example) prints some kind of statistics containing box.
        What should you then write on the respective page in order to print the box?

            if (Module::isRegistered('MyModule')) {
                Module::run('MyModule', array());
            }

        The second argument passed to Module::run() is the $argv array passed on to main() (see below).
*/
/*
	Using the league prefences league and global administrators can change the touranments displayed on the homepage dynamically.
	Within the settings_xxx.php file the ID for each box should be set to 'prime' or 'second' to pick up the tournaments selected as primary and secondary.
    In addition the primary tournament will be selected by default on the league tables page.
*/

class LeaguePref implements ModuleInterface
{

/***************
 * ModuleInterface requirements. These functions MUST be defined.
 ***************/

/*
 *  Basically you are free to design your main() function as you wish.
 *  If you are writing a simple module that merely echoes out some data, you may want to have main() doing all the work (i.e. place all your code here).
 *  If you on the other hand are writing a module which is divided into several routines, you may (and should) use the main() as a wrapper for calling the appropriate code.
 *
 *  The below main() example illustrates how main() COULD work as a wrapper, when the subdivision of code is done into functions in this SAME class.
 */
public static function main($argv) # argv = argument vector (array).
{
    /*
        Let $argv[0] be the name of the function we wish main() to call.
        Let the remaining contents of $argv be the arguments of that function, in the correct order.

        Please note only static functions are callable through main().
    */

    $func = array_shift($argv);
    return call_user_func_array(array(__CLASS__, $func), $argv);
}

/*
 *  This function returns information about the module and its author.
 */
public static function getModuleAttributes()
{
    return array(
        'author'     => 'DoubleSkulls',
        'moduleName' => 'LeaguePref',
        'date'       => '2011', # For example '2009'.
        'setCanvas'  => true, # If true, whenever your main() is run through Module::run() your code's output will be "sandwiched" into the standard HTML frame.
    );
}

/*
 *  This function returns the MySQL table definitions for the tables required by the module. If no tables are used array() should be returned.
 */
public static function getModuleTables()
{
    global $CT_cols;

	return array(
        # Table name => column definitions
        'league_prefs' => array(
			'f_lid'       => $CT_cols[T_NODE_LEAGUE].' NOT NULL PRIMARY KEY ',
	        'prime_tid'   => $CT_cols[T_NODE_TOURNAMENT],
	        'second_tid'  => $CT_cols[T_NODE_TOURNAMENT],
	        'league_name' => 'VARCHAR(128) ',
	        'forum_url'   => 'VARCHAR(256) ',
	        'welcome'     => 'TEXT ',
	        'rules'       => 'TEXT ',
        ),
    );
}

public static function getModuleUpgradeSQL()
{
    global $CT_cols;
    return array(
        '096-097' => array(
            'CREATE TABLE IF NOT EXISTS league_prefs
            (
                f_lid       ' . $CT_cols[T_NODE_LEAGUE] . ' NOT NULL PRIMARY KEY,
                prime_tid   ' . $CT_cols[T_NODE_TOURNAMENT] . ',
                second_tid  ' . $CT_cols[T_NODE_DIVISION] . ',
                league_name VARCHAR(128),
                forum_url   VARCHAR(256),
                welcome     TEXT,
                rules       TEXT
            )'            
        ),
    );
}

public static function triggerHandler($type, $argv){
    global $settings;
    
    switch ($type) {
        case ( $type == T_TRIGGER_BEFORE_PAGE_RENDER ):
            if(isset($_POST['core_theme_id']))
                $settings['stylesheet'] = $_POST['core_theme_id'];
        
            break;
    }
}

/***************
 * OPTIONAL subdivision of module code into class methods.
 *
 * These work as in ordinary classes with the exception that you really should (but are strictly required to) only interact with the class through static methods.
 ***************/

/***************
 * Properties
 ***************/
/*public $lid      = 0;*/
public $l_name    = '';
public $p_tour   = 0;
public $s_tour = 0;
public $league_name = '';
public $forum_url = '';
public $welcome = '';
public $rules = '';
public $existing = false;
public $theme_css = '';
public $core_theme_id = 0;
public $tv = 0;
public $tv_sevens = 0;
public $language = 'en-GB';
public $amazon = 0;
public $chorf = 0;
public $helf = 0;
public $vamps = 0;
public $khemri = 0;
public $slann = 0;
public $dungeon = 0;
public $sevens = 0;
public $randomskillrolls = 0;
public $randomskillmanualentry = 0;
public $megastars = 0;
public $major_win_tds = 0;
public $major_win_pts = 0;
public $clean_sheet_pts = 0;
public $major_beat_cas = 0;
public $major_beat_pts = 0;

function __construct($lid, $name, $ptid, $stid, $league_name, $forum_url, $welcome, $rules, $existing, $theme_css, $core_theme_id, $tv, $tv_sevens, $language, $amazon, $chorf, $helf, $vamps, $khemri, $slann, $dungeon,  $sevens, $randomskillrolls, $randomskillmanualentry, $megastars, $major_win_tds, $major_win_pts, $clean_sheet_pts, $major_beat_cas, $major_beat_pts) {
	global $settings;
	$this->lid = $lid;
	$this->l_name = $name;
	$this->p_tour = $ptid;
	$this->s_tour = $stid;
	$this->league_name = isset($league_name) ? $league_name: $settings['league_name'];
	$this->forum_url = isset($forum_url) ? $forum_url: $settings['forum_url'];
	$this->welcome = isset($welcome) ? $welcome: $settings['welcome'];
	$this->rules = isset($rules) ? $rules: $settings['rules'];
	$this->existing = $existing;
    $this->theme_css = $theme_css;
    $this->core_theme_id = $core_theme_id;
    $this->tv = $tv;
    $this->tv_sevens = $tv_sevens;
    $this->language = $language;
    $this->amazon = $amazon;
    $this->chorf = $chorf;
    $this->helf = $helf;
    $this->vamps = $vamps;
    $this->khemri = $khemri;
    $this->slann = $slann;
    $this->dungeon = $dungeon;
    $this->sevens = $sevens;
    $this->randomskillrolls = $randomskillrolls;
    $this->randomskillmanualentry = $randomskillmanualentry;
    $this->megastars = $megastars;
    $this->major_win_tds = $major_win_tds;
    $this->major_win_pts = $major_win_pts;
    $this->clean_sheet_pts = $clean_sheet_pts;
    $this->major_beat_cas = $major_beat_cas;
    $this->major_beat_pts = $major_beat_pts;
}

/* Gets the preferences for the current league */
public static function getLeaguePreferences() {
	global $settings, $coach, $leagues, $rules;

    list($sel_lid, $HTML_LeagueSelector) = HTMLOUT::simpleLeagueSelector();
    echo $HTML_LeagueSelector;

	$result = mysql_query("SELECT lid, name, prime_tid, second_tid, league_name, forum_url, welcome, rules FROM leagues LEFT OUTER JOIN league_prefs on lid=f_lid WHERE lid=$sel_lid");

    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            $theme_css = FileManager::readFile(FileManager::getCssDirectoryName() . "/league_override_$sel_lid.css"); 
            
            return new LeaguePref($row['lid'], $row['name'],
                $row['prime_tid'], $row['second_tid'], $row['league_name'], $row['forum_url'],
                $row['welcome'], $row['rules'], true, $theme_css, 
                $settings['stylesheet'], $rules['initial_treasury'], 
				$rules['initial_treasury_sevens'], $settings['lang'],
				$rules['amazon'],$rules['chorf'],$rules['helf'],
				$rules['vamps'],$rules['khemri'],$rules['slann'],
				$rules['dungeon'],$rules['sevens'],
				$rules['randomskillrolls'],$rules['randomskillmanualentry'],$rules['megastars'],
				$rules['major_win_tds'],$rules['major_win_pts'],$rules['clean_sheet_pts'],
				$rules['major_beat_cas'],$rules['major_beat_pts']);
        }
    } else {
		return new LeaguePref($sel_lid, $leagues['lname'], null, null, null, null, null, null, false, null, 
            $settings['stylesheet'], $rules['initial_treasury'], 
			$rules['initial_treasury_sevens'], $settings['lang'],
			$rules['amazon'],$rules['chorf'],$rules['helf'],
			$rules['vamps'],$rules['khemri'],$rules['slann'],
			$rules['dungeon'],$rules['sevens'],
			$rules['randomskillrolls'],$rules['randomskillmanualentry'],$rules['megastars'],
			$rules['major_win_tds'],$rules['major_win_pts'],$rules['clean_sheet_pts'],
			$rules['major_beat_cas'],$rules['major_beat_pts']);
	}
}

function validate() {
	return $this->p_tour != $this->s_tour;
}

function save() {
    global $settings, $rules;
    
    $hasLeaguePref = mysql_fetch_object(mysql_query("SELECT f_lid from league_prefs where f_lid=$this->lid"));
    if($hasLeaguePref) {
        $query = "UPDATE league_prefs SET prime_tid=$this->p_tour,second_tid=$this->s_tour, league_name='".mysql_real_escape_string($this->league_name)."', forum_url='".mysql_real_escape_string($this->forum_url)."' , welcome='".mysql_real_escape_string($this->welcome)."' , rules='".mysql_real_escape_string($this->rules)."'  WHERE f_lid=$this->lid";
    } else {
        $query = "INSERT INTO league_prefs (f_lid, prime_tid, second_tid, league_name, forum_url, welcome, rules) VALUE ($this->lid, $this->p_tour, $this->s_tour, '".mysql_real_escape_string($this->league_name)."', '".mysql_real_escape_string($this->forum_url)."', '".mysql_real_escape_string($this->welcome)."', '".mysql_real_escape_string($this->rules)."')";
    }
    $savedcss = preg_replace('/<p>/', '', $this->theme_css);
    $savedcssfinal = preg_replace('/<\/p>/', '', $savedcss);
    FileManager::writeFile(FileManager::getCssDirectoryName() . "/league_override_$this->lid.css", $savedcssfinal);
    
    $settingsFileContents = FileManager::readFile(FileManager::getSettingsDirectoryName() . "/settings_$this->lid.php");
    $settingsFileContents = preg_replace("/settings\['stylesheet'\]\s*=\s['A-Za-z0-9_]+/", "settings['stylesheet'] = $this->core_theme_id", $settingsFileContents);
    $settingsFileContents = preg_replace("/settings\['lang'\]\s*=\s['A-Za-z0-9_\-]+/", "settings['lang'] = '$this->language'", $settingsFileContents);
    $settingsFileContents = preg_replace("/rules\['initial_treasury'\]\s*=\s['A-Za-z0-9_]+/", "rules['initial_treasury'] = $this->tv", $settingsFileContents);
    $settingsFileContents = preg_replace("/rules\['initial_treasury_sevens'\]\s*=\s['A-Za-z0-9_]+/", "rules['initial_treasury_sevens'] = $this->tv_sevens", $settingsFileContents);
	if ($this->amazon == 1) {
		$settingsFileContents = preg_replace("/rules\['amazon'\]\s*=\s['A-Za-z0-9_]+/", "rules['amazon'] = $this->amazon", $settingsFileContents);
	} else {
        $settingsFileContents = preg_replace("/rules\['amazon'\]\s*=\s['A-Za-z0-9_]+/", "rules['amazon'] = 0", $settingsFileContents);
    }
	if ($this->chorf == 1) {
		$settingsFileContents = preg_replace("/rules\['chorf'\]\s*=\s['A-Za-z0-9_]+/", "rules['chorf'] = $this->chorf", $settingsFileContents);
	} else {
        $settingsFileContents = preg_replace("/rules\['chorf'\]\s*=\s['A-Za-z0-9_]+/", "rules['chorf'] = 0", $settingsFileContents);
    }
	if ($this->helf == 1) {
		$settingsFileContents = preg_replace("/rules\['helf'\]\s*=\s['A-Za-z0-9_]+/", "rules['helf'] = $this->helf", $settingsFileContents);
	} else {
        $settingsFileContents = preg_replace("/rules\['helf'\]\s*=\s['A-Za-z0-9_]+/", "rules['helf'] = 0", $settingsFileContents);
    }
	if ($this->vamps == 1) {
		$settingsFileContents = preg_replace("/rules\['vamps'\]\s*=\s['A-Za-z0-9_]+/", "rules['vamps'] = $this->vamps", $settingsFileContents);
	} else {
        $settingsFileContents = preg_replace("/rules\['vamps'\]\s*=\s['A-Za-z0-9_]+/", "rules['vamps'] = 0", $settingsFileContents);
    }
	if ($this->khemri == 1) {
		$settingsFileContents = preg_replace("/rules\['khemri'\]\s*=\s['A-Za-z0-9_]+/", "rules['khemri'] = $this->khemri", $settingsFileContents);
	} else {
        $settingsFileContents = preg_replace("/rules\['khemri'\]\s*=\s['A-Za-z0-9_]+/", "rules['khemri'] = 0", $settingsFileContents);
    }
	if ($this->slann == 1) {
		$settingsFileContents = preg_replace("/rules\['slann'\]\s*=\s['A-Za-z0-9_]+/", "rules['slann'] = $this->slann", $settingsFileContents);
	} else {
        $settingsFileContents = preg_replace("/rules\['slann'\]\s*=\s['A-Za-z0-9_]+/", "rules['slann'] = 0", $settingsFileContents);
    }
	if ($this->dungeon == 1) {
		$settingsFileContents = preg_replace("/rules\['dungeon'\]\s*=\s['A-Za-z0-9_]+/", "rules['dungeon'] = $this->dungeon", $settingsFileContents);
	} else {
        $settingsFileContents = preg_replace("/rules\['dungeon'\]\s*=\s['A-Za-z0-9_]+/", "rules['dungeon'] = 0", $settingsFileContents);
    }
	if ($this->sevens == 1) {
		$settingsFileContents = preg_replace("/rules\['sevens'\]\s*=\s['A-Za-z0-9_]+/", "rules['sevens'] = $this->sevens", $settingsFileContents);
	} else {
        $settingsFileContents = preg_replace("/rules\['sevens'\]\s*=\s['A-Za-z0-9_]+/", "rules['sevens'] = 0", $settingsFileContents);
    }
	if ($this->randomskillrolls == 1) {
		$settingsFileContents = preg_replace("/rules\['randomskillrolls'\]\s*=\s['A-Za-z0-9_]+/", "rules['randomskillrolls'] = $this->randomskillrolls", $settingsFileContents);
	} else {
        $settingsFileContents = preg_replace("/rules\['randomskillrolls'\]\s*=\s['A-Za-z0-9_]+/", "rules['randomskillrolls'] = 0", $settingsFileContents);
    }
	if ($this->randomskillmanualentry == 1) {
		$settingsFileContents = preg_replace("/rules\['randomskillmanualentry'\]\s*=\s['A-Za-z0-9_]+/", "rules['randomskillmanualentry'] = $this->randomskillmanualentry", $settingsFileContents);
	} else {
        $settingsFileContents = preg_replace("/rules\['randomskillmanualentry'\]\s*=\s['A-Za-z0-9_]+/", "rules['randomskillmanualentry'] = 0", $settingsFileContents);
    }
	if ($this->megastars == 1) {
		$settingsFileContents = preg_replace("/rules\['megastars'\]\s*=\s['A-Za-z0-9_]+/", "rules['megastars'] = $this->megastars", $settingsFileContents);
	} else {
        $settingsFileContents = preg_replace("/rules\['megastars'\]\s*=\s['A-Za-z0-9_]+/", "rules['megastars'] = 0", $settingsFileContents);
    }
	if ($this->major_win_tds > 0) {
		$settingsFileContents = preg_replace("/rules\['major_win_tds'\]\s*=\s['A-Za-z0-9_]+/", "rules['major_win_tds'] = $this->major_win_tds", $settingsFileContents);
	} else {
        $settingsFileContents = preg_replace("/rules\['major_win_tds'\]\s*=\s['A-Za-z0-9_]+/", "rules['major_win_tds'] = 0", $settingsFileContents);
    }
	if ($this->major_win_pts > 0) {
		$settingsFileContents = preg_replace("/rules\['major_win_pts'\]\s*=\s['A-Za-z0-9_]+/", "rules['major_win_pts'] = $this->major_win_pts", $settingsFileContents);
	} else {
        $settingsFileContents = preg_replace("/rules\['major_win_pts'\]\s*=\s['A-Za-z0-9_]+/", "rules['major_win_pts'] = 0", $settingsFileContents);
    }
	if ($this->clean_sheet_pts > 0) {
		$settingsFileContents = preg_replace("/rules\['clean_sheet_pts'\]\s*=\s['A-Za-z0-9_]+/", "rules['clean_sheet_pts'] = $this->clean_sheet_pts", $settingsFileContents);
	} else {
        $settingsFileContents = preg_replace("/rules\['clean_sheet_pts'\]\s*=\s['A-Za-z0-9_]+/", "rules['clean_sheet_pts'] = 0", $settingsFileContents);
    }
	if ($this->major_beat_cas > 0) {
		$settingsFileContents = preg_replace("/rules\['major_beat_cas'\]\s*=\s['A-Za-z0-9_]+/", "rules['major_beat_cas'] = $this->major_beat_cas", $settingsFileContents);
	} else {
        $settingsFileContents = preg_replace("/rules\['major_beat_cas'\]\s*=\s['A-Za-z0-9_]+/", "rules['major_beat_cas'] = 0", $settingsFileContents);
    }
	if ($this->major_beat_pts > 0) {
		$settingsFileContents = preg_replace("/rules\['major_beat_pts'\]\s*=\s['A-Za-z0-9_]+/", "rules['major_beat_pts'] = $this->major_beat_pts", $settingsFileContents);
	} else {
        $settingsFileContents = preg_replace("/rules\['major_beat_pts'\]\s*=\s['A-Za-z0-9_]+/", "rules['major_beat_pts'] = 0", $settingsFileContents);
    }
    FileManager::writeFile(FileManager::getSettingsDirectoryName() . "/settings_$this->lid.php", $settingsFileContents);
    
    $settings['stylesheet'] = $this->core_theme_id;
    $settings['lang'] = $this->language;
    $rules['initial_treasury'] = $this->tv;
    $rules['initial_treasury_sevens'] = $this->tv_sevens;
            
    return mysql_query($query);
}

public static function showLeaguePreferences() {
    global $lng, $tours, $coach, $leagues, $settings, $rules;
    title($lng->getTrn('name', 'LeaguePref'));

	self::handleActions();

	// short cuts to text lookups
	$prime_title = $lng->getTrn('prime_title', 'LeaguePref');
	$prime_help = $lng->getTrn('prime_help', 'LeaguePref');
	
	$second_title = $lng->getTrn('second_title', 'LeaguePref');
	$second_help = $lng->getTrn('second_help', 'LeaguePref');

	$league_name_title = $lng->getTrn('league_name_title', 'LeaguePref');
	$league_name_help = $lng->getTrn('league_name_help', 'LeaguePref');

	$forum_url_title = $lng->getTrn('forum_url_title', 'LeaguePref');
	$forum_url_help = $lng->getTrn('forum_url_help', 'LeaguePref');

	$welcome_title = $lng->getTrn('welcome_title', 'LeaguePref');
	$welcome_help = $lng->getTrn('welcome_help', 'LeaguePref');

	$rules_title = $lng->getTrn('rules_title', 'LeaguePref');
	$rules_help = $lng->getTrn('rules_help', 'LeaguePref');

	$submit_text = $lng->getTrn('submit_text', 'LeaguePref');
	$submit_title = $lng->getTrn('submit_title', 'LeaguePref');

	$rTours = array_reverse($tours, true);
	$l_pref = self::getLeaguePreferences();
	// check this coach is allowed to administer this league
	$canEdit = is_object($coach) && $coach->isNodeCommish(T_NODE_LEAGUE, $l_pref->lid) ? "" : "DISABLED";
    ?>
	<div class='boxWide'>
		<h3 class='boxTitle4'><?php echo $l_pref->l_name; ?></h3>
		<div class='boxConf'>
            <form method="POST">
                <input type="hidden" name="lid" value="<?php echo $l_pref->lid; ?>" />
                <input type="hidden" name="existing" value="<?php echo $l_pref->existing; ?>" />
                <table width="100%" border="0">
                    <tr title="<?php echo $league_name_help; ?>">
                        <td>
                            <?php echo $league_name_title; ?>:
                        </td>
                        <td>
                            <input type="text" size="118" maxsize="128" name="league_name" <?php echo $canEdit; ?> value="<?php echo $l_pref->league_name; ?>" />
                        </td>
                    </tr>
                    <tr title="<?php echo $lng->getTrn('tv_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('tv_title', 'LeaguePref'); ?>:
                        </td>
                        <td>
                            <input type="number" min="0" name="tv" <?php echo $canEdit; ?> value="<?php echo $l_pref->tv; ?>" />
                        </td>
                    </tr>
                    <tr title="<?php echo $lng->getTrn('tv_sevens_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('tv_sevens_title', 'LeaguePref'); ?>:
                        </td>
                        <td>
                            <input type="number" min="0" name="tv_sevens" <?php echo $canEdit; ?> value="<?php echo $l_pref->tv_sevens; ?>" />
                        </td>
                    </tr>
                    <tr title="<?php echo $lng->getTrn('language_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('language_title', 'LeaguePref'); ?>:
                        </td>
                        <td>
                            <select name="language" <?php echo $canEdit; ?>>
                                
                                <option value="en-GB" <? echo 'en-GB' == $settings['lang'] ? 'selected' : '' ?>><?php echo $lng->getTrn('common/english'); ?></option>
                                <option value="es-ES" <? echo 'es-ES' == $settings['lang'] ? 'selected' : '' ?>><?php echo $lng->getTrn('common/spanish'); ?></option>
                                <option value="de-DE" <? echo 'de-DE' == $settings['lang'] ? 'selected' : '' ?>><?php echo $lng->getTrn('common/german'); ?></option>
                                <option value="fr-FR" <? echo 'fr-FR' == $settings['lang'] ? 'selected' : '' ?>><?php echo $lng->getTrn('common/french'); ?></option>
                                <option value="it-IT" <? echo 'it-IT' == $settings['lang'] ? 'selected' : '' ?>><?php echo $lng->getTrn('common/italian'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr title="<?php echo $forum_url_help; ?>">
                        <td>
                            <?php echo $forum_url_title; ?>:
                        </td>
                        <td>
                            <input type="text" size="118" maxsize="256" name="forum_url" <?php echo $canEdit; ?> value="<?php echo $l_pref->forum_url; ?>" />
                        </td>
                    </tr>
                    <tr title="<?php echo $welcome_help; ?>">
                        <td>
                            <?php echo $welcome_title; ?>:
                        </td>
                        <td>
                            <textarea rows="4" cols="90" class="html_edit" name="welcome" <?php echo $canEdit; ?>><?php echo $l_pref->welcome; ?></textarea>
                        </td>
                    </tr>
                    <tr title="<?php echo $rules_help; ?>">
                        <td>
                            <?php echo $rules_title; ?>:
                        </td>
                        <td>
                            <textarea rows="4" cols="90" class="html_edit" name="rules" <?php echo $canEdit; ?>><?php echo $l_pref->rules; ?></textarea>
                        </td>
                    </tr>
					
					
                    <tr title="<?php echo $prime_help; ?>">
                        <td>
                            <?php echo $prime_title; ?>:
                        </td>
                        <td>
                            <select name="p_tour">
                                <?php
                                    foreach ($rTours as $trid => $desc) {
                                        echo "<option value='$trid'" . ($trid==$l_pref->p_tour ? 'SELECTED' : '') . " " . $canEdit . ">" . $desc['tname'] . "</option>\n";
                                    }
                                ?>
                            </select>
                        </td>
                    </tr>
					<tr title="<?php echo $second_help; ?>">
                        <td>
                            <?php echo $second_title; ?>:
                        </td>
                        <td>
                            <select name="s_tour">
								<option value="0">--Select--</option>
                                <?php
                                    foreach ($rTours as $trid => $desc) {
                                        echo "<option value='$trid'" . ($trid==$l_pref->s_tour ? 'SELECTED' : '') . " " . $canEdit . ">" . $desc['tname'] . "</option>\n";
                                    }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr title="<?php echo $lng->getTrn('core_theme_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('core_theme_title', 'LeaguePref'); ?>:
                        </td>
                        <td>
                            <select name="core_theme_id">
                            <?php
                                $stylesheetLength = strlen('stylesheet');
                                foreach(FileManager::getAllCoreCssSheetFileNames() as $cssFileName) {
                                    $extensionIndex = strrpos($cssFileName, '.');
                                    $fileStartIndex = strrpos($cssFileName, 'stylesheet');
                                    $idLength = $extensionIndex - $fileStartIndex -$stylesheetLength;
                                    $cssId = substr($cssFileName, $fileStartIndex + $stylesheetLength, $idLength);
                                    
                                    // '_default' isn't a valid option, it's the default.
                                    if($cssId != '_default') {
                                        $coreThemeName = isset($settings['core_theme_names'][$cssId]) ? $settings['core_theme_names'][$cssId] : $cssId;
                                        echo '<option value="' . $cssId . '"' . ($cssId == $settings['stylesheet'] ? 'SELECTED' : '') . '>' . $coreThemeName . '</option>';
                                    }
                                }
                            ?>
                            </select>
                        </td>
                    </tr>
                    <tr title="<?php echo $lng->getTrn('css_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('css_title', 'LeaguePref'); ?>
                        </td>
                        <td>
                            <textarea rows="10" cols="120" name="theme_css" <?php echo $canEdit; ?>><?php echo $l_pref->theme_css; ?></textarea>
                        </td>                        
                    </tr>
					
                    <tr title="<?php echo $lng->getTrn('teams_legend_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('teams_legend_title', 'LeaguePref'); ?>
                        </td>
                        <td>     
							<input type='checkbox' name='amazon' value='1' onclick='slideToggleFast("amazon");'	<?php if($rules['amazon'] == 1) {echo 'checked';}?>>
							<b><?php echo $lng->getTrn('teams_legend_amazon', 'LeaguePref'); ?></b>
							<br>
							<input type='checkbox' name='chorf' value='1' onclick='slideToggleFast("chorf");'	<?php if($rules['chorf'] == 1) {echo 'checked';}?>>
                            <b><?php echo $lng->getTrn('teams_legend_chorf', 'LeaguePref'); ?></b>
							<br>
							<input type='checkbox' name='helf' value='1' onclick='slideToggleFast("helf");'	<?php if($rules['helf'] == 1) {echo 'checked';}?>>
                            <b><?php echo $lng->getTrn('teams_legend_helf', 'LeaguePref'); ?></b>
							<br>
							<input type='checkbox' name='vamps' value='1' onclick='slideToggleFast("vamps");'	<?php if($rules['vamps'] == 1) {echo 'checked';}?>>
                            <b><?php echo $lng->getTrn('teams_legend_vamps', 'LeaguePref'); ?></b>
							<br>
							<input type='checkbox' name='khemri' value='1' onclick='slideToggleFast("khemri");'	<?php if($rules['khemri'] == 1) {echo 'checked';}?>>
                            <b><?php echo $lng->getTrn('teams_legend_khemri', 'LeaguePref'); ?></b>
							<br>
							<input type='checkbox' name='slann' value='1' onclick='slideToggleFast("slann");'	<?php if($rules['slann'] == 1) {echo 'checked';}?>>
                            <b><?php echo $lng->getTrn('teams_legend_slann', 'LeaguePref'); ?></b>
                        </td>                        
                    </tr>
                    <tr title="<?php echo $lng->getTrn('dungeonbowl_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('dungeonbowl_title', 'LeaguePref'); ?>
                        </td>
                        <td>     
							<input type='checkbox' name='dungeon' value='1' onclick='slideToggleFast("dungeon");'	<?php if($rules['dungeon'] == 1) {echo 'checked';}?>>
                            <b><?php echo $lng->getTrn('dungeonbowl', 'LeaguePref'); ?></b>
                        </td>                        
                    </tr>
                    <tr title="<?php echo $lng->getTrn('sevens_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('sevens_title', 'LeaguePref'); ?>
                        </td>
                        <td>     
							<input type='checkbox' name='sevens' value='1' onclick='slideToggleFast("sevens");'	<?php if($rules['sevens'] == 1) {echo 'checked';}?>>
                            <b><?php echo $lng->getTrn('sevens', 'LeaguePref'); ?></b>
                        </td>                        
                    </tr>
                    <tr title="<?php echo $lng->getTrn('megastars_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('megastars_title', 'LeaguePref'); ?>
                        </td>
                        <td>     
							<input type='checkbox' name='megastars' value='1' onclick='slideToggleFast("megastars");'	<?php if($rules['megastars'] == 1) {echo 'checked';}?>>
                            <b><?php echo $lng->getTrn('megastars', 'LeaguePref'); ?></b>
                        </td>                        
                    </tr>
                    <tr title="<?php echo $lng->getTrn('randomskillrolls_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('randomskillrolls_title', 'LeaguePref'); ?>
                        </td>
                        <td>     
							<input type='checkbox' name='randomskillrolls' value='1' onclick='slideToggleFast("randomskillrolls");'	<?php if($rules['randomskillrolls'] == 1) {echo 'checked';}?>>
                            <b><?php echo $lng->getTrn('randomskillrolls', 'LeaguePref'); ?></b>
                        </td>                        
                    </tr>
                    <tr title="<?php echo $lng->getTrn('randomskillmanualentry_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('randomskillmanualentry_title', 'LeaguePref'); ?>
                        </td>
                        <td>     
							<input type='checkbox' name='randomskillmanualentry' value='1' onclick='slideToggleFast("randomskillmanualentry");'	<?php if($rules['randomskillmanualentry'] == 1) {echo 'checked';}?>>
                            <b><?php echo $lng->getTrn('randomskillmanualentry', 'LeaguePref'); ?></b>
                        </td>                        
                    </tr>
                    <tr title="<?php echo $lng->getTrn('bonuspoints_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('bonuspoints_title', 'LeaguePref'); ?>
                        </td>
                        <td>     
							<input type="number" min="0" max="10" name="major_win_tds" <?php echo $canEdit; ?> value="<?php echo $rules['major_win_tds'] ?>" />
							<b><?php echo $lng->getTrn('major_win_tds', 'LeaguePref'); ?></b><br>
							<input type="number" min="0" max="10" name="major_win_pts" <?php echo $canEdit; ?> value="<?php echo $rules['major_win_pts'] ?>" />
							<b><?php echo $lng->getTrn('major_win_pts', 'LeaguePref'); ?></b><br>
							<input type="number" min="0" max="10" name="clean_sheet_pts" <?php echo $canEdit; ?> value="<?php echo $rules['clean_sheet_pts'] ?>" />
							<b><?php echo $lng->getTrn('clean_sheet_pts', 'LeaguePref'); ?></b><br>
							<input type="number" min="0" max="10" name="major_beat_cas" <?php echo $canEdit; ?> value="<?php echo $rules['major_beat_cas'] ?>" />
							<b><?php echo $lng->getTrn('major_beat_cas', 'LeaguePref'); ?></b><br>
							<input type="number" min="0" max="10" name="major_beat_pts" <?php echo $canEdit; ?> value="<?php echo $rules['major_beat_pts'] ?>" />
							<b><?php echo $lng->getTrn('major_beat_pts', 'LeaguePref'); ?></b>
                        </td>                        
                    </tr>
                    <tr title="<?php echo $submit_title; ?>">
                        <td colspan="2">
                            <br><input type="submit" name="action" <?php echo $canEdit; ?> value="<?php echo $submit_text; ?>" style="position:relative; right:-200px;">
                        </td>
                    </tr>
                </table>
            </form>
		</div>
	</div>
    <div class='boxWide'>
        <?php HTMLOUT::helpBox($lng->getTrn('help', 'LeaguePref'), ''); ?>
	</div>
    <?php
}

public static function handleActions() {
    global $lng, $coach;
    
    if (isset($_POST['action'])) {
    	if (is_object($coach) && $coach->isNodeCommish(T_NODE_LEAGUE, $_POST['lid'])) {
			$l_pref = new LeaguePref($_POST['lid'], "", $_POST['p_tour'], $_POST['s_tour'],
                $_POST['league_name'], $_POST['forum_url'], $_POST['welcome'], 
                $_POST['rules'], $_POST['existing'], $_POST['theme_css'], 
                $_POST['core_theme_id'], $_POST['tv'], $_POST['tv_sevens'], $_POST['language'],
				$_POST['amazon'],$_POST['chorf'],$_POST['helf'],
				$_POST['vamps'],$_POST['khemri'],$_POST['slann'],
				$_POST['dungeon'],$_POST['sevens'],
				$_POST['randomskillrolls'],$_POST['randomskillmanualentry'],$_POST['megastars'],
				$_POST['major_win_tds'],$_POST['major_win_pts'],$_POST['clean_sheet_pts'],
				$_POST['major_beat_cas'],$_POST['major_beat_pts']);
			if($l_pref->validate()) {
				if($l_pref->save()) {
					echo "<div class='boxWide'>";
					HTMLOUT::helpBox($lng->getTrn('saved', 'LeaguePref'), '');
					echo "</div>";
				} else {
					echo "<div class='boxWide'>";
					HTMLOUT::helpBox($lng->getTrn('failedSave', 'LeaguePref'), '', 'errorBox');
					echo "</div>";
				}
			} else {
				echo "<div class='boxWide'>";
				HTMLOUT::helpBox($lng->getTrn('failedValidate', 'LeaguePref'), '', 'errorBox');
				echo "</div>";
			}
		} else {
			echo "<div class='boxWide'>";
			HTMLOUT::helpBox($lng->getTrn('failedSecurity', 'LeaguePref'), '', 'errorBox');
			echo "</div>";
		}
    }
}
}
?>
