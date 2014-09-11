// JavaScript Document
 function creationXHR() {
   var resultat=null;
   try {//test pour les navigateurs : Mozilla, Opéra, ...
	    resultat= new XMLHttpRequest();
     } 
     catch (Error) {
     try {//test pour les navigateurs Internet Explorer > 5.0
     resultat= new ActiveXObject("Msxml2.XMLHTTP");
     }
     catch (Error) {
         try {//test pour le navigateur Internet Explorer 5.0
         resultat= new ActiveXObject("Microsoft.XMLHTTP");
         }
         catch (Error) {
            resultat= null;
         }
     }
  }
  return resultat;
}
//Fonctions de gestion du DOM 
function remplacerContenu(id, texte) {
  var element = document.getElementById(id);
  if (element != null) { var ok = supprimerContenu(element); }  
  var nouveauContenu = document.createTextNode(texte);
  element.appendChild(nouveauContenu);
  return true;
}
function ajouterImg(id,orig,alter){
  var element = document.getElementById(id);
  if (element != null) { var ok = supprimerContenu(element); } 
  var balise = document.createElement("img");
  balise.src = orig;
  balise.alt = alter;
  element.appendChild(balise);
  return true;
}  
function supprimerContenu(element) {
  if (element != null) {
    while(element.firstChild) { element.removeChild(element.firstChild); }
  }
  return true;
}
//Fonctions encodage code en UTF8, la valeur d'un élément dont id passé en parametre
function codeContenu(id) { 
	var valeur=document.getElementById(id).value;
	return encodeURIComponent(valeur);
}

