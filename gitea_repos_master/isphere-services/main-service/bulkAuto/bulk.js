$("#stepThree").submit(function(){
     //alert('here');
     var count = $(':checkbox:checked').length;
     //alert(count);
     if(count > 0){
         $("submit").prop("disabled", true);
         return true;
     }else{
         alert('Выбрите источник!');
         return false;
     }
});