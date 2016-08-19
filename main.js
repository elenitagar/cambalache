

function validaDNI(dni) {
  var expresion_regular_dni = /^\d{8}[a-zA-Z]$/;
  // Valida por expresion regular.
  if(expresion_regular_dni.test (dni) == true){
    // Si se cumple, comprueba que la letra coincida.
    var numero = dni.substr(0,dni.length-1) % 23;
    var letra='TRWAGMYFPDXBNJZSQVHLCKET';
    letra=letra.substring(numero,numero+1);
    if (letra==dni.substr(dni.length-1,1).toUpperCase())
       return true;
  }
 return false;
}

// Formatea el DNI añadiendo ceros y poniendo la letra en mayusculas.
function formateaDNI(dni){
    dni = dni.replace(/\-/g,'').toUpperCase();
    for( var i=dni.length;i<9;++i)
        dni="0"+dni;
    return dni;
}


function OnDocumentReady(){
    jQuery("#dni").change(function () {
        var element = jQuery(this);
        var str = formateaDNI(element.val());
        element.val(str);
        var res = validaDNI(str); // retorna true o false
        if(res) jQuery("#dni-err").hide(); else jQuery("#dni-err").show();
        // jQuery(this).css("border", res ? "":"2px solid red");
        // console.log(str+" = "+res);
    });
}

jQuery( document ).ready(OnDocumentReady);
