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


function execSQL($cSQL){
  try{
echo '1';    
    $app['pdo']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
echo '2';
    $st = $app['pdo']->prepare($cSQL); 
echo '3';    
    $st->execute();
echo '4';    

  } catch (PDOException $exception) {
      echo 'PDOException: '.$exception;
  } catch (Exception $exception) {
      echo 'Exception: '.$exception;
  }
echo '5';    

};





// web handlers
$app->get('/', function() use($app) {
  //$app['monolog']->addDebug('logging output.');
  //return $app['twig']->render('index.twig');
  return 'nada aqui';
});

// Create
$app->post('/create', function() use($app) {

  $content = trim(file_get_contents("php://input"));
  $decoded = json_decode($content, true);
  
  $customer_id   = $decoded['customer_id'];
  $company_name  = $decoded['company_name'];
  $contact_name  = $decoded['contact_name'];
  $contact_title = $decoded['contact_title'];
  $city          = $decoded['city'];
  $region        = $decoded['region'];
  $postal_code   = $decoded['postal_code'];
  $country       = $decoded['country'];
  $phone         = $decoded['phone'];
  $fax           = $decoded['fax'];
  
  execSQL("insert into customers(customer_id,company_name,contact_name,contact_title,city  ,region  , postal_code,country  ,phone, fax  ) 
                                  values('".$customer_id."',
                                          '".$company_name."',
                                          '".$contact_name."',
                                          '".$contact_title."',
                                          '".$city."',
                                          '".$region."',
                                          '".$postal_code."',
                                          '".$country."',
                                          '".$phone."',
                                          '".$fax."') ");
  //echo $lSql;


  /*
  try{
      $app['pdo']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $st = $app['pdo']->prepare("insert into customers(customer_id,company_name,contact_name,contact_title,city  ,region  , postal_code,country  ,phone, fax  ) 
                                                values('".$customer_id."',
                                                        '".$company_name."',
                                                        '".$contact_name."',
                                                        '".$contact_title."',
                                                        '".$city."',
                                                        '".$region."',
                                                        '".$postal_code."',
                                                        '".$country."',
                                                        '".$phone."',
                                                        '".$fax."') ");
      $st->execute();
  } catch (PDOException $exception) {
      echo 'PDOException: '.$exception;
  } catch (Exception $exception) {
      echo 'Exception: '.$exception;
  }
  */

  return json_encode($decoded);

});






// Read
$app->get('/read', function() use($app) {
  $st = $app['pdo']->prepare('SELECT first_name FROM employees');
  $st->execute();

  $names = array();
  while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
    $app['monolog']->addDebug('Row ' . $row['first_name']);
    $names[] = $row;
  }

  return json_encode($names);
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





