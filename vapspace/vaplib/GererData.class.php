 <?php
 
/**
@class GererData :  
@brief traitement en database des actions sql (insertion, mise à jour, suppression, sélection) des données en database

[basé sur la notion de contexte traité par sa classe parente IniData] (@ref IniData)
[classe appellée par le controleur principal] [@ref Controleur]

@author marcvancraesbeck@scarlet.be
@copyright [GNU Public License](@ref licence.dox)
*/
include_once('IniData.class.php');

class GererData extends IniData
{
  //Déclaration attributs 
  protected $attributsAttendus = array();	/**<  pour appeller la méthode parente attributsTable */
  protected $champUtile = '';	/**< string, le nom de la colonne de liaison employée */
  protected $champInutile = '';	/**< string, le nom de la colonne de liaison non employée */
  protected $classement = array();	/**< pour classer données, utilisé par inscrire, mettreajour */
  protected $cleStat = '';	/**< le nom de la FK (de liaison) qui pointe la PK de table statique'*/
	protected $lastId ;	/**< integer, mémoriser le dernière PK introduite*/  
  protected $nomClePK; /**< nom de la Principal Primary Key (PPK) */
  protected $nomCleFK;	/**< nom d'une cle FK pointant vers la PPK */
  protected $nomContexte; /**< nom du contexte */ 
  protected $ofk;	/**< other foreign key, nom de cle FK qui ne pointe pas sur une PK de table statique*/
  protected $tableLi;	/**< nom de la table de liaison */  
  protected $valChampUtile = '';	/**< string, valeur employée par la colonne de liaison */
  protected $valChampinutile = '';  /**< string, valeur attribuée à la colonne de liaison non utilisée */
  protected $valofk;	/**< valeur de la other foreign key (ofk) */
  protected $valPPK;  /**< valeur de la PPK */

/** construct réalise quelques initiations puis appelle la méthode parent.  
  */
  public function __construct($contexte=NULL,$statut=NULL)
  {
    //Initiation
    $this->tableLi = $this->nomClePK = $this->nomCleFK = $this->ofk = $this->valofk = '';
    $this->champUtile = $this->valChampUtile = $this->champInutile = $this->valChampInutile = NULL;   
    $this->attributsAttendus = $this->classement = array();  

    //appel le constructeur parent en surchargeant les parametres
    if ($contexte != NULL && $statut != NULL) {
      parent::__construct($contexte,$statut);    
    } 
    else { parent::__construct(); }  
  }
  
  //-------------ENCAPSULATION de IniData pour les controleurs-------------------//
  
