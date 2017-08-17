<?php
define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';

$page_title = array('Create Site', 'Multiflux Administration');
require PUN_ROOT.'header.php';
?>
    <div class="box">
      <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
        <p><input type="text" name="subdom" size="20" maxlength="30" /><span class="formlabel">.<?php echo $subdom_super; ?></span></p>
        <p><input type="submit" name="create" value="Create Site" /></p>
      </form>
    </div>
<?php
require PUN_ROOT.'footer.php';
?>
