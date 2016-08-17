

function nif(dni) {
  var numero
  var letr
  var letra
  var expresion_regular_dni
 
  expresion_regular_dni = /^\d{8}[a-zA-Z]$/;
 
  if(expresion_regular_dni.test (dni) == true){
     numero = dni.substr(0,dni.length-1);
     letr = dni.substr(dni.length-1,1);
     numero = numero % 23;
     letra='TRWAGMYFPDXBNJZSQVHLCKET';
     letra=letra.substring(numero,numero+1);
     if (letra==letr.toUpperCase())
       return true;
  }
 return false;
}

function formateaDNI(dni){
    dni = dni.replace(/\-/g,'').toUpperCase();
    for( var i=dni.length;i<9;++i)
        dni="0"+dni;
    return dni;
}


function ValidateDNI() {
    var element = jQuery(this);
    var str = formateaDNI(element.val());
    element.val(str);
    console.log(str[0]);
var res = nif(str);
    jQuery(this).css("border", res?"":"2px solid red");
    console.log(str+" = "+res);
}

function OnDocumentReady(){
     jQuery("#dni").change(ValidateDNI);
}

jQuery( document ).ready(OnDocumentReady);

3/8 