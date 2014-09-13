<?php
 
/**
@class GererData :  
@brief traiter les actions entre les données clientes et la database.

[basé sur la notion de contexte traité par sa classe parente IniData] (@ref IniData)

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
  protected $cleStat = '';	/**< le nom de la FK (de liaison) qui pointe sur la PK de table statique'*/  
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
  /** La tableau calculé par parent::chargerContexte() sur base de contexte.ini[statique]
  * @return array $this->schemaDataStatique
  */
  public function getSchemaDataStatique()
  {
    return $this->schemaDataStatique;
  }
  /** La tableau calculé par parent::chargerContexte() sur base de contexte.ini[passive] 
  * @return array $this->schemaDataPassive,  
  */
  public function getSchemaDataPassive()
  {
    return $this->schemaDataPassive;
  }
  /** la liste des champs pour organiser la vue d'un contexte
  @return array $this->dataVue
  TODO: inclure un parametre statut pour  selectionner une vue selon un statut, 
  modifier alors $this->dataVue en $this->dataVue['membre'] p.ex 
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
  * @param	$flag, bool
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
  /**
  * dataEntity() connerti les elements html en entites html
  * @param $chaine : string en entrée
  * @return string, une chaine convertie
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
  /**
  * preparation()  crée et retourne un tableau par Table avec 
  * - comme cles : les noms des champs oblig et facul de la table du contexte en parametre 
  * - comme valeur: leurs valeurs PRISES DANS LA DATABASE
  * @param $valcle, integer  est une FK (propre au contexte) ou un PK mais la FK du contexte sera selectionnée en premier
  * @param $table: string, nom de la table oé preparer les donnees
  */
  protected function preparation($valcle,$table) 
  {    
    $x = 0;  $tableBaseCont = array(); $idx = 0; $wagonSql = ' ';        
    $this->table = strval($table); 
    $this->attributsAttendus = array(); $testkeys = NULL;     
    $this->schema = $this->schemaTable($this->table);
    //Recherche du nom des cles FK ou PK qui représentent la ligne é preparer 
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
    //Recherche des attributs attendus dans la base de donnée, l'appel de parent::attributsTable() est justifié
    $this->attributsAttendus  = $this->attributsTable($this->table);    
    $nbr = count($this->attributsAttendus);      
    foreach ($this->attributsAttendus as $nomVal){    //Boucle sur les attributs oblig et facul
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
  /**
  * chargeDataStat()
  * recoit en parametres : nom(string) et valeur($string) 
  * 'nom' est le nom d'une valeur statique selectionnee precedemment par dataClasse()
  * valeur est la valeur 'statique' de 'nom'
  * retourne un array() 
  * avec comme clés : un nom des colonnes parmis FK et 'champs spécifiques' de la table de liaison 
  * avec comme valeurs : la valeur de ces colonnes pour la table de liaison (sauf pour la cle FK vers PPK)
  * Cette methode fait donc la relation entre des valeurs statiques et 
  * les valeurs ad hoc différentes mais liées sur la table de liaison
  * exemple : 
  * entree parametre statique : 'STIB' 'ouiavec',  
  * sortie array('lienhum'=>'idhum,'lientrans'=>12,'utilisation'=>'oui','abonne'=>'oui') 
  * C'est Controleur.class.php::constructEntity() qui prend la main
  */
  protected function chargeDataStat($nom=NULL,$valeur=NULL)
  {
    $tableaustat = array(); $laclefk = array();
    if (!$nom || !$valeur) { //aucuns choix statique mais le contexte statique existe bien            
      //Parcourir le tableau des cles etrangéres du contexte      
      foreach ($this->liensCles as $name=>$val){  
        //Détecter les cles FK de $this->liensCles presentent dans $this->schemaLiaison        
        if (isset($this->schemaLiaison[$name])){
          //Détection du nom de la FK qui pointe vers le PPK du contexte 
          if ($this->schemaLiaison[$name]["cleSecondaireRelie"] == $this->PPK){ 
            $tableaustat[$name] = $val;  
          }
          else {  //Une valeur par default du champ ad hoc doit exister pour la cle qui pointe pas sur PPK       
            $tableaustat[$name] = $this->schemaLiaison[$name]['default']; 
          }
        }      
      }
      if (! empty($this->liaisonChamps)) { //Si des champs spécifiques é la table de liaison existent 
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
  /**
  * Une methode qui classe les DONNEES CLIENTES (prealablement filtrees par une action du controleur) par TABLE
  * methodes attributsTable()  -> IniData
  * boucle foreach 1 : Boucle sur toutes les tables dynamiques du contexte
  * sous_boucle foreach 1-1: Boucle sur les attributs oblig et facul
  * sous_boucle foreach 1-2: Recherche d'une cle FK si semblable é $this->PPK  et non listée dans attributsAttendus
  * boucle foreach 2 : Boucle  sur toutes les donnees  du contexte é la recherche de donnees 'Statiques'      
  * Si le modéle inclus une structure statique:
  * sous_boucle 2-1 sur le tableau fourni; detection données statique dans tableau client et
  * appel de chargeDataStat() avec les donnees ou pas de données 
  * certains attributsAttendus sont facultatifs...tres important ici...pour de la souplesse
  * c'est la couche 'controle" qui verifiera la presence obligatoire de certaines donnees      
  * Retourne un array : 
  * data 'dynamiques': $wagon[$this->table] = array("nom"=>"valeurcliente")
  * data 'liaison' : $wagon[$this->tableLiaison][$int] = array('lienhum'=>'idhum,'lientrans'=>12,'utilisation'=>'oui','abonne'=>'oui') 
  * dataClasse travaille avec un tableau fourni par le client
  * si une $cleligne est fournie, on est en mode 'update'
  */
  protected function dataClasse($tableau,$FKey=NULL)
  {
    $drapstat = 0;  $clestatiques = array(); $wagon = array();
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
          $wagon[$this->table][$champ] = $option["cleSecondaireRelie"];   //valeur symbolique(son nom) maintenant
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
  /**
  * Injecter: Possibilité d'injecter ici plusieurs données non classée(ni oblig ni facul) 
  * @arg $dataIn, les donneeés é injecter et la table d'injection type: array($table,array($nomChamp),array($valChamp))
  * @arg $trier, type array: le tableau de valeurs initiales.
  */    
  protected function Injecter($trier,$dataIn)
  {
    $injTable = $dataIn[0];
    $injChamp = $dataIn[1];
    $injVal = $dataIn[2];
    if (! is_array($injChamp)){ $alertInj = 1;}      
    elseif (! is_array($injVal)){ $alertInj = 1;}
    if (!empty($alertInj)) { throw new MyPhpException('inscrire: array injection invalide'); } 
    $cptChamp = count($injChamp);      
    $cptVal = count($injVal); 
    if ($cptChamp != $cptVal) { throw new MyPhpException(' inscrire: array injection: les sous-tableaux[1] et [2] sont inegaux '); }    
    foreach ($injChamp as $cle=>$val) {
      $valAccroc = $injVal[$cle];
      $cleanValAccroc = $this->dataEntity($valAccroc);
      $trier[$injTable][$val] = $cleanValAccroc;
    }
    return $trier ;
  }
  /**
  *   methode insertion() insere une ligne dans une table (selon contexte)
  *   retourne $this->lastId valeur de la PK de la ligne inseree  
  */
  protected function insertion($nomTable,$train=array(),$lastPK=NULL)
  { 
    $tablesOrig = array_keys($this->classement);
    if (! in_array($nomTable,$tablesOrig)){ 
      $error = $nomTable.' inconnue dans ce contexte';      
      throw new MyPhpException($error);
    }
    $this->schema = $this->schemaTable($nomTable);
    $nbr = count($train);     
    $idx = 0; $marqueur = ''; $listeNomAttrib = ''; $listeMarqueurs = ''; 
    //Attribution de la valeur reelle ($lastPK) aux cles secondaires qui avaient une valeur symbolique et pointent sur $this->PPK 
    //La 'premiere' table du contexte (celle de la premiere insertion) ne doit pas avoir de FK qui point vers PPK   
    foreach ($this->schema as $champ=>$opt){
      if (($opt['cleSecondaire'])&& ($opt["cleSecondaireRelie"] == $this->PPK)){
        if (is_null($lastPK)){ 
          $msg = $champ.' : pas de valeur pour cette cle en insertion ??';          
          throw new MyPhpException($msg);
        }
        else { $train[$champ] = $lastPK; }
      }
    }
    //creation des chaines $listeNomAttr et $listeValAttrib avec le $sep(arateur)     
    foreach ($train as $cle=>$val){
      ($idx < ($nbr-1))?  $Sep = ", ": $Sep = NULL; 
      $listeNomAttrib .= $cle.$Sep;
      $listeMarqueurs .= ' :'.$cle.$Sep; //et pas $listeNomAttrib !!
      $idx++;
    }
    //finalisation des listes d'attributs, de marqueurs et creation de la requete sql
    $listeNomAttrib = ' ('.$listeNomAttrib.') ';
    $listeMarqueurs = ' ('.$listeMarqueurs.') ';
    $tableChamps = $nomTable.$listeNomAttrib;
    $sql = strval("INSERT INTO $tableChamps VALUES $listeMarqueurs ");   
    $stmt = $this->bd->prepare($sql); 
    //liaison marqueurs-valeurs   
    foreach ($train as $cle=>$val){
      switch ($this->schema[$cle]["type"]){
        case ('char'):case('varchar'): case('date'):case('text'): $data_type = PDO::PARAM_STR ; break;
        case('bigint'):case ('int'):case('tinyint'):case('timestamp'): $data_type = PDO::PARAM_INT ; break;
      }                  
      $marqueur = ":".$cle;         
      $stmt->bindValue($marqueur,$val,$data_type);  
    } 
    //Exécution de la requete preparée.    
    if ($ok = $stmt->execute()) { 
      $lastId = intval($this->bd->lastInsertId());
      $stmt = NULL;
      return $lastId;
    }
    else { throw new MyPhpException('Impossible d\'inserer la donnee');}
  }
  /**
  * La requete de mise é jour d'une ligne d'une table
  * @param $nomtable : la table sur laquelle faire un update
  * @param $train : array() le train des valeurs é mettre é jour
  * @param $id :integer, la valeur de la PK de la ligne é mettre é jour
  * @param $flag:(NULL) un drapeau pour evaluer si l'update doit se baser sur une FK
  * note : $train peut etre minimaliste...
  * donc, p.ex la mise é jour du champ numbr de spip_vap_core peut s'effectuer par appel de cette methode 
  * depuis le controleur MembreCtrl.php 
  */ 
  protected function update($nomTable,$train,$id,$condExtra=NULL)
  {
      $this->table = $nomTable;
      $this->schema = $this->schemaTable($this->table);
      $nbr = count($train);    
      $idx = 0; $marqueur = ''; $chaine = '';$testkey = 0;
      foreach ($this->schema as $nom => $option) {
        if (empty($flag) && ($option["cleSecondaire"]) && ($option["cleSecondaireRelie"] == $this->PPK)){
          $nomCle = $nom; 
          $train[$nom] = $id;
          $testkeys = 1;
        }
      }
      if (empty($testkeys)){
        foreach ($this->schema as $nom => $option){        
          if ($option['clePrimaire']){ $nomCle = $nom; $testkeys = 1; }
        }   
      }
      if (empty($testkeys)){ throw new MyPhpException ("La table: ".$this->table." n'a pas de clé primaire en update()??");}
      //creation des chaines $listeNomAttr et $listeValAttrib avec le $sep(arateur)
      //creation de la requete sql     
      foreach ($train as $cle=>$val){
        ($idx < ($nbr-1))?  $Sep = ", ": $Sep = NULL ; 
        $marqueur = ':'.$cle; 
        $fullMarque =  $cle.' = '.$marqueur.$Sep;      
        $chaine .= $fullMarque;
        $idx++;
      }
      $marqueurCle = ":".$nomCle ;
      $chaineCle = strval($nomCle ." = ". $marqueurCle) ;
      $sql = "UPDATE $this->table SET $chaine WHERE $chaineCle "  ;
      
      //liaison marqueurs valeurs, preparation de requete 
      $stmt = $this->bd->prepare($sql);
      foreach ($train as $cle=>$val)
      {
        switch ($this->schema[$cle]["type"])
        {
          case ('char') : case('varchar') : case('date') : case('text') : $data_type = PDO::PARAM_STR ; break;
          case('bigint') : case ('int') : case('tinyint') : case('timestamp') : $data_type = PDO::PARAM_INT ; break;
        }    
        $marqueur = ":".$cle;       
        $stmt->bindValue($marqueur,$val,$data_type); 
      }
      $stmt->bindValue($marqueurCle,$id,PDO::PARAM_INT);
      if ($stmt->execute()){ $stmt = NULL; $train = array(); return true;}
      else { throw new MyPhpException('Erreur d\'execution de requete preparee par update()');}
    }
  
  //--------------public---------------------//
  /**
  * Cette fonction inscrit des donnees clients en base
  * Appelle 2 methodes :
  * $this->dataClasse pour le tri selon les tables
  * $this->insertion pour le traitement sql
  * @param $tableau: array() le tableau de donnee é insérer
  * @param $injection: array() eventuellement un array supplémentaire pour injection
  */
  public function inscrire($tableau,$injection=array())
  {
    $x = 0;  $id=NULL; $alertInj = NULL;  $premTri = array(); 
    //Pour toutes les donnees recues, je les range comme suit:
    $premTri = $this->dataClasse($tableau);
    if (! empty($injection)){
      $this->classement = $this->Injecter($premTri,$injection);
    }
    else { $this->classement = $premTri; }
    $tableIns = array_keys($this->classement); 
    foreach ($tableIns as $table){               
      // Bien que traitée en premier ici, $id doit exister donc une donnée statique vient aprés une standart      
      if ($table == $this->liaisonTable){ 
        foreach ($this->classement[$table] as $nbr=>$tabliaison){
          $idliaison = $this->insertion($table,$tabliaison,$id);
        }
      }
      else { //premier enregistrement? on recupere lastid et l'attribue é id et é valPPK
        $this->lastId = $this->insertion($table,$this->classement[$table],$id);
        if ($x == 0) { 
          $this->valPPK= $this->lastId; 
          $id = $this->lastId;          
        }
        $x++;
      }   
    }
    return $this->valPPK;
  }
  /**
  * Met a jour un contexte multi table par appel é update(unetable)
  * @param $tableau: array() tableau de données  
  * @param $clelligne: integer la valeur d'une ligne 
  */
  public function mettreajour($tableau,$cleligne,$injection=array())
  {
    $x = $sortie = 0;  $id = NULL; 
    $inLiaison = $premTri = array();     
    //Pour toutes les donnees recues, je les range [par table] et  [par nom=valeur]
    $premTri = $this->dataClasse($tableau);
    if (! empty($injection)){
      $this->classement = $this->Injecter($premTri,$injection);
    }
    else { $this->classement = $premTri; }
   /* 
    echo '<pre>'; print_r($this->classement); echo '<pre>';
    exit();
   */
    //  Parcourir les tables concernees:
    $tableUp = array_keys($this->classement); 
    foreach ($tableUp as $table){   
      //1. Je m'occupe de la table de liaison qui gere le multi2multi                           
      if ($table == $this->liaisonTable){                                                    
        if (!$ok = $this->supprimePlusLignes($cleligne,$this->liaisonTable)){                 
          $msg = 'Impossible de nettoyer'.$this->liaisonTable;          
          throw new MyPhpException($msg);
        }
        foreach ($this->classement[$table] as $nbr=>$ligne){                                  
          if ($ok = $this->insertion($table,$ligne,$cleligne)){ $sortie++ ; }
        }  
      }
      else {
        if ($ok = $this->update($table,$this->classement[$table],$cleligne)){ $sortie++ ; }
        else { throw new MyPhpException("erreur mettreajour() dans table: $table");}
      }
    }
    return $sortie;
  }
   /**
  * Fonction multi table qui selectionne en DATABASE  et retourne l'ensemble des donnees liées é la PPK d'un contexte.
  * Pour toute table 'dynamique', les donnees sont : $tableau[$nomTable][$attribut]=$valeur
  * Pour les tables de liaisons, les donnees sont : $tableau[$idx][$attribut]=$valeur
  * $idx est de type INT, $nomTable est de type ASSOC ...c'est permis
  */    
  public function selection($clePK)
  {
    $retour = array(); $tableauliaison = array(); $x = 0;
    if (empty($clePK)) {
      throw new MyPhpException("Clé de selection invalide");
    } 
    $this->valPPK = intval($clePK); //valPPK est la valeur de la PK de la premiére table du contexte
    foreach ($this->dynTables as $nomtable){     
     $retour[$nomtable] = $this->preparation($this->valPPK,$nomtable);
    }
    if (!empty($this->liaisonTable)){ //traitement des donnes de liaison lignes par lignes     
      $tableauliaison = $this->preparation($this->valPPK,$this->liaisonTable);   
      foreach ($tableauliaison as $ligne){
        $retour[$x] = $ligne;$x++;
      }
    }
    //petits ajouts pratiques...
    $retour['inliaison'] = $x;
    $retour['valppk'] = $this->valPPK;
    return $retour;  	       
  }
  /**
  * Retourne une ligne de table car on selectionne sur une PK
  * $valcle: string, la valeur de la PK de $table
  * $table: string, la table sur laquelle travailler 
  * $colonnes: array(), facultatif mais permet de moduler les colonnes selectionnées 
  */
  public function selectUneLigne($valcle,$table,$colonnes=array())  
  {
    $result = array();  
    $this->table = $table;
    $valcle = intval($valcle);
    $this->schema = $this->schemaTable($this->table);
    //Recherche du nom des cles FK ou PK qui représentent la ligne é preparer      
    foreach ($this->schema as $champ=>$option){
      if ($option['clePrimaire']) { $this->nomClePK = $champ;}    
    }
    if (empty($this->nomClePK)){ trigger_error("La table: ".$this->table." n'a pas de clé primaire?",E_USER_ERROR);}
    if (empty($colonnes)) {    
      $sql = "SELECT * FROM $this->table WHERE $this->nomClePK = :pk";
    }
    else {
      $idx = 0;
      $nbr = count($colonnes);
      $wagonSql = ' '; 
      foreach ($colonnes as $name) {     
        ($idx < ($nbr-1))?  $Sep = ", ": $Sep = " "; 
        $wagonSql .= $name.$Sep;
        $idx++;
      }
      $sql = "SELECT $wagonSql FROM $this->table WHERE $this->nomClePK = :pk";      
    }  
    $stmt = $this->bd->prepare($sql);
    $stmt->execute(array(':pk'=>$valcle));
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt = NULL;  
    //$result = $this->magicNormHTTP($result);  
    return  $result;    
  }
  /**
  * Methode de selection de ligne par valeurs, 
  * employée par (Chat,Help,Membre,News)Ctrl.php pour selectionner une seule ligne
  * employée par Iter.php pour selection multiple ligne 
  * @param $colonnes, array() : un tableau des colonnes é selectionner
  * @param $tables, str: la table de selection
  * @param $nomval, str, nom de la colonne qui conditionne la sélection
  * @param $varval, la valeur de la colonne de condition type variable
  * @param $all, bool, drapeau pour indiquer la methode de fetch
  */
  public function ligneParValeurs($colonnes,$table,$nomval,$varval,$all=NULL,$condi=NULL)
  {
    $idx = 0;    
    $nbr = count($colonnes);
    $wagonSql = ' ';    
    foreach ($colonnes as $name) {
      ($idx < ($nbr-1))?  $Sep = ", ": $Sep = " "; 
      $wagonSql .= $name.$Sep;
      $idx++;
    }    
    if (empty($condi)) { $sql = "SELECT $wagonSql FROM $table WHERE $nomval = :varval "; }
    else { $sql = "SELECT $wagonSql FROM $table WHERE $nomval = :varval $condi"; }
    $stmt = $this->bd->prepare($sql);
    $stmt->execute(array(":varval"=>$varval));
    if (empty($all)) { $result = $stmt->fetch(PDO::FETCH_ASSOC); }
    else { $result = $stmt->fetchAll(PDO::FETCH_ASSOC); }
    $stmt = NULL; 
    //$result = $this->magicNormHTTP($result);     
    return  $result;    
  }

   /**
  * Retourne une ou plus ligne(s) de table car on selectionne sur une FK mais dans le cadre du contexte
  * $valcle est ici une FK
  */
  public function selectPlusLignes($valcle,$table,$cle=NULL)
  {
    $result = array(); $sortie = array();$x = 0;
    $this->table = $table;
    $valcle = intval($valcle);
    $this->schema = $this->schemaTable($this->table);
    //Recherche du nom des cles FK ou PK qui représentent la ligne é preparer      
    // 1 si une 'vrai' clé étrangére est fournie    
    if (!empty($cle)){
      foreach ($this->schema as $champ=>$option){
        if (($option["cleSecondaire"]) && ($option["cleSecondaireRelie"] == $cle)){
          $this->nomCleFK = $champ; 
        } 
      }
    }
    else {  // 2 sinon on se rabat sur une cle etrangére qui pointe vers la PPK
      foreach ($this->schema as $champ=>$option){
        if (($option["cleSecondaire"]) && ($option["cleSecondaireRelie"] == $this->PPK)){
          $this->nomCleFK = $champ; 
        }
      }
    }
    if (empty($this->nomCleFK)){  throw new MyPhpException ("La table: ".$this->table." n'a pas de clé secondaire ");}
    $sql = "SELECT * FROM $this->table WHERE $this->nomCleFK = :fk " ; 
    $stmt = $this->bd->prepare($sql);
    $stmt->execute(array(':fk'=>$valcle));
    while ($result = $stmt->fetch(PDO::FETCH_ASSOC)){    
      $sortie[$x] = $result;
      $x++;      
    }
    $stmt = NULL;
    return $sortie;
  }
  /**
  * Supprime les donnees liés é un identifiant de contexte
  * Suppose que $this->dataContexte est defini (par appel é $this->chargerContexte($contexte,$statut))
  */
  public function supprime($valcle)
  {
    $testdel = 1;      
    foreach ($this->dynTables as $nom){
      if (! $ok = $this->supprimeUneLigne($valcle,$nom)){ 
        $testdel = 0;          
        throw new MyPhpException('Erreur de suppression de table, methode supprime()');
      }
    }
    if (!empty($this->liaisonTable)){
      if (! $ok = $this->supprimePlusLignes($valcle,$this->liaisonTable)){
        $testdel = 0;  
        throw new MyPhpException('Erreur de suppression de table de liaison, methode supprime()');
      }
    }
    return $testdel;
  }       
   /**
  * Supprime une ligne de table car on supprime sur une PK($valcle)
  */
  public function supprimeUneLigne($valcle,$table)
  {
    $result = array();  
    $this->table = $table;
    $valcle = intval($valcle);
    $this->schema = $this->schemaTable($this->table);
    //Recherche du nom des cles FK ou PK qui représentent la ligne é preparer
    foreach ($this->schema as $champ=>$option){
      if ($option['clePrimaire']) { $this->nomClePK = $champ;$testPK = 1;}       
    }
    if (empty($testPK)){ throw new MyPhpException ("La table: ".$this->table." n'a pas de clé primaire?");}
    $sql = "DELETE FROM $this->table WHERE $this->nomClePK = :pk";
    $stmt = $this->bd->prepare($sql);
    if ($stmt->execute(array(':pk'=>$valcle))){    
      $stmt = NULL; 
      return true;
    }
  }
  /**
  * $valcle est ici une FK 
  */
  public function supprimePlusLignes($valcle,$table,$cle=NULL)
  {
    $result = array();  
    $this->table = $table;
    $valcle = intval($valcle);
    $this->schema = $this->schemaTable($this->table);
    //Recherche du nom des cles FK ou PK qui représentent la ligne é preparer      
    if (!empty($cle)){
      foreach ($this->schema as $champ=>$option){
        if (($option["cleSecondaire"]) && ($option["cleSecondaireRelie"] == $cle)){
        $this->nomCleFK = $champ; 
        }
      }
    }    
    else {    
      foreach ($this->schema as $champ=>$option){
        if (($option["cleSecondaire"]) && ($option["cleSecondaireRelie"] == $this->PPK)){
          $this->nomCleFK = $champ; 
        }
      }
    }    
    if (empty($this->nomCleFK)){ throw new MyPhpException ("La table: ".$this->table." n'a pas de clé secondaire?");}
    $sql = "DELETE FROM $this->table WHERE $this->nomCleFK = :fk";
    $stmt = $this->bd->prepare($sql);    
    if ($stmt->execute(array(':fk'=>$valcle))){
      $stmt = NULL; 
      $ok = true;
    }
    else {$ok = false;}
    return $ok;
  }
        
}
