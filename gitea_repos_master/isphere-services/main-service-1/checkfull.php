<?php

include('config.php');
include('auth.php');
include("xml.php");

set_time_limit($total_timeout+$http_timeout+15);

$user_level = get_user_level($mysqli);

if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['fssp'])) $_REQUEST['sources']['fssp'] = 1;
if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['fms'])) $_REQUEST['sources']['fms'] = 1;
if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['fns'])) $_REQUEST['sources']['fns'] = 1;
if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['bankrot'])) $_REQUEST['sources']['bankrot'] = 1;
if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['terrorist'])) $_REQUEST['sources']['terrorist'] = 1;
//if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['rz']))  $_REQUEST['sources']['rz'] = 1;
if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['people']))  $_REQUEST['sources']['people'] = 1;
if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['vk']))  $_REQUEST['sources']['vk'] = 1;
if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['ok']))  $_REQUEST['sources']['ok'] = 1;
if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['rossvyaz']))  $_REQUEST['sources']['rossvyaz'] = 1;
if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['hlr']))  $_REQUEST['sources']['hlr'] = 1;
if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['facebook']))  $_REQUEST['sources']['facebook'] = 1;
if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['beholder']))  $_REQUEST['sources']['beholder'] = 1;
if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['hh'])) $_REQUEST['sources']['hh'] = 1;
if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['announcement']))  $_REQUEST['sources']['announcement'] = 1;
if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['boards']))  $_REQUEST['sources']['boards'] = 1;
if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['skype']))  $_REQUEST['sources']['skype'] = 1;
if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['viber']))  $_REQUEST['sources']['viber'] = 1;
//if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['whatsapp']))  $_REQUEST['sources']['whatsapp'] = 1;
//if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['telegram']))  $_REQUEST['sources']['telegram'] = 1;
if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['commerce']))  $_REQUEST['sources']['commerce'] = 1;
if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['yamap']))  $_REQUEST['sources']['yamap'] = 1;
if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['2gis']))  $_REQUEST['sources']['2gis'] = 1;
if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['tc']))  $_REQUEST['sources']['tc'] = 1;
if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['numbuster']))  $_REQUEST['sources']['numbuster'] = 1;
if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['sberbank']))  $_REQUEST['sources']['sberbank'] = 1;
//if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['avinfo']))  $_REQUEST['sources']['avinfo'] = 1;
if(!isset($_REQUEST['mode']) && !isset($_REQUEST['sources']['phonenumber'])) $_REQUEST['sources']['phonenumber'] = 1;

if (!isset($_REQUEST['last_name'])) $_REQUEST['last_name']='';
if (!isset($_REQUEST['first_name'])) $_REQUEST['first_name']='';
if (!isset($_REQUEST['patronymic'])) $_REQUEST['patronymic']='';
if (!isset($_REQUEST['date'])) $_REQUEST['date']='';
if (!isset($_REQUEST['passport_series'])) $_REQUEST['passport_series']='';
if (!isset($_REQUEST['passport_number'])) $_REQUEST['passport_number']='';
if (!isset($_REQUEST['issueDate'])) $_REQUEST['issueDate']='';
if (!isset($_REQUEST['issueAuthority'])) $_REQUEST['issueAuthority']='';
if (!isset($_REQUEST['placeOfBirth'])) $_REQUEST['placeOfBirth']='';
if (!isset($_REQUEST['mobile_phone'])) $_REQUEST['mobile_phone']='';
if (!isset($_REQUEST['home_phone'])) $_REQUEST['home_phone']='';
if (!isset($_REQUEST['work_phone'])) $_REQUEST['work_phone']='';
if (!isset($_REQUEST['additional_phone'])) $_REQUEST['additional_phone']='';
if (!isset($_REQUEST['email'])) $_REQUEST['email']='';
if (!isset($_REQUEST['skype'])) $_REQUEST['skype']='';
if (!isset($_REQUEST['homeaddress'])) $_REQUEST['homeaddress']='';
if (!isset($_REQUEST['homeaddressArr'])) $_REQUEST['homeaddressArr']='';
if (!isset($_REQUEST['regaddress'])) $_REQUEST['regaddress']='';
if (!isset($_REQUEST['regaddressArr'])) $_REQUEST['regaddressArr']='';
if (!isset($_REQUEST['region_id'])) $_REQUEST['region_id']=0;
if (!isset($_REQUEST['sources'])) $_REQUEST['sources']=array();
if (!isset($_REQUEST['recursive'])) $_REQUEST['recursive']=0;
if (!isset($_REQUEST['async'])) $_REQUEST['async']=($_SERVER['REQUEST_METHOD']=='POST'?0:1);

