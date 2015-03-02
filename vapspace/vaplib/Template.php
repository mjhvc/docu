<?php
/**
  @class  Template
  @author  Philippe Rigaux
  @copyright [GNU Public License](@ref licence.dox) 
  @brief Une classe implantant un moteur de templates semblable à celui de la PHPLIB.
*/

class Template
{
  private $classname = "Template";
  private $path = "";           /**< le chemin général du dossier des template */
  private $varkeys = array();   /**< le tableau des entités qui serviront de pattern regex à substituer */
  private $varvals = array();   /**< le tableau des valeurs des entités  */
  private $file = array();      /**< le tableau des chemins de fichiers */
   
  /** Constructeur, charge un repertoire de fichiers template par (@ref setScriptPath($path))
    @param $tmplPath string, chemin d'u repertoire de templates 
  */
  public function __construct($tmplPath = NULL)
  {
    if ($tmplPath) { 
      $this->setScriptPath($tmplPath); 
    }
  }

  /** Attribue à $this->path la valeur du parametre fourni et vérification d'usage 
    @param  $path string The directory to set as the path.
    @return void
  */
  public function setScriptPath($path)
  {
    $this->path = $path; 
    
    //$this->path doit exister
    if (!is_readable($this->path)) {
      throw new Exception("Invalid path '$this->path' provided");
    }
    return true;
  }

  /** Récupère le chemin du répertoire de template comme un array
    @return array  
  */
  public function getScriptPaths()
  {
    return array($this->path);
  }

  /** @brief Attribue un FQName au fichier de template $filename 
             Classe le contenu du fichier dans l'entite $handle : (@ref loadfile($handle,$final))
    @param $handle string ( ou array)  : est l'entite d'un fichier template représenté par $filename,
    @param $filename string : nom de fichier du template
    @param $final bool flag pour debug pas utilisé 
    @return void
  */
  public function setFile($handle, $filename = "",$final=null) 
  {
    if (!is_array($handle)) {

      // le fichier .tpl doit exister
      if ($filename == "") {
        throw new Exception ("set_file: For handle $handle filename is empty.");
      }

      //stocker le FQName du template $filename dans $this->file['entite']
      $this->file[$handle] = $this->filename($filename);

      // Charge le fichier comme une entite  
      if (!empty($final)){$this->loadFile($handle,$final);}
      else { $this->loadFile($handle);}
    } 
    else {
      reset($handle);
      while(list($h, $f) = each($handle)) {
        $this->file[$h] = $this->filename($f);
      }
    }
  }

  /** Traitement et classement automatique (en boucle) d'un block html au sein de son entité parente dans une entité renouvellée et à chaque fois substituée
    @param $parent string l'entité parente doit pre exister
    @param $handle string l'entité de base à traiter 
    @param $name string facultatif, dans ce cas on prend $handle comme entité  
    @return void  
  */
  public function setBlock($parent, $handle, $name = "") 
  {
    //le chargement de $parent doir reussir    
    if (!$this->loadfile($parent)) {
      throw new Exception ("subst: unable to load $parent.");
      return false;
    }
    if (empty($name)) {
       $name = $handle; 
    }
    else { //cree un nouvelle entité $name (si $name est vide, $name= $handle) 'à vide'
      $this->setVar($name, "");
    } 

    //retrouver la valeur du noeud (entité) $parent
    $str = $this->getVar($parent); 

    //traitement du block défini par <!--BEGIN $handle --> contenu du block \n <!-- END $handle -->
    $reg = "/<!--\s+BEGIN $handle\s+-->(.*)\n\s*<!--\s+END $handle\s+-->/sm";
    
    // le contenu du block (.*) est capturé dans $m[1][0]
    preg_match_all($reg, $str, $m); 

    //Créer une nouvelle entité    
    $newHandle = "{" . $name . "}";

    //reassignation de $str : $reg y est remplacé par $newHandle dans $str  
    $str = preg_replace($reg,$newHandle,$str); 
    
    if(!isset($m[1][0])) { 
      throw new Exception ("Block $handle does not exist");
    }
    
    //classement: cle<-$handle et valeur<-le bloc matché par $reg  
    $this->setVar($handle, $m[1][0]); 

    //classement: cle<-$parent et valeur<-$str 
    // ici $str est classé à sa nouvelle valeur
    $this->setVar($parent, $str); 
  } 

