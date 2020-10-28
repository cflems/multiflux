<?php
// TODO: update descriptive strings
/**
 * Copyright (C) 2008-2012 FluxBB
 * based on code by Rickard Andersson copyright (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// The FluxBB version this script installs
define('FORUM_VERSION', '1.5.11');

define('FORUM_DB_REVISION', 21);
define('FORUM_SI_REVISION', 2);
define('FORUM_PARSER_REVISION', 2);

define('MIN_PHP_VERSION', '4.4.0');
define('MIN_MYSQL_VERSION', '4.1.2');
define('MIN_PGSQL_VERSION', '7.0.0');
define('PUN_SEARCH_MIN_WORD', 3);
define('PUN_SEARCH_MAX_WORD', 20);


define('PUN_ROOT', dirname(__FILE__).'/');

// Send the Content-type header in case the web server is setup to send something else
header('Content-type: text/html; charset=utf-8');

// Load the functions script
require PUN_ROOT.'include/functions.php';

// Load UTF-8 functions
require PUN_ROOT.'include/utf8/utf8.php';

// Strip out "bad" UTF-8 characters
forum_remove_bad_characters();

// Reverse the effect of register_globals
forum_unregister_globals();

// Disable error reporting for uninitialized variables
error_reporting(E_ALL);

// Force POSIX locale (to prevent functions such as strtolower() from messing up UTF-8 strings)
setlocale(LC_CTYPE, 'C');

// Turn off PHP time limit
@set_time_limit(0);


// If we've been passed a default language, use it
$install_lang = isset($_REQUEST['install_lang']) ? pun_trim($_REQUEST['install_lang']) : 'English';

// Make sure we got a valid language string
$install_lang = preg_replace('%[\.\\\/]%', '', $install_lang);

// If such a language pack doesn't exist, or isn't up-to-date enough to translate this page, default to English
if (!file_exists(PUN_ROOT.'lang/'.$install_lang.'/install.php'))
	$install_lang = 'English';

require PUN_ROOT.'lang/'.$install_lang.'/install.php';

// Attempt to load the configuration file config.php
if (file_exists(PUN_ROOT.'config.php'))
	require PUN_ROOT.'config.php';

// If PUN isn't defined, config.php is missing or corrupt
if (!defined('PUN'))
{
	header('Location: install.php');
	exit;
}

// If the cache directory is not specified, we use the default setting
if (!defined('FORUM_CACHE_DIR'))
	define('FORUM_CACHE_DIR', PUN_ROOT.'cache/');

// Make sure we are running at least MIN_PHP_VERSION
if (!function_exists('version_compare') || version_compare(PHP_VERSION, MIN_PHP_VERSION, '<'))
	exit(sprintf($lang_install['You are running error'], 'PHP', PHP_VERSION, FORUM_VERSION, MIN_PHP_VERSION));

// Load the appropriate DB layer class
switch ($db_type)
{
	case 'mysql':
		require PUN_ROOT.'include/dblayer/mysql.php';
		break;

	case 'mysql_innodb':
		require PUN_ROOT.'include/dblayer/mysql_innodb.php';
		break;

	case 'mysqli':
		require PUN_ROOT.'include/dblayer/mysqli.php';
		break;

	case 'mysqli_innodb':
		require PUN_ROOT.'include/dblayer/mysqli_innodb.php';
		break;

	case 'pgsql':
		require PUN_ROOT.'include/dblayer/pgsql.php';
		break;

	case 'sqlite':
		require PUN_ROOT.'include/dblayer/sqlite.php';
		break;

	default:
		error(sprintf($lang_install['DB type not valid'], $db_type));
}

// Create the database object (and connect/select db)
$db = new DBLayer($db_host, $db_username, $db_password, $db_name, $db_prefix, $p_connect);

// Make sure the site we are installing is actually valid
$db->start_transaction();
require PUN_ROOT.'include/site_id.php';
set_siteid_from_hostname();
$db->end_transaction();

if (!isset($_POST['form_sent']))
{
	// Make an educated guess regarding base_url
	$base_url  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';	// protocol
	$base_url .= preg_replace('%:(80|443)$%', '', $_SERVER['HTTP_HOST']);							// host[:port]
	$base_url .= str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));							// path

	if (substr($base_url, -1) == '/')
		$base_url = substr($base_url, 0, -1);

	$username = $email = '';
	$title = $lang_install['My FluxBB Forum'];
	$description = '<p><span>'.$lang_install['Description'].'</span></p>';
	$default_lang = $install_lang;
	$default_style = 'Air';
}
else
{
	$username = pun_trim($_POST['req_username']);
	$email = strtolower(pun_trim($_POST['req_email']));
	$password1 = pun_trim($_POST['req_password1']);
	$password2 = pun_trim($_POST['req_password2']);
	$title = pun_trim($_POST['req_title']);
	$description = pun_trim($_POST['desc']);
	$base_url = pun_trim($_POST['req_base_url']);
	$default_lang = pun_trim($_POST['req_default_lang']);
	$default_style = pun_trim($_POST['req_default_style']);
	$alerts = array();

	// Make sure base_url doesn't end with a slash
	if (substr($base_url, -1) == '/')
		$base_url = substr($base_url, 0, -1);

	if (parse_url($base_url, PHP_URL_HOST) != $_SERVER['HTTP_HOST'])
		error('The site you are trying to configure is not the one you are accessing the installer from. Please correct this discrepancy'); // TODO: lang-ify

	// Validate username and passwords
	if (pun_strlen($username) < 2)
		$alerts[] = $lang_install['Username 1'];
	else if (pun_strlen($username) > 25) // This usually doesn't happen since the form element only accepts 25 characters
		$alerts[] = $lang_install['Username 2'];
	else if (!strcasecmp($username, 'Guest'))
		$alerts[] = $lang_install['Username 3'];
	else if (preg_match('%[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}%', $username) || preg_match('%((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))%', $username))
		$alerts[] = $lang_install['Username 4'];
	else if ((strpos($username, '[') !== false || strpos($username, ']') !== false) && strpos($username, '\'') !== false && strpos($username, '"') !== false)
		$alerts[] = $lang_install['Username 5'];
	else if (preg_match('%(?:\[/?(?:b|u|i|h|colou?r|quote|code|img|url|email|list)\]|\[(?:code|quote|list)=)%i', $username))
		$alerts[] = $lang_install['Username 6'];

	if (pun_strlen($password1) < 6)
		$alerts[] = $lang_install['Short password'];
	else if ($password1 != $password2)
		$alerts[] = $lang_install['Passwords not match'];

	// Validate email
	require PUN_ROOT.'include/email.php';

	if (!is_valid_email($email))
		$alerts[] = $lang_install['Wrong email'];

	if ($title == '')
		$alerts[] = $lang_install['No board title'];

	$languages = forum_list_langs();
	if (!in_array($default_lang, $languages))
		$alerts[] = $lang_install['Error default language'];

	$styles = forum_list_styles();
	if (!in_array($default_style, $styles))
		$alerts[] = $lang_install['Error default style'];
}

// Check if the cache directory is writable
if (!forum_is_writable(FORUM_CACHE_DIR))
	$alerts[] = sprintf($lang_install['Alert cache'], FORUM_CACHE_DIR);

// Check if default avatar directory is writable
if (!forum_is_writable(PUN_ROOT.'img/avatars/'))
	$alerts[] = sprintf($lang_install['Alert avatar'], PUN_ROOT.'img/avatars/');

if (!isset($_POST['form_sent']) || !empty($alerts))
{
	// Fetch a list of installed languages
	$languages = forum_list_langs();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $lang_install['FluxBB Installation'] ?></title>
<link rel="stylesheet" type="text/css" href="style/<?php echo pun_htmlspecialchars($default_style) ?>.css" />
<script type="text/javascript">
/* <![CDATA[ */
function process_form(the_form)
{
	var required_fields = {
		"req_username": "<?php echo $lang_install['Administrator username'] ?>",
		"req_password1": "<?php echo $lang_install['Password'] ?>",
		"req_password2": "<?php echo $lang_install['Confirm password'] ?>",
		"req_email": "<?php echo $lang_install['Administrator email'] ?>",
		"req_title": "<?php echo $lang_install['Board title'] ?>",
		"req_base_url": "<?php echo $lang_install['Base URL'] ?>"
	};
	if (document.all || document.getElementById)
	{
		for (var i = 0; i < the_form.length; ++i)
		{
			var elem = the_form.elements[i];
			if (elem.name && required_fields[elem.name] && !elem.value && elem.type && (/^(?:text(?:area)?|password|file)$/i.test(elem.type)))
			{
				alert('"' + required_fields[elem.name] + '" <?php echo $lang_install['Required field'] ?>');
				elem.focus();
				return false;
			}
		}
	}
	return true;
}
/* ]]> */
</script>
</head>
<body onload="document.getElementById('install').req_username.focus();document.getElementById('install').start.disabled=false;" onunload="">