if($_REQUEST['recursive']) {
    $_REQUEST['sources']['rossvyaz'] = 1;
    $_REQUEST['sources']['vk'] = 1;
    $_REQUEST['sources']['ok'] = 1;
    $_REQUEST['sources']['facebook'] = 1;
    $_REQUEST['sources']['beholder'] = 1;
    $_REQUEST['sources']['hh'] = 1;
    $_REQUEST['sources']['announcement'] = 1;
    $_REQUEST['sources']['boards'] = 1;
    $_REQUEST['sources']['commerce'] = 1;
    $_REQUEST['sources']['skype'] = 1;
    $_REQUEST['sources']['whatsapp'] = 1;
    $_REQUEST['sources']['viber'] = 1;
//    $_REQUEST['sources']['telegram'] = 1;
    $_REQUEST['sources']['yamap'] = 1;
    $_REQUEST['sources']['2gis'] = 1;
    $_REQUEST['sources']['tc'] = 1;
    $_REQUEST['sources']['numbuster'] = 1;
//    $_REQUEST['sources']['avinfo'] = 1;
    $_REQUEST['sources']['phonenumber'] = 1;
}

?>
<h1>Проверка физлица</h1><hr/><a href="admin.php">Назад</a><br/><br/>
<form id="checkform" method="POST">
    <table>
        <tr>
            <td>Регион:</td>
            <td>
            <select name="region_id">
                <option value="" selected>Все регионы</option>
                <option value="77" >Москва</option>
                <option value="22" >Алтайский край</option>
                <option value="28" >Амурская область</option>
                <option value="29" >Архангельская область</option>
                <option value="30" >Астраханская область</option>
                <option value="31" >Белгородская область</option>
                <option value="32" >Брянская область</option>
                <option value="33" >Владимирская область</option>
                <option value="34" >Волгоградская область</option>
                <option value="35" >Вологодская область</option>
                <option value="36" >Воронежская область</option>
                <option value="79" >Еврейская АО</option>
                <option value="75" >Забайкальский край</option>
                <option value="37" >Ивановская область</option>
                <option value="38" >Иркутская область</option>
                <option value="07" >Кабардино-Балкария</option>
                <option value="39" >Калининградская область</option>
                <option value="40" >Калужская область</option>
                <option value="41" >Камчатский край</option>
                <option value="09" >Карачаево-Черкессия</option>
                <option value="42" >Кемеровская область</option>
                <option value="43" >Кировская область</option>
                <option value="44" >Костромская область</option>
                <option value="23" >Краснодарский край</option>
                <option value="24" >Красноярский край</option>
                <option value="45" >Курганская область</option>
                <option value="46" >Курская область</option>
                <option value="47" >Ленинградская область</option>
                <option value="48" >Липецкая область</option>
                <option value="49" >Магаданская область</option>
                <option value="50" >Московская область</option>
                <option value="51" >Мурманская область</option>
                <option value="83" >Ненецкий АО</option>
                <option value="52" >Нижегородская область</option>
                <option value="53" >Новгородская область</option>
                <option value="54" >Новосибирская область</option>
                <option value="55" >Омская область</option>
                <option value="56" >Оренбургская область</option>
                <option value="57" >Орловская область</option>
                <option value="58" >Пензенская область</option>
                <option value="59" >Пермский край</option>
                <option value="25" >Приморский край</option>
                <option value="60" >Псковская область</option>
                <option value="01" >Республика Адыгея</option>
                <option value="04" >Республика Алтай</option>
                <option value="02" >Республика Башкортостан</option>
                <option value="03" >Республика Бурятия</option>
                <option value="05" >Республика Дагестан</option>
                <option value="06" >Республика Ингушетия</option>
                <option value="08" >Республика Калмыкия</option>
                <option value="10" >Республика Карелия</option>
                <option value="11" >Республика Коми</option>
                <option value="12" >Республика Марий-Эл</option>
                <option value="13" >Республика Мордовия</option>
                <option value="14" >Республика Саха (Якутия)</option>
                <option value="16" >Республика Татарстан</option>
                <option value="17" >Республика Тыва</option>
                <option value="19" >Республика Хакасия</option>
                <option value="61" >Ростовская область</option>
                <option value="62" >Рязанская область</option>
                <option value="63" >Самарская область</option>
                <option value="78" >Санкт-Петербург</option>
                <option value="64" >Саратовская область</option>
                <option value="65" >Сахалинская область</option>
                <option value="66" >Свердловская область</option>
                <option value="15" >Северная Осетия-Алания</option>
                <option value="67" >Смоленская область</option>
                <option value="26" >Ставропольский край</option>
                <option value="68" >Тамбовская область</option>
                <option value="69" >Тверская область</option>
                <option value="70" >Томская область</option>
                <option value="71" >Тульская область</option>
                <option value="72" >Тюменская область</option>
                <option value="18" >Удмуртская Республика</option>
                <option value="73" >Ульяновская область</option>
                <option value="27" >Хабаровский край</option>
                <option value="86" >Ханты-Мансийский АО</option>
                <option value="74" >Челябинская область</option>
                <option value="20" >Чеченская Республика</option>
                <option value="21" >Чувашская Республика</option>
                <option value="87" >Чукотский АО</option>
                <option value="89" >Ямало-Ненецкий АО</option>
                <option value="76" >Ярославская область</option>
            </select>
            </td>
        </tr>
        <tr>
            <td>Фамилия</td>
            <td>
                <input type="text" name="last_name" value="<?=$_REQUEST['last_name']?>" maxlength="500" />
            </td>
        </tr>
        <tr>
            <td>Имя</td>
            <td>
                <input type="text" name="first_name" value="<?=$_REQUEST['first_name']?>" maxlength="500" />
            </td>
        </tr>
        <tr>
            <td>Отчество</td>
            <td>
                <input type="text" name="patronymic" value="<?=$_REQUEST['patronymic']?>" maxlength="500" />
            </td>
        </tr>
        <tr>
            <td>Дата рождения</td>
            <td>
                <input type="text" id="date" name="date" value="<?=$_REQUEST['date']?>" pattern="(0[1-9]|1[0-9]|2[0-9]|3[01])\.(0[1-9]|1[012])\.[0-9]{4}" data-type="date" maxlength="50" />
            </td>
        </tr>

        <tr>
            <td>Серия паспорта</td>
            <td>
                <input type="text" name="passport_series" value="<?=$_REQUEST['passport_series']?>" maxlength="4" />
            </td>
        </tr>
        <tr>
            <td>Номер паспорта</td>
            <td>
                <input type="text" name="passport_number" value="<?=$_REQUEST['passport_number']?>" maxlength="6" />
            </td>
        </tr>
        <tr>
	     <td>Дата выдачи паспорта</td>
	     <td>
	         <input type="text" name="issueDate" value="<?=$_REQUEST['issueDate']?>" pattern="(0[1-9]|1[0-9]|2[0-9]|3[01])\.(0[1-9]|1[012])\.[0-9]{4}" data-type="date" maxlength="10" />
	     </td>
        </tr>
	<tr>
	     <td>Кем выдан паспорт</td>
	     <td>
	         <input type="text" name="issueAuthority" value="<?=$_REQUEST['issueAuthority']?>" maxlength="300" />
	     </td>
	</tr>
        <tr>
	    <td>Место рождения</td>
	    <td>
	        <input type="text" name="placeOfBirth" value="<?=$_REQUEST['placeOfBirth']?>" maxlength="300" />
	    </td>
	</tr>

        <tr>
            <td>Мобильный телефон<span class="req"></span></td>
            <td>
                <input type="text" name="mobile_phone" value="<?=$_REQUEST['mobile_phone']?>" maxlength="20" />
            </td>
        </tr>
        <tr>
            <td>Домашний телефон<span class="req"></span></td>
            <td>
                <input type="text" name="home_phone" value="<?=$_REQUEST['home_phone']?>" maxlength="20" />
            </td>
        </tr>
        <tr>
            <td>Рабочий телефон<span class="req"></span></td>
            <td>
                <input type="text" name="work_phone" value="<?=$_REQUEST['work_phone']?>" maxlength="20" />
            </td>
        </tr>
        <tr>
            <td>Дополнительный телефон<span class="req"></span></td>
            <td>
                <input type="text" name="additional_phone" value="<?=$_REQUEST['additional_phone']?>" maxlength="20" />
            </td>
        </tr>
        <tr>
            <td>Email<span class="req"></span></td>
            <td>
                <input type="text" name="email" value="<?=$_REQUEST['email']?>" maxlength="100" />
            </td>
        </tr>
        <tr>
	     <td>Домашний адрес</td>
	     <td><input class="address" id="homeaddress" name="homeaddress" type="text" size="100" value="<?=$_REQUEST['homeaddress']?>" />
	         <input type="hidden" id="homeaddressArr" name="homeaddressArr" size="1000" value="<?=htmlspecialchars($_REQUEST['homeaddressArr'])?>" />
	     </td>	     
	</tr>
	<tr>
	     <td>Совпадает</td>
	     <td><input type="checkbox" <?=($_REQUEST['same'] ? 'checked': '')?> name="same" id="same"></td>
	</tr>
	<tr>
	     <td>Адрес регистрации</td>
	     <td><input class="address" id="regaddress" name="regaddress" type="text" size="100" value="<?=$_REQUEST['regaddress']?>" />
	         <input type="hidden" id="regaddressArr" name="regaddressArr" size="1000" value="<?=htmlspecialchars($_REQUEST['regaddressArr'])?>" />
	     </td>
	</tr>
        <tr>
            <td>Источники</td>
            <td>
                <input type="checkbox" <?=($_REQUEST['sources']['fssp'] ? 'checked': '')?> name="sources[fssp]"> ФССП
                <!--input type="checkbox" <?=($_REQUEST['sources']['fsspsite'] ? 'checked': '')?> name="sources[fsspsite]"> ФССП (сайт) -->
                <input type="checkbox" <?=($_REQUEST['sources']['fms'] ? 'checked': '')?> name="sources[fms]"> ФМС
                <input type="checkbox" <?=($_REQUEST['sources']['fns'] ? 'checked': '')?> name="sources[fns]"> ФНС
                <br/>
                <input type="checkbox" <?=($_REQUEST['sources']['bankrot'] ? 'checked': '')?> name="sources[bankrot]"> Банкроты
                <input type="checkbox" <?=($_REQUEST['sources']['terrorist'] ? 'checked': '')?> name="sources[terrorist]"> Террористы
		<input type="checkbox" <?=($_REQUEST['sources']['rz'] ? 'checked': '')?> name="sources[rz]"> Реестр залогов
                <br/>
		<!-- input type="checkbox" <?=($_REQUEST['sources']['croinform'] ? 'checked': '')?> name="sources[croinform]"> Кронос -->
		<!-- input type="checkbox" <?=($_REQUEST['sources']['nbki'] ? 'checked': '')?> name="sources[nbki]"> НБКИ -->
                <br/>
		<input type="checkbox" <?=($_REQUEST['sources']['people'] ? 'checked': '')?> name="sources[people]"> Соцсети
		<input type="checkbox" <?=($_REQUEST['sources']['vk'] ? 'checked': '')?> name="sources[vk]"> VK
		<input type="checkbox" <?=($_REQUEST['sources']['ok'] ? 'checked': '')?> name="sources[ok]"> OK
                <input type="checkbox" <?=($_REQUEST['sources']['facebook'] ? 'checked': '')?> name="sources[facebook]"> Facebook
		<input type="checkbox" <?=($_REQUEST['sources']['beholder'] ? 'checked': '')?> name="sources[beholder]"> Beholder
                <br/>
                <input type="checkbox" <?=($_REQUEST['sources']['rossvyaz'] ? 'checked': '')?> name="sources[rossvyaz]"> Россвязь
                <input type="checkbox" <?=($_REQUEST['sources']['hlr'] ? 'checked': '')?> name="sources[hlr]"> HLR
                <input type="checkbox" <?=($_REQUEST['sources']['ss7'] ? 'checked': '')?> name="sources[ss7]"> HLR-2
                <br/>
