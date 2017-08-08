<?php
define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';

##########################################################
#                       Important:                       #
#                                                        #
#  No effort has been made to port this to anything but  #
#  MySQL using InnoDB. If you are using a different DB   #
# system, you'll have to change the queries accordingly. #
#                                                        #
##########################################################

$db->query('CREATE TABLE '.$db->prefix.'hostmap ( host VARCHAR(255) NOT NULL , site_id INT(255) NOT NULL , UNIQUE (host)) ENGINE = InnoDB') or die($db->error);

$manage_tables = array(
  'bans',
  'categories',
  'censoring',
  'config',
  'forum_perms',
  'forum_subscriptions',
  'forums',
  'groups',
  'gz_akismet_queue',
  'messages',
  'online',
  'poll',
  'poll_voted',
  'posts',
  'reports',
  'search_cache',
  'search_matches',
  'search_words',
  'test_registrations',
  'topic_subscriptions',
  'topics',
  'users',
);

foreach ($manage_tables as $tbl_name) {
  $db->query('ALTER TABLE '.$db->prefix.$tbl_name.' ADD site_id INT(255) NOT NULL DEFAULT 0') or die($db->error);
  echo '<p>'.$tbl_name.' completed.</p>'.PHP_EOL;
}
?>