<div id="puninstall" class="pun">
<div class="top-box"><div><!-- Top Corners --></div></div>
<div class="punwrap">

<div id="brdheader" class="block">
	<div class="box">
		<div id="brdtitle" class="inbox">
			<h1><span><?php echo $lang_install['FluxBB Installation'] ?></span></h1>
			<div id="brddesc"><p><?php echo $lang_install['Welcome'] ?></p></div>
		</div>
	</div>
</div>

<div id="brdmain">
<?php if (count($languages) > 1): ?><div class="blockform">
	<h2><span><?php echo $lang_install['Choose install language'] ?></span></h2>
	<div class="box">
		<form id="install" method="post" action="setup.php">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_install['Install language'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_install['Choose install language info'] ?></p>
						<label><strong><?php echo $lang_install['Install language'] ?></strong>
						<br /><select name="install_lang">
<?php

		foreach ($languages as $temp)
		{
			if ($temp == $install_lang)
				echo "\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.$temp.'</option>'."\n";
			else
				echo "\t\t\t\t\t".'<option value="'.$temp.'">'.$temp.'</option>'."\n";
		}

?>
						</select>
						<br /></label>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="start" value="<?php echo $lang_install['Change language'] ?>" /></p>
		</form>
	</div>
</div>
<?php endif; ?>

<div class="blockform">
	<h2><span><?php echo sprintf($lang_install['Install'], FORUM_VERSION) ?></span></h2>
	<div class="box">
		<form id="install" method="post" action="setup.php" onsubmit="this.start.disabled=true;if(process_form(this)){return true;}else{this.start.disabled=false;return false;}">
		<div><input type="hidden" name="form_sent" value="1" /><input type="hidden" name="install_lang" value="<?php echo pun_htmlspecialchars($install_lang) ?>" /></div>
			<div class="inform">
<?php if (!empty($alerts)): ?>				<div class="forminfo error-info">
					<h3><?php echo $lang_install['Errors'] ?></h3>
					<ul class="error-list">
<?php

foreach ($alerts as $cur_alert)
	echo "\t\t\t\t\t\t".'<li><strong>'.$cur_alert.'</strong></li>'."\n";
?>
					</ul>
				</div>
<?php endif; ?>			</div>
			<div class="inform">
				<div class="forminfo">
					<h3><?php echo $lang_install['Administration setup'] ?></h3>
					<p><?php echo $lang_install['Info 7'] ?></p>
				</div>
				<fieldset>
					<legend><?php echo $lang_install['Administration setup'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_install['Info 8'] ?></p>
						<label class="required"><strong><?php echo $lang_install['Administrator username'] ?> <span><?php echo $lang_install['Required'] ?></span></strong><br /><input type="text" name="req_username" value="<?php echo pun_htmlspecialchars($username) ?>" size="25" maxlength="25" /><br /></label>
						<label class="conl required"><strong><?php echo $lang_install['Password'] ?> <span><?php echo $lang_install['Required'] ?></span></strong><br /><input id="req_password1" type="password" name="req_password1" size="16" /><br /></label>
						<label class="conl required"><strong><?php echo $lang_install['Confirm password'] ?> <span><?php echo $lang_install['Required'] ?></span></strong><br /><input type="password" name="req_password2" size="16" /><br /></label>
						<div class="clearer"></div>
						<label class="required"><strong><?php echo $lang_install['Administrator email'] ?> <span><?php echo $lang_install['Required'] ?></span></strong><br /><input id="req_email" type="text" name="req_email" value="<?php echo pun_htmlspecialchars($email) ?>" size="50" maxlength="80" /><br /></label>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<div class="forminfo">
					<h3><?php echo $lang_install['Board setup'] ?></h3>
					<p><?php echo $lang_install['Info 11'] ?></p>
				</div>
				<fieldset>
					<legend><?php echo $lang_install['General information'] ?></legend>
					<div class="infldset">
						<label class="required"><strong><?php echo $lang_install['Board title'] ?> <span><?php echo $lang_install['Required'] ?></span></strong><br /><input id="req_title" type="text" name="req_title" value="<?php echo pun_htmlspecialchars($title) ?>" size="60" maxlength="255" /><br /></label>
						<label><?php echo $lang_install['Board description'] ?><br /><input id="desc" type="text" name="desc" value="<?php echo pun_htmlspecialchars($description) ?>" size="60" maxlength="255" /><br /></label>
						<label class="required"><strong><?php echo $lang_install['Base URL'] ?> <span><?php echo $lang_install['Required'] ?></span></strong><br /><input id="req_base_url" type="text" name="req_base_url" value="<?php echo pun_htmlspecialchars($base_url) ?>" size="60" maxlength="100" /><br /></label>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_install['Appearance'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_install['Info 15'] ?></p>
						<label class="required"><strong><?php echo $lang_install['Default language'] ?> <span><?php echo $lang_install['Required'] ?></span></strong><br /><select id="req_default_lang" name="req_default_lang">
<?php

		$languages = forum_list_langs();
		foreach ($languages as $temp)
		{
			if ($temp == $default_lang)
				echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.$temp.'</option>'."\n";
			else
				echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'">'.$temp.'</option>'."\n";
		}

?>
						</select><br /></label>
						<label class="required"><strong><?php echo $lang_install['Default style'] ?> <span><?php echo $lang_install['Required'] ?></span></strong><br /><select id="req_default_style" name="req_default_style">
<?php

		$styles = forum_list_styles();
		foreach ($styles as $temp)
		{
			if ($temp == $default_style)
				echo "\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.str_replace('_', ' ', $temp).'</option>'."\n";
			else
				echo "\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'">'.str_replace('_', ' ', $temp).'</option>'."\n";
		}

?>
						</select><br /></label>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="start" value="<?php echo $lang_install['Start install'] ?>" /></p>
		</form>
	</div>
</div>
</div>

</div>
<div class="end-box"><div><!-- Bottom Corners --></div></div>
</div>

</body>
</html>
<?php

}
else
{
	// Make sure FluxBB isn't already installed
	$result = $db->query('SELECT 1 FROM '.$db_prefix.'users WHERE site_id='.SITE_ID);
	if ($db->num_rows($result))
		error(sprintf($lang_install['Existing table error'], $db_prefix, $db_name));

	// Start a transaction
	$db->start_transaction();
	$now = time();


	// Insert the four preset groups
	$db->query('INSERT INTO '.$db->prefix.'groups (g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood, g_report_flood, site_id) VALUES(\''.$db->escape($lang_install['Administrators']).'\', \''.$db->escape($lang_install['Administrator']).'\', 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, '.SITE_ID.')') or error('Unable to add group', __FILE__, __LINE__, $db->error());
	$ADMIN_GROUP = $db->insert_id();

	$db->query('INSERT INTO '.$db->prefix.'groups (g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_mod_promote_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood, g_report_flood, site_id) VALUES(\''.$db->escape($lang_install['Moderators']).'\', \''.$db->escape($lang_install['Moderator']).'\', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, '.SITE_ID.')') or error('Unable to add group', __FILE__, __LINE__, $db->error());


	$db->query('INSERT INTO '.$db->prefix.'groups (g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood, g_report_flood, site_id) VALUES(\''.$db->escape($lang_install['Guests']).'\', NULL, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 1, 1, 0, 60, 30, 0, 0, '.SITE_ID.')') or error('Unable to add group', __FILE__, __LINE__, $db->error());
	$GUEST_GROUP = $db->insert_id();

	$db->query('INSERT INTO '.$db->prefix.'groups (g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood, g_report_flood, site_id) VALUES(\''.$db->escape($lang_install['Members']).'\', NULL, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 60, 30, 60, 60, '.SITE_ID.')') or error('Unable to add group', __FILE__, __LINE__, $db->error());


	// Insert guest and first admin user
	$db->query('INSERT INTO '.$db_prefix.'users (group_id, username, password, email, site_id) VALUES('.$GUEST_GROUP.', \''.$db->escape($lang_install['Guest']).'\', \''.$db->escape($lang_install['Guest']).'\', \''.$db->escape($lang_install['Guest']).'\', '.SITE_ID.')')
		or error('Unable to add guest user. Please check your configuration and try again', __FILE__, __LINE__, $db->error());

	$db->query('INSERT INTO '.$db_prefix.'users (group_id, username, password, email, language, style, num_posts, last_post, registered, registration_ip, last_visit, site_id) VALUES('.$ADMIN_GROUP.', \''.$db->escape($username).'\', \''.pun_hash($password1).'\', \''.$email.'\', \''.$db->escape($default_lang).'\', \''.$db->escape($default_style).'\', 1, '.$now.', '.$now.', \''.$db->escape(get_remote_address()).'\', '.$now.', '.SITE_ID.')')
		or error('Unable to add administrator user. Please check your configuration and try again', __FILE__, __LINE__, $db->error());

	// Enable/disable avatars depending on file_uploads setting in PHP configuration
	$avatars = in_array(strtolower(@ini_get('file_uploads')), array('on', 'true', '1')) ? 1 : 0;

	// Insert config data
	$pun_config = array(
		'o_cur_version'				=> FORUM_VERSION,
		'o_database_revision'		=> FORUM_DB_REVISION,
		'o_searchindex_revision'	=> FORUM_SI_REVISION,
		'o_parser_revision'			=> FORUM_PARSER_REVISION,
		'o_board_title'				=> $title,
		'o_board_desc'				=> $description,
		'o_default_timezone'		=> 0,
		'o_time_format'				=> 'H:i:s',
		'o_date_format'				=> 'Y-m-d',
		'o_timeout_visit'			=> 1800,
		'o_timeout_online'			=> 300,
		'o_redirect_delay'			=> 1,
		'o_show_version'			=> 0,
		'o_show_user_info'			=> 1,
		'o_show_post_count'			=> 1,
		'o_signatures'				=> 1,
		'o_smilies'					=> 1,
		'o_smilies_sig'				=> 1,
		'o_make_links'				=> 1,
		'o_default_lang'			=> $default_lang,
		'o_default_style'			=> $default_style,
		'o_default_user_group'		=> 4,
		'o_topic_review'			=> 15,
		'o_disp_topics_default'		=> 30,
		'o_disp_posts_default'		=> 25,
		'o_indent_num_spaces'		=> 4,
		'o_quote_depth'				=> 3,
		'o_quickpost'				=> 1,
		'o_users_online'			=> 1,
		'o_censoring'				=> 0,
		'o_show_dot'				=> 0,
		'o_topic_views'				=> 1,
		'o_quickjump'				=> 1,
		'o_gzip'					=> 0,
		'o_additional_navlinks'		=> '',
		'o_report_method'			=> 0,
		'o_regs_report'				=> 0,
		'o_default_email_setting'	=> 1,
		'o_mailing_list'			=> $email,
		'o_avatars'					=> $avatars,
		'o_avatars_dir'				=> 'img/avatars',
		'o_avatars_width'			=> 60,
		'o_avatars_height'			=> 60,
		'o_avatars_size'			=> 10240,
		'o_search_all_forums'		=> 1,
		'o_base_url'				=> $base_url,
		'o_admin_email'				=> $email,
		'o_webmaster_email'			=> $email,
		'o_forum_subscriptions'		=> 1,
		'o_topic_subscriptions'		=> 1,
		'o_smtp_host'				=> NULL,
		'o_smtp_user'				=> NULL,
		'o_smtp_pass'				=> NULL,
		'o_smtp_ssl'				=> 0,
		'o_regs_allow'				=> 1,
		'o_regs_verify'				=> 0,
		'o_announcement'			=> 0,
		'o_announcement_message'	=> $lang_install['Announcement'],
		'o_rules'					=> 0,
		'o_rules_message'			=> $lang_install['Rules'],
		'o_maintenance'				=> 0,
		'o_maintenance_message'		=> $lang_install['Maintenance message'],
		'o_default_dst'				=> 0,
		'o_feed_type'				=> 2,
		'o_feed_ttl'				=> 0,
		'p_message_bbcode'			=> 1,
		'p_message_img_tag'			=> 1,
		'p_message_all_caps'		=> 1,
		'p_subject_all_caps'		=> 1,
		'p_sig_all_caps'			=> 1,
		'p_sig_bbcode'				=> 1,
		'p_sig_img_tag'				=> 0,
		'p_sig_length'				=> 400,
		'p_sig_lines'				=> 4,
		'p_allow_banned_email'		=> 1,
		'p_allow_dupe_email'		=> 0,
		'p_force_guest_email'		=> 1
	);

	foreach ($pun_config as $conf_name => $conf_value)
	{
		$db->query('INSERT INTO '.$db_prefix.'config (conf_name, conf_value, site_id) VALUES(\''.$conf_name.'\', '.(is_null($conf_value) ? 'NULL' : '\''.$db->escape($conf_value).'\'').', '.SITE_ID.')')
			or error('Unable to insert into table '.$db_prefix.'config. Please check your configuration and try again', __FILE__, __LINE__, $db->error());
	}

	// Test content has been removed due to a preposterous number of hardcoded IDs
	// Also, it always annoys me having to delete those as an admin anyway

	$db->end_transaction();


	$alerts = array();

	// Check if we disabled uploading avatars because file_uploads was disabled
	if ($avatars == '0')
		$alerts[] = $lang_install['Alert upload'];

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $lang_install['FluxBB Installation'] ?></title>
<link rel="stylesheet" type="text/css" href="style/<?php echo pun_htmlspecialchars($default_style) ?>.css" />
</head>
<body>

<div id="puninstall" class="pun">
<div class="top-box"><div><!-- Top Corners --></div></div>
<div class="punwrap">

<div id="brdheader" class="block">
	<div class="box">
		<div id="brdtitle" class="inbox">
			<h1><span><?php echo $lang_install['FluxBB Installation'] ?></span></h1>
			<div id="brddesc"><p><?php echo $lang_install['FluxBB has been installed'] ?></p></div>
		</div>
	</div>
</div>

<div id="brdmain">

<div class="blockform">
	<h2><span><?php echo $lang_install['Final instructions'] ?></span></h2>
	<div class="box">
		<div class="fakeform">
			<div class="inform">
				<div class="forminfo">
					<p><?php echo $lang_install['FluxBB fully installed'] ?></p>
				</div>
			</div>
		</div>
	</div>
</div>

</div>

</div>
<div class="end-box"><div><!-- Bottom Corners --></div></div>
</div>

</body>
</html>
<?php

}
?>
