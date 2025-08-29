$(document).ready(function() {
       initform();
       $("#checkform").submit(function(){
                var waiting = '<img src="wait.gif" style="padding: 0 5px;"/>Подождите, запрос обрабатывается...';
                $("#submitbutton").prop("disabled", true);
                $("#request").html('');
                $("#response").html(waiting);
                $.post(location.href, $('#checkform').serialize(), function(result){
                       var mode = $("#mode").val();
                       var request = $(result).filter("#request").html();
                       var response = $(result).filter("#response").html();
                       var url = $(result).filter("#url").val();
                       var status = $(result).filter("#status").val();
                       var checkform = $(result).filter("#checkform").html();

                       $("#request").html(request);

                       if (status==0){
                           $("#response").html(response + waiting);
                           setTimeout(function tick() {
                               $.ajax({
                                   url: url,
                                   success: function(response) {
                                       status = -1;
                                       if (mode=='xml') {
                                           status = response.documentElement.getAttribute('status');
                                           response = new XMLSerializer().serializeToString(response.documentElement);
                                           response = 'Ответ XML: <textarea style="width:100%;height:70%">' + response + '</textarea>';
                                       } else {
                                           status = $(response).find("#status").val();
                                       }
                                       if (status==1) {
                                           $("#response").html(response);
                                           $("#submitbutton").prop("disabled", false);
                                           if (mode=='html') {
                                               $("#checkform").html(checkform);
                                               initform();
                                           }
                                       } else {
                                           $("#response").html(response + waiting);
                                           setTimeout(tick, 3000);
                                       }
                                   },
                                   error: function(XMLHttpRequest, textStatus, errorThrown) { 
                                       setTimeout(tick, 3000);
/*
                                       if (XMLHttpRequest.status == 0) {
                                           alert(' Check Your Network.');
                                       } else if (XMLHttpRequest.status == 404) {
                                           alert('Requested URL not found.');
                                       } else if (XMLHttpRequest.status == 500) {
                                           alert('Internel Server Error.');
                                       }  else {
                                           alert('Unknow Error.\n' + XMLHttpRequest.responseText);
                                       }
*/     
                                   }
                               });
                           }, 3000);
                       } else {
                           $("#response").html(response);
                           $("#submitbutton").prop("disabled", false);
                           if (mode=='html') {
                               $("#checkform").html(checkform);
                               initform();
                           }
                       }
                });
                return false;
       });

    $("#ts").val( new Date().getTime() );

    $("#thefile").change(function(){
        $("#loading").toggle();
        $("#thefile").attr('disabled','disabled');
        files = this.files;
        var data = new FormData();
        $.each( files, function( key, value ){
            data.append( key, value );
        });
        data.append('ts', $("#ts").val());
        $.ajax({
            url: 'files.php?uploadfiles',
            type: 'POST',
            data: data,
            cache: false,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function( respond, textStatus, jqXHR ){
                if( typeof respond.error === 'undefined' ){
                    $("#loading").toggle();
//                    $("#thefile").removeAttr('disabled');
                    $("#thefile").hide();
                    $("#thefile").val('');
                    $.each(respond.files, function( key, value ) {
                        $("#forfiles").append('Загружен файл ' + value);
                        $("#filename").val(value);
                        $("#filename").show();
                    });
                }else{
                    $("#loading").toggle();
                    $("#thefile").removeAttr('disabled');
                    $("#thefile").val('');
                    alert('Ошибка ответа: ' + respond.error );
                }
            },
            error: function( jqXHR, textStatus, errorThrown ){
                $("#loading").toggle();
                $("#thefile").removeAttr('disabled');
                $("#thefile").val('');
                alert('Ошибка передачи: ' + errorThrown);
            }
        });
    });
});

function initform() {
        $("button#clear").click(function(){
            $("input[type=text]").val('');
            $("input[type=date]").val('');
            return false;
        });

        $("button#selectall").click(function(){
            $("input[type=checkbox].source").prop('checked',true);
            return false;
        });
        $("button#clearall").click(function(){
            $("input[type=checkbox].source").prop('checked',false);
            return false;
        });

        $("button#selectallrules").click(function(){
            $("input[type=checkbox].rule").prop('checked',true);
            return false;
        });
        $("button#clearallrules").click(function(){
            $("input[type=checkbox].rule").prop('checked',false);
            return false;
        });
}
