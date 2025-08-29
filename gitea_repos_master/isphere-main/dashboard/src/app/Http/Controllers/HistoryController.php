<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;


use App\Models\User;
use App\Models\Client;

use App\Models\RequestNew;
use App\Models\ResponseNew;

class HistoryController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $clients = [];

        $users = User::getUsersQuery($user);
        $users = $users->get(['Login','Id', 'ClientId'])->toArray();

        if(Gate::allows('use-function', 'clients_own')) {
            $clients = Client::orderBy('Id', 'desc');

            if (!Gate::allows('use-function', 'clients_all')) {

                $clients->where('MasterUserId', $user->Id)
                    ->orWhere('id', $user->ClientId);
            }

            $clients = $clients->pluck('Name','id')->toArray();
            $clients = [''=>'Все'] + $clients;
        }

        $clientId = $request->input('clientId', false);

        if($clientId) {
            $users = array_filter($users, function ($row, $key) use ($clientId) {
                return$row['ClientId'] == $clientId;
            }, ARRAY_FILTER_USE_BOTH);
        }

        $usersMap = [''=>'Все'];
        foreach ($users as $user)
            $usersMap[$user['Id']] = $user['Login'];

        $history = RequestNew::orderBy('Id', 'desc');

        $from = $request->input('from', date('Y-m-d'));
        $to = $request->input('to', date('Y-m-d'));

        $history->where('created_date','>=', $from);
        $history->where('created_date','<=', $to);

        if($request->input('clientId', false) && !$request->input('userId', false)) {
            $history->where('client_id',$request->input('clientId'));
        }

        if($request->input('Login', false)) {
            $userId = array_search($request->input('Login'), $usersMap);

            $history->where('user_id', $userId ?? -1);
        }

        if($request->input('userId', false)) {
            $history->where('user_id', $request->input('userId'));
        }

        if(!Gate::allows('use-function', 'clients_all')) {
            $history->whereIn('user_id', array_keys($usersMap));
        }

        return view('private.common.history')
            ->with('clients', $clients)
            ->with('history', $history->paginate(20))
            ->with('users', $usersMap)
            ->with('request', $request)
            ->with('from', $from)
            ->with('to', $to)
            ->with('pageTitle', 'История');
    }

    public function details(RequestNew $requestNew, Request $request)
    {
        $theResult = $this->getResultContent($requestNew);



        $type = $request->input('type', 'html');

        $this->detailsOutput($theResult, $type, $requestNew);
    }

    protected function getResultContent($requestNew) {
        $subDir = env('XML_RESULTS_DIR');

        if(true) { // Для отладки
            $theResult = file_get_contents($_SERVER['PWD'].'/example.response.xml');
            return $theResult;
        }

        $numName = str_pad($requestNew->id, 9, '0', STR_PAD_LEFT);
        $titles = str_split($numName, 3);

        $subName = $subDir.$titles[0].'/'.$titles[1].'/'.$titles[2];

        if(file_exists($subName.'_res.xml'))
            $theResult = file_get_contents($subName.'_res.xml');
        elseif(file_exists($subDir.$titles[0].'/'.$titles[1].'.tar.gz'))
            $theResult = shell_exec('tar xzfO '.$subDir.$titles[0].'/'.$titles[1].'.tar.gz '.$titles[2].'_res.xml');

        return $theResult;
    }

    protected function detailsOutput($theResult, $type, $requestNew)
    {
        if(!$theResult) {
            echo 'Данные недоступны';
            exit();
        }

        switch ($type) {
            case 'xml' :
                header ("Content-Type:text/xml");
                echo $theResult;
                break;

            case 'json' :
                header ("Content-Type:application/json");

                $xml = simplexml_load_string($theResult);
                $xml['result'] = strtr($xml['result'],array('mode=xml'=>'mode=json'));

                $json = json_encode($xml, true);
                echo $json;

                break;
            default :

                $doc = $this->xml_transform(strtr($theResult,array('request>'=>'Request>')), $type=='pdf' ? './isphere_view_pdf.xslt' : './isphere_view.xslt');
                if ($doc){
                    $servicename = isset($servicenames[$_SERVER['HTTP_HOST']])?'платформой '.$servicenames[$_SERVER['HTTP_HOST']]:'';

                    $html = strtr($doc->saveHTML(),array('$servicename'=>$servicename));

                    if ($type=='pdf') {
                        $descriptorspec = [
                            0 => ['pipe', 'r'], //stdin
                            1 => ['pipe', 'w'], //stdout
                            2 => ['pipe', 'w'], //stderr
                        ];

                        $i = 0; $pdf = false;
                        while ($i++<=3 && !$pdf) {
                            $process = proc_open("xvfb-run -a timeout 10 wkhtmltopdf --quiet --disable-local-file-access --javascript-delay 3000 --margin-left 20mm --dpi 96 - -", $descriptorspec, $pipes);
                            if (is_resource($process)) {
                                copy('./view.css','/tmp/view.css');

                                fwrite($pipes[0], $html);
                                fclose($pipes[0]);

                                $pdf = stream_get_contents($pipes[1]);
                                fclose($pipes[1]);

                                $err = stream_get_contents($pipes[2]);
                                fclose($pipes[2]);
                                $exitCode = proc_close($process);

                                $start = strpos($pdf,'%PDF');
                                if ($start) {
                                    $pdf = substr($pdf,$start);
                                } else {
                                    if ($pdf) {
                                        file_put_contents('./storage/logs/pdf/'.$requestNew->id.'_'.time().'.txt',$pdf);
                                    } elseif ($i<5) {
                                        sleep(5);
                                    }
                                    $pdf = false;
                                }

                            }
                        }
                        if ($pdf) {
                            header("Content-Type:applcation/pdf");
                            header("Content-Disposition:attachment; filename=report_".$requestNew->id.".pdf");
                            echo $pdf;
                        } else {
                            echo 'Ошибка сохранения в pdf';
                            file_put_contents('./storage/logs/pdf/'.$requestNew->id.'_'.time().'.txt',$pdf);
                        }
                    } else {
                        echo $html;
                    }
                }else{
                    echo 'Данные недоступны';
                }

                break;
        }

    }

    function xml_transform($xml,$xslt_file) {
        if ($xml && (strpos($xml,'<')!==false)) {
            $doc = new \DOMDocument();
            $doc->loadXML(strtr($xml,array('&nbsp;'=>' ','&ensp;'=>' ','&emsp;'=>' ','&ndash;'=>'–','&mdash;'=>'—','&bull;'=>'•','&deg;'=>'°','&trade;'=>'™','&copy;'=>'©','&infin;'=>'∞','&hearts;'=>'♥',''=>'♥',''=>'♠',''=>'♣',''=>'♦',''=>'•',''=>'◄',''=>'►',''=>'♫',''=>'☼',''=>'↕',''=>'‼',''=>'¶',''=>'§',''=>'▬',''=>'↨',''=>'↑',''=>'↓',''=>'→',''=>'←',''=>'',''=>'↔',''=>'▲',''=>'▼',''=>'♂',''=>'♀',''=>'◘',''=>'☻',''=>'☺')));
            $resdoc = $this->doc_transform($doc,$xslt_file);
        } else {
            $resdoc = false;
        }
        return $resdoc;
    }
    function doc_transform($doc,$xslt_file) {
        global $xslt_dir;
        $resdoc = false;
        if ($doc) {
            $xsldoc = new \DOMDocument();
            if ($xsldoc->load(($xslt_dir ? $xslt_dir : '') . $xslt_file)) {
                $xsl = new \XSLTProcessor();
                $xsl->importStyleSheet($xsldoc);
                $resdoc = $xsl->transformToDoc($doc);
            }
        }
        return $resdoc;
    }

}
