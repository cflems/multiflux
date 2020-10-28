# MultiFlux
A modified version of FluxBB 1.5.11 to allow multiple installations to share the same tables.

## Installation
MultiFlux has the same dependencies as the same-numbered FluxBB version, but the installation
process has been modified and tailored more toward hosting providers. There is still a manual
step which may need to be overseen by way of an admin panel at some point. We'll see if I get
to it.

0. Install LAMP or LEMP or L\*MP, who cares.
1. Run (installation root)/install.php. You can't miss it, every forum script will redirect you
   to it if your database hasn't been installed.
2. (Manual Step) Insert virtual hosts into the hostmap table. You can very easily build a simple
   script to do this, like PunBB-Hosting did back in the day. The table format is very simple:
   - host (TEXT) => The virtual host (forums.example.com).
   - site_id (UNSIGNED INT) => Any number that is unique to this site. You can do this in ascending
     order if you would like (seems most logical), but it's not required.
  * 2b. Insert CNAMES of your virtual hosts. For example if you inserted ('`example.com`', 1), you
     most likely also want to insert ('`www.example.com`', 1). This will ensure they are treated as
     synonymous.
3. Direct users (and likely yourself) to their forum URLs in order to do board setup. This is also
   hard to miss as you will be redirected automatically. This involves setting up the administrator
   account and the board title, language, default style, etc.
