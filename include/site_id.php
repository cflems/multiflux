<?php
// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;


function set_siteid_from_hostname ()
{
  global $db, $lang_multisite, $pun_user;

	if (!isset($lang_multisite))
	{
		if (isset($pun_user['language']) && file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/multisite.php'))
			require PUN_ROOT.'lang/'.$pun_user['language'].'/multisite.php';
		else
			require PUN_ROOT.'lang/English'.'/multisite.php';
	}

  $result = $db->query('SELECT site_id FROM '.$db->prefix.'hostmap WHERE host = \''.$db->escape(strtolower($_SERVER['HTTP_HOST'])).'\'') or cant_get_siteid($lang_multisite['Unable to fetch site ID error'], __FILE__, __LINE__, $db->error());

  if (!$db->num_rows($result))
	{
		header('HTTP/1.1 404 Not Found');
		error($lang_multisite['No such forum error']);
	}
  else
		define('SITE_ID', $db->result($result));
}

// If the site ID can't be fetched, then initial database setup may be needed
function cant_get_siteid ($message, $file = null, $line = null, $db_error = false)
{
	if (basename($_SERVER['PHP_SELF']) != 'install.php')
	{
		header('Location: install.php');
		exit;
	}

	error($message, $file, $line, $db_error);
}
?>
