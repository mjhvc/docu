<?php
 
/**
@class FiltresData 
@brief la classe des filtres à appeler dans le cadre d'un contexte précis.
	 
[vers le descripteur des contextes] (@ref  descripteursContexte.dox) 
@author marcvancraesbeck@scarlet.be
@copyright [GNU Public License](@ref licence.dox)
*/  
require_once('IniData.class.php');

class FiltresData extends IniData
{
  private $mail = '' ;                    /**< string un mail à tester */
  protected $attributsAttendus = array(); /**< array utilisé par $this->filtreMasques et $this->filtreTaille  */
  private $erreurMasque = array();        /**< array utilisé par $this->filtreMasques */
  private $erreurTaille = array();        /**< array utilisé par $this->filtretaille */
  private $erreurOblig ='' ;              /**< array utilisé par $this->filtreOblig */
  private $formatMail = array();          /**< array tableau des champs à n e pas filtrer */
  
  /** initiation a minima et appel du __construct parent 
    @param $contexte string le contexte à charger
    @param $statut string le statut à charger
  */
  function __construct($contexte=NULL,$statut=NULL)
  {
   //appel le constructeur parent avec ses parametres
    if ($contexte != NULL && $statut != NULL) {
      parent::__construct($contexte,$statut); 
    } 
    else {  parent::__construct(); }  
  }
 
/** affichage des caractères capturés par preg_match_all
  	@param $nb  integer le nombre d'erreurs,
		@param $match array le tableau fourni par preg_match_all,
  	@return  string $retour
  */
  public function affichErreurs($nb,$match)
  {
    $retour = '';	  
    for ($i=0;$i<$nb;$i++)
	  { $retour .= $match[0][$i];}
	  return $retour;
  }
  
/** Methode generique. Application de la suppression de tout echappement produit par magic_quotes si necessaire pour tout tableau contenant des données http.
  	Sources du script: http://sourceforge.net/projects/webscope/
  */
  public function filtreMagicQuote()
  {
	  //Si echappement automatique, rectifier:
	  if (get_magic_quotes_gpc()) {
	  	$_POST = $this->magicNormHTTP($_POST);
	  	$_GET = $this->magicNormHTTP($_GET);
	  	$_REQUEST = $this->magicNormHTTP($_REQUEST);
	  	$_COOKIE = $this->magicNormHTTP($_COOKIE);
	  }
  } 

  /** Fonction generique qui enleve tout espace invisible en début et fin des valeurs d'un tableau
  @param $tableau array passé par référence
	@return $tableau array 
  */
  public function filtreCarMasq(&$tableau)
  {		
	  foreach($tableau  as $val) {	
      if (is_array($val)){ 
        $val = filtreCarMasq($val); 
      }  
      else {  $val = trim($val); }
    }
    return $tableau;
  }

