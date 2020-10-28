<?php
// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

function set_siteid_from_hostname ()
{
  global $db;

  $result = $db->query('SELECT site_id FROM '.$db->prefix.'hostmap WHERE host = \''.$db->escape(strtolower($_SERVER['HTTP_HOST'])).'\'') or cant_get_siteid('Unable to fetch site ID', __FILE__, __LINE__, $db->error());

  if (!$db->num_rows($result)) error('No valid forum was found at this address'); // TODO: lang-ify
  else define('SITE_ID', $db->result($result));
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