<?php 
if ($user_level>0) echo '     
		<input type="checkbox" '.($_REQUEST['sources']['hh'] ? 'checked': '').' name="sources[hh]"> HH';
?>
		<input type="checkbox" <?=($_REQUEST['sources']['announcement'] ? 'checked': '')?> name="sources[announcement]"> Объявления
		<input type="checkbox" <?=($_REQUEST['sources']['commerce'] ? 'checked': '')?> name="sources[commerce]"> Commerce
                <br/>
                <input type="checkbox" <?=($_REQUEST['sources']['skype'] ? 'checked': '')?> name="sources[skype]"> Skype
                <input type="checkbox" <?=($_REQUEST['sources']['viber'] ? 'checked': '')?> name="sources[viber]"> Viber
                <input type="checkbox" <?=($_REQUEST['sources']['whatsapp'] ? 'checked': '')?> name="sources[whatsapp]"> WhatsApp
                <!--input type="checkbox" <?=($_REQUEST['sources']['checkwa'] ? 'checked': '')?> name="sources[checkwa]">  CheckWA -->
                <!--input type="checkbox" <?=($_REQUEST['sources']['telegram'] ? 'checked': '')?> name="sources[telegram]"> Telegram -->
		<br />
		<input type="checkbox" <?=($_REQUEST['sources']['yamap'] ? 'checked': '')?> name="sources[yamap]"> Яндекс.Карты
		<input type="checkbox" <?=($_REQUEST['sources']['2gis'] ? 'checked': '')?> name="sources[2gis]"> 2ГИС
		<input type="checkbox" <?=($_REQUEST['sources']['sberbank'] ? 'checked': '')?> name="sources[sberbank]"> Сбербанк
		<input type="checkbox" <?=($_REQUEST['sources']['tinkoff'] ? 'checked': '')?> name="sources[tinkoff]"> Tinkoff
		<br />
		<input type="checkbox" <?=($_REQUEST['sources']['tc'] ? 'checked': '')?> name="sources[tc]"> TrueCaller
		<input type="checkbox" <?=($_REQUEST['sources']['numbuster'] ? 'checked': '')?> name="sources[numbuster]"> NumBuster
		<!--input type="checkbox" <?=($_REQUEST['sources']['avinfo'] ? 'checked': '')?> name="sources[avinfo]"> AvInfo -->
		<input type="checkbox" <?=($_REQUEST['sources']['phonenumber'] ? 'checked': '')?> name="sources[phonenumber]"> PhoneNumber
                <br/>
	    </td>
        </tr>
        <tr>
            <td>Поиск по найденным контактам</td>
            <td>
                <input type="checkbox" <?=($_REQUEST['recursive'] ? 'checked': '')?> name="recursive" id="recursive">
	    </td>
        </tr>
        <tr>
            <td>Подгружать информацию по мере получения</td>
            <td>
                <input type="checkbox" <?=($_REQUEST['async'] ? 'checked': '')?> name="async" id="async">
	    </td>
        </tr>
        <tr>
            <td>Формат ответа:</td>
            <td>
            <select name="mode" id="mode">
                <option value="xml">XML</option>
                <option value="html" selected>HTML</option>
            </select>
            </td>
        </tr>

        <tr>
            <td colspan="2">
                <input id="submitbutton" type="submit" value="Найти">
            </td>
        </tr>
    </table>
