$(document).ready(function(){
  $("li.menuPerso").hide();
  $("li.liReg").hide();
  $("li.liChat").hide();
  $("li.slireg").hide();
  $("#Perso").click(function(){
    if ($("li.menuPerso").is(":hidden")){ $("ul.connect").children("li.menuPerso").slideDown('slow'); }
    else { $("ul.connect").children("li.menuPerso").slideUp('slow'); }
  });
  $("a.aChat").click(function(){
    if ($("li.liChat").is(":hidden")){ $(".navChat").children(".liChat").slideDown('slow'); }
    else { $(".navChat").children(".liChat").slideUp('slow'); }
  }); 
  $("a.statreg").click(function(){
    if ($("li.slireg").is(":hidden")){ $(".sreg").children(".slireg").slideDown('slow'); }
    else { $(".sreg").children(".slireg").slideUp('slow'); }
  });    
  $("a.aReg").click(function(){
    if ($(this).next("ul.navReg").children("li.liReg").is(":hidden")){ 
      $(this).next(".navReg").children(".liReg").find(".liAnt").hide();
      $(this).next(".navReg").children(".liReg").slideDown('slow'); }
    else { $(".navReg").children(".liReg").slideUp('slow'); }
  });   
  $("li.liReg").click(function(){
    if ($(this).find("li.liAnt").is(":hidden")){
      $(this).find("li.liAnt").slideDown('slow');
      $(this).siblings().find("li.liAnt").slideUp('slow');
    } else {
      $(this).find("li.liAnt").slideUp('slow');
    } 
  });
});
