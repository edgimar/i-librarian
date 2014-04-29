I, Librarian 2 Instructions

Contents:
    ### Automated installation using installers ###
    ### Windows manual installation ###
    ### Linux manual installation ###
    ### Mac OS X manual installation ###
    ### First use ###
    ### Uninstallation ###

### Automated installation using installers ###

    You can download and execute installers for Windows XP, Vista, and 7, plus
    a DEB package and a console installer for Ubuntu, Debian, and its derivatives.
    A console installer for Mac OS X is also available. These installers will
    install and/or configure Apache and PHP for you. If you don't want that,
    follow the instructions below to install manually.

### Windows manual installation ###

    Before you start, uninstall Microsoft IIS, close Skype or any other software
    using port 80.

    Install Apache 2.2 and PHP 5.3 using a Windows installer like WAMPServer or
    ZendServer.

    Edit Apache configuration file (httpd.conf). Append this at the end using
    Notepad:

    Alias /librarian "C:\I, Librarian"
    <Directory "C:\I, Librarian">
        AllowOverride None
        Order deny,allow
        Deny from all
        Allow from 127.0.0.1
        <IfModule mod_php5.c>
            php_value upload_max_filesize 200M
            php_value post_max_size 800M
            php_value max_input_time 120
        </IfModule>
    </Directory>
    <Directory "C:\I, Librarian\library">
        IndexIgnore *
        AddType text/plain .html .htm .shtml .php
        <IfModule mod_php5.c>
            php_admin_flag engine off
        </IfModule>
        <FilesMatch "\.(sq3|pdf)$">
            Order allow,deny
        </FilesMatch>
    </Directory>

    You may change "C:\I, Librarian" to any directory where you want to have
    I, Librarian, including an external drive. For a groupware use, you need to
    allow access to more IP numbers or domain names. Just add more Allow from
    directives (Allow from mydomain.net).

    Restart either Apache server or the computer.

    Unzip I, Librarian files into the directory defined by Alias in httpd.conf.

### Linux manual installation ###

    Linux users, if you did not use the DEB package, make sure you have installed
    these packages from repositories:

    apache2 (may also be named httpd)
        - a web server (you may run I, Librarian with a different web server)
    php5 (may also be called php)
        - I, Librarian is written in PHP5
    php5-sqlite (may also be named php-pdo)
        - SQLite database for PHP5
    php5-gd (may also be named php-gd)
        -GD library for PHP5
    poppler-utils
        -required for PDF indexing and for the built-in PDF viewer
    bibutils
        - required for import/export from bibtex format
    ghostscript
        - required for the built-in PDF viewer
    pdftk
        - required for PDF bookmarks, attachments and watermarking

    If you are installing from the tar.gz, login as root or use sudo, and
    extract files into "librarian" directory in your web sever root directory.

        Example:
        tar zxf I,-Librarian-*.tar.gz -C /var/www/html/librarian

    Alternatively, extract the package into

        /usr/share/i-librarian/www

    and create Alias in your Apache httpd.conf:

        Alias /librarian /usr/share/i-librarian/www

    Change the owner of the library subfolder to Apache.

        Example:
        chown -R apache:apache /var/www/html/librarian/library
        chown root:root /var/www/html/librarian/library/.htaccess

    Insert a safe setting like this example into your Apache configuration file:

    <Directory "/var/www/html/librarian">
        AllowOverride None
        Order deny,allow
        Deny from all
        Allow from 127.0.0.1
        <IfModule mod_php5.c>
            php_value upload_max_filesize 200M
            php_value post_max_size 800M
            php_value max_input_time 120
        </IfModule>
    </Directory>
    <Directory "/var/www/html/librarian/library">
        IndexIgnore *
        AddType text/plain .html .htm .shtml .php
        <IfModule mod_php5.c>
            php_admin_flag engine off
        </IfModule>
        <FilesMatch "\.(sq3|pdf)$">
            Order allow,deny
        </FilesMatch>
    </Directory>

    To enable access from the Network, you need to allow access to more
    IP numbers or domain names. Just add more Allow from directives (Allow from
    mydomain.net).

    Restart Apache or the computer.

### Mac OS X manual installation using Zend Server CE ###

    Download and install Zend Server CE.

    Edit Zend Server httpd.conf using TextEdit. Open X11 terminal and write:

        sudo chown -R yourusername /usr/local/zend/apache2/conf
        open -a TextEdit /usr/local/zend/apache2/conf/httpd.conf

    Scroll to the bottom of the file and paste this at the very end:

        Alias /librarian /Users/yourusername/Sites/librarian
        <Directory /Users/Yourusername/Sites/librarian>
        AllowOverride None
        Order deny,allow
        Deny from all
        Allow from 127.0.0.1
        </Directory>
        <Directory /Users/Yourusername/Sites/librarian/library>
        IndexIgnore *
        AddType text/plain .html .htm .shtml .php
        <FilesMatch "\.(sq3|pdf)$">
        Order allow,deny
        </FilesMatch>
        </Directory>

    Don't forget to change "yourusername" to your actual user name. You can find
    out your user name by typing whoami in Terminal.

    Save the httpd.conf file and change the conf owner back to root:

        sudo chown -R root /usr/local/zend/apache2/conf

    Restart Zend Server Apache. Write in Terminal:

        sudo /usr/local/zend/apache2/bin/apachectl restart

    Start Zend Server from the Applications menu. Your web browser will open.
    Follow the instructions to finalize the Zend Server installation.

    In Zend Server Tasks menu, click "Change PHP directive Values", and change
    two PHP settings:

        upload_max_filesize to 400M
        post_max_size to 800M

    In the bottom right corner click Restart PHP. This will allow recording large
    PDF files.

    Download and install Pdftk.

    Download I, Librarian for Mac OS X and double-click the file to extract its
    contents.

    Rename the extracted directory to "librarian" and move it to your Sites folder.
    Make sure that your Sites folder is accessible to Others. Use the Get Info
    dialog of the Sites directory to change permissions for Everyone to access
    and read. You also need to make sure Everyone has Execute permissions for
    your home directory.

    Change the owner of the "library" subfolder to Zend Server Apache. Open X11
    terminal and write:

        sudo chown -R daemon:daemon /Users/yourusername/Sites/librarian/library

    Open your web browser and go to http://127.0.0.1:10088/librarian. 

### First use ###

    In order to start I, Librarian, open your web browser, and visit:

    http://127.0.0.1/librarian

    Replace 127.0.0.1 with your static IP, or qualified server domain name, if
    you have either one.

    Create an account and head to Tools->Installation Details to check if
    everything checks fine.

    You should also check Tools->Settings to run I, Librarian the way you want.

    Thank you for installing I, Librarian.

### Uninstallation ###

    If you used the DEB package, execute the uninstall.sh uninstaller.

    Otherwise uninstall all programs that you installed solely to use I, Librarian.
    These may include Apache and PHP.

    Delete I, Librarian directory.