<?php 
 
/**
@class IniData() 
@brief initier la connexion à DB et charger le bon contexte.
	 
  -	charge un fichier ini situé dans vapdata/contextes/  décrivant le contexte soit : 
  		+	les interactions des tables sql du contexte entre elles 
  		+ les donnees obligatoires et facultatives de ce contexte
			+	les champs sql du contexte à visionner 
  -	fournit un tableau de ce contexte reprenant
			+ les propriétés sql des champs du contexte, enrichies 
			+	d'une regex associée
			+	d'un drapeau marqueur 'obligatoire-facultatif'
  -	met ce tableau en cache dans vapdata/nomContexte_data.ini 
  -	fourni quelques méthodes pour un classement des données par table, par obligation, etc

[vers le descripteur des contextes] (@ref  descripteursContexte.dox) 
@author marcvancraesbeck@scarlet.be
@copyright [GNU Public License](@ref licence.dox)
*/ 

include_once('UniConnect.class.php');

class IniData extends UniConnect
{
  // Les attributs de la classe:   
  protected $arrayStatuts = array();      /**< tableau des statuts */
  protected $arrayContextes = array();    /**< tableau des contextes */
  protected $bd;                          /**< ressource Mysql */
  protected $clesDataContexte = array();  /**< tableau des clés SQL (FK, PK) du contexte */
  protected $contexte;                    /**< string le nom d'un contexte précis */
  protected $dataContexte = array();      /**< tableau fourni par methode chargerContexte  */
  protected $dataOblig = array();         /**< tableau des colonnes obligtoires */
  protected $dataFacul = array();         /**< tableau des colonnes facultatives */                
  protected $dataVue = array();           /**< tableau des colonnes pour une VUE */          
  protected $dynTables = array();         /**< tableau des tables sql dynamiques selon contexte.ini */
  protected $dynPK = array();             /**< tableau des clés Primaires (PK) */
  protected $dynFK = '';                  /**< nom d'une clé étrangère (FK) */
  protected $dynFKP='';                   /**< nom d'une colonne référence pour FK */
  protected $fileTable = '';              /**< nom d'une table sql de contexte.ini  */
  protected $extraTable = array();        /**< tableau des tables 'extra' dans contexte.ini */
  protected $extraFK = array();           /**< tableau des clés FK 'extra'*/
  protected $extraFKP = array();          /**< tableau des colonnes de références liés à extraFK */
  protected $fileStatiqueTable = '';      /**< fichier cache.ini lié à une table sql statique */
  protected $fileStatiqueData = '';       /**< fichier cache.ini avec les données statiques de table */
  protected $filePassiveTable = '';       /**< fichier cache.ini lié à une table sql 'passive' */
  protected $filePassiveData = '';        /**< fichier cache.ini avec les données 'passives'  de table */
  protected $fileContexte = '';           /**< fichier.ini de configuration du contexte */ 
  protected $fileDataContexte = '';       /**< fichier cache.ini avec données calculées d'un contexte */
  protected $fileLiaisonTable = '';       /**< fichier cache.ini  lié à une table sql 'de liaison' */
  protected $haCont = '';                 /**< nom de l'antenne 'hors_antenne' */
  protected $haReg = '';                  /**< nom de la region 'hors-region' */
  protected $liaisonTable = '';           /**< nom d'une table sql de liaison */
  protected $liaisonFK = array();         /**< tableau des clés FK (clés de liaison) */ 
  protected $liaisonFKP = array();        /**< tableau :colonnes pointées par les clés  liaisonFK  */
  protected $liaisonChamps = array();     /**< tableau :colonnes supplémentaires sur table de liaison */
  protected $liaisonValeurs = '';         /**< valeur des colonnes supplémentaires sur table de liaison */
  protected $liensCles = array();         /**< tableau avec toutes les Foreign Key(FK) du contexte*/
  protected $masques = array();           /**< tableau de gestion des masques regex */
  protected $mailCont = '';               /**< mail general de contact  */                 
  protected $PPK = '';                    /**< nom de la cle Primaire Principale du contexte */  
  protected $passiveTable='';             /**< nom d'une table 'passive' */
  protected $passiveChamps = array();     /**< tableau de colonne liées à une table 'passive' */
  protected $passivePK='';                /**< nom de la cle PK de table 'passive' */
  protected $schema = array();            /**< tableau rassemblant le schema d'une table */
  protected $schemaLiaison= array();      /**< tableau rassemblant le schema d'une table 'de liaison' */
  protected $schemaPassive=array();       /**< tableau rassemblant le schema sql d'une table 'passive' */
  protected $schemaStatique=array();      /**< tableau rassemblant le schema sql d'une table 'statique'*/ 
  protected $schemaDataStatique = array();/**< tableau resultat de calculDataStatique()  */ 
  protected $schemaDataPassive=array();   /**< tableau résultat de calculDataPassive() */
  protected $statut;                      /**< statut de fonctionnement du système */
  protected $statiqueTable = '';          /**< nom d'une table déclaré 'statique'    */   
  protected $statiqueChamps = '';         /**< nom d'une colonne sql 'statique' */
  protected $statiquePK = '';             /**< string : cle PK d'une table statique */                  
  protected $statiqueValeurs = array();   /**< valeurs que peuvent prendre un champ sql 'statique' */
  protected $table = '' ;                 /**< nom d'une table sql */
   
/** Constructeur : initialise le nom du statut (facultatif), le nom du contexte(facultatif) et une instance PDO.
  
	  $this->arrayStatuts <- les 4 statuts du système  
		$this->arrayContextes <- le nom de tous les contextes 
	  Si dsn.php alors $this->bd  initie une connexion PDO via (@ref getInstance())
	  Si ($contexte et $statut) alors chargement de $this->dataContexte (@ref chargerContexte($contexte,$statut)) 
	
 	  @param $contexte  string, le nom du contexte
	  @param $statut  string, le nom du statut
*/  
  function  __construct($contexte=NULL,$statut=NULL)
  {	  
    $this->contexte = $contexte;
    $this->statut = $statut; 
    $this->arrayStatuts = array('anonym','membre','responsable','admin');
    $this->arrayContextes = array('Membre','Antenne','Region','Chat','Sponsor','News','File','Auth','Index','Help','Test','Passage','Trajet','Install'); 
    
		// C'est l'installateur TODOREF qui initie le fichier dsn.php
		if (!file_exists(DIRLIB.'dsn.php')) { 
			$this->bd = NULL; 
		}
    else { 
			$this->bd = UniConnect::getInstance(); 
		}     
		// Chargement du contexte si les paramètres sont fournis
    if ($contexte !== NULL && $statut !== NULL) {
			$this->dataContexte = $this->chargerContexte($this>contexte,$this->statut);
		}
  }
   
/** 
		@brief Chargement d'un tableau de regex pour chaque colonnes de la table sql fournie.
	  @param $table string, nom de la table MySQL a charger.
		@return array('nomColonne'=>'valeuRegex')
	  recquis : @ref libRgx.php le fichier des regex.\n
	  recquis  : @ref sqlini.dox réparti la regex ad hoc selon la colonne.\n 
  */
  private function chargerMasques($table)
  {    
    require('libRgx.php');
    $colo = $regex = $masques = $tabSql = array();
    $fileSqlIni = DIRCONTEXTE."Sql.ini";
    $tabSql = parse_ini_file($fileSqlIni,TRUE);
    switch($table) {
      case T_HUM: $sqlIni='sqlHum'; break;
      case T_CORE:; $sqlIni='sqlCore'; break;
      case T_SOC : $sqlIni='sqlTranspu'; break;
      case T_HUM_SOC: $sqlIni='sqlMobi'; break;
      case T_ANT: $sqlIni='sqlAnt'; break;
      case T_CHAT:$sqlIni='sqlChat';break;
      case T_NEWS:$sqlIni='sqlNews';break;
      case T_REG:$sqlIni='sqlRegions';break;
      case T_LOAD:$sqlIni='sqlFile';break;
      case T_MEET:$sqlIni='sqlPassage';break;
      case T_ROUTE:$sqlIni='sqlTrajet';break;
      default: throw new MyPhpException('chargermasques : table : '.$table.' inexistante');
    } 
    $colo = explode(",",$tabSql[$sqlIni]['colonnes']);
    $regex = explode(",",$tabSql[$sqlIni]['rgx']);
    foreach($colo as $cpt=>$nomCol) {
      $masques[$nomCol] = ${$regex[$cpt]};
    }
    return $masques; 
  }   

/** Enregistrer les caractéristiques sql d'une table.
   
   Retourne un tableau et un cache.ini par table basé sur la requete:"SHOW COLUMNS FROM $nom_table".\n 
	 Détection des FK grace à $this->liensCles \n
   Chaque attribut(colonne) de la table est un array avec 
		- le typage sql, 
		- la clePrimaire(PK), 
		- la longueur, 
		- sa valeur default 
   	- rajoute en sus masque=1,oblig=0, possible=0 pour attributions ultérieures.
	 
	 @param $nom_table string, le nom d'une table a charger
	 @return array     
*/  
  private function showColumns($nom_table)
  {  
    $ini = $tab = $inischema = array();   
    $x = $y = $off = 0;
    $on = -1;
    $this->table = $nom_table;  
    
    //calcul des données (mono-tables) 
    $sqlmeta = "SHOW COLUMNS FROM ".$this->table;
    $stmt = $this->bd->query($sqlmeta); 
    while ($tab = $stmt->fetch())
    {
        $drapFK = $drapExtra = 0;
        //attribution de FIELD (nom colonne)            
        $ini[$x] = "[".$tab[0]."]"; $x++;

        //attribution de la longueur colonne, extraite de Type (pour integer, char et varchar)
        if(preg_match('#(?<=[rt]\()([0-9]+)(?=\))#',$tab[1],$matches)) { $long = $matches[0];}
        elseif($tab[1] == 'date') { $long = 12; }
        elseif($tab[1] == 'timestamp') { $long = 20; }
        elseif($tab[1] == 'text') { $long = 65000; } 
        else { $long = 'non'; }
        $ini[$x] = "longueur=".$long; $x++; 
            
        //attribution du type (sans sa longueur)
        $type = preg_replace('#(?<=[rt])\([0-9]+\)#',' ',$tab[1]);
        $ini[$x] = "type=".$type; $x++;
            
        //attribution de clePrimaire et reconnaissance de la Principal Primary Key (PPK) 
        if ($tab[3] == 'PRI') { $PK  = 1; }
        else { $PK  = 0; }
        $ini[$x]='clePrimaire='.$PK; $x++;

        //detection et structuration si column est une cleSecondaires (FK),  
        if (!empty($this->liensCles)){
          foreach ($this->liensCles as $nomcle=>$ptcle){
            if ($nomcle == $tab[0]){
              $fkdyn = $ptcle;
              $drapFK = 1;
            }
          }  
        }
        $ini[$x] = 'cleSecondaire='.$drapFK; $x++;                 
        if ($drapFK) { $ini[$x] = 'cleSecondaireRelie='.$fkdyn; $x++; } 
      
        //attribution de Default: 
        if (($tab[4] === '') || ($tab[4] === NULL)) {   $def = ' ';}
        else {  $def = $tab[4]; }
        $ini[$x]='default='.$def;$x++;
         
        //preparation de oblig
        $ini[$x]='oblig=0';$x++;
        $ini[$x]='possible=0';$x++;
  
        //preparer le champ masque : une regex lié à la colonne:
        $ini[$x]='masque=1'; $x++;
    }
    //ecriture, finalisation, nettoyage  
    $iniSchema =  $this->ecriture($this->fileTable,$ini);
    $stmt = NULL;$tab = array();$ini = array();
    return $iniSchema;
  }