	/** Charger le contexte selon un contexte et un statut, 
  @param $contexte	string obligatoire
  @param $statut	string obligatoire
	@return array [parent::chargerContexte($contexte,$statut)] (@ref chargerContexte())
  */
  public function getContexte($contexte,$statut)
  {
    return $this->chargerContexte($contexte,$statut);
  }
  /** Charger le tableau du contexte et du statut en cours (pas de contexte ni de statut fourni) 
  @return array calculé dans parent::chargerContexte($contexte_encours,$statut_encours)  
  */  
  public function getDataContexte()
  {
    return $this->dataContexte;  
  }
  /** Appel de parent::rechargerContexte($contexte,$statut). 
	@return array	$this->dataContexte (en cours)
  */ 
  public function resetContexte($contexte,$statut){
    try {
      $tabNewContexte = array();  
      $tabNewContexte = parent::rechargerContexte($contexte,$statut);
      return $tabNewContexte;
    }
    catch(MyPhpException $e) {
      $msg = $e->getMessage();
      $e->alerte($msg);  
    }
  }
  /** Appel parent::$this->liensCles
  @return $this->liensCles : array() calculé par parent::chargerContexte($contexte,$statut)
  */
  public function getLiensCles()
  {
    return $this->liensCles;
  }
  /** Tableau liste des contextes
  @return array liste de tous les contextes
  */  
  public function getArrayContextes()
  {
    return $this->arrayContextes;
  }
  /** Appel parent::getTables()
  @return array	parent::getTables()
  */
  public function getTables($dyn=NULL)  
  {
    return parent::getTables($dyn);
  }
  /** Appel de la liste des tables dynamiques du contexte
  @return array	parent::getTables(1)
  */
  public function getDynTables()
  {
    $dynamique = 1;    
    return parent::getTables($dynamique);
  }
  /** Appel parent::getPassiveTable()
  @return string  
  */
  public function getPassiveTable()
  {
    $passive = parent::getPassiveTable();
    return $passive;
  }
  /** Appel du nom de la table de liaison selon contexte et statut en cours
  * @return string $this->liaisonTable 
  */
  public function getLiaisonTable()
  {
    return $this->liaisonTable;
  }
   /** Appel des noms des tables extra du contexte et statut en cours. 
  * @return array avec le nom des tables extra
  */
  public function getExtraTable()
  {
    $extraTable = parent::getExtraTable();
    return $extraTable;
  }
  /** Le nom de la premiere PK de la premiere table dynamique d'un contexte
  @return string  
  */
  public function getPPK()  
  {
     return $this->PPK;
  }
  /** Appel du tableau des clés FK classées [extra] dans le contexte.ini
  * @return array un tableau des FK classées dans [extra] uniquement 
  */
  public function getExtraFK()
  {
    return $this->extraFK;
  }
  /** Une instance de l'objet de connexion à la Base de Donnée (BD)
  * @return l'objet de la connexion en bd
  */
  public function getCnx()   
  {
    return $this->bd;
  }
  /** la liste des attributs obligatoires et facultatifs liés au contexte
  @return array(), 
  */
  public function getListeAttr()
  {
    $liste = parent::getListeAttr();
    return $liste;
  }
  /** la liste des attributs obligatoires au contexte
  @return array(), 
  */
  public function getOblig()
  {
    $liste = parent::getOblig();
    return $liste;
  }
  /** la liste de tous les attributs Facul possibles (les statiques compris)
  @return array 
  */
  public function getFaculFull()
  {
    $liste = parent::getFaculFull();
    return $liste;
  }
  /** la liste des attributs facultatifs des tables dynamiques
  @return array
  */
  public function getFaculDyn()
  {
    $liste = $this->dataFacul;
    return $liste;
  }
  /** La liste des valeurs [statiques] calculé par parent::chargerContexte($contexte,$statut)
  @return array $this->statiqueValeurs, 
  */
  public function getStatiqueValeurs()
  {
    return $this->statiqueValeurs;
  }
  /** La tableau calculé par parent::chargerContexte() sur base des données statiques du fichier du contexte.ini
  * @return array $this->schemaDataStatique
  */
  public function getSchemaDataStatique()
  {
    return $this->schemaDataStatique;
  }
  /** La tableau calculé par parent::chargerContexte() sur base des données passives du fichier de contexte.ini 
  * @return array $this->schemaDataPassive,  
  */
  public function getSchemaDataPassive()
  {
    return $this->schemaDataPassive;
  }
  /** la liste des champs pour organiser la vue d'un contexte (selon son statut)
  @return array $this->dataVue
  */
  public function getVue()
  {
     return $this->dataVue;
  }
  /** stripslashes les données extraites de la database
  */
  public function getmaGpc($tableau)
  {
    $newTab = $this->magicNormHTTP($tableau);
    return $newTab;
  }
  /** Charger le schéma d'une table
  * @param	$table string le nom d'une table sql
  * @param	$flag boolen
  * @return array
  */
  public function getDataTable($table,$flag=NULL)
  {
    $data = array();    
    if (!empty($flag)){
      $data = parent::chargerTable($table,'1');
    }
    else { $data = parent::chargerTable($table);}
    return $data;
  }
  /** Récupérer les variables fixes Hors-Antenne,Hors-Region et $mail
  */
  public function getGeneral($type)
  { 
    $dataCont = NULL ;    
    if (!empty($this->dataContexte['general'][$type])) 
    { $dataCont = $this->dataContexte['general'][$type]; }
    return $dataCont;
  }
	 
