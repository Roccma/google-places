<?php
require 'vendor/autoload.php';
ini_set('display_errors','off');
$app = new Slim\App();


$app->get('/nearby-search/{location}/{keywords}/{range}', function ($request, $response, $args){
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: GET, POST');
  header("Access-Control-Allow-Headers: X-Requested-With");
  $googleKey = "AIzaSyDK4IJCRHpiObsHWUASG8sN4chVn42J9QE";
  $location = $args['location'];
  $keywords = $args['keywords'];
  $range = $args['range'];
  $keywords = trim(preg_replace('/\s+/', ' ', $keywords));
  $keywords = str_replace( ' ', '%20', $keywords );
  $next_page_token = '';
  $results = array();
  while( isset($next_page_token) ){
    $resp = file_get_contents("https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=" . trim($location) . "&radius=" . trim($range) . "&keyword=" . $keywords . "&key=" . trim($googleKey) . "&pagetoken=" . trim($next_page_token));
    echo "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=" . trim($location) . "&radius=" . trim($range) . "&keyword=" . $keywords . "&key=" . trim($googleKey) . "&pagetoken=" . trim($next_page_token) . "<br>";
    //echo "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=" . $location . "&radius=" . $range . "&keyword=" . $keywords . "&key=" . $googleKey;
    echo $resp;
    $responseJson = json_decode( $resp, true );
    
    $places_ids = array();
    
    $next_page_token = $responseJson['next_page_token'];
    foreach( $responseJson['results'] as $data ){
      $places_ids[] = $data['place_id'];
    }
    
    foreach( $places_ids as $pids ){
      $placeData = file_get_contents( "https://maps.googleapis.com/maps/api/place/details/json?place_id=" . $pids . "&key=" . $googleKey );
      echo "https://maps.googleapis.com/maps/api/place/details/json?place_id=" . $pids . "&key=" . $googleKey . "<br>";
      $placeDataJson = json_decode( $placeData, true )['result'];
      //echo $placeDataJson['name'] . " " . $placeDataJson['formatted_address'] . " " . $placeDataJson['international_phone_number'] . " " . $placeDataJson['website'] . "<br>";
      $phone = $placeDataJson['international_phone_number'];
      if( !$phone ){
        $phone = $placeDataJson['formatted_phone_number'] ? $placeDataJson['formatted_phone_number'] : "";
      }
      if( !existsInArray( $results, $placeDataJson['geometry']['location']['lat'], $placeDataJson['geometry']['location']['lng'] ) ){
        $results[] = array(
          "name" => $placeDataJson['name'],
          "address" => $placeDataJson['formatted_address'] ? $placeDataJson['formatted_address'] : "",
          "phone" => $phone,
          "website" => $placeDataJson['website'] ? $placeDataJson['website'] : "",
          "lat" => $placeDataJson['geometry']['location']['lat'],
          "lng" => $placeDataJson['geometry']['location']['lng']
        );
      }
      else{
        //echo "devolver";
        return json_encode(array(
          "ok" => true,
          "count" => count( $results ),
          "data" => $results
        ));
      }
    
    }
  }
  
  return json_encode(array(
    "ok" => true,
    "count" => count( $results ),
    "data" => $results
  ));
});

function existsInArray( $results, $lat, $lng ){
  foreach( $results as $rs ){
    if( $rs['lat'] == $lat && $rs['lng'] == $lng ){
      return true;
    }
  }
  return false;
}

$app->run();