	/** Crée un fichier.ini par une requète sélect des valeurs des champs statiques. 

  Relie chaque ligne dans contexte:statique:valeurs, avec contexte:liaison:champs voir : [fichier de contexte Membre] (@ref  membre.dox)  
	
  @param $tableau  array le resulat de schemaTable pour la table statique 
  @param $fichier  string l'adresse ($path) du fichier a créer
	@param $table  string nom d'une table	
	@return array()
	*/
  private function calculDataStatique($table,$tableau,$fichier)
  {  
    $ini = $inischema = $sortie = array(); 
    $x = 0; 
    $colonnes = $this->statiqueChamps.','.$this->statiquePK;
    $titre = $this->statiqueChamps; 
    $cleLigne = $this->statiquePK;
    
    //si nécessaire, sérialiser [statique][valeurs] et [liaison][champ]  
    if (!empty($this->liaisonValeurs)){ $name = implode(",",$this->statiqueValeurs);}
    else { $name = NULL;}  
    if(!empty($this->liaisonChamps)){ $name2tab = implode(",",$this->liaisonChamps);} 
    else {  $name2tab = NULL;}      
    
    // requete sql et calculs 
    $sql = "SELECT  $colonnes  FROM  $table ";
    $stmt = $this->bd->query($sql);
    while ($rslt = $stmt->fetch(PDO::FETCH_OBJ)){ 
      //la valeur du titre est la valeur du nom de la colonne statique
      $valTitre = $rslt->$titre;      
			$valTitre = $this->aposentite($valTitre); 
      //creation du fichierData.ini: relier les donnees de [statique][valeurs] aux valeurs de liaison  
      //ligne 266: le chiffre magique 1 defini la cle FK qui pointe vers la PK statique 
			//TODO remplacer ce chiffre magique par des champs FKDYN et FKSTAT
			//Ceci serait une solution générale plus rationelle à l'astuce de correspondance horizontale ...
      $ini[$x] = "[".$valTitre."]" ; $x++;       
      $ini[$x] = "data=statique";$x++;
      $ini[$x] = "tableLiaison=".$this->liaisonTable;$x++;
      $ini[$x] = "cleLiaison=".$this->liaisonFK[1];$x++;    
      $ini[$x] = "valCleLiaison=".$rslt->$cleLigne;$x++;
			// Si il y a une chaine issue de [statique][valeurs], établir la colonne [liaison][champ]ad hoc
      if (!empty($name)){
        $ini[$x] = "valeurs=".$name;$x++;
        $ini[$x] = "champsLienValeur=".$name2tab;$x++;
      } 
			//ce qui suit provient de $tableau, le schema sql de la table.        
      $ini[$x] = "longueur=".$tableau[$titre]["longueur"]; $x++;
      $ini[$x] = "type=".$tableau[$titre]["type"]; $x++;
      $ini[$x] = 'clePrimaire=0'; $x++;   
      $ini[$x] = 'default='.$tableau[$titre]["default"];$x++;
      $ini[$x] = 'oblig=0';$x++;
      $ini[$x] = 'possible=1';$x++; //les champs extras sont facultatifs 
      $ini[$x] = 'masque=statique'; $x++;
    }
    $sortie = $this->ecriture($fichier,$ini);
    $stmt = NULL ; $ini = array();    
    return $sortie;
  } 
	/** methode de detection et traitement des apostrophes entitisés.
	@param $chaine string chaine à traiter
	@return $chaine modifiée ou non
	*/
	private function aposentite($chaine)
	{
		$chaine = strval($chaine);		
		if ($posApos = strpos($chaine,"&")){    //repère les apostrophes entitisées
    	$chaine = htmlspecialchars_decode($chaine,ENT_QUOTES);          
      $chaine = str_replace("'",'"\'"',$chaine);
		}
		return $chaine;
	}
	
/** Creation du fichier ini pour les 'champs passifs' selon un nom de table et un nom de fichier.
  * @param $table: string, nom d'une table 'passive'
  * @param $fichier: string, nom complet du fichier ini 
  * @return array() le fichier ini crée et parsé en memoire
*/
  private function calculDataPassive($table,$fichier)    //($table,$tableau,$fichier)
  {  
    $ligne = $column = $ini = $inischema = $sortie = array();
    $x = $y = 0; 
    $passiveline = '';
    $colonnes = implode(',',$this->passiveChamps); 
    $compt = count($this->passiveChamps);

    // requete sql et calculs:
    // boucle while : selection ligne par ligne de la table 
    // boucle for: repère le nom et la valeur des champs passifs pour chaques lignes de la table
    $sql = "SELECT DISTINCT $colonnes FROM $table ";
    $stmt = $this->bd->query($sql);
    while ($rslt = $stmt->fetch(PDO::FETCH_OBJ)){   
      for ($y = 0; $y < $compt;$y++){               
        $nomColumn = $this->passiveChamps[$y];
        $nomDeCol = $rslt->$nomColumn;
        $column[$y] = $this->aposentite($nomDeCol);
      }
      $ligne[]=$column;
    }
    
    //cree l'ensemble des lignes [Passive] du futur fichier ini
    $ini[$x] = "[Passive]" ; $x++ ; 
    foreach ($ligne as $num=>$valigne){
      $x = $x + $num;           
      for($y = 0; $y < $compt;$y++){
        if ($y == $compt-1){  $passiveline .= $valigne[$y];}
        else { $passiveline .= $valigne[$y].',';}
      }
      $ini[$x] = $num."=".$passiveline;
      $passiveline = '';
    } 

    $sortie = $this->ecriture($fichier,$ini);
    $stmt = NULL ; $ini = array();
    return $sortie;
  }   
 
