<?php
error_reporting(1);
date_default_timezone_set('Asia/Kolkata');
require_once '../vendor/autoload.php';
//require_once '../bshaffer/Proximitybeacon.php';

include('function.php');

session_start();

$client = new Google_Client();
$client->setAuthConfig('client_secrets.json');
$client->setAccessType('offline'); // default: offline
$client->addScope(Google_Service_Proximitybeacon::USERLOCATION_BEACON_REGISTRY);
//print_r($_SESSION['access_token']);exit;
$_SESSION['beacon_data'] = $_GET['data'];
if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    $rslt = array();
    $access_token = $_SESSION['access_token']['access_token'];
    $token_type = $_SESSION['access_token']['token_type'];
    $created = $_SESSION['access_token']['created'];
    $expires_in = $_SESSION['access_token']['expires_in'];
    /*echo 'Your access token is =>'. $access_token .'<br>';
    echo 'Created at =>'. date('y-m-d H:i:s',(int) $created).'<br>';
    echo 'Expire at =>'. date('H:i:s',(int) strtotime($expires_in) ).'<br>';*/

    //$beacon = new Google_Service_Proximitybeacon();
    //print_r($_SESSION).'</br>';exit;
    //echo 'hiii';
    $data = json_decode(base64_decode($_GET['data']));
    //echo '<pre>';
    //print_r($data);exit;
    $id = hex2str($data->beaconid);
    //echo $id;
    $name = $data->name;
    
    if($data->flag=='add'){
        addBeacon($data,$access_token);
    }elseif($data->flag=='edit'){
        editBeacon($data,$access_token);
    }elseif($data->flag=='status'){
        //echo'k';exit;
        changeStatus($data,$access_token);
    }elseif($data->flag=='sync'){
        syncData($data,$access_token);
    }else{
        //delete($access_token);
    }

    exit;

    $client->setAccessToken($_SESSION['access_token']);
    $drive = new Google_Service_Drive($client);
    foreach ($drive->files->listFiles()->getFiles() as $key => $object){
        echo $object->getName().'<br>';
    }
} else {
    $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/google/oauth/oauth2callback.php?data='.$_GET['data'];
    header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}

function addBeacon($data,$access_token){
   /*start add beacon details*/ 	
    	$array = array(
          "advertisedId"=>array(
              "type"=> "EDDYSTONE",
               "id"=>hex2str($data->beaconid)
              ),
          "status"=> "INACTIVE",
          "placeId"=> "ChIJTxax6NoSkFQRWPvFXI1LypQ",
          "latLng"=> array(
          "latitude"=> "47.6693771",
          "longitude"=> "-122.1966037"
                 ),
          "indoorLevel"=> array(
          "name"=> "1"
              ),
        "expectedStability"=> "STABLE",
        "description"=> $data->message,
        "properties"=> array(
          "position"=> "entryway"
        )
      );
     //echo $access_token;   
     //print_r($array);exit;
    $json = json_encode($array);
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://proximitybeacon.googleapis.com/v1beta1/beacons:register",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS =>  $json,
      CURLOPT_HTTPHEADER => array(
        "authorization: Bearer ".$access_token,
        "cache-control: no-cache",
        "content-type: application/json",
        "postman-token: 694998c6-808c-777e-2ace-0ff43314fa59"
      ),
    ));

    $response = curl_exec($curl);
    //print_r($response);exit;
    $rslt1 = json_decode($response,true);
          if($rslt1['error']['code']=='401'){
            unset($_SESSION['access_token']);
            $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/google/oauth/oauth2callback.php';
            header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
          }elseif($rslt1['error']['message']!=''){
            //print_r($response);exit;
            header("Location: http://".$_SERVER['HTTP_HOST']."/admin/beacon/?error=".$rslt1['error']['message']);
          }
    $err = curl_error($curl);
   
    curl_close($curl); 
    /*start add beacon details*/ 
    if ($err) {
      echo "cURL Error #:" . $err;
    }else{
        /*add nearby notification*/
      $rslt = json_decode($response,true);
      $beaconname = $rslt['beaconName'];
      $rslt['id'] = $data->id;
      $res = base64_encode(json_encode($rslt));
        $attach = array(
            'title'=>$data->message,
              'url'=>$data->url
            );
          $json_attach = base64_encode(json_encode($attach));
          $attachment = array(
                  "namespacedType"=>"com.google.nearby/en",
                  "data"=>$json_attach 
            );
          $atta = json_encode($attachment);
          $curl = curl_init();

          curl_setopt_array($curl, array(
            CURLOPT_URL => "https://proximitybeacon.googleapis.com/v1beta1/$beaconname/attachments",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS =>  $atta,
            CURLOPT_HTTPHEADER => array(
              "authorization: Bearer ".$access_token,
              "cache-control: no-cache",
              "content-type: application/json",
              "postman-token: 694998c6-808c-777e-2ace-0ff43314fa59"
            ),
          ));

          $response = curl_exec($curl);
          $rslt1 = json_decode($response,true);
          if($rslt1['error']['code']=='401'){
            unset($_SESSION['access_token']);
            $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/google/oauth/oauth2callback.php';
            header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
          }
          $err = curl_error($curl);
          curl_close($curl); 
          header("Location: http://".$_SERVER['HTTP_HOST']."/admin/beacon/?data=". $res);  
          /*add nearby notification*/
    }
}