	//--------------protected---------------------//

	/**converti les elements html en entites html

	Il n'y a pas de conversion dans le contexte news (seulement accessible aux statuts admins et responsable) 
  @param $chaine string en entrée
  @return string une chaine convertie
  */
  protected function dataEntity($chaine)
  {
    if ($this->contexte == 'News'){ $propre = $chaine;}
    else { 
      $propre = htmlspecialchars($chaine,ENT_QUOTES,"ISO-8859-1");
      $propre = trim($propre);
    }
    return $propre;
  }
	 /**crée et retourne un tableau par ligne de Table 
  
	- cles du tableau : les noms des champs oblig et facul de la table du contexte en parametre 
  - valeurs du tableau : leurs valeurs PRISES DANS LA DATABASE
  @param $valcle integer  valeur d'une cle sql (PK ou FK) la FK du contexte sera selectionnée en premier
  @param $table string nom de la table oé preparer les donnees
  */
  protected function preparation($valcle,$table) 
  {    
    $x = 0;  $tableBaseCont = array(); $idx = 0; $wagonSql = ' ';        
    $this->table = strval($table); 
    $this->attributsAttendus = array(); $testkeys = NULL;     
    $this->schema = $this->schemaTable($this->table);
    //Recherche du nom des cles FK ou PK qui représentent la ligne à preparer 
    //$valcle represente-t-il la valeur d'une cle etrangére d'une table?     
    foreach ($this->schema as $champ=>$option){
      if (($option["cleSecondaire"]) && ($option["cleSecondaireRelie"] == $this->PPK)){
        $this->nomClePK = $champ;
        $testkeys = 1;
      }
    }
    if (empty($testkeys)){
      foreach ($this->schema as $champ=>$option){ 
        if ($option['clePrimaire']) { 
          $this->nomClePK = $champ; 
          $testkeys = 1;
        }
      }
    }
    if (empty($testkeys)){ throw new MyPhpException("La table: ".$this->table." n'a pas de clé primaire?");}
    //construction et execution de la requete sql de selection selon la clé (PK ou FK)  
    $ptmq = " = ? ";  
    $whereSql = $this->nomClePK.$ptmq; 
    $fromSql = $this->table;  
    //Recherche des attributs attendus dans la base de donnée, 
    $this->attributsAttendus  = $this->attributsTable($this->table);    
    $nbr = count($this->attributsAttendus);      
		//Boucle sur les attributs oblig et facul
    foreach ($this->attributsAttendus as $nomVal){    
      ($idx < ($nbr-1))?  $Sep = ", ": $Sep = " "; 
      $wagonSql .= $nomVal.$Sep;
      $idx++; 
    }
    $sql = "SELECT $wagonSql FROM $fromSql WHERE $whereSql ";
    $stmt = $this->bd->prepare($sql);
    $stmt->execute(array($valcle));
    
    //le classement des donnees doit etre différent selon que il s'agit d'une table de 'liaison' ou pas 
    if ($this->table == $this->liaisonTable){
      while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)){
        $tableBaseCont[$x] = $ligne;
        $x++;
      }
    }
    else {
      while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)){
        $tableBaseCont = $ligne;
      }
    }
    $stmt = NULL;
    return $tableBaseCont;   
  }
   /** Une methode qui classe les DONNEES CLIENTES (prealablement filtrees par une action du controleur) par TABLE
    
  Basée sur la methode IniData->attributsTable() 
  - La boucle foreach 1 : Boucle sur toutes les tables dynamiques du contexte
    + sous_boucle 1: Boucle sur les attributs oblig et facul
    + sous_boucle 2: Recherche d'une cle FK si semblable é $this->PPK  et non listée dans attributsAttendus
  - La boucle foreach 2 : Boucle  sur toutes les donnees  du contexte à la recherche de donnees 'Statiques'      

  Si le contexte.ini inclus une structure statique:
  - sous_boucle 2 sur le tableau fourni; 
    + detection données statique dans tableau client 
    + appel de chargeDataStat() avec les donnees ou pas de données 
  
  Certains attributsAttendus sont facultatifs...tres important ici...pour de la souplesse
  c'est la couche 'controle" qui verifiera la presence obligatoire de certaines donnees      
  
  Exemple de la structure retournée (array)
  - Si data 'dynamiques': $wagon[$this->table] = array("nom"=>"valeurcliente")
  - Si inclus data 'liaison' (exemple contexte Membre.ini): $wagon[$this->tableLiaison][$int] = array('lienhum'=>'idhum,'lientrans'=>12,'utilisation'=>'oui','abonne'=>'oui') 
  
  @return array    
  @param $tableau array() fourni par le client
  @param $FKey string si le nom d'une clé de ligne est fournie, on est en mode 'update'
  */
  protected function dataClasse($tableau,$FKey=NULL)
  {
    $drapstat = 0;  $clestatiques = $wagon = array();
    if (!empty($FKey)) { $adhoCle = $FKey ; }
    else { $adhoCle = $this->PPK ; }     
    foreach ($this->dynTables as $nom){         
      $this->table = $nom; 
      $this->schema = $this->schemaTable($this->table);    
      $this->attributsAttendus  = $this->attributsTable($this->table);    
      foreach ($this->attributsAttendus as $nomVal){    
        if (isset($tableau[$nomVal]) && $tableau[$nomVal] === 0) {
          $wagon[$this->table][$nomVal] = $tableau[$nomVal];
        }        
        elseif (!empty($tableau[$nomVal])) {        
          $valeurPropre =  $this->dataEntity($tableau[$nomVal]);          
          $wagon[$this->table][$nomVal] = $valeurPropre;
        }
      }
      foreach ($this->schema as $champ=>$option){
        if (($option["cleSecondaire"]) && ($option["cleSecondaireRelie"] == $adhoCle)){
          //valeur symbolique(son nom) maintenant          
          $wagon[$this->table][$champ] = $option["cleSecondaireRelie"];  
        }
      }
    }
    foreach ($this->dataContexte as $cont=>$opt) { 
      if (!empty($opt['data'])) { $drapstat++ ; }
    }
    if (!empty($drapstat)){
      $this->table = $this->liaisonTable;
      $this->cleSchemaDataStat = array_keys($this->schemaDataStatique);
      $cpt = 0;
      foreach ($tableau as $stat=>$valeur){  
        if (in_array($stat,$this->cleSchemaDataStat)){    
          $dataStatique[$cpt] = $this->chargeDataStat($stat,$valeur);
          $cpt++;
        }
      }        
      if (empty($cpt)) { $dataStatique[0] = $this->chargeDataStat();} 
      foreach ($dataStatique as $id=>$ligne){    
        $wagon[$this->table][$id] = $ligne;
      } 
    }
    return $wagon;
  }

  /**Cette methode classe des valeurs clients 'statiques' perçues pour un traitement ad hoc en table de liaison
    
  @param $nom string nom d'une valeur statique sélectionnée par GererData->dataClasse() 
  @param $valeur string la valeur 'statique' de $nom 
  @return  array() 
  - avec comme clés : un nom des colonnes parmis FK et 'champs spécifiques' de la table de liaison 
  - avec comme valeurs : la valeur de ces colonnes pour la table de liaison (sauf pour la cle FK vers PPK)
 
  Exemple (contexte Membre) : 
  - Si la méthode reçoit  : 
    + $nom = 'STIB'; 
    + $valeur ='ouiavec';  
  - Il en sort la structure ad hoc pour insertion ultérieure en table de liaison sql: 
    + array('lienhum'=>'idhum,'lientrans'=>12,'utilisation'=>'oui','abonne'=>'oui') 
  
  C'est Controleur.class.php::constructEntity() qui prend la main
  */
  protected function chargeDataStat($nom=NULL,$valeur=NULL)
  {
    $tableaustat = array(); $laclefk = array();
    //aucuns choix statique mais le contexte statique existe bien                  
    if (!$nom || !$valeur) { 
      //Parcourir le tableau des cles etrangéres du contexte   
      foreach ($this->liensCles as $name=>$val){  
        //Détecter les cles FK de $this->liensCles presentent dans $this->schemaLiaison        
        if (isset($this->schemaLiaison[$name])){
          //Détection du nom de la FK qui pointe vers le PPK du contexte 
          if ($this->schemaLiaison[$name]["cleSecondaireRelie"] == $this->PPK){ 
            $tableaustat[$name] = $val;  
          }
          else {  
          //Une valeur par default du champ ad hoc doit exister pour la cle qui pointe pas sur PPK       
            $tableaustat[$name] = $this->schemaLiaison[$name]['default']; 
          }
        }      
      }
      //Si des champs spécifiques é la table de liaison existent 
      if (! empty($this->liaisonChamps)) {
        foreach ($this->liaisonChamps as $champ){ 
          $tableaustat[$champ] = $this->schemaLiaison[$champ]['default'];  
        }
      }
    }
    else {          
      //detecter la table de liaison, le nom et la valeur de la FK de la table de liaison 
      //qui pointent vers les valeurs 'Statiques'  
      $this->tableLi = $this->schemaDataStatique[$nom]['tableLiaison'];            
      $this->cleStat = $this->schemaDataStatique[$nom]['cleLiaison'];          
      $this->valCleStat = $this->schemaDataStatique[$nom]['valCleLiaison'];         
      //detecter le nom de la FK de liaison qui pointe sur la PPK         
      foreach ($this->liaisonFK as $num=>$cle){
        if ($cle != $this->cleStat){ 
          $this->ofk = $cle;
          $this->valofk = $this->liaisonFKP[$num];  //ici, rien que le 'nom'de la cle 
        }
      }
      $tableaustat[$this->cleStat] = $this->valCleStat; //la cle FK statique et sa valeur reelle
      $tableaustat[$this->ofk] = $this->valofk;         //la cle PPK et le nom de la PPK en valeur
      
      //relie 'valeur'(statique) fournie en paramétre aux champs de 'liaison' sur la table de liaison :
      //Une 'valeur statique' établi une relation avec une valeur champ 'specifique' sur la table de liaison  
      if (!empty($this->statiqueValeurs)) {         
        foreach ($this->statiqueValeurs as $idx=>$mot){   
          if ($mot == $valeur){ 
            //$this->champUtile <- le nom ad hoc du champ de liaison
            //$this->valChampUtile <- la valeur de 'liaison' ad hoc (unique pour le contexte membre)
            $this->champUtile = $this->liaisonChamps[$idx];  
            $this->valChampUtile = $this->liaisonValeurs;
            $idxchamp = $idx;
          } 
        }
        //detection du champ specifique de table de liaison  non choisi en parametre et de sa valeur  
        switch($idxchamp){ 
          case 0:        
            $this->champInutile = $this->liaisonChamps[1];
            $this->valChampInutile = $this->schemaLiaison[$this->champInutile]['default'];
            break;
          case 1: 
            $this->champInutile = $this->liaisonChamps[0];
            $this->valChampInutile = $this->liaisonValeurs; //ici: utilisation = 'oui' et abonne = 'oui'
            break; 
        }
        $tableaustat[$this->champUtile] = $this->valChampUtile;
        $tableaustat[$this->champInutile] = $this->valChampInutile;      
      }
    }
    return $tableaustat;
  }
}
