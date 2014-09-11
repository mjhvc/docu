<?php

/******************************************************************************
*    vapspace est un logiciel libre : vous pouvez le redistribuer ou le       *
*    modifier selon les termes de la GNU General Public Licence tels que      *
*    publiés par la Free Software Foundation : à votre choix, soit la         *
*    version 3 de la licence, soit une version ultérieure quelle qu'elle      *
*    soit.
*
*    vapspace est distribué dans l'espoir qu'il sera utile, mais SANS AUCUNE  *
*    GARANTIE ; sans même la garantie implicite de QUALITÉ MARCHANDE ou       *
*    D'ADÉQUATION À UNE UTILISATION PARTICULIÈRE. Pour plus de détails,       *
*    reportez-vous à la GNU General Public License.                           *
*
*    Vous devez avoir reçu une copie de la GNU General Public License         *
*    avec vapspace. Si ce n'est pas le cas, consultez                         *
*    <http://www.gnu.org/licenses/>                                           *
******************************************************************************** 
*/
/**
* index.php est la porte d'entrée de toutes les urls de vapspace
* @category vapspace
* @copyright Marc Van Craesbeeck, 2011
* @license GPL
* @package controleur
* @version 1.0.0
* @author marcvancraesbeeck@scarlet.be
*/
error_reporting(E_ALL|E_STRICT);
date_default_timezone_set('Europe/Paris');
//setlocale(LC_TIME,'fr_BE'); chargé depuis Controleur/chargerVue()
  
/*
* Declaration des chemins des fichiers (PATH)
* Pour une installation locale, mettre le chemin absolu de votre repertoire racine ici
*/
	 
  if ($_SERVER['SERVER_NAME'] == 'localhost') { $root =  str_replace('vapspace/index.php','',$_SERVER["SCRIPT_FILENAME"]); }
  else { $root = dirname($_SERVER['DOCUMENT_ROOT']).'/' ; }
  
  set_include_path('.'
	  .PATH_SEPARATOR.$root.'vaplib'.'/'
	  .PATH_SEPARATOR.$root.'vapapi'.'/'
	  .PATH_SEPARATOR . $root . 'vapapi'.'/'.'controleurs' . '/'  
    .PATH_SEPARATOR . $root . 'vapapi'.'/'.'fonctions' . '/'
	  .PATH_SEPARATOR.get_include_path()
	  );

// les constantes principales,
// Les Gestion des erreurs et des exceptions (dans lib/) et la gestion magic_quotes_gpc 
  require_once('Connect.php'); 
  require_once('Erreur2Exception.php');
  require_once('normalisation.php');
  
  set_error_handler('GestionErreursPerso');
  set_exception_handler('RamassException'); 
  
//les directives de configuration du php
  ini_set("display_errors",DISPLAYERRORS);
  ini_set("log_errors",LOGERRORS);
  ini_set("error_log",BASELOG);
  ini_set("session.save_path",SESSION);
  ini_set("safe_mode","off");
  ini_set("session.use_only_cookies","1");
  ini_set("session.cookie_httponly","on"); 
  ini_set("register_globals","off");

// Si on est en échappement automatique: vient de normalisation.php
  if (get_magic_quotes_gpc()) {
    $_POST = NormalisationHTTP($_POST);
    $_GET = NormalisationHTTP($_GET);
    $_REQUEST = NormalisationHTTP($_REQUEST);
    $_COOKIE = NormalisationHTTP($_COOKIE);
  }

//On charge le contrôleur frontal
  require_once('Frontal.php');
  $frontal = new Frontal();

// On demande au contrôleur frontal de traiter la requête HTTP
  try { $frontal->execute(); }
  catch(MyPhpException $e) {
    $msg = $e->getMessage();
    $e->alerte($msg);  
  }
  

