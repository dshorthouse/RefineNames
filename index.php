<?php
namespace RefineNames;

require_once __DIR__.'/src/ReconciliationService.class.php';

spl_autoload_register(function ($class) {
    $file = __DIR__.'/src/Services/'.str_replace(__NAMESPACE__.'\\', '', $class).'.class.php';
    if (file_exists($file)) {
        require $file;
    }
});

//Example call to a service 
$init = new Resolver;
$init->call($_REQUEST);