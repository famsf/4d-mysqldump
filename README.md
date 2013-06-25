4D Database Dump to MySQL
by Brad Erickson <eosrei@gmail.com> 2013

Command line script to dump a 4D database to a compliant MySQL dump file.
The PDO_4D PHP extension is required: http://www.php.net/manual/en/ref.pdo-4d.php
 
To compile/install PDO_4D in Ubuntu 12.04LTS
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