</form>

<hr/>

<link href="https://dadata.ru/static/css/lib/suggestions-15.6.css" type="text/css" rel="stylesheet" />
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<!--[if lt IE 10]>
<script type="text/javascript" src="http://cdnjs.cloudflare.com/ajax/libs/jquery-ajaxtransport-xdomainrequest/1.0.1/jquery.xdomainrequest.min.js"></script>
<![endif]-->
<script type="text/javascript" src="https://dadata.ru/static/js/lib/jquery.suggestions-15.6.min.js"></script>
<script type="text/javascript">
    var nsuggestion;
    $(".address").suggestions({
        serviceUrl: "https://dadata.ru/api/v2",
        token: "ae9417a657d43f6d74b6c65f449e2f4a9fff0e38",
        type: "ADDRESS",
        count: 10,
	minChars: 5,
	deferRequestBy: 1000,
        onSelect: function(suggestion) {
//            console.log(suggestion);
              nsuggestion = suggestion;
        }
    });
</script>
<script>
       if($("#same").prop('checked')){
              $('#regaddress').prop( "disabled", true );
       }
       else{
              $('#regaddress').prop( "disabled", false );
       }
       $("#same").change(function(){
//                alert($(this).prop('checked'));
                  if( $('#homeaddress').val() != '' && $('#homeaddressArr').val() != '' ){
                           if( $(this).prop('checked') ){
		                 $('#regaddress').prop( "disabled", true );
				 $('#regaddress').val($('#homeaddress').val());
				 $('#regaddressArr').val($('#homeaddressArr').val());
		           }
		           else{
                                 $('#regaddress').prop( "disabled", false );
				 $('#regaddress').val('');
				 $('#regaddressArr').val('');		         
		           }
	          }
		  else{
		           $(this).prop('checked', false);
			   $('#regaddress').prop( "disabled", false );
			   alert('Адрес не указан');
		  }
       });       
       $(".address").blur(function(){
                 var parentTD = $(this).parent();
		 var arrayNAME = $(this).attr('id');
//		 alert(arrayNAME);
                 if(typeof(nsuggestion) == 'object'){
                         for(var key in nsuggestion.data){
//			          parentTD.append('<input type="text" name="'+arrayNAME+'Arr['+key+']" value="'+nsuggestion.data[key]+'">');
//                                  parentTD.append('<input type="text" name="'+key+'" value="'+nsuggestion.data[key]+'">');
                                  if(nsuggestion.data[key] == null){
				          delete(nsuggestion.data[key]);
				  }
			 }
                         $('#'+arrayNAME+'Arr').val(JSON.stringify(nsuggestion.data));
		 }
       });