   /** Concentre les opérations d'ecritures dans $fichier
  * @param $fichier: string; le nom absolu d'un fichier ini
  * @param $tableau: array(), le tableau des lignes à ecrires dans $fichier
  * @return array() rempli par parse_ini_file("$fichier",true)
  */ 
  private function ecriture($fichier,$tableau)
  {
    $iniSchema = array();
    if (! is_array($tableau)) { throw new MyPhpException('Inidata->ecriture : tableau fourni invalide'); }  
    $lockFile = dirname($fichier).DIRECTORY_SEPARATOR."lock.txt";
    if (! file_exists($lockFile)) { touch($lockFile); }
    $lockCtrl = fopen($lockFile,'r');  
    flock($lockCtrl,LOCK_EX);
    $fp = fopen("$fichier","a");      
    foreach ($tableau as $cle=>$val){ 
      fwrite($fp,$val);
      $car = "\n";
      fwrite($fp,$car);
    }
    fclose($fp);
    flock($lockCtrl,LOCK_UN);
    fclose($lockCtrl);
    //conclusions
    $iniSchema =  parse_ini_file("$fichier",TRUE);
    return $iniSchema;
  } 

  
  /** Chargement du fichier $this->fileContexte, crée un fichier .ini pour tout le contexte (multi table).
  *  @param $contexte string, nom du contexte, ici obligatoire
  *  @param $statut string, nom du statut, ici obligatoire
  * 
  * La constante DIRCONTEXTE est le path du repertoire des fichiers de  contexte
  */
  protected function chargerContexte($contexte,$statut)
  {
    //variables internes    
    $x = 0;
    $fpc = $regex = $regstat = '';
    $iniCont = $tables = $tabContexte = $iniMasques = $cles = array(); 
    
    //Le statut et le contexte ne s'improvise pas
    if (! in_array($statut, $this->arrayStatuts)) {  
			throw new MyPhpException ("Statut invalide !");
		}
    elseif (! in_array($contexte,$this->arrayContextes)) {  
			throw new MyPhpException ("Contexte invalide !");
		}
    //Initialisations de variables de la classe, parser le fichier_de_contexte.ini
    $this->statut = $statut;    
    $this->contexte = $contexte;
    $this->fileContexte = DIRCONTEXTE.$this->contexte.'.ini'; 
    if (! $tabContexte = parse_ini_file("$this->fileContexte",TRUE)){
     	throw new MyPhpException ("Le fichier de contexte est absent");
		} 
		//à ce stade, ce n'est qu'une chaine
    $this->fileDataContexte = DIRDATA.$this->contexte."_data.ini"; 
    $this->mailCont = $tabContexte["general"]["mail"];

    if (!empty($tabContexte["general"]["horsAntenne"])) { 
			$this->haCont = $tabContexte["general"]["horsAntenne"]; 
		}
    if (!empty($tabContexte["general"]["horsRegion"])) { 
			$this->haReg = $tabContexte["general"]["horsRegion"];  
		}
    //initialisation des tables dynamiques, les tables principales:    
    $this->dynTables = explode(",",$tabContexte["dynamique"]["tables"]);
    $this->dynPK = explode(",",$tabContexte["dynamique"]["PK"]); 
    $this->PPK = $this->dynPK[0]; 
    
		if (isset($tabContexte["dynamique"]["FK"])){   
      $this->dynFK = $tabContexte["dynamique"]["FK"];
      $this->dynFKP = $tabContexte["dynamique"]["FKP"];
      $this->liensCles[$this->dynFK] = $this->dynFKP;
    }
    //$tabContexte['extra'] decrit les cles FK d'une table :  
    //on remplit $this->liensCles, array("nameCle"=>"ValueCle")   
    if (!empty($tabContexte["extra"])){
      $this->extraTable = explode(",",$tabContexte["extra"]["tables"]);
      $this->extraFK = explode(",",$tabContexte["extra"]["FK"]);
      $this->extraFKP = explode(",",$tabContexte["extra"]["FKP"]);
      foreach ($this->extraFK as $num=>$cle){
        $this->liensCles[$cle] = $this->extraFKP[$num];
      }
    }    
		// si une liaison sql multi2multi est necessaire : 
		//initialisation des paramètres dits de liaison 
		// initialisation du schema sql basique des tables de'liaison' 
      if (!empty($tabContexte["liaison"])){
      $this->liaisonTable = $tabContexte["liaison"]["table"];
      $this->liaisonFK = explode(",",$tabContexte["liaison"]["FK"]);
      $this->liaisonFKP = explode(",",$tabContexte["liaison"]["FKP"]);
      if (!empty($tabContexte["liaison"]["champs"])){
        $this->liaisonChamps = explode(",",$tabContexte["liaison"]["champs"]);
        $this->liaisonValeurs = $tabContexte["liaison"]["valeurs"];
      }
      $this->fileLiaisonTable = DIRTABLE.$this->liaisonTable.'.ini';
      foreach ($this->liaisonFK as $num=>$cle){  
        $this->liensCles[$cle] = $this->liaisonFKP[$num];
      }
      if (file_exists($this->fileLiaisonTable)){ 
				$this->schemaLiaison= parse_ini_file("$this->fileLiaisonTable",TRUE); 
			}
      else { 
				$this->schemaLiaison= $this->schemaTable($this->liaisonTable); 
			}
    }    
    // initialisation des valeurs de tables et colonnes 'statiques'
    // table dynamique et table statique sont reliées par une table de liaison 
    // relation sql : multi(dynamique) to multi(statique) via table 'liaison' 
    // caculer le schema sql basique: par appel à schemaTable($this->statInTable)  
    // calculer les specificités relationnelles statiques  : appel à calculDataStatique($this->statiqueTable,$this->schemaStatique,$this->fileStatiqueData)
      
    if (!empty($tabContexte["statique"])){
      $this->statiqueTable = $tabContexte["statique"]["tables"];
      $this->statiquePK = $tabContexte["statique"]["PK"];
      if (!empty($tabContexte["statique"]["champs"])){  
				$this->statiqueChamps = $tabContexte["statique"]["champs"];
			}
      if (!empty($tabContexte["statique"]["valeurs"])){ 
				$this->statiqueValeurs = explode(",",$tabContexte["statique"]["valeurs"]);
			}
      $this->fileStatiqueTable = DIRTABLE.$this->statiqueTable.'.ini';
      $this->fileStatiqueData = DIREXTRA.$this->statiqueTable.'_stat.ini'; 
      if (file_exists($this->fileStatiqueTable)){ 
				$this->schemaStatique = parse_ini_file("$this->fileStatiqueTable",TRUE);  
			}
      else { 
				$this->schemaStatique = $this->schemaTable($this->statiqueTable);
			}
      if (file_exists($this->fileStatiqueData)){ 
				$this->schemaDataStatique = parse_ini_file("$this->fileStatiqueData",TRUE); 
			}
      else {  
				$this->schemaDataStatique = $this->calculDataStatique($this->statiqueTable,$this->schemaStatique,	$this->fileStatiqueData); 
			} 
    }  
    //Prendre en compte les données passives (la liste passive sera un tableau mis en cache)
    //relation sql: multi(dynamique) to one(passive) 
    // calculer le schéma sql basique : par appel de schemaTable($this->passiveTable)
    //calculer les spécificités 'passives': par appel de calculDataPassive($this->passiveTable,$this->filePassiveData)
    
    if (!empty($tabContexte["passive"])){
      $this->passiveTable = $tabContexte["passive"]["tables"];
			//doit inclure la PK de la table passive
      $this->passiveChamps = explode(",",$tabContexte["passive"]["champs"]); 
      $this->passivePK = $tabContexte["passive"]["PK"];
      $this->filePassiveTable = DIRTABLE.$this->passiveTable.'.ini';
      $this->filePassiveData = DIREXTRA.$this->passiveTable.'_pass.ini';
      if (file_exists($this->filePassiveTable)){ 
				$this->schemaPassive = parse_ini_file("$this->filePassiveTable",TRUE); 
			}
      else { 
				$this->schemaPassive = $this->schemaTable($this->passiveTable); 
			}
      if (file_exists($this->filePassiveData)){ 
				$this->schemaDataPassive = parse_ini_file("$this->filePassiveData",TRUE); 
			}
      else { 
				$this->schemaDataPassive = $this->calculDataPassive($this->passiveTable,$this->filePassiveData); 
			}     
    }  

    //creation du tableau des formulaires Obligatoires et Facultatifs selon les statuts et les tables dynamiques
    if ($this->statut == 'anonym'){    
      if (!empty($tabContexte["anonym"]["facul"])){ 
				$this->dataFacul = explode(",",$tabContexte["anonym"]["facul"]); 
			}
      if (!empty($tabContexte["anonym"]["oblig"])){ 
				$this->dataOblig = explode(",",$tabContexte["anonym"]["oblig"]); 
			}       
    }    
    elseif ($this->statut == 'membre'){
      if (!empty($tabContexte["membre"]["facul"])){ 
				$this->dataFacul = explode(",",$tabContexte["membre"]["facul"]); 
			}      
      if (!empty($tabContexte["membre"]["oblig"])){ 
				$this->dataOblig = explode(",",$tabContexte["membre"]["oblig"]); 
			}
      if (!empty($tabContexte["membre"]["vue"])) { 
				$this->dataVue = explode(",",$tabContexte["membre"]["vue"]); 
			}
    }
    elseif ($this->statut == 'responsable'){
      if (!empty($tabContexte["responsable"]["facul"])){ 
				$this->dataFacul = explode(",",$tabContexte["responsable"]["facul"]); 
			}
      if (!empty($tabContexte["responsable"]["oblig"])){ 
				$this->dataOblig = explode(",",$tabContexte["responsable"]["oblig"]); 
			}
      if (!empty($tabContexte["responsable"]["vue"])) { 
				$this->dataVue = explode(",",$tabContexte["responsable"]["vue"]); 
			}
    }  
    elseif ($this->statut == 'admin'){
      if (!empty($tabContexte["admin"]["facul"])){ 
				$this->dataFacul = explode(",",$tabContexte["admin"]["facul"]); 
			}
      if (!empty($tabContexte["admin"]["oblig"])){ 
				$this->dataOblig = explode(",",$tabContexte["admin"]["oblig"]); 
			}
      if (!empty($tabContexte["admin"]["vue"])) { 
				$this->dataVue = explode(",",$tabContexte["admin"]["vue"]); 
			}
    }
    //charge le fichier _data.ini ad hoc  ou calcul du schema de contexte pour chargement à la fin
    if ( file_exists($this->fileDataContexte)){     
    	$this->dataContexte = parse_ini_file("$this->fileDataContexte",TRUE);
		}
    else { 
      //appel de methode schemaTable pour chaque table dynamique        
      foreach ($this->dynTables as $idx=>$nomTable){
        $this->table = $nomTable;          
        $this->schema = $this->schemaTable($this->table);
        foreach ($this->schema as $nom => $column){
          $iniCont[$x] = "[".$nom."]";$x++;        
          foreach ($column as $cle => $val){  
            $iniCont[$x] = $cle."=".$val;$x++;
          }
        }
      }
      //Ecriture du schema de contexte (multi tables) et chargement du contexte dans $this->dataContexte
      $this->dataContexte = $this->ecriture($this->fileDataContexte,$iniCont);
      //nettoyages
      unset($iniCont);
    }
    //Ajout des donnees statiques à $this->dataContexte et completion du tableau $this->dataFacul
    if (!empty($this->schemaDataStatique)){
      foreach ($this->schemaDataStatique as $nomextra=>$valextra){
        $this->dataContexte[$nomextra] = $valextra;  
      }                     
    }
    //detection de oblig et facul répartie à toutes les donnees  
    $this->clesDataContexte = array_keys($this->dataContexte);
    foreach($this->clesDataContexte as $x=>$nom){
      foreach($this->dataOblig as $key=>$val){
        if ($val == $nom){  $this->dataContexte[$nom]["oblig"] = 1;}
      }
      foreach ($this->dataFacul as $key=>$val){  
        if ($val == $nom){  $this->dataContexte[$nom]['possible'] = 1;}
      }
    }        
    //rajout des masques au schema de contexte (toujours après ecriture du .ini) 
    foreach ($this->dynTables as $nomTable){ 
      //parcour des tables requises et chargement des masques pour chaque table
      //Le nom du masque doit correspondre au nom d'une colonne de la table !!      
      $this->table = $nomTable;
      if (! $this->masques = $this->chargerMasques($this->table)){
       	throw new MyPhpException("Impossible de charger le tableau des masques");
			}
      
      //creation du tableau de tous les masques (multi table)     
      foreach ($this->masques as $nomasque=>$masq){
        $iniMasques[$nomasque] = $masq;
			}
    }
    // parcours du tableau produit par la lecture de $this->fileDataContexte
    // association de l'attribut et de son masque regex
    //traitement du masque pour donnees statiques (selon les mots du contextes statique)
    //le calcul du masque statique se base sur la valeur statique attendue
    foreach ($this->dataContexte as $nom => $options){    
      if ($options["masque"] == 1) { $regex = "'".$iniMasques[$nom]."'"; }     
      elseif ($options['masque'] == 'statique' && !empty($this->statiqueValeurs)){
        $z = (count($this->statiqueValeurs)-1);
        foreach($this->statiqueValeurs as $id=>$mots){  
          if ($id < $z){ $regstat .= $mots."|";}
          else {$regstat .= $mots;}
        }
        $regstat = "(".$regstat.")";
        $regex = "'".$regstat."'";
      }  
      elseif ($options['masque'] == 'statique' && empty($this->statiqueValeurs)){
        if ($this->contexte == 'News') { $mask =  "[^-A-Za-zâàäéêèôöûùîïç\s\'_,.]"; }
        elseif ($this->contexte == 'Trajet') { $mask = '[^-a-zA-Z0-9àâäéèêûôùïîç\s:;,_.\\\']'; }       
        $regex = "'".$mask."'";  
      } 
      else { $regex = 0;}   
      $this->dataContexte[$nom]['masque']= $regex; 
      $regstat = '';
    }
    unset($iniMasques);  

    //rajout des donnees passives si présentes
    if (!empty($this->schemaDataPassive)){
      foreach ($this->schemaDataPassive as $nompassif=>$valpassif){
        $this->dataContexte[$nompassif]=$valpassif;
      }
    }
    $this->dataContexte['general']['mail'] = $this->mailCont;
    $this->dataContexte['general']['horsAntenne'] = $this->haCont;
    $this->dataContexte['general']['horsRegion'] = $this->haReg;
    return $this->dataContexte;
  }
  /**
  * Comment recharger un contexte?
  * On efface les données produites par chargerContexte() et on rappelle chargerContexte()
  */ 
  protected function rechargerContexte($contexte,$statut)
  {
    $tabpath = array(DIRDATA,DIRTABLE,DIREXTRA);
    foreach ($tabpath as $rep){ 
      if (is_dir($rep)){	
			  chdir($rep);
        if ($rep == DIRDATA){
			    $fichiers = glob('./*_data.ini');
        }
        else  { $fichiers = glob('./*.ini');}
			  foreach ($fichiers as $nom){	
          unlink($nom);
        }
			  chdir(BASEDIR);				
		  }
    }
    $this->dataContexte = $this->chargerContexte($contexte,$statut);
    return $this->dataContexte;
  }  
  /** Execute la méthode privee showColumns ou lit le fichier .ini
  *  @param  $nom_table string le nom de la table a charger
  *  la constante DIRTABLE doit exister et indiquer le path des fichiers $nom_table.ini
  *  @return array un tableau des metaData de : $this->table
  */
  protected function schemaTable($nom_table)
  {      
    $regex = '';$tabIniT = array();
    $this->table = $nom_table;
    $this->fileTable =  DIRTABLE.$this->table.'.ini';       
    if (!file_exists($this->fileTable)){  
			$tabIniT = $this->showColumns($this->table); 
		}
    else { 
			$tabIniT = parse_ini_file("$this->fileTable",TRUE); 
		} 
    return $tabIniT;
  } 
   
