4D Database Dump to MySQL
=========================
This PHP script connects to a 4D database then dumps all or some table
structure and data into compliant MySQL SQL statements. Due to limitations
of the 4D SQL implementation, some columns are inaccessible and therefore
the data is skipped.

Copyright 2013 Fine Arts Museums of San Francisco
by Brad Erickson <eosrei@gmail.com>

Requires PDO_4D PHP extension: http://www.php.net/manual/en/ref.pdo-4d.php

Usage
-----

    ./4d-mysqldump.php -hHostname -uUsername -pPassword [-rRetries] [-tTable] [-l] [-b]

Options:
  -h    Hostname
  -u    Username
  -p    Password
  -r    Number of connection attempt tries (default 3)
  -t    Specific table to dump (used internally)
  -l    List all tables and exit
  -b    Include blob/picture binary fields (not implemented yet)

Known Issues
------------
*READ THIS!*

* The 4D library used by the PDO_4D PHP extension has a severe memory leak.
  Each table is automatically dumped with a new PHP process to reduce max
  memory requirements, but significant amounts of ram can still be required.
  Example: A table with 75 columns including binary data in 150k rows, requires
  4GB+ of ram. Long term solution is fixing the 4D library or rewriting to not
  use it.
* Columns with spaces in the names cannot be retrieved from 4D via SQL and are
  therefore ignored/dropped. Fix the column names before exporting.
* Columns named using SQL reserved words cannot be retrieved from 4D via SQL
  and are therefore ignored/dropped. Fix the column names before exporting.
* Columns of binary type Picture are not handled and are therefore ignored/
  dropped. This may be corrected in the future.
* Columns of binary type Blob are not handled and are therefore ignored/
  dropped. This may be corrected in the future.
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
Tested on Linux Mint and Ubuntu 12.04LTS

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
    # Create php5 module configuration file
    sudo sh -c "echo extension=pdo_4d.so > /etc/php5/mods-available/pdo_4d.ini"
    # Enable the module
    sudo php5enmod pdo_4d
    # Restart apache
    sudo apache2ctl restart
    # Check for PDO_4D in the PHP CLI Information
    php -i | grep 4D
