/*
* Une fonction qui 'de-check' un element de formulaire radio precedemment 'checked'
* param el,objet l'element selectionné 
* utilise 2 variables détectées par document.getElementById(el)
*   à var radio, est affecté l'objet reçu par evenement onclick du formulaire  
*   à var temp, est affecté l'objet de nom :el.name+'_temp'
* Fonctionnement : 
* Premier click : radio reçoit une valeur, temp est vide donc temp.value reçoit radio.value
* Second click (sur input radio identique): Si la valeur de radio est bien égale à celle de temp, on annule radio.check    
* Second click sur autre radio : si les valeurs radio et temp diffèrent on attribue à temp la nouvelle valeur 
*/
function uncheck(el) {

if (document.getElementById) {
  var radio = document.getElementById(el.id);
  //var radio_val = radio.value;
  var temp = document.getElementById(el.name+'_temp');
  //var temp_val = temp.value;
  if (! temp_val){ temp_val = "undefined"; }
  //var glb = radio_val + "\n" + temp_val;
  //alert(glb);
} 
else if (document.all) { //pour vieux IE
  var radio = document.all[el.id];
  var temp = document.all[el.name+'_temp'];
} 
else { //pour netscape 4
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
