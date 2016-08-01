<?php

  class DB{

    protected $config;
    protected $connection;

    public function __construct() {
       $this->config = include(__DIR__ . '/../Configs/configs.php');
    }

    /** Connect to SQL database.
     *  Modifies the global connection variable.
     **/
    public function connect(){

      if(!empty($this->config["db_host"]) && !empty($this->config["db_user"]) && !empty($this->config["db_pass"]) && !empty($this->config["db_name"]) && !empty($this->config["db_port"])){
        $this->connection = mysqli_connect($this->config["db_host"],
                                           $this->config["db_user"],
                                           $this->config["db_pass"],
                                           $this->config["db_name"],
                                           $this->config["db_port"]);
        if(!$this->connection){
          echo("Could not connect to database: ". mysqli_connect_error() . "\n");
        }
        else{
          echo("Connected to database! \n");
        }
      }
      else{
        echo "Could not find necessary config variables. Use the query method directly if using an API endpoint. \n";
      }
    }

    /**
     * Main method to write to a .sql batch file
     * @param {Boolean} Is API endpoint provided ? true: false
     * @param {String} Table to read from
     * @param {String} Table to insert to
     * @param {Array} Array of strings that represent column names
     * @param{Integer} Batch size to read from database.
     */
    public function writeToBatchFile($is_api_endpoint, $table_to_read, $table_to_insert, $column_names, $batch_size){

      $offset = 0;

      $file = fopen(__DIR__ . '/../BatchFile/batch.sql', 'w');

      $response;

      while(true){

        $response = $this->selectBatch($is_api_endpoint, $table_to_read, $offset, $batch_size);
        if(empty($response) || count($response) == 0){
          break;
        }
        $arr = json_decode($response);

        //**** Modify according to however your JSON is formatted **** //
        $data = $arr->data; //Modify according to however your JSON is formatted
        //************************************************************* //

        if($data == null) break; //If no data is returned from database, then end reading from it

        //Pass array to prepareInserts, to generate the insert query and write to the .sql file
        $this->prepareInserts($data, $file, $table_to_insert, $column_names, $batch_size);
        $offset += $batch_size;
      }

      fclose($file);

      echo("Done writing to .sql file! \n");
    }

    /**
     * Prepare insert statements and write to .sql FilesystemIterator
     * @param {Array} Contains JSON array results from database
     * @param {String} Name of table to insert to
     * @param {Array} Array of strings representing column names
     * @param {Integer} Size of batches to extract and write data.
     **/
    public function prepareInserts($result_arr, $file, $table_to_insert, $column_names, $batch_size){

      $insert_query = "INSERT INTO $table_to_insert (";

      //Add column names into insert query
      foreach ($column_names as $column_name) {
        $insert_query .= "$column_name,";
      }
      $insert_query = rtrim($insert_query, ",");
      $insert_query .= ') ';

      //Add values to insert query
      $insert_query .= " VALUES ";
      $count = 0;
      foreach ($result_arr as $i => $json_object) {

        //**** Modify according to however your JSON is formatted **** //
        $insert_query .= '(' . $json_object->profileid . ',' . $json_object->phoneuid . ',' . $json_object->source . '),';
        //************************************************************* //
        $count++;

        //Insert a linebreak every 2 statements
        if($count%2 == 0){
          $insert_query .= "\n";
        }
      }

      //Remove end of line commas and add semi colon
      $insert_query = trim($insert_query, ', '.PHP_EOL);
      $insert_query .= ";";

      //Write query to .sql file
      $this->writeToFile($file, $insert_query);
      unset($insert_query);
    }

     /**
      * Writes to file (given file) and query to write out to the file
      * @param {File} File object to write to
      * @param {String} Insert query to write to file
      */
     public function writeToFile($file, $queryToWrite){
       $queryToWrite .= "\n \n";
       fwrite($file, $queryToWrite);
     }

    /**
     * Select a specified number of rows (n) from the database - to prevent the database from locking up from one huge read query.
     * @param Integer specifying batches of rows you'd like to select at a time.
     * @return Array containing n rows of data
     */
     public function selectBatch($is_api_endpoint, $table_to_read, $offset, $batch_size){

       $select_batch_query = "SELECT * FROM $table_to_read OFFSET $offset LIMIT $batch_size;";

       $result = $this->query(true, $this->config['api_endpoint'], $select_batch_query);

       return $result;
     }

    /**
     * Close connection to SQL database.
     **/
     public function close(){
       if(!empty($this->connection)){
         mysqli_close($this->connection);
         echo("Closed connection to database! \n");
       }
       else{
         echo 'No connection to close! \n';
       }
     }

    /**
     * @param Boolean Is API endpoint provided?
     * @param String SQL query for Redshift
     * @param String API Endpoint for Redshift
     * @return Array containing results from table if results exist. Else, returns null
     **/
     private function query($is_api_endpoint, $api_endpoint, $query){

       $response;

       if(is_bool($is_api_endpoint) && $is_api_endpoint) {

         $data_string ="query=".urlencode($query); //URL encode SQL query
         $ch = curl_init($api_endpoint);           //Initialize CURL call

         //Set CURL options
         curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
         curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_HTTPHEADER, array(
           'Content-Type: application/x-www-form-urlencoded',
           'Content-Length: ' . strlen($data_string))
         );
         $response = curl_exec($ch);             //Execute CURL call
         curl_close($ch);
         $response = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $response); //Strip out backlashes from string
       }
       //If API endpoint is not provided, try querying connection
       else{
         if($result = mysqli_query($this->connection, $query)){
            $response =  mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
          }
          else{
            $response = NULL;
          }
       }

       if(empty($response)){
         echo('Could not find any results!');
       }
       return $response;

   }

  }

?>