  /** Traitement des échappement automatiques
	*
  * Cette fonction supprime tout echappement automatique des donnees http
  * dans un tableau de dimmension quelconque. Agit ou fait un appel récursif.
  * Sources: http://www.lamsade.dauphine.fr/rigaux/mysqlphp/?page=code
	* @param $tableau array le tableau à traiter
  */
  protected function magicNormHTTP($tableau)
  {
    if (! is_array($tableau)) {  $tableau = stripslashes($tableau); }
    else {	  
      foreach($tableau as $key => $valeur) { 
	  	  if (! is_array($valeur))  { $tableau[$key] = stripslashes($valeur); }
		    else 	{ $tableau[$key] = $this->magicNormHttp($valeur); }
	    }
    }
    return $tableau;
  }
	/** Les donnees oblig pour une table orientation formulaire client.
	*
	* @param $nom_table string nom de la table sql
	* @return array $dataTableOblig
	*/
  public function attributsOblig($nom_table)
  {
    $clesTable = $schema = $dataTableOblig = array();    
    $schema = $this->schemaTable($nom_table);
    $clesTable = array_keys($schema);
    if (!empty($this->dataOblig)){
      $dataTableOblig = array_intersect($clesTable,$this->dataOblig);
    }
    return $dataTableOblig;  
  }
  
