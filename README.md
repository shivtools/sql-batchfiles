#SQL Batchfiles

I found that migrating data from Redshift to other Amazon services was a pain. This script was hacked together to help with the process by generating a .sql batch file which can then be uploaded with a single command.

This makes it seamless to add entries to a database without locking it up, especially when you're talking about millions of rows.

``` php
$db = new DB();

//Array of column names for the table you want to migrate data TO.
$column_names = array('profileid', 'phoneuid', 'source');

/**
 * @param {Boolean} Is an API endpoint provided?
 * @param {String} Table to read from
 * @param {String} Table to write to
 * @param {Array} Array containing column names
 * @param {Integer} Batch size to read from DB
 **/
$db->writeToBatchFile(true, 'lucktastic.nonfbprofiles', 'lucktastic.nonfbprofiles', $column_names, 10000);

```

Navigate to the BatchFile folder and execute the following commands, replacing whatever is in brackets with the necessary parameters (don't include brackets!)

```
mysql -[DB Host] -u{DB user} -p{DB password} {DB name} < batch.sql
```

Make sure that you have a folder called **Configs** in the root directory and a file called **configs.php** with the necessary fields provided:

```php
<?php

  //Config variables to make available to rest of the script
  return array(
    'api_endpoint' => '',
    'db_host' => '',
    'db_password' => '',
    'db_username' => '',
    'db_host' => '',
    'db_port' => ''
  );
```