  /** Filtre tout doublon selon paramètres fournis, peut également servir de compteur si une clé faculataive est fournie (paramètre $alt)
  @param $champ string: nom du champ à verifier
  @param $valeur string: valeur du champ
  @param $table string nom de la table
	@param $alt integer faculatif cle sql pour transformer la methode en compteur général
	@return booléen
  */
  public function doublon($champ,$valeur,$table,$alt=NULL)
  {
    if (!empty($alt)) {
      $marq = ':'.$alt;
      $cle = $alt;
    }
    else {
      $marq = ':'.$champ;
      $cle = $champ;
    }    
    $sql = "SELECT COUNT($champ) AS nbre FROM $table WHERE $cle = $marq ";
    $stmt = $this->bd->prepare($sql);
    $stmt->execute(array($marq=>$valeur));     
    $reponse = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($reponse['nbre'] > 0) { $sortie = 1; } 
    else { $sortie = false;}
    return $sortie;
  }  
  /* sélectionne un champ sql dans une  table selon une cle qui vaut valcle   
	@param $champ string nom du-des champ(s)
	@param $nomcle string nom de clé sql
	@param $valcle integer valeur d'une clé
	@param $stable string nom d'une table sql
	@param $flag bool facultatif, détermine la valeur à retourner 
	@return la valeur de $champ si flag absent et succès, sinon un booleen 
  */
  public function getOld($champ,$nomcle,$valcle,$table,$flag=NULL)
  {
    $doublon = false;	$ancien = array();  
    //sélection de l'ancien champ
	  $sql=  "SELECT $champ FROM $table WHERE $nomcle = :valcle ";
	  $stmt = $this->bd->prepare($sql);
    $stmt->execute(array(":valcle"=>$valcle));
	  $ancien = $stmt->fetch(PDO::FETCH_ASSOC);
    if (empty($flag) && (!empty($ancien[$champ]))) { $retour = $ancien[$champ]; }
    elseif ((!empty($flag)) && (!empty($ancien[$champ]))) { $retour = true; }
    else { $retour = false;}
    return $retour;
  }

/** Filtre dns : verifie si le dns d'un mail est correct
  @param $mail string, le mail a verifier
  @return   booleen
*/
  public function filtreDns($mail)	 
  {
    $this->mail = $mail;  
    $pos = strpos($mail,'@');		//retourne la position de @
  	$dns = substr($mail,$pos+1);	// retourne la sous-chaine qui suit @ ($pos+1)		
  	if ($ok = checkdnsrr($dns)) { return TRUE;}
  	else {return FALSE;}
  }

/** Le filtre anti-spam du contexte Membre
  @param $valeur string valeur à tester
  @param $lang string semble obsolète
  @return booleen
  */
  public function antiSpam($valeur,$lang)
  {
    $valeur = trim($valeur);
    $vrai = array(7,'sept','SEPT','zeven','ZEVEN');
    if (in_array($valeur,$vrai)) { $filtre = true; }
    else { $filtre = false;}
    return $filtre;
  }
 /** contrôle de la validité d'une clé sql, utilise methode getPPK()
	@param $num (int) est la valeur de la ppk d'un contexte
  @return booleen
  */
  public function filtreClePPK($num)
  {
    $tables = $this->getTables();     
    $laTable = $tables[0]; //ici, choix arbitraire, filtre uniquement une cle primaire de contexte
    $ppk = $this->getPPK();
    $sql = "SELECT $ppk FROM $laTable WHERE  $ppk = :laCle ";
    $stmt = $this->bd->prepare($sql);
    $stmt->bindValue(':laCle',$num,PDO::PARAM_INT);
    $stmt->execute();
	  if ($tab = $stmt->fetch(PDO::FETCH_ASSOC)){
	    return 	true;
    }
	  else {	return false; }
  } 
  