  /** Les donnes facultatives pour une table selon contexte dynamique, orientation formulaire (client).
  * Si il s'agit d'une table de liaison, mettre les clés du tableau du schema  statique   
	*
	* @param $nom_table string nom de la table sql
	* @return array $dataTableFacul  
	*/
   public function attributsFacul($nom_table)
   {
    $clesTable = $schema = $dataTableFacul = array();     
    if ($nom_table == $this->liaisonTable){  
      if (!empty($this->schemaStatique)){
        $dataTableFacul = array_keys($this->schemaDataStatique);
      }    
    }      
    else {    
      $schema = $this->schemaTable($nom_table);
      $clesTable = array_keys($schema);
      $dataTableFacul = array_intersect($clesTable,$this->dataFacul);  
    }    
    return $dataTableFacul;
  }
  /**l'ensemble des donnees requises et facul pour une table.
	*
  *  orientation database, les attributs viennent tous de la base  
	*	 @param $nom_table string nom de la table sql  
	*  @return array $dataTableContexte
	*/
  public function attributsTable($nom_table)
  {  
    $attrTableF = $attrTableO = $dataTableContexte = array();  
     
    //Si c'est une table 'de liaison':  inclure les cles FK de liaison dans le tableau oblig
    if ($nom_table == $this->liaisonTable) {  
      $attrTableO = array_merge($this->liaisonFK,$attrTableO);
      if (!empty($this->liaisonChamps)){
        $attrTableF = array_merge($this->liaisonChamps,$attrTableF);
      } 
    }
    else {  
      $attrTableO = $this->attributsOblig($nom_table);
      $attrTableF = $this->attributsFacul($nom_table); 
    } 
    $dataTableContexte = array_merge($attrTableO,$attrTableF);
    return $dataTableContexte;
  } 
  /**
  * charge  les données facultatives et obligatoires avec leurs proprietes completes pour une table d'un contexte
  * pour les données 'statiques', charge le schema des données statiques
  * @param $nom_table string la table a charger
  * @param $pk  bool indique si la cle primaire doit etre rajoutee ou pas
  * retourne un tableau
  */
  public function chargerTable($nom_table,$pk = NULL)
  {
    $schema = $dataTableOblig = $dataTableCharge = $dataTable = $dataTableContexte = array();    
    if ($nom_table == $this->liaisonTable){  $dataTableCharge = $this->schemaDataStatique; }         
    else {          
      $schema = $this->schemaTable($nom_table);      
      $dataTableContexte = $this->attributsTable($nom_table);
      foreach ($dataTableContexte as $attr){
        $dataTableCharge[$attr] = $this->dataContexte[$attr];
      }
      if (!empty($pk)){
        foreach ($schema as $nom=>$option){
          if ($option['clePrimaire']){ 
            $dataTableCharge[$nom] = $option;}
        }
      }
      $dataTableContexte = array();
    }    
    return $dataTableCharge;
  }
  /** Retourne le nom des tables dynamiques du contexte en cours.
	@param $dynonly bool si absent et présence de tables de liaison, celles-ci seront rajoutées  
	@return array 
	*/
  public function getTables($dynonly=NULL)
  {
    $wagon = array();
    foreach ($this->dynTables as $tabula){
      $wagon[] = $tabula;
    }
    if ((!empty($this->liaisonTable)) && (empty($dynonly))) {
        array_push($wagon,$this->liaisonTable);
    }
    return $wagon;
  }
  /** Retourne le nom de la table passive du contexte ou NULL
  */
  public function getPassiveTable()
  {
    $pass = '';    
    if (!empty($this->passiveTable)){ $pass =  $this->passiveTable; }
    return $pass; 
  }
  /**
  * @return array() avec le nom des tables extra
  */
  public function getExtraTable()
  {
    return $this->extraTable();    
  }
  /*
  * retourne le nom de l'attribut PPK (Principal Primary Key) du contexte
  * utilisé par classe filtreData
  */
  public function getPPK()
  {
    return $this->PPK;
  }
/**
* Retourne un array() de tous les attributs attendus d'un contexte:dynamiques et statiques
* Fait la meme chose que attributsTable() mais pour tout un contexte
*/
  public function getListeAttr()
  {
    $liste = array();   
    $liste = array_merge($this->dataOblig,$this->dataFacul);
    if (!empty($this->schemaDataStatique)){
      $champStatiques = array_keys($this->schemaDataStatique);
      foreach ($champStatiques as $champ){
        array_push($liste,$champ);
      }
    }
    return $liste;
  }
  /**
  * Tous les attributs facultatifs du contexte
  */
  public function getFaculFull()
  {
    $liste = $this->dataFacul;
    if (! empty($this->schemaDataStatique)){
      $champStatiques = array_keys($this->schemaDataStatique);
      foreach ($champStatiques as $champ){
        array_push($liste,$champ);
      }
    }
    return $liste;
  }
  /**
  * Tous les champs obligatoires du contexte
  */
  public function getOblig()
  {
    return $this->dataOblig;
  }
  
}  
  
