<?php
// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

##########################################################
#                       Important:                       #
#                                                        #
#  No effort has been made to port this to anything but  #
#  MySQL using InnoDB. If you are using a different DB   #
#  system you'll have to change the queries accordingly. #
#                                                        #
##########################################################

$manage_tables = array(
  'bans',
  'categories',
  'censoring',
  'config',
  'forum_perms',
  'forum_subscriptions',
  'forums',
  'groups',
  'online',
  'posts',
  'reports',
  'search_cache',
  'search_matches',
  'search_words',
  'topic_subscriptions',
  'topics',
  'users',
);

foreach ($manage_tables as $tbl_name) {
  $db->query('ALTER TABLE '.$db->prefix.$tbl_name.' ADD site_id INT(255) NOT NULL DEFAULT 0') or die(var_export($db->error(), true));
}
?>
