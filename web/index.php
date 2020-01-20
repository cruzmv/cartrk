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

// Our web handlers
$app->get('/', function() use($app) {
  //$app['monolog']->addDebug('logging output.');
  //return $app['twig']->render('index.twig');
  return 'nada aqui';
});

// Create
$app->post('/create', function() use($app) {
  $content = trim(file_get_contents("php://input"));
  $decoded = json_decode($content, true);
  

  
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
