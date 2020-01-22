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


function validaDados($content){
  
  $decoded = json_decode($content, true);
  $aRet['msg'] = '';
  $aRet['data'] = $decoded;
  
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

function execSQL($app,$cSQL){
  $aRet['msg'] = '';
  $aRet['data'] = array();
  try{
    $st = $app['pdo']->prepare($cSQL);
    $st->execute();
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
      $aRet['data'] = $row;
    }
  } catch (PDOException $exception) {
    $aRet['msg'] =  'PDOException: '.$exception;
  } catch (Exception $exception) {
    $aRet['msg'] =  'Exception: '.$exception;
  }
  $aRet['status'] = empty($aRet['msg']);
  return $aRet;
}


// web handlers
$app->get('/', function() use($app) {
  //$app['monolog']->addDebug('logging output.');
  //return $app['twig']->render('index.twig');
  return 'nada aqui';
});


// Create
$app->post('/create', function() use($app) {

  $content = trim(file_get_contents("php://input"));
  $aValid = validaDados($content);

  if (!$aValid['status']){    
    return $aValid['msg'];
  }
  $aSQL = execSQL($app,"insert into customers(customer_id,company_name,contact_name,contact_title,city  ,region  , postal_code,country  ,phone, fax  ) 
                                values('".$aValid['data']['customer_id']."',
                                       '".$aValid['data']['company_name']."',
                                       '".$aValid['data']['contact_name']."',
                                       '".$aValid['data']['contact_title']."',
                                       '".$aValid['data']['city']."',
                                       '".$aValid['data']['region']."',
                                       '".$aValid['data']['postal_code']."',
                                       '".$aValid['data']['country']."',
                                       '".$aValid['data']['phone']."',
                                       '".$aValid['data']['fax']."') ");
  if (!$aSQL['status']){ 
    return $aSQL['msg'];
  }

  return 'Customer add succefully';  
});






// Read
$app->get('/read', function() use($app) {

  $aSql = execSQL($app,'select customer_id,
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
                          from customers
                         order by contact_name ');
  if (!$aSql['status']){
    return $aSql['msg'];
  }
  return json_encode($aSql['data'];

  /*
  $names = array();
  while ($row = $aSql['sql']->fetch(PDO::FETCH_ASSOC)) {
    //$app['monolog']->addDebug('Row ' . $row['customer_id']);
    $names[] = $row;
  }
  return json_encode($names);
  */
});


// Update
$app->post('/create', function() use($app) {
  
  $content = trim(file_get_contents("php://input"));
  $decoded = json_decode($content, true);
  
  return json_encode($decoded);


});


// Delete
$app->post('/delete', function() use($app) {
  $content = trim(file_get_contents("php://input"));
  $decoded = json_decode($content, true);
  
  return json_encode($decoded);
});


$app->run();