</script>

<?php

if(!isset($_REQUEST['mode']) || (!sizeof($_REQUEST['sources']))) {
    print '<div id="request">';
    print '</div>';
    print '<div id="response">';
    print '</div>';
    exit();
}

$xml ="
<Request>
        <UserIP>{$_SERVER['REMOTE_ADDR']}</UserIP>
        <UserID>{$_SERVER['PHP_AUTH_USER']}</UserID>
        <Password>{$_SERVER['PHP_AUTH_PW']}</Password>
        <requestId>".time()."</requestId>
        <requestType>checkfull</requestType>
        <sources>".implode(',',array_keys($sources))."</sources>
        <recursive>".($_REQUEST['recursive']?'1':'0')."</recursive>
        <async>".($_REQUEST['async']?'1':'0')."</async>"
. (!$_REQUEST['last_name'] && !$_REQUEST['passport_number'] ? "" : "
        <PersonReq>
            <first>{$_REQUEST['first_name']}</first>
            <middle>{$_REQUEST['patronymic']}</middle>
            <paternal>{$_REQUEST['last_name']}</paternal>"
. (!$_REQUEST['date'] ? "" : "
            <birthDt>{$_REQUEST['date']}</birthDt>"

) . (!$_REQUEST['placeOfBirth'] ? "" : "
            <placeOfBirth>{$_REQUEST['placeOfBirth']}</placeOfBirth>"
	    	    
) . (!$_REQUEST['passport_number'] ? "" : "
            <passport_series>{$_REQUEST['passport_series']}</passport_series>
            <passport_number>{$_REQUEST['passport_number']}</passport_number>"
) . (!$_REQUEST['issueDate'] ? "" : "
            <issueDate>{$_REQUEST['issueDate']}</issueDate>"
	    
) . (!$_REQUEST['issueAuthority'] ? "" : "
            <issueAuthority>{$_REQUEST['issueAuthority']}</issueAuthority>"
	    
) . (!$_REQUEST['region_id'] ? "" : "
            <region_id>{$_REQUEST['region_id']}</region_id>"

) . (!$_REQUEST['homeaddress'] ? "" : "
            <homeaddress>{$_REQUEST['homeaddress']}</homeaddress>"

) . (!$_REQUEST['homeaddressArr'] ? "" : "
            <homeaddressArr>{$_REQUEST['homeaddressArr']}</homeaddressArr>"

) . (!$_REQUEST['regaddress'] ? "" : "
            <regaddress>{$_REQUEST['regaddress']}</regaddress>"	    

) . (!$_REQUEST['regaddressArr'] ? "" : "
            <regaddressArr>{$_REQUEST['regaddressArr']}</regaddressArr>"
	    
	    
) . "
        </PersonReq>"
) . (!$_REQUEST['mobile_phone'] ? "" : "
        <PhoneReq>
            <phone>{$_REQUEST['mobile_phone']}</phone>
        </PhoneReq>"
) . (!$_REQUEST['home_phone'] ? "" : "
        <PhoneReq>
            <phone>{$_REQUEST['home_phone']}</phone>
        </PhoneReq>"
) . (!$_REQUEST['work_phone'] ? "" : "
        <PhoneReq>
            <phone>{$_REQUEST['work_phone']}</phone>
        </PhoneReq>"
) . (!$_REQUEST['additional_phone'] ? "" : "
        <PhoneReq>
            <phone>{$_REQUEST['additional_phone']}</phone>
        </PhoneReq>"
) . (!$_REQUEST['email'] ? "" : "
        <EmailReq>
            <email>{$_REQUEST['email']}</email>
        </EmailReq>"
) . "
</Request>";

print '<div id="request">';
if ($_REQUEST['mode']=='xml') {
    print 'Запрос XML: <textarea style="width:100%;height:30%">';
    $request = preg_replace("/<Password>[^<]+<\/Password>/", "<Password>***</Password>", $xml);
    print $request;
    print '</textarea>';
    print "<hr/>";
}
print '</div>';

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $serviceurl.'index.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, $total_timeout+10);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
curl_setopt($ch, CURLOPT_POST, 1);

$answer = curl_exec($ch);
curl_close($ch);

if ($_REQUEST['mode']=='xml') {
    print 'Ответ XML: <textarea style="width:100%;height:70%">';
    print $answer;
    print '</textarea>';
} else {
    $answer = substr($answer,strpos($answer,'<?xml'));
    $doc = xml_transform($answer, 'isphere_view.xslt');
    if ($doc) {
        $servicename = isset($servicenames[$_SERVER['HTTP_HOST']])?'платформой '.$servicenames[$_SERVER['HTTP_HOST']]:'';
        echo strtr($doc->saveHTML(),array('$servicename'=>$servicename));
    } else  {
        echo $answer?'Некорректный ответ сервиса':'Нет ответа от сервиса';
    }
}
