<?php

require ('config.php');
include ('engine/RequestContext.php');
include ('engine/plugins/PluginInterface.php');

$mysqli = mysqli_connect ($database['server'],$database['login'],$database['password'], $database['name']) or die(mysqli_errno($db) . ": " . mysqli_error($db));
if ($mysqli) {
    mysqli_query($mysqli, "Set character set utf8");
    mysqli_query($mysqli, "Set names 'utf8'");
}
    
//////////////////////////////////////////////////////////

$plugin_interface = array();
$plugins = initPlugins();
$results = initRequestContexts($plugins);

$response = "source_code;source_name;source_title;checktype;checktype_title;plugin\n";
$response = "";
foreach($results as $result) {
    $response .= $result->getSource().";".$result->getSourceName().";".$result->getSourceTitle().";".$result->getCheckType().";".$result->getCheckTitle().';'.get_class($result->getPlugin())."\n";
    $mysqli->query("UPDATE CheckType SET plugin='".get_class($result->getPlugin())."' WHERE code='".$result->getCheckType()."'");
}
echo $response;

function initRequestContexts($plugins,$level=0,$path=false,$start=false) {
    $ContextPool = array();

            foreach($plugins['person'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                        $ContextPool[] = new RequestContext($source, $source_check, false, $path, $start, 'person', $level, $plugin, array());

            foreach($plugins['org'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                        $ContextPool[] = new RequestContext($source, $source_check, false, $path, $start, 'org', $level, $plugin, array());

            foreach($plugins['car'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                        $ContextPool[] = new RequestContext($source, $source_check, false, $path, $start, 'car', $level, $plugin, array());

            foreach($plugins['phone'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                        $ContextPool[] = new RequestContext($source, $source_check, false, $path, $start, 'phone', $level, $plugin, array());

            foreach($plugins['email'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                        $ContextPool[] = new RequestContext($source, $source_check, false, $path, $start, 'email', $level, $plugin, array());

            foreach($plugins['skype'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                        $ContextPool[] = new RequestContext($source, $source_check, false, $path, $start, 'skype', $level, $plugin, array());

            foreach($plugins['nick'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                        $ContextPool[] = new RequestContext($source, $source_check, false, $path, $start, 'nick', $level, $plugin, array());

            foreach($plugins['url'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                        $ContextPool[] = new RequestContext($source, $source_check, false, $path, $start, 'url', $level, $plugin, array());

            foreach($plugins['ip'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                        $ContextPool[] = new RequestContext($source, $source_check, false, $path, $start, 'ip', $level, $plugin, array());

            foreach($plugins['card'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                        $ContextPool[] = new RequestContext($source, $source_check, false, $path, $start, 'card', $level, $plugin, array());

            foreach($plugins['fssp_ip'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                        $ContextPool[] = new RequestContext($source, $source_check, false, $path, $start, 'fssp_ip', $level, $plugin, array());

            foreach($plugins['osago'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                        $ContextPool[] = new RequestContext($source, $source_check, false, $path, $start, 'osago', $level, $plugin, array());

            foreach($plugins['text'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                        $ContextPool[] = new RequestContext($source, $source_check, false, $path, $start, 'text', $level, $plugin, array());

    return $ContextPool;
}

function initPlugins()
{
/*
    $fms = new FMSPlugin();
    $fmsdb = new FMSDBPlugin();
    $mvdwanted = new MVDWantedPlugin();
    $gosuslugi = new GosuslugiPlugin();
    $fns = new FNSPlugin();
    $egrul = new EGRULPlugin();
    $gisgmp = new GISGMPPlugin();
    $fssp = new FSSPPlugin();
    $fsspapi = new FSSPAPIPlugin();
    $fsspapp = new FSSPAppPlugin();
    $vestnik = new VestnikPlugin();
    $gks = new GKSPlugin();
    $kad = new KADPlugin();
    $zakupki = new ZakupkiPlugin();
    $bankrot = new BankrotPlugin();
    $cbr = new CBRPlugin();
    $terrorist = new TerroristPlugin();
    $croinform = new CROINFORMPlugin();
    $nbki = new NBKIPlugin();
    $people = new YaPeoplePlugin();
    $vk = new VKPlugin();
    $ok = new OKPlugin();
    $mailru = new MailRuPlugin();

    $rossvyaz = new RossvyazPlugin();
    $smsc = new SMSCPlugin();
    $infobip = new InfobipPlugin();
//    $infobip = new InfobipNewPlugin();
    $stream = new StreamPlugin();
    $facebook = new FacebookPlugin();
    $instagram = new InstagramPlugin();
    $twitter = new TwitterPlugin();
    $hh = new HHPlugin();
    $whatsapp = new WhatsAppPlugin();
    $whatsappweb = new WhatsAppWebPlugin();
//    $checkwa = new CheckWAPlugin();
    $announcement = new AnnouncementPlugin();
    $boards = new BoardsPlugin();
    $yamap = new YaMapPlugin();
    $gis = new GISPlugin();
    $listorg = new ListOrgPlugin();
    $commerce = new CommercePlugin();
    $viber = new ViberPlugin();
    $viberwin = new ViberWinPlugin();
    $telegram = new TelegramPlugin();
    $truecaller = new TrueCallerPlugin();
    $truecallerweb = new TrueCallerWebPlugin();
    $tc = new TCPlugin();
    $emt = new EMTPlugin();
    $getcontact = new GetContactPlugin();
    $numbuster = new NumBusterPlugin();
    $names = new NamesPlugin();
    $phones = new PhonesPlugin();
    $vkcheck = new VKCheckPlugin();
    $sberbank = new SberbankPlugin();

//    $tinkoff = new TinkoffPlugin();
//    $banks = new BanksPlugin();
//    $visa = new VISAPlugin();
//    $sbert = new SberTPlugin();
//    $alfabankt = new AlfabankTPlugin();
//    $raiffeisent = new RaiffeisenTPlugin();
//    $psbankt = new PSBankTPlugin();
//    $rosbankt = new RosbankTPlugin();
//    $raiffeisen = new RaiffeisenPlugin();
//    $tinkoffr = new TinkoffRPlugin();
//    $alfabankr = new AlfabankRPlugin();
//    $psbankr = new PSBankRPlugin();
//    $rosbankr = new RosbankRPlugin();
//    $sovcombankr = new SovcombankRPlugin();
//    $gazprombankr = new GazprombankRPlugin();
//    $qiwibankr = new QiwibankRPlugin();

    $sberw = new SberWPlugin();
    $sbers = new SberSPlugin();
    $sbps = new SBPSPlugin();

//    $tinkoffs = new TinkoffSPlugin();
//    $alfabanks = new AlfabankSPlugin();
//    $psbanks = new PSBankSPlugin();
//    $raiffeisens = new RaiffeisenSPlugin();
//    $sovcombank = new SovcombankPlugin();

    $phonenumber = new PhoneNumberPlugin();
    $avinfo = new AvInfoPlugin();
    $beholder = new BeholderPlugin();
    $skype = new SkypePlugin();
    $googleplus = new GooglePlusPlugin();
    $google = new GooglePlugin();
    $apple = new ApplePlugin();
    $qiwi = new QiwiPlugin();
    $yamoney = new YaMoneyPlugin();
    $elecsnet = new ElecsnetPlugin();
    $webmoney = new WebMoneyPlugin();
    $aeroflot = new AeroflotPlugin();
    $uralair = new UralAirPlugin();
    $rzd = new RZDPlugin();
    $papajohns = new PapaJohnsPlugin();
    $biglion = new BiglionPlugin();
    $avito = new AvitoPlugin();

    $gibdd = new GIBDDPlugin();
    $eaisto = new EAISTOPlugin();
    $rsa = new RSAPlugin();
    $kbm = new KBMPlugin();
    $rz = new RZPlugin();
    $reestrzalogov = new ReestrZalogovPlugin();
//    $autoru = new AutoRuPlugin();
    $vin = new VINPlugin();
    $avtokod = new AvtoKodPlugin();
    $mosru = new MosRuPlugin();
    $mosrufines = new MosRuFinesPlugin();
    $nbkiauto = new NBKIAutoPlugin();
    $avinfo = new AvInfoPlugin();

    $dns = new DNSPlugin();
    $ripe = new RIPEPlugin();
    $ipgeo = new IPGeoBasePlugin();
    $sypexgeo = new SypexGeoPlugin();
    $shodan = new ShodanPlugin();
    $censys = new CensysPlugin();
*/
    $fms = 'FMSPlugin';
    $fmsdb = 'FMSDBPlugin';
    $mvdwanted = 'MVDWantedPlugin';
    $gosuslugi = 'GosuslugiPlugin';
    $fns = 'FNSPlugin';
    $egrul = 'EGRULPlugin';
    $gisgmp = 'GISGMPPlugin';
    $notariat = 'NotariatPlugin';
    $fssp = 'FSSPPlugin';
    $fsspapi = 'FSSPAPIPlugin';
    $fsspapp = 'FSSPAppPlugin';
    $vestnik = 'VestnikPlugin';
    $gks = 'GKSPlugin';
    $kad = 'KADPlugin';
    $zakupki = 'ZakupkiPlugin';
    $bankrot = 'BankrotPlugin';
    $cbr = 'CBRPlugin';
    $terrorist = 'TerroristPlugin';
    $croinform = 'CROINFORMPlugin';
    $nbki = 'NBKIPlugin';
    $people = 'YaPeoplePlugin';
    $vk = 'VKPlugin';
    $ok = 'OKPlugin';
    $okapp = 'OKAppPlugin';
    $okappr = 'OKAppRPlugin';
    $mailru = 'MailRuPlugin';
    $fotostrana = 'FotostranaPlugin';

    $rossvyaz = 'RossvyazPlugin';
    $smsc = 'SMSCPlugin';
    $infobip = 'InfobipPlugin';
//    $infobip = 'InfobipNewPlugin';
    $stream = 'StreamPlugin';
    $smspilot = 'SMSPilotPlugin';
    $hlr = 'HLRPlugin';
    $facebook = 'FacebookPlugin';
    $instagram = 'InstagramPlugin';
    $twitter = 'TwitterPlugin';
    $hh = 'HHPlugin';
    $whatsapp = 'WhatsAppPlugin';
    $whatsappweb = 'WhatsAppWebPlugin';
//    $checkwa = 'CheckWAPlugin';
    $announcement = 'AnnouncementPlugin';
    $boards = 'BoardsPlugin';
    $yamap = 'YaMapPlugin';
    $gis = 'GISPlugin';
    $listorg = 'ListOrgPlugin';
    $commerce = 'CommercePlugin';
    $viber = 'ViberPlugin';
    $viberwin = 'ViberWinPlugin';
    $telegram = 'TelegramPlugin';
    $telegramweb = 'TelegramWebPlugin';
    $icq = 'ICQPlugin';
    $truecaller = 'TrueCallerPlugin';
    $truecallerweb = 'TrueCallerWebPlugin';
    $tc = 'TCPlugin';
    $emt = 'EMTPlugin';
    $getcontact = 'GetContactPlugin';
    $getcontactapp = 'GetContactAppPlugin';
    $callapp = 'CallAppPlugin';
    $simpler = 'SimplerPlugin';
    $numbuster = 'NumBusterPlugin';
    $numbusterapp = 'NumBusterAppPlugin';
    $numbusterpro = 'NumBusterProPlugin';
    $names = 'NamesPlugin';
    $phones = 'PhonesPlugin';
    $vkcheck = 'VKCheckPlugin';
    $vkauth = 'VKAuthPlugin';
    $okbot= 'OKBotPlugin';
    $sberbank = 'SberbankPlugin';
/*
    $tinkoff = 'TinkoffPlugin';
    $banks = 'BanksPlugin';
    $visa = 'VISAPlugin';
    $sbert = 'SberTPlugin';
    $alfabankt = 'AlfabankTPlugin';
    $raiffeisent = 'RaiffeisenTPlugin';
    $psbankt = 'PSBankTPlugin';
    $rosbankt = 'RosbankTPlugin';
    $raiffeisen = 'RaiffeisenPlugin';
    $tinkoffr = 'TinkoffRPlugin';
    $alfabankr = 'AlfabankRPlugin';
    $psbankr = 'PSBankRPlugin';
    $rosbankr = 'RosbankRPlugin';
    $sovcombankr = 'SovcombankRPlugin';
    $gazprombankr = 'GazprombankRPlugin';
    $qiwibankr = 'QiwibankRPlugin';
*/
    $sberw = 'SberWPlugin';
    $sbers = 'SberSPlugin';
    $sbpw = 'SBPWPlugin';
    $sbps = 'SBPSPlugin';
/*
    $sovcombank = 'SovcombankPlugin';
    $tinkoffs = 'TinkoffSPlugin';
    $alfabanks = 'AlfabankSPlugin';
    $psbanks = 'PSBankSPlugin';
    $raiffeisens = 'RaiffeisenSPlugin';
*/
    $sb = 'SBPlugin';
    $phonenumber = 'PhoneNumberPlugin';
    $avinfo = 'AvInfoPlugin';
    $beholder = 'BeholderPlugin';
    $microsoft = 'MicrosoftPlugin';
    $skype = 'SkypePlugin';
    $googleplus = 'GooglePlusPlugin';
    $google = 'GooglePlugin';
    $apple = 'ApplePlugin';
    $qiwi = 'QiwiPlugin';
    $yamoney = 'YaMoneyPlugin';
    $elecsnet = 'ElecsnetPlugin';
    $webmoney = 'WebMoneyPlugin';
    $pochta = 'PochtaPlugin';
    $aeroflot = 'AeroflotPlugin';
    $uralair = 'UralAirPlugin';
    $rzd = 'RZDPlugin';
    $papajohns = 'PapaJohnsPlugin';
    $biglion = 'BiglionPlugin';
    $avito = 'AvitoPlugin';

    $gibdd = 'GIBDDPlugin';
    $eaisto = 'EAISTOPlugin';
    $rsa = 'RSAPlugin';
    $kbm = 'KBMPlugin';
    $rz = 'RZPlugin';
    $reestrzalogov = 'ReestrZalogovPlugin';
//    $autoru = 'AutoRuPlugin';
    $vin = 'VINPlugin';
    $avtokod = 'AvtoKodPlugin';
    $mosru = 'MosRuPlugin';
    $mosrufines = 'MosRuFinesPlugin';
    $nbkiauto = 'NBKIAutoPlugin';
    $avinfo = 'AvInfoPlugin';

    $dns = 'DNSPlugin';
    $ripe = 'RIPEPlugin';
    $ipgeo = 'IPGeoBasePlugin';
    $sypexgeo = 'SypexGeoPlugin';
    $shodan = 'ShodanPlugin';
    $censys = 'CensysPlugin';

    $plugins = array(
      'person' => array(
        'fms' => array('fms_passport' => $fms),
        'fmsdb' => array('fmsdb_passport' => $fmsdb),
        'gosuslugi' => array('gosuslugi_passport' => $gosuslugi, 'gosuslugi_inn' => $gosuslugi, 'gosuslugi_snils' => $gosuslugi),
        'fns' => array('fns_inn' => $fns, 'fns_bi' => $fns, 'fns_disqualified' => $fns, 'fns_mru' => $fns, 'fns_npd' => $fns, 'fns_invalid' => $fns),
        'mvd' => array('mvd_wanted' => $mvdwanted),
        'gisgmp' => array('gisgmp_taxes' => $gisgmp, /*'gisgmp_fssp' => $gisgmp,*/ 'gisgmp_fines' => $gisgmp),
        'notariat' => array('notariat_person' => $notariat),
        'fssp' => array('fssp_person' => /*$fsspapp),
        'fsspsite' => array('fssp_person' => */$fssp),
//        'fsspapi' => array('fssp_person' => $fsspapi),
        'fssp_suspect' => array('fssp_suspect' => $fssp),
        'bankrot' => array('bankrot_person' => $bankrot, 'bankrot_inn' => $bankrot),
        'cbr' => array('cbr_person' => $cbr),
        'terrorist' => array('terrorist_person' => $terrorist),
//        'croinform' => array('croinform_person' => $croinform),
//        'nbki' => array('nbki_credithistory' => $nbki),
//        'people' => array('people' => $people),
        'vk' => array('vk_person' => $vk),
        'ok' => array('ok_person' => $ok),
        'rz' => array('rz_person' => $rz),
        'reestrzalogov' => array('reestrzalogov_person' => $reestrzalogov),
//        'avtokod' => array(/*'avtokod_driver' => $avtokod, *//*'avtokod_fines' => $mosrufines*/),
        'gibdd' => array('gibdd_driver' => $gibdd),
        'rsa' => array('rsa_kbm' => $rsa /*$kbm*/),
        'egrul' => array('egrul_person' => $egrul),
        'zakupki' => array(/*'zakupki_eruz' => $zakupki, */'zakupki_order' => $zakupki, 'zakupki_contract' => $zakupki, 'zakupki_fz223' => $zakupki, 'zakupki_capital' => $zakupki, 'zakupki_dishonest' => $zakupki, 'zakupki_guarantee' => $zakupki, 'zakupki_rkpo' => $zakupki),
        'kad' => array('kad_person' => $kad),
        '2gis' => array('2gis_inn' => $gis),
      ),
      'phone' => array(
        'gosuslugi' => array('gosuslugi_phone' => $gosuslugi),
        'rossvyaz' => array('rossvyaz_phone' => $rossvyaz),
        'hlr' => array('hlr_phone' => $hlr /*$smspilot*/ /*$stream*/),
//        'ss7' => array('infobip_phone' => $stream),
        'smsc' => array('smsc_phone' => $smsc),
//        'infobip' => array('infobip_phone' => $infobip),
//        'sber' => array('sberbank_phone' => $sberbank),
        'sberbank' => array('sberbank_phone' => $sbers),
        'sbertest' => array('sberbank_phone' => $sberw),
        'tinkoff' => array('tinkoff_phone' => $sbps),
        'alfabank' => array('alfabank_phone' => $sbps),
//        'vtb' => array('vtb_phone' => $sbps),
        'openbank' => array('openbank_phone' => $sbps),
        'psbank' => array('psbank_phone' => $sbps),
        'rosbank' => array('rosbank_phone' => $sbps),
        'unicredit' => array('unicredit_phone' => $sbps),
        'raiffeisen' => array('raiffeisen_phone' => $sbps),
        'sovcombank' => array('sovcombank_phone' => $sbps),
        'gazprombank' => array('gazprombank_phone' => $sbps),
        'mkb' => array('mkb_phone' => $sbps),
        'rsb' => array('rsb_phone' => $sbps),
        'avangard' => array('avangard_phone' => $sbps),
        'qiwibank' => array('qiwibank_phone' => $sbps),
        'rnko' => array('rnko_phone' => $sbps),
//        'visa' => array('visa_phone' => $visa),
        'facebook' => array('facebook_phone' => $facebook),
        'vk' => array('vk_phone' => $vk, 'vk_phonecheck' => $vkcheck),
        'ok' => array('ok_phone' => $ok, 'ok_phonecheck' => $okbot, 'ok_phoneapp' => $okappr),
        'instagram' => array('instagram_phone' => $instagram),
        'twitter' => array('twitter_phone' => $twitter),
        'fotostrana' => array('fotostrana_phone' => $fotostrana),
//        'beholder' => array('beholder_phone' => $beholder),
        'microsoft' => array('microsoft_phone' => $microsoft),
        'skype' => array('skype_phone' => $skype),
        'googleplus' => array('googleplus_phone' => $googleplus),
        'google' => array( 'google_phone' => $google, 'google_name' => $google),
//        'googlename' => array('googlename_phone' => $google),
        'viber' => array('viber_phone' => $viberwin),
//        'viberwin' => array('viberwin_phone' => $viberwin),
        'telegram' => array('telegram_phone' => $telegram),
//        'telegramweb' => array('telegramweb_phone' => $telegram),
//        'telegramweb' => array('telegramweb_phone' => $telegramweb),
//        'icq' => array('icq_phone' => $icq),
        'whatsapp' => array('whatsappweb_phone' => $whatsapp),
//        'whatsappweb' => array('whatsappweb_phone' => $whatsappweb),
        'whatsapp_phone' => array('whatsapp_phone' => $whatsapp),
//        'hh' => array('hh_phone' => $hh),
        'truecaller' => array('truecaller_phone' => $truecaller),
//        'truecaller' => array('truecallerweb_phone' => $tc),
        'tc' => array('truecaller_phone' => $truecaller),
//        'tc' => array('truecallerweb_phone' => $tc),
        'emt' => array('emt_phone' => $emt),
//        'getcontactweb' => array('getcontactweb_phone' => $getcontact),
        'getcontact' => array('getcontact_phone' => $getcontactapp),
        'getcontacttags' => array('getcontacttags_phone' => $getcontactapp),
        'callapp' => array('callapp_phone' => $callapp),
        'simpler' => array('simpler_phone' => $simpler),
        'numbuster' => array('numbuster_phone' => $numbuster),
//        'numbusterapp' => array('numbuster_phone' => $numbusterapp),
        'numbusterpro' => array('numbusterpro_phone' => $numbusterpro),
        'names' => array('names_phone' => $names),
        'phones' => array('phones_phone' => $phones),
//        'qiwi' => array('qiwi_phone' => $qiwi),
//        'yamoney' => array('yamoney_phone' => $yamoney),
//        'elecsnet' => array('elecsnet_phone' => $elecsnet),
        'webmoney' => array('webmoney_phone' => $webmoney),
//        'phonenumber' => array('phonenumber_phone' => $phonenumber),
        'announcement' => array('announcement_phone' => $announcement),
        'boards' => array('boards_phone' => $boards, 'boards_phone_kz' => $boards, 'boards_phone_by' => $boards, 'boards_phone_pl' => $boards, 'boards_phone_ua' => $boards, 'boards_phone_uz' => $boards, 'boards_phone_ro' => $boards, 'boards_phone_pt' => $boards, 'boards_phone_bg' => $boards),
//        'commerce' => array(/*'commerce_phone' => $commerce*/),
        'yamap' => array('yamap_phone' => $yamap),
        '2gis' => array('2gis_phone' => $gis),
        'egrul' => array('listorg_phone' => $listorg),
//        'avinfo' => array('avinfo_phone' => $avinfo),
        'pochta' => array('pochta_phone' => $pochta),
        'aeroflot' => array('aeroflot_phone' => $aeroflot),
//        'uralair' => array('uralair_phone' => $uralair),
        'papajohns' => array('papajohns_phone' => $papajohns),
        'avito' => array('avito_phone' => $avito),
//        'biglion' => array('biglion_phone' => $biglion),
      ),
      'email' => array(
        'gosuslugi' => array('gosuslugi_email' => $gosuslugi),
        'facebook' => array('facebook_email' => $facebook),
        'vk' => array('vk_email' => $vk, 'vk_emailcheck' => $vkcheck),
        'ok' => array('ok_email' => $ok, 'ok_emailcheck' => $okbot, 'ok_emailapp' => $okappr),
        'instagram' => array('instagram_email' => $instagram),
        'twitter' => array('twitter_email' => $twitter),
        'mailru' => array('mailru_email' => $mailru),
        'fotostrana' => array('fotostrana_email' => $fotostrana),
        'microsoft' => array('microsoft_email' => $microsoft),
        'skype' => array('skype_email' => $skype),
        'googleplus' => array('googleplus_email' => $googleplus),
        'google' => array('google_email' => $google, 'google_name' => $google),
//        'googlename' => array('googlename_email' => $google),
        'apple' => array('apple_email' => $apple),
//        'hh' => array('hh_email' => $hh),
//        'commerce' => array('commerce_email' => $commerce),
        'aeroflot' => array('aeroflot_email' => $aeroflot),
//        'uralair' => array('uralair_email' => $uralair),
        'rzd' => array('rzd_email' => $rzd),
//        'papajohns' => array('papajohns_email' => $papajohns),
        'avito' => array('avito_email' => $avito),
      ),
      'skype' => array(
        'microsoft' => array('microsoft_nick' => $microsoft),
        'skype' => array('skype' => $skype),
//        'commerce' => array('commerce_skype' => $commerce),
      ),
      'nick' => array(
        'microsoft' => array('microsoft_nick' => $microsoft),
        'skype' => array('skype' => $skype),
//        'commerce' => array('commerce_skype' => $commerce),
      ),
      'url' => array(
//        'facebook' => array('facebook_url' => $facebook),
        'vk' => array('vk_url' => $vk),
        'ok' => array('ok_url' => $ok/*, 'ok_urlcheck' => $okbot*/),
        'instagram' => array('instagram_url' => $instagram),
//        'hh' => array('hh_url' => $hh),
      ),
      'car' => array(
        'gibdd' => array('gibdd_history' => $gibdd, 'gibdd_aiusdtp' => $gibdd, 'gibdd_wanted' => $gibdd, 'gibdd_restricted' => $gibdd, 'gibdd_diagnostic' => $gibdd, 'gibdd_fines' => $gibdd),
        'eaisto' => array('eaisto' => $eaisto),
        'rsa' => array('rsa_policy' => $rsa),
        'rz' => array('rz_auto' => $rz),
        'reestrzalogov' => array('reestrzalogov_auto' => $reestrzalogov),
        'gisgmp' => array('gisgmp_fines' => $gisgmp),
//        'autoru' => array('autoru' => $autoru),
//        'vin' => array('vin' => $vin),
//        'avtokod' => array('avtokod_history' => $mosru, 'avtokod_pts' => $mosru, /*'avtokod_fines' => $mosrufines,*/ /*'avtokod_status' => $avtokod,*/ 'avtokod_taxi' => $mosru),
//        'nbki' => array('nbki_auto' => $nbkiauto),
//        'avinfo' => array('avinfo_auto' => $avinfo),
      ),
      'ip' => array(
        'dns' => array('dns' => $dns),
        'ripe' => array('ripe' => $ripe),
        'ipgeo' => array('ipgeo' => $ipgeo),
        'sypexgeo' => array('sypexgeo' => $sypexgeo),
        'shodan' => array('shodan' => $shodan),
        'censys' => array('censys' => $censys),
      ),
      'org' => array(
        'egrul' => array('egrul_org' => $egrul, /*'egrul_daughter' => $egrul*//*, 'listorg_org' => $listorg*/),
        'fns' => array('fns_bi' => $fns, /*'fns_svl' => $fns, */'fns_disfind' => $fns, 'fns_zd' => $fns, /*'fns_sshr' => $fns, 'fns_snr' => $fns, 'fns_revexp' => $fns, 'fns_paytax' => $fns, 'fns_debtam' => $fns, 'fns_taxoffence' => $fns *//*, 'fns_uwsfind' => $fns, 'fns_ofd' => $fns*/),
        'vestnik' => array('vestnik_org' => $vestnik/*, 'vestnik_fns' => $vestnik*/),
//        'gks' => array('gks_org' => $gks),
        'zakupki' => array(/*'zakupki_eruz' => $zakupki, */'zakupki_org' => $zakupki, /*'zakupki_customer223' => $zakupki, */'zakupki_order' => $zakupki, 'zakupki_contract' => $zakupki, 'zakupki_fz223' => $zakupki, 'zakupki_capital' => $zakupki, 'zakupki_dishonest' => $zakupki, 'zakupki_guarantee' => $zakupki, 'zakupki_rkpo' => $zakupki),
        'kad' => array('kad_org' => $kad),
        'bankrot' => array('bankrot_org' => $bankrot),
        'cbr' => array('cbr_org' => $cbr),
        'rz' => array('rz_org' => $rz),
        'reestrzalogov' => array('reestrzalogov_org' => $reestrzalogov),
        'rsa' => array('rsa_org' => $rsa),
        'fssp' => array('fssp_org' => $fssp),
//        'fsspapi' => array('fssp_org' => $fsspapi),
        'fsspsite' => array('fssp_org' => $fssp),
        '2gis' => array('2gis_inn' => $gis),
      ),
      'card' => array(
//        'sber' => array('sberbank_card' => $sb),
      ),
      'fssp_ip' => array(
        'fssp' => array('fssp_ip' => $fssp),
//        'fsspapi' => array('fssp_ip' => $fsspapi),
//        'fsspsite' => array('fssp_ip' => $fssp),
//        'gisgmp' => array('gisgmp_ip' => $gisgmp),
      ),
      'osago' => array(
        'rsa' => array('rsa_bsostate' => $rsa/*, 'rsa_osagovehicle' => $rsa*/),
      ),
      'text' => array(
//        'facebook' => array('facebook_text' => $facebook),
//        'vk' => array('vk_text' => $vk),
//        'ok' => array('ok_text' => $ok),
//        'hh' => array('hh_text' => $hh),
      ),
    );

    return $plugins;
}

