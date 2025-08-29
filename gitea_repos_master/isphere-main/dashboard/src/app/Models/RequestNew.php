<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestNew extends Model
{
    protected $table = 'RequestNew';
    protected $primaryKey = 'id';

    public $timestamps = false;
    public $parsedRequest = false;

    public function user() {
        return $this->hasOne(User::class, 'Id', 'user_id');
    }

    public function client() {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }

    public function parsedRequest() {

        if($this->parsedRequest) {
            return $this->parsedRequest;
        }


        $result['request'] = '';

        $numName = str_pad($this->id, 9, '0', STR_PAD_LEFT);
        $titles = str_split($numName, 3);
        if(file_exists('/opt/xml/'.$titles[0].'/'.$titles[1].'/'.$titles[2].'_req.xml')){
            $result['request'] = file_get_contents('/opt/xml/'.$titles[0].'/'.$titles[1].'/'.$titles[2].'_req.xml');
        }elseif(file_exists('/opt/xml/'.$titles[0].'/'.$titles[1].'.tar.gz')){
            $result['request'] = shell_exec('tar xzfO /opt/xml/'.$titles[0].'/'.$titles[1].'.tar.gz '.$titles[2].'_req.xml');
        }

        $result['request'] = file_get_contents($_SERVER['PWD'].'/example.request.xml');

        $result['request'] = preg_replace("/<\?xml[^>]+>/", "", substr($result['request'],strpos($result['request'],'<')));

        $this->parsedRequest = simplexml_load_string($result['request']);

        return $this->parsedRequest;
    }

    function entities() {

        $entities = [];

        $request = $this->parsedRequest();

        if(isset($request->PersonReq)){
            $data = json_decode(json_encode($request->PersonReq), true);
            foreach($data as $key => $val){
                if($val && !is_array($val) && !in_array($key, array('UserID','Password','requestId','sources'))){
                    $entities[] = $key.": ".$val;
                }
            }
        }

        if(isset($request->PhoneReq)){
            foreach($request->PhoneReq as $req)
                $entities[] = $req->phone;
        }

        if(isset($request->EmailReq)){
            foreach($request->EmailReq as $req)
                $entities[] = $req->email;
        }

        if(isset($request->SkypeReq)){
            foreach($request->SkypeReq as $req)
                $entities[] = $req->skype;
        }

        if(isset($request->URLReq)){
            foreach($request->URLReq as $req)
                $entities[] = $req->url;
        }

        if(isset($request->CarReq)){
            $data = json_decode(json_encode($request->CarReq), true);
            foreach($data as $key => $val){
                if ($val && !is_array($val))
                    $entities[] = $key.": ".$val;
            }
        }

        if(isset($request->IPReq))
            foreach($request->IPReq as $req)
                $entities[] = $req->ip;

        if(isset($request->OrgReq)){
            $data = json_decode(json_encode($request->OrgReq), true);
            foreach($data as $key => $val)
                if($val && !is_array($val))
                    $entities[] = $key.": ".$val;

        }

        if(isset($request->OtherReq)){
            $data = json_decode(json_encode($request->OtherReq), true);

            foreach($data as $key => $val)
                if ($val && !is_array($val))
                    $entities[] = $key.": ".$val;

        }

        if(isset($request->CardReq))
            foreach($request->CardReq as $req)
                $entities[] = $req->card;


        return $entities;
    }
}
