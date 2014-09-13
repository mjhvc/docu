<?php

/** @class UniConnect() 
		@brief un singleton pour gérer une unique connexion sql par appel.

 		[référence sur: apprendre-php.com] (http://www.apprendre-php.com/tutoriels/tutoriel-47-classe-singleton-d-acces-aux-sgbd-integrant-pdo.html) 
		@author marcvancraesbeck@scarlet.be
	  @copyright [GNU Public License](@ref licence.dox)
*/ 

class UniConnect 
{
  private static $statement = null; /**<  paramètres propres à l'objet PDO*/
  private $pdoStatement = null; /**<  paramètres propres à l'objet PDO*/
  
  /**  Constructeur prive, garanti le singleton car une seule instance de la classe est fournir pour tout le script.
  
  Crée une instance de la classe PDO avec leparamètres DSN fournis par le fichier dsn.php créé à l'installation de l'application. TODOREF

  */ 
  private function __construct()
  { 
    try {    
      if (file_exists(DIRLIB.'dsn.php')) { 
        include_once('dsn.php'); 
        $this->pdoStatement = new PDO(DSN,NOM,PASS);
      }
      else { $this->pdoStatement = new PDO(NULL,NULL,NULL); }
      $this->pdoStatement->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    }
    catch (PDOException $e){
      print "Error from PDO : ".$e->getCode()."--".$e->getMessage()."<br />\n";
    }
  }

	/** crée et retourne l'objet PDO
	*/

  public static function getInstance() 
  {
    if(!isset(self::$statement)) {
      self::$statement = new UniConnect();      
    }
    return self::$statement;
  }

	/** exécution de l'objet PDO par methode d'appel magique php.
	
			@param $method string Le nom de la methode d'appel évoquée.
			@param $argument array Le tableau des arguments évoqués par $method.
	*/
  public function __call($method,$argument)
  {
    $args = implode(",",$argument);    
    return $this->pdoStatement->$method($args);
  }
}
?>
