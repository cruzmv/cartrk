<?php
header('Content-type:application/json;charset=utf-8');

require('../vendor/autoload.php');

$app = new Silex\Application();
$app['debug'] = true;

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

// Register view rendering
//$app->register(new Silex\Provider\TwigServiceProvider(), array(
//    'twig.path' => __DIR__.'/views',
//));

$dbopts = parse_url(getenv('DATABASE_URL2'));
$app->register(new Csanquer\Silex\PdoServiceProvider\Provider\PDOServiceProvider('pdo'),
               array(
                'pdo.server' => array(
                   'driver'   => 'pgsql',
                   'user' => $dbopts["user"],
                   'password' => $dbopts["pass"],
                   'host' => $dbopts["host"],
                   'port' => $dbopts["port"],
                   'dbname' => ltrim($dbopts["path"],'/')
                   )
               )
);
$app['pdo']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//Validate the data
function validaDados($content){

  $decoded = json_decode($content, true);
  $aRet['msg'] = '';
  $aRet['data'] = $decoded;

  if( empty($decoded['customer_id']) ){
    $aRet['msg'] = 'Please informar a customer ID';
  }
  if(strlen($decoded['company_name'])<=0 || strlen($decoded['company_name']) > 40){
    $aRet['msg'] = 'Company name can not be empty and has to be less or equal than 40 characters';
  }
  if(strlen($decoded['contact_name']) > 30){
    $aRet['msg'] = 'Contact name has to be less or equal than 30 characters';
  }
  if(strlen($decoded['contact_title']) > 30){
    $aRet['msg'] = 'Contact title has to be less or equal than 30 characters';
  }
  if(strlen($decoded['address']) > 60){
    $aRet['msg'] = 'Address has to be less or equal than 60 characters';
  }
  if(strlen($decoded['city']) > 15){
    $aRet['msg'] = 'City has to be less or equal than 60 characters';
  }
  if(strlen($decoded['region']) > 15){
    $aRet['msg'] = 'Region has to be less or equal than 15 characters';
  }
  if(strlen($decoded['postal_code']) > 10){
    $aRet['msg'] = 'Postal code has to be less or equal than 10 characters';
  }
  if(strlen($decoded['country']) > 15){
    $aRet['msg'] = 'Country has to be less or equal than 15 characters';
  }
  if(strlen($decoded['phone']) > 24){
    $aRet['msg'] = 'Phone has to be less or equal than 24 characters';
  }
  if(strlen($decoded['fax']) > 24){
    $aRet['msg'] = 'Fax has to be less or equal than 24 characters';
  }

  $aRet['status'] = empty($aRet['msg']);

  return $aRet;
}

// Execute a SQL statament
function execSQL($app,$cSQL){
  $aRet['msg'] = '';
  $aRet['data'] = array();
  try{
    $st = $app['pdo']->prepare($cSQL);
    $st->execute();
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
      $aRet['data'][] = $row;
    }
  } catch (PDOException $exception) {
    $aRet['msg'] =  'PDOException: '.$exception;
  } catch (Exception $exception) {
    $aRet['msg'] =  'Exception: '.$exception;
  }
  $aRet['status'] = empty($aRet['msg']);
  return $aRet;
}

// Create
$app->put('/', function() use($app) {

  $aRet = array();

  // Get the raw
  $content = trim(file_get_contents("php://input"));

  // Validate the data
  $aValid = validaDados($content);
  if (!$aValid['status']){
    return $aValid['msg'];
  }

  // Check if customer ID exists
  $aSQL = execSQL($app,"select count(*) as count from customers where customer_id = '".$aValid['data']['customer_id']."'");
  if (!$aSQL['status']){
    return $aSQL['msg'];
  }
  if ($aSQL['data'][0]['count']>0){
    return 'Customer ID '.$aValid['data']['customer_id'].' alread exists.';
  }

  // Add the new customer
  $aSQL = execSQL($app,"insert into customers(customer_id,company_name,contact_name,contact_title,address,city,region,postal_code,country,phone,fax)
                                values('".$aValid['data']['customer_id']."',
                                       '".$aValid['data']['company_name']."',
                                       '".$aValid['data']['contact_name']."',
                                       '".$aValid['data']['contact_title']."',
                                       '".$aValid['data']['address']."',
                                       '".$aValid['data']['city']."',
                                       '".$aValid['data']['region']."',
                                       '".$aValid['data']['postal_code']."',
                                       '".$aValid['data']['country']."',
                                       '".$aValid['data']['phone']."',
                                       '".$aValid['data']['fax']."') ");
  return json_encode($aSQL);
});

// Read
$app->get('/', function() use($app) {

  $content = trim(file_get_contents("php://input"));
  $decoded = json_decode($content, true);
  $cSQL = 'select customer_id,
                  company_name
                  contact_name,
                  contact_title,
                  address,
                  city,
                  region,
                  postal_code,
                  country,
                  phone,
                  fax
             from customers';
  if(!empty($decoded['customer_id'])){
    $cSQL=$cSQL." where customer_id = '".$decoded['customer_id']."' ";
  }
  $cSQL = $cSQL . 'order by contact_name ';
  $aSql = execSQL($app, $cSQL );
  if (!$aSql['status']){
    return $aSql['msg'];
  }

  return json_encode($aSql['data']);
});

// Update
$app->post('/', function() use($app) {

  $content = trim(file_get_contents("php://input"));
  $aValid = validaDados($content);

  // Check if customer ID exists
  $aSQL = execSQL($app,"select count(*) as count from customers where customer_id = '".$aValid['data']['customer_id']."'");
  if (!$aSQL['status']){
    return $aSQL['msg'];
  }
  if ($aSQL['data'][0]['count']<=0){
    return 'Customer ID '.$aValid['data']['customer_id'].' does not exists.';
  }

  // update the customer data
  $aSQL = execSQL($app,"update customers
                           set company_name = '".$aValid['data']['company_name']."',
                               contact_name = '".$aValid['data']['contact_name']."',
                               contact_title = '".$aValid['data']['contact_title']."',
                               address = '".$aValid['data']['address']."',
                               city = '".$aValid['data']['city']."',
                               region = '".$aValid['data']['region']."',
                               postal_code = '".$aValid['data']['postal_code']."',
                               country = '".$aValid['data']['country']."',
                               phone = '".$aValid['data']['phone']."',
                               fax = '".$aValid['data']['fax']."'
                        where customer_id = '".$aValid['data']['customer_id']."'");
  return json_encode($aSQL);
});

// Delete
$app->delete('/', function() use($app) {
  $content = trim(file_get_contents("php://input"));
  $decoded = json_decode($content, true);
  if(empty($decoded['customer_id']) ){
    $aRet['msg'] = 'Please informar a customer ID';
  }

  // Check if customer ID exists
  $aSQL = execSQL($app,"select count(*) as count from customers where customer_id = '".$decoded['customer_id']."'");
  if (!$aSQL['status']){
    return $aSQL['msg'];
  }
  if ($aSQL['data'][0]['count']<=0){
    return 'Customer ID '.$decoded['customer_id'].' does not exists.';
  }

  // Delete customer
  $aSQL = execSQL($app,"delete from customers where customer_id = '".$decoded['customer_id']."'");

  return json_encode($aSQL);
});


$app->run();





