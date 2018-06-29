# bu-alert-plugin

Plugin documentation: https://developer.bu.edu/webteam/developer/libraryframework/wordpress/plugins/bu-alert/

BU Alert general documentation: https://developer.bu.edu/webteam/applications/bu-alert/

BU Alert emergency documentation: https://developer.bu.edu/webteam/applications/bu-alert/emergency/

BU Alert launch procedures: https://developer.bu.edu/webteam/applications/bu-alert/launch-procedures/

## Where is the complete history of this plugin?

This plugin was originally hosted in Subversion. Multiple attempts were made to convert the SVN repo to a Git repo but no were successful. This repo starts off with an initial commit replicates the trunk of the bu-alert plugin in SVN. Trunk is identical to the latest SVN release/tag:

http://bifrost.bu.edu/svn/repos/wordpress/plugins/bu-alert/tags/2.3.2

If ever a need arises to review the history of this plugin before this initial commmit that history can be found in our Subversion repository.

As far best as I, adamzp, can tell the root issue in converting this history originates from when this plugin was migrated from
http://bifrost.bu.edu/svn/repos/wordpress/cms/trunk/wp-content/mu-plugins/bu-alerts.php to http://bifrost.bu.edu/svn/repos/wordpress/plugins/bu-alert/trunk at revision 15041 in Subversion.

As of now, 11/21/2017, all new development on this plugin will take place in Git.

Below are the errors encountered when attempting to convert the SVN repo to a Git repo:

Using higher level of URL: http://bifrost.bu.edu/svn/repos/wordpress/plugins/bu-alert => http://bifrost.bu.edu/svn/repos
W: Ignoring error from SVN, path probably does not exist: (160013): Filesystem has no item: File not found: revision 100, path '/wordpress/plugins/bu-alert'
W: Do not be alarmed at the above message git-svn is just searching aggressively for old history.
This may take a while on large repositories
Found possible branch point: http://bifrost.bu.edu/svn/repos/wordpress/cms/trunk/wp-content/mu-plugins/bu-alerts.php => http://bifrost.bu.edu/svn/repos/wordpress/plugins/bu-alert/trunk, 15041
Initializing parent: refs/remotes/origin/trunk@15041
W: Ignoring error from SVN, path probably does not exist: (160013): Filesystem has no item: File not found: revision 101, path '/wordpress/cms/trunk/wp-content/mu-plugins/bu-alerts.php'
W: Do not be alarmed at the above message git-svn is just searching aggressively for old history.
This may take a while on large repositories
fatal: Not a valid object name
ls-tree -z  ./: command returned error: 128