function editBeacon($data,$access_token){
    /*End Edit beacon details*/
    $array = array(
          "advertisedId"=>array(
              "type"=> "EDDYSTONE",
               "id"=>hex2str($data->beaconid)
              ),
          "status"=> ($data->isactive!='')?$data->isactive:"INACTIVE",
          "placeId"=> "ChIJTxax6NoSkFQRWPvFXI1LypQ",
          "latLng"=> array(
          "latitude"=> "47.6693771",
          "longitude"=> "-122.1966037"
                 ),
          "indoorLevel"=> array(
          "name"=> "1"
              ),
        "expectedStability"=> "STABLE",
        "description"=> $data->message,
        "properties"=> array(
          "position"=> "entryway"
        )
      );
    //print_r($array);exit;
    $json = json_encode($array);
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://proximitybeacon.googleapis.com/v1beta1/".$data->bname,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "PUT",
      CURLOPT_POSTFIELDS =>  $json,
      CURLOPT_HTTPHEADER => array(
        "authorization: Bearer ".$access_token,
        "cache-control: no-cache",
        "content-type: application/json",
        "postman-token: 694998c6-808c-777e-2ace-0ff43314fa59"
      ),
    ));

    $response = curl_exec($curl);
    $rslt1 = json_decode($response,true);
          if($rslt1['error']['code']=='401'){
            unset($_SESSION['access_token']);
            $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/google/oauth/oauth2callback.php';
            header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
          }
    $err = curl_error($curl);
   // print_r($response);exit;
    curl_close($curl);
   /*End Edit beacon details*/
    if ($err) {
      echo "cURL Error #:" . $err;
    }else{
      $rslt = json_decode($response,true);
      $beaconname = $rslt['beaconName'];
      $rslt['id'] = $data->id;
      $res = base64_encode(json_encode($rslt));
      if($data->bname!=''){
          $attach = array(
            'title'=>$data->message,
              'url'=>$data->url
            );
          $json_attach = base64_encode(json_encode($attach));
          $attachment = array(
                  "namespacedType"=>"com.google.nearby/en",
                  "data"=>$json_attach 
            );
          $atta = json_encode($attachment);
          $curl = curl_init();

          curl_setopt_array($curl, array(
            CURLOPT_URL => "https://proximitybeacon.googleapis.com/v1beta1/$data->bname/attachments",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS =>  $atta,
            CURLOPT_HTTPHEADER => array(
              "authorization: Bearer ".$access_token,
              "cache-control: no-cache",
              "content-type: application/json",
              "postman-token: 694998c6-808c-777e-2ace-0ff43314fa59"
            ),
          ));

          $response = curl_exec($curl);
          $rslt1 = json_decode($response,true);
          if($rslt1['error']['code']=='401'){
            unset($_SESSION['access_token']);
            $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/google/oauth/oauth2callback.php';
            header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
          }
          //print_r($rslt1);exit;
          $err = curl_error($curl);
          curl_close($curl);
           if($data->flag=="sync"){
               changeStatus($data,$access_token);
               header("Location:http://".$_SERVER['HTTP_HOST']."/admin/beacon/sync/?data=". $res);
           }else{
               header("Location: http://".$_SERVER['HTTP_HOST']."/admin/beacon/edit/?data=". $res);
           }
      }
    }
}

