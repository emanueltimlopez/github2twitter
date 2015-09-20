<?php

/**** CONFIG PART ****/
//DATABASE
define("DB_HOST", "localhost");
define("DB_NAME", "**********");
define("DB_USER", "**********");
define("DB_PASS", "**********");

/*
CREATE TABLE IF NOT EXISTS `bot_github` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_twitter` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=8 ;
*/

//TWITTER
define("CONSUMER_KEY", "**********");
define("CONSUMER_SECRET", "**********");
define("ACCESS_KEY", "**********");
define("ACCESS_SECRET", "**********");
//GITHUB
define("URL_GITHUB", "https://api.github.com/users/timbislopez/starred");

require_once 'curl.php'; //Download: https://github.com/php-curl-class/php-curl-class/blob/master/src/Curl/Curl.php
require_once 'twitteroauth.php'; //Download: https://github.com/abraham/twitteroauth

use \Curl\Curl;

class botGithub {

	private $conn = null;

  	public function __construct() {

		$twitter = new TwitterOAuth (CONSUMER_KEY ,CONSUMER_SECRET , ACCESS_KEY , ACCESS_SECRET );

    		//OBTAIN THE STARRED PROJECTS
		$curl = new Curl();
		$curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
		$curl->get(URL_GITHUB);

		if ($curl->error) {
		    echo 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage;
		} else {

			$existe = false;

			do {
			  	//PROCESS A BEAUTY STRING
				$random = array_rand($curl->response);
				$name = $curl->response[$random]->name;
				$description = $curl->response[$random]->description;
				$cutdescription = (strlen($description) > 70)?substr($description, 0, 60).'...':$description;
				$home_url = $curl->response[$random]->html_url;
				
				//PUBLISH YET?
				if ($this->connectDB()) {
					$query = $this->conn->prepare('SELECT id_twitter FROM bot_github WHERE nombre LIKE :nombre;');
				    $query->bindParam(':nombre', $name, PDO::PARAM_STR);
				    $query->execute();
				    $result_row = $query->fetchObject();

	                if (isset($result_row->id_twitter)) {
	                	$existe = true;
	                }
				}
			} while ($existe);

			$tweet = $name.': '.$cutdescription.' '.$home_url;
		    	//TWEET
	    		$return = $twitter->post('statuses/update', array('status' => $tweet));
	    	
	    		if ($this->connectDB()) {
	    	  		//SAVE THE TWEET
	    			$query = $this->conn->prepare('INSERT INTO bot_github (nombre, id_twitter) VALUES (:nombre, :id_twitter)');
			    	$query->bindParam(':nombre', $name, PDO::PARAM_STR);
			   	$query->bindParam(':id_twitter', $return->id, PDO::PARAM_STR);
			    	$query->execute();
	    		}

	    		echo '<pre>';
	    		print_r($return);
	 	    	echo '</pre>';

		}
	}

	private function connectDB() {
        	if ($this->conn != null) {
            		return true;
        	} else {
            		try {
                		$this->conn = new PDO('mysql:host='. DB_HOST .';dbname='. DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
                		return true;
            		} catch (PDOException $e) {
				echo 'Error: '.$e->getMessage();
            		}
        	}
        	return false;
	}

}

$botgithub = new botGithub();
?>

	