  /** @ brief Surcharge de la méthode __set(): 
    - met $val dans un tableau indexé par $key 
    - place $key formatté en pattern regex dans un autre tableau, indexé par $key
   @param $key string Nom de l'entité
   @param $val mixed Valeur de l'entité
   @return void
  */
  public function __set($key, $val) 
  {
    $this->setVar($key, $val);
  }

  /** Obtenir une entité assignée par surcharge de metthode __get()
   @param $key string nom de l'entité
   @return mixed la valeur de cette entité
  */
  public function __get($key)
  {
    return $this->getVar($key);
  }

  /** Tester l'existence d'une entité
   @param $key string 
   @return boolean
  */
  public function __isset($key)
  {
    return (NULL !== $this->varvals[$key]);
  }

  /** Effacer une entité et sa valeur
   @param $key string
   @return void
   */
  public function __unset($key)
  {
    $this->clearVar($key); 
  }

  /** 
    @brief methode de classement centrale des entités et de leur valeurs 
    - Attribue à $this->varkeys[$varname] l'entité $varname transformée en pattern de regex par (@ref varname($varname))
    - Attribue à $this->varvals[$varname] la valeur de cette entité : $value
    - Important : $value peut être vide
    @param $varname string ou array : nom ou tableau de nom d'entité
    @param $value string facultatif : valeur d'une entité
    @return true
  */
  public function setVar ($varname,$value = "")
  {
    if (!is_array($varname)) {
      if (!empty($varname)){
        //l'entité $varname, protégée par (@ref varname($varname)), est assignée à une clé de $this->varkeys[$varname]
        $this->varkeys[$varname] = "/". $this->varname($varname) ."/"; // la valeur du masque 
        
        //la valeur de l'entité $varname (peut être nulle)  est attribuée à $this->varvals[$varname]
        $this->varvals[$varname] = $value;
      }
      return true;
    } 
    else {
      reset($varname);
      foreach ($varname as $k => $v) {
        if (!empty($k)){
          $this->varkeys[$k] = "/".$this->varname($k)."/";
          $this->varvals[$k] = $v;
        }
      }
      return true;    
    }
    
  }

  /** Obtenir la valeur d'une entité
   @param $varname: nom de l'entité à fournir
   @return:   la valeur de l'entité
  */
  public function getVar ($varname)
  {
    return $this->varvals[$varname];
  }

  /** Effacer toute trace d'une seule entité dans  $this->varkeys et $this->varvals
   @param $varname nom de l'entité à effacer
   @return   void
  */
  public function clearVar ($varname)
  {
    $this->varvals[$varname] = null;
    $this->varkeys[$varname] = null;
  }
   
  /** Supprime toutes les entités assignées à $this->varkeys et $this->varvals par reset array()
   @return void
  */
  public function clearVars()
  {
    $this->varvals = array();
    $this->varkeys = array();
  }

  /** Appel public de la methode privée (@ref parse($target,$handle,$append))   
    @param $target string une entité
    @param $handle string entité à assigner
    @return bool 
  */
  public function assign ($target, $handle=null) 
  {
    if ($handle) { 
      $this->parse ($target, $handle); 
      return true;
    }
    else { return false; }
  }
   
  /**  Appel public de la methode privée (@ref parse($target,$handle,$append)) 
   @param $target string ou array, une entité à créer si inexistante 
   @param $handle string l'entité à traiter et à placer dans $target
   @return void   
  */
  public function append ($target, $handle) 
  {
    //si $target n'a pas d'entité on crée l'entité $target à vide 
    if (!isSet($this->varvals[$target])) { $this->setVar($target, "");  } 

    // (à mon avis) le true ici ne rajoute rien de précis  
    $this->parse ($target, $handle, true);
  }
  
  /**méthode finale par traitement complet d'un template, de ses entités et retourne le processus achevé
    @param $name string l'entité de template à traiter
    @return string The output.
  */
  public function render($name)
  {
    $this->parse("my_view", $name);
    $str = $this->getVar("my_view");
    $this->clearVar ("my_view");
    return $str;
  }

  /************** Private part of the class  *****************/