function changeStatus($data,$access_token){
    //print_r($data);exit;
    $array = array();
    //print_r($array);exit;
    $json = json_encode($array);
    /*Beacon Status*/ 	
    $curl = curl_init();
      if($data->flag=='sync'){
            $url = "https://proximitybeacon.googleapis.com/v1beta1/".$data->bname.":".$data->status;
        }else{
            $url = "https://proximitybeacon.googleapis.com/v1beta1/".$data->name.":".$data->status;
        }
    curl_setopt_array($curl, array(
       
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS =>  "",
      CURLOPT_HTTPHEADER => array(
        "authorization: Bearer ".$access_token,
        "cache-control: no-cache",
        "content-type: application/json",
        "postman-token: 694998c6-808c-777e-2ace-0ff43314fa59"
      ),
    ));

    $response = curl_exec($curl);
    $rslt1 = json_decode($response,true);
    if($rslt1['error']['code']=='401'){
      unset($_SESSION['access_token']);
      $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/google/oauth/oauth2callback.php';
      header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
    }
    $err = curl_error($curl);
    //print_r($response);exit;
    curl_close($curl);
   /*Beacon Status*/ 
    
    if ($err) {
      echo "cURL Error #:" . $err;
    } else {
    $rslt['flag'] = 'status';
    $rslt['status']=$data->status;
       //print_r($rslt);exit;
    $json_active = base64_encode(json_encode($rslt));
    if ($data->flag == 'status') {
            header("Location: http://" . $_SERVER['HTTP_HOST'] . "/admin/beacon/?data=" . $json_active);
        }
    }
}

function syncData($data,$access_token){
    editBeacon($data,$access_token);
}

function delete($access_token){
    // ----------- For Delete -----------------

  $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://proximitybeacon.googleapis.com/v1beta1/beacons",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_POSTFIELDS =>  "",
      CURLOPT_HTTPHEADER => array(
        "authorization: Bearer ".$access_token,
        "cache-control: no-cache",
        "content-type: application/json",
        "postman-token: 694998c6-808c-777e-2ace-0ff43314fa59"
      ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    $bnames = json_decode($response, true);
    //print_r($bnames);exit;
    $cnt = 0;
    foreach ($bnames as $key => $value) {
              $curl = curl_init();

              echo $my_URL = "https://proximitybeacon.googleapis.com/v1beta1/".$value[$cnt]['beaconName'];
              $cnt++;
              curl_setopt_array($curl, array(
                CURLOPT_URL => $my_URL,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "DELETE",
                CURLOPT_POSTFIELDS =>  "",
                CURLOPT_HTTPHEADER => array(
                  "authorization: Bearer ".$access_token,
                  "cache-control: no-cache",
                  "content-type: application/json",
                  "postman-token: 694998c6-808c-777e-2ace-0ff43314fa59"
                ),
              ));
              $res = curl_exec($curl);
              $err = curl_error($curl);
              curl_close($curl);
    }
  exit;
// ----------- For Delete -----------------
}