  /** une  pour 'matcher' un courriel 
	Cette methode n'est plus employée
	Regex copiée selon: http://www.expreg.com/expreg_article.php?art=verifmail
	@param $mail string le mail à filtrer
	@return booleen
	*/
  public function filtreNonMail($mail)
  {
	  $match = array();
	  if (! preg_match('#^[[:alnum:]](?:[-_.]?[[:alnum:]])+_?@[[:alnum:]](?:[-.]?[[:alnum:]])+\.[a-z]{2,6}$#',$mail,$match))
	  { return TRUE;}
	  else
	  { return FALSE;}	
  }

/** double filtrage du format des dates pour MYSQL (aaaa-mm-dd)
		filtre par regex et filtre par checkdate(mm,dd,aaaa)
		@param $date string la date à filtrer
		@return un integer comme valeur booleenne (O ou 1 ou 2)
*/
  public function filtreDate($date)
  {
    $masque_date = '#^\d{4}-\d{2}-\d{2}$#';     
    $tabdate = explode('-',$date);
    $errDate = 0 ;        
    if (! preg_match($masque_date,$date)){  $errDate = 1; }
    elseif (! checkdate($tabdate[1],$tabdate[2],$tabdate[0])){ $errDate = 2;}
    return $errDate;
  }
  /** Filtre simple de date naissance
		@param $an integer l'année de naissance
		@param $min integer seuil minima
		@param $max seuil maxima
		@return booleen 
  */
  public function naissance($an,$min=1912,$max=2000)
  {
    $date = intval($an);
    $sortie = false;
    if (($date < $min) || ($date > $max)) {
      $sortie = true;
    }
    return $sortie;
  } 
/** matche toutes les valeurs clientes fournies par $tableau via 
  2 boucles: 

  - une boucle sur chaque table sql via parent::chargertable(), matche la valeurs du champ sql client selon les masques regex fourni par le contexte de table
  - une boucle sur parent::dataContexte() pour détecter l'usage de paramètres 'statiques' et agir selon les masques regex fourni par le contexte général
    
    @param $tableau array $tableau[champ]=valeurclient du champ
    @return array  this->erreurMasque[attribut] = les caractères interdits de masques détectés
   
  */
  public function filtreMasques($tableau)
  {    
    $drapstat = 0; $masque = array();
    //initialisation des champs sql à filtrer avec la methode filter_var
    $this->formatMail = array('mail','chatmail','expediteur','commanditaire');
    //3 champs  font exeption aux filtres (table: _vap_chat, _vap_news) 
    $valeursExepte = array('message','contenu','altcontenu');  
    // appelle filtreCarMasq() pour supprimer tout caractère invisible 
    $this->filtreCarMasq($tableau);    
    //Boucle sur toutes les tables dynamiques du contexte($this->tablesdyn vient de $this->chargerContexte)  
    foreach ($this->dynTables as $nom)
    {
      // appel chargerTable (IniData) qui charge les proprietes de la table selon le contexte fourni      
      $this->tableData = $this->chargerTable($nom); 
      
      //appel attributsTable (IniData) qui charge un tableau simple avec le nom des attributs attendus dynamiques-oblig et facul-  
      $this->attributsAttendus  = $this->attributsTable($nom);
 
      foreach ($this->attributsAttendus as $x=>$valeur){  
        //certains attributs sont facul 
        if (!empty($tableau[$valeur])) {  
          if ($masque = strval($this->tableData[$valeur]['masque'])){    
            $attribut = strval($tableau[$valeur]); 
            if (! in_array($valeur,$valeursExepte)){   
              if (in_array($valeur,$this->formatMail)){
                if ( $attribut != MBR_NOMAIL) {   
                  if (! $email = filter_var($attribut,FILTER_VALIDATE_EMAIL)) {  
                    $this->erreurMasque[$valeur] = 'nomail';
                  }
                }
              }              
              elseif (preg_match_all($masque, $attribut, $match)){
                $nb= count($match[0]);
                $this->erreurMasque[$valeur] = $this->affichErreurs($nb,$match);      
              }                
            }
          }
        }
      }
    }   //Boucle 2   : sur toutes les donnees  du contexte 
    foreach ($this->dataContexte as $cont=>$opt) {  //Le contexte peut-il contenir des donnees statiques ?   
      if (!empty($opt['data'])) { $drapstat++ ; }
    }
    if ($drapstat > 0){
      $this->cleSchemaDataStat = array_keys($this->schemaDataStatique); 
      foreach ($tableau as $stat=>$valeur){
        if (in_array($stat,$this->cleSchemaDataStat)){     //une valeur statique detectée dans le tableau client      
          $masque =  $this->dataContexte[$stat]['masque'];         
          $attribut = $valeur;              
          if (! preg_match_all($masque,$attribut,$match)){
            $nb= count($match[0]);
            $this->erreurMasque[$stat] = $this->affichErreurs($nb,$match);    
          }
        }
      }
    }
    return $this->erreurMasque ;
  }

/** Un filtre de taille de donnees qui retourne un tableau vide ou remplis par attribut
    @param $tableau array $tableau[champ]=valeurclient du champ
    @return array
  
  */
  public function filtreTaille($tableau)
  {
    $drapstat = 0;    
    //Boucle sur toutes les tables dynamiques du contexte($this->tablesdyn vient de $this->chargerContexte)  
    foreach ($this->dynTables as $nom)
    {
      // appel chargerTable (IniData) qui charge les proprietes de la table selon le contexte fourni      
      $this->tableData = $this->chargerTable($nom); 
      
      //appel attributsTable (IniData) qui charge un tableau simple avec le nom des attributs attendus dynamiques-oblig et facul-  
      $this->attributsAttendus  = $this->attributsTable($nom);
      foreach ($this->attributsAttendus as $valeur){    //Boucle sur les attributs oblig et facul
        if (!empty($tableau[$valeur])) {    //certains attributs sont facul
          $taille = $this->tableData[$valeur]['longueur'];  
          if (strlen($tableau[$valeur]) > $taille) {
            $this->erreurTaille[$valeur] = $taille ;
          }
        }      
      }
    }
    foreach ($this->dataContexte as $cont=>$opt) { //Boucle 2   : sur toutes les donnees  du contexte
      if (!empty($opt['data'])) { $drapstat++ ; }  //Le contexte peut-il contenir des donnees statiques ?    
    }
    if (!empty($drapstat)){
      $this->cleSchemaDataStat = array_keys($this->schemaDataStatique); 
      foreach ($tableau as $stat=>$valeur){
        if (in_array($stat,$this->cleSchemaDataStat)){ //une valeur statique detectée dans le tableau client 
          $taille = $this->dataContexte[$stat]['longueur']; 
          if (strlen($valeur) > $taille) {
            $this->erreurTaille[$stat] = $taille ;
          }
        }
      }
    }  
    return $this->erreurTaille;
  }

/** Filtre la presence des elements requis(obligatoires) d'une table ou d'un contexte.
    utilise parent::dataOblig parent::attributOblig()
    @param $tableau array  les données à filtrer
    @param $flag string facul, permet de sauter le filtre sur passe
    @param $table string facul, nom d'une table sur laquelle limiter le travail 
   @return une chaine vide ou remplie des parametres manquants
  */
  public function filtreOblig($tableau,$flag=NULL,$table=NULL)
  {
    $corrige = $dataOblig = array(); 
    $back = 1; 
    $stroblig =  $this->erreurOblig = '';
    if (!empty($table)){ $dataOblig = $this->attributsOblig($table); }
    else { $dataOblig = $this->dataOblig; }    
    sort($dataOblig); //$this->attributsOblig() retourne un tableau 'détrié'  
    if ($flag == 'nopass'){
      $idxpass = array_search('passe',$dataOblig);
      array_splice($dataOblig,$idxpass,1);
    }
    foreach ($dataOblig as $val){ 
      if (($val == 'idpere') && ($tableau[$val] == 0)) { $back = 1; }       
      elseif(empty($tableau[$val]) || trim($tableau[$val]) == '')  
      {
        $back = 0; 
        $corrige[] = $val;
      }        
    }
    if ($back == 0){
      $stroblig = implode(',',$corrige);
      $this->erreurOblig = $stroblig;
    }
    else { $this->erreurOblig = '';}
    return $this->erreurOblig;
  }
  /** n'est plus utilisée vérifie que un nombre precis de champs parmis une liste est present
  @param $tabUn array le tableau de toutes les donnees en entrée
  @param $faculA array la liste des champs en alternace
  @param $max: int, le nombre de champs en alternance possible
  */
  public function ObligAlterne($tabUn,$faculA,$max)  
  {
    $compt = 0;  $this->erreurOblig = '';  
    foreach($faculA as $valA) {
      if (array_key_exists($valA,$tabUn) && (!empty($tabUn[$valA]))) { $compt++; }
    }    
    if ($compt < $max) { $this->erreurOblig = 'Choisir au moins un lieu de passage '; }
    elseif ($compt > $max) { $this->erreurOblig = 'Seulement un seul choix parmis les noeuds de proximité, merci'; }
    return $this->erreurOblig;
  }
  /** teste l'absence totale de donnees attendues
    @param $tableau array le tableau des données à filtrer
    @return booleen
  */
  
  public function filtreVacuum($tableau)
  {
    $ensembleDonnees = array_merge($this->dataOblig,$this->dataFacul);
    $tableau = $this->filtreCarMasq($tableau);
    $x = 0;
    foreach ($ensembleDonnees as $key=>$val)
    {
      if (!empty($tableau[$val])){ $x++;} 
    }
    if ($x == 0) {  return true;}
    else  { return false;}  
  }
  /** verifie que le nom des données est bien attendu
    @param $tableau array le tableau des données
    @param $hidden array facul un tableau de valeurs supplémentaires à chercher
    @return string (succès) booleen echec
  */
  public function postInattendu($tableau,$hidden=array())
  {
    $retour = false;    $inattendu = '';
    $this->listeName = $this->getListeAttr();  
    $this->listePost = array_merge($this->listeName,$hidden);
    if ($this->contexte == 'Membre'){ array_push($this->listePost,'confirmation'); }
    foreach ($tableau as $key=>$val) {
      if (! in_array($key,$this->listePost)){ $retour = $key; }
    }
    return $retour;
  }
  /** selectionne la plus grande valeur d'une PK de table sql
    @param $cle integer la clé à sélectionner
    @param $table string la table à sélectionner
    @return integer, la valeur maximale de cette clé
  */
  public function calculKeyMax($cle,$table)
  {
    $sql = "SELECT MAX($cle) AS maxi FROM $table ";
    $stmt = $this->bd->query($sql);
    $rsps = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt == NULL;
    return $rsps['maxi'];
  }
  /** selectionne une PK de T_CORE selon un paramètre supplémentaire la clé d'une antenne
    @param $cle integer la valeur de Pk à vérifier
    @param $cleant integer la valeur de la clé d'antenne    
    @return  integer (la clé) succes  ou booléen (false)
  */  
  public function mbrInAnt($cle,$cleant)
  {
    $sql = "SELECT idvap FROM ".T_CORE." WHERE idvap = :cle AND lienant = :lienant";
    $stmt = $this->bd->prepare($sql); 
    $stmt->bindValue(':cle',$cle,PDO::PARAM_INT);
    $stmt->bindValue(':lienant',$cleant,PDO::PARAM_INT);
    $stmt->execute();
    $rslt = $stmt->fetch(PDO::FETCH_ASSOC);
    if(empty($rslt['idvap'])) { $rsps = false; }
    else { $rsps = $cle; }
    return $rsps;
  }  
  /** selection d'une region en fonction d'une cle d'antenne
  @param $lienant integer valeur de la clé PK idant
  @return integer la valeur de lienreg ou false 
  */  
  public function selectreg($lienant)
  {
    $sql = "SELECT lienreg FROM ".T_ANT." WHERE idant=:reg";
    $stmt = $this->bd->prepare($sql); 
    $stmt->bindValue(':reg',$lienant,PDO::PARAM_INT);
    $stmt->execute();
    $rslt = $stmt->fetch(PDO::FETCH_ASSOC);
    if(empty($rslt['lienreg'])) { $rsps = false; }
    else { $rsps = $rslt['lienreg']; }
    return $rsps;
  } 
  /** Vérifie si le mail appartient bien à quelqu'un en base
  @param $mail string, le mail à vérifier
  @return booleen
  
  */
  public function verifmail($mail)
  {
	  $sql = "SELECT COUNT(idhum) AS nbres,statut FROM ".T_HUM." WHERE mail = :mail GROUP BY idhum";
	  $stmt = $this->bd->prepare($sql);
    $stmt->bindValue(':mail',$mail,PDO::PARAM_STR);
    $stmt->execute();
    $rslt = $stmt->fetch(PDO::FETCH_ASSOC);
	  if ($rslt['nbres'] >= 1){ $end =  true; }
	  else { $end = false; }
    return $end;
  }
   /** Compte les news actives et renvoi une adresse mail ou false
    @return string une adresse mail (succès) ou false (échec)
  */ 
  public function lockMoulin()
  {
    $sql = "SELECT commanditaire,COUNT(idnews) AS id FROM ".T_NEWS." WHERE active = 'oui' GROUP BY commanditaire";
    $stmt = $this->bd->query($sql);
    $rslt = $stmt->fetch(PDO::FETCH_ASSOC);  
    if ($rslt['id'] >= 1) {  $retour = $rslt['commanditaire']; }   
    else { $retour = false ; }
    return $retour;
  }
   /** matche vite un mail, utilisé par membre::inscrire et membre::promo
    @param $mail string un mail à matcher
    @return booleen
  */
  public function matchMail($mail)
  { 
    if (! $test = $this->filtreDns($mail)) { return false; }
    elseif (! ($test =  filter_var($mail,FILTER_VALIDATE_EMAIL))) { return false; }
    else { return true; }
  }       
  /** Une methode qui teste le zip code belge
	 	@param $zip integer le code postal a controler
		@return booleen 
	*/
	public function testZip($zip)
	{
		$zipS = intval($zip);	
		$retour = false;	
		if (($zipS < 1000) || ($zipS > 9999)) { $retour = false; }
		else ( $retour = true; } 
		return $retour;
	}  
  
}
