window.onload=testerNavigateur;

function testerNavigateur() {   
	var objetXHR = creationXHR();
	if(objetXHR==null) {
		document.getElementById("promoGo").disabled= true;
		var erreurNavigateur="Erreur Navigateur : Création d'objet XHR impossible";
		remplacerContenu("msgAjax", erreurNavigateur);
	    document.getElementById("msgAjax").style.visibility="visible";
	}
  document.getElementById("promoGo").onclick=lancerPromo;
}
function promoTest() {
  var msg = 'Hello Click';
  var ok = remplacerContenu("msgAjax",msg);
}
function lancerPromo() {    
	objetXHR = creationXHR();
  var temps = new Date().getTime(); 
  var promoMail = new Array("promoUn","promoDeux","promoTrois");
  var wagon = new Array();
  var parametres = ''; var queue = '&'; var cpt = 0;
  document.getElementById("promoUn").style.backgroundColor="#f8f4ef";
  for (var i=0; i<promoMail.length; i++) { 
    if (document.getElementById(promoMail[i]).value){
      cpt++;
      var transit = promoMail[i]+"="+codeContenu(promoMail[i]);
      wagon.unshift(transit);    
    }
  }
  if (cpt == 0){ 
    document.getElementById("promoUn").style.backgroundColor="#f08a0c";
    alert("Un mail est necessaire !");
    return null;
  }
  if (document.getElementById("promoMsg").value) {
    var transit = "promoMsg="+codeContenu("promoMsg");
    wagon.push(transit);
  }
  wagon.push("anticache="+temps);
  for (var i=0; i<wagon.length; i++){
    if (i == (wagon.length-1)){ queue = "";}
    parametres = parametres+wagon[i]+queue;
  }  
  var urlDest = "http://"+window.location.host+"/vapspace/index.php?ctrl=membre&action=promo";
  objetXHR.open("post",urlDest,true);
  objetXHR.onreadystatechange = actualiserPage;
  objetXHR.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
  document.getElementById("promoGo").disabled= true;
  var ok = ajouterImg("charge","photos/chargeur.gif"," ");
	document.getElementById("charge").style.visibility="visible";  
  objetXHR.send(parametres);  
}
function actualiserPage() {
	if (objetXHR.readyState == 4) {
    if (objetXHR.status == 200) { 
      document.getElementById("promoGo").disabled= false;
      document.getElementById("promoUn").value = ''; 
      document.getElementById("promoDeux").value = ''; 
      document.getElementById("promoTrois").value = '';         
      var nouveauResultat = objetXHR.responseText;
      var ok = remplacerContenu("charge",nouveauResultat); 
    }
    else {
      var erreurServeur="Erreur serveur : "+objetXHR.status+" - "+ objetXHR.statusText;
      document.getElementById("promoGo").disabled= false;  	    
      var ok = remplacerContenu("charge", erreurServeur);	    
	    objetXHR.abort();  
	    objetXHR=null;
	  }
  }	
}	 

function uncheck(el) {
  if (document.getElementById) {
    var radio = document.getElementById(el.id);
    var temp = document.getElementById(el.name+'_temp');
    if (! temp_val){ temp_val = "undefined"; }
  }else if (document.all) {  
    var radio = document.all[el.id];
    var temp = document.all[el.name+'_temp'];
  } else {  
    if ((navigator.appname.indexOf("Netscape") != -1) && parseInt(navigator.appversion == 4)) {
      var radio = document.layers[el.id];
      var temp = document.layers[el.name+'_temp'];
    }
  }
  if(radio.value == temp.value) {
    radio.checked = false;
    temp.value = '';
  } else {
    temp.value = radio.value;
  }
}

