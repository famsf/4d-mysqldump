4D Database Dump to MySQL
=========================
This PHP script connects to a 4D database then dumps all or some table
structure and data into compliant MySQL SQL statements. Due to limitations
of the 4D SQL implementation, some columns are inaccessible and therefore
the data is skipped.

Copyright 2013 Fine Arts Museums of San Francisco
by Brad Erickson <eosrei at gmail.com>

Requires PDO_4D PHP extension: http://www.php.net/manual/en/ref.pdo-4d.php

Note: This was created and tested with 4D V11.4 and Ubuntu 12.04LTS YMMV.
A 3.7GB SQL file was sucessful exported from 4D and imported to MySQL.

Usage
-----

    4d-mysqldump.php [OPTIONS]
      -H, --help          Display this help and exit.
      -h, --host=name     4D server hostname or IP (required.)
      -u, --user=name     4D username (required.)
      -p, --password=password
                          4D password (required.)
      -r, --retries=count Number of connection attempt retries (default 3, for a
                          total of 4.)
      -l, --list          List all tables and exit.
      -t, --table=name    Dumps a specific table instead of all tables.
      -b, --ignore-binary Ignore picture/blob columns. They contain binary data
                          which may significantly increase the export size, but may
                          not be needed.
      -s, --skip-structure
                          Only print data, don't print table structure.
      -o, --offset=count  The offset to use during the export.
      -c, --limit=count   The limit to use during the export.
      -w, --allow-spaces  Don't removed spaces from column names.


Known Issues
------------
*READ THIS!*

* The 4D library used by the PDO_4D PHP extension has a severe memory leak.
  The workaround used in this is a new thread for every table and whenever the
  memory use hits a predetermined limit. The long term solution is fixing the
  4D library or rewriting to not use it.
* Columns of type Subtable Relation when manually created cannot return useful
  information via SQL and are therefore dropped/ignored. The automatically
  created Subtable Relation fields are exported correctly.

Install
-------
1. Install PHP
2. Compile/enable the PDO_4D extension (see directions below)
3. Clone this script
4. Test the connection using -t to list available tables.
5. Dump your database and import to MySQL!

Compile/enable PDO_4D
-----------------------------------------
Tested on Linux Mint with PHP 5.4.6 and Ubuntu 12.04LTS with PHP 5.3.10

    # Install dependencies (You may need more)
    sudo apt-get install php5-dev
    # Get most recent copy of the code
    svn checkout http://svn.php.net/repository/pecl/pdo_4d/trunk pdo_4d
    cd pdo_4d
    # Prepare the PHP extension for compiling
    phpize
    # Fix problem with generated configure pointing to incorrect header location.
    # See: https://bugs.php.net/bug.php?id=63902
    sed -i -e 's/php\//php5\//g' configure
    # Configure the package to the system
    ./configure --with-pdo-4d
    # Copy fourd.h to the main directory. Note: This is not the correct way to
    # fix this problem, but it works to get the extension compiled.
    cp lib4d_sql/fourd.h .
    # Compile!
    make
    # Copy the extension to PHP's library
    sudo make install
    # Create php5 module configuration file (PHP 5.4)
    sudo sh -c "echo extension=pdo_4d.so > /etc/php5/mods-available/pdo_4d.ini"
    # Enable the module (PHP 5.4)
    sudo php5enmod pdo_4d
    # Enable the module (PHP 5.3)
    sudo sh -c "echo extension=pdo_4d.so > /etc/php5/conf.d/pdo_4d.ini"
    # Restart apache
    sudo apache2ctl restart
    # Check for PDO_4D in the PHP CLI Information
    php -i | grep 4D

Todo
----
* A 'test' option to list all problem tables/columns.
* Output the warnings/notices via stderr instead of PHP's trigger_error.
* Export 4D foreign key constraints.
* Add comments to tables describing missing columns.
* Add comments to columns describing foreign keys.
* Load h/u/p from ~/.4d.conf.
* Error for missing php 4d extension.
* Allow queries to be run (maybe.)
* Include default values for columns in SQL.
* Fix parseopt() to handle multi-value arrays and prioritize argument order.
* Add all missing data types.
* Thread Manager really should be a different class.
* Rewrite the whole thing in C! (Probably not)