  /** @brief methode de traitement centrale : charge une entité, effectue la/les substitutions et reclasse le resultat dans une nouvelle entité 
    appel des methodes (@ref subst($nandle)) et (@ref setVar($target,$handle)
    @param $target string nouvelle entité à générer
    @param $handle string entité à substituer
    @param $append string facultatif à rajouter à $handle
    @return void   
  */
  private function parse($target, $handle, $append = NULL ) 
  {
    //lance les substitutions
    $str = $this->subst($handle); 
  
    if ($append) { //Rajouter le nouveau résultat à l'ancien
      $newstr = $this->getVar($target) . $str;
      $this->setVar($target, $newstr);
    } 
    else { // Reclassement de l'entité avec sa valeurs substituée
      $this->setVar($target, $str);
    }
  }

  /** effectue la substitution par appel de preg_replace   
    @param $handle  entite à substituer handle of template where variables are to be substituted.
    @return string avec la substitution des entités effectués
  */
  private function subst($handle) 
  {    
    // le fichier de template doit être chargé 'non substitué' dans son entité
    if (!$this->loadfile($handle)) {
      throw new Exception("subst: unable to load $handle.");
    }  
   	// Récupérer le contenu de l'entité  classée  
    $str = $this->getVar($handle);
    
    // remplace $this->varkeys (recherchées dans $str) par $this->varvals,  et retourner le resultat
    $str = preg_replace($this->varkeys, $this->varvals, $str);
    return $str;
  }

  /** protection préalable d'entité avant substitution automatique de regex  
    @param $varname string  nom d'une entité de remplacement à protéger
    @return string    
  */
  private function varname($varname) 
  {
    return preg_quote("{".$varname."}");
  }

  /** 
   @brief charge le nom du fichier complet (FQName)
   @param $filename string name to be completed.
   @return string FQName of the file
   */
  private function filename($filename) 
  {
    if (substr($filename, 0, 1) != "/") {
       $filename = $this->path . DIRECTORY_SEPARATOR . $filename;
    }
    if (!file_exists($filename))
    { throw new Exception ("filename: file $filename does not exist.");}
    return $filename;
  }

  /** Classe le fichier .tpl de l'entité $handle par appel de (@ref setVar($handle,$value)) 
    @param $handle  load file defined by handle, if it is not loaded yet.
    @param $final bool flag pour debug (non utilisé)
    @return boolean
  */
  private function loadfile($handle,$final=null) 
  {    
    //Si l'entité handle existe, finir ici
    if (isset($this->varkeys[$handle]) and !empty($this->varvals[$handle]))
    { return true;}
    
    //L'entité doit exister
    if (!isset($this->file[$handle])) {
      throw new Exception ("loadfile: $handle is not a valid handle.");
      return false;
    }

    // retrouver le nom complet du chemin de l'entité déjà assigné par (@ref setFile($handle,$filename,$final))
    $filename = $this->file[$handle];

    //lit le contenu de l'entité  dans une chaine...
    $str = file_get_contents($filename);

    //$str doit exister
    if (empty($str)) {
      throw new Exception ("loadfile: While loading $handle, $filename does not exist or is empty.");
      return false;
    }
    //utile pour déboguage 
    if (!empty($final)){
      $ksearch = array_keys($this->varkeys);
      preg_match_all('#(?<={)([^\{\}])+(?=})#',$str,$match); //detecte bien les entites  
      echo '<pre>';    
      print_r($this->varvals);  
      echo '</pre>';  
    }
    //lance le classement de l'entité et de sa valeur
    //$str est une chaine dont la valeur est le contenu du fichier.tpl
    //crée un noeud avec cle $handle et valeur $str
    $this->setVar($handle, $str);
                                 
    return true;
  }

  /** Méthode statique pour créer un champ select à partir d'une liste PHP
	  @param $nom string nom du formulaire
	  @param $liste array($val=>$libelle)
	  @param $defaut string name d'un formulaire option "selected"
	  @param $required bool si la ligne est requise  
  */
  static function  champSelect ($nom, $liste, $defaut=NULL, $required=NULL)
  {
    $options = "";$id = $nom;
    foreach ($liste as $val => $libelle) {
      if ($val != $defaut) {
        $options .=  "<option value=\"$val\">$libelle</option>\n";
      }
      else {
         $options .= "<option value=\"$val\" selected=\"selected\">$libelle</option>\n";
      }
    }
    return "<select name=\"$nom\" id=\"$id\" class=\"form-control\" $required >\n" . $options . "</select>\n";
  }
}

