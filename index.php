<?php
namespace RefineNames;

require_once __DIR__.'/src/ReconciliationService.class.php';

spl_autoload_register(function ($class) {
    $file = __DIR__.'/src/Services/'.str_replace(__NAMESPACE__.'\\', '', $class).'.class.php';
    if (file_exists($file)) {
        require $file;
    }
});

//where 50 = batch size of names for POST requests and 5 = number of possible name matches to return per name
$init = new Resolver(50, 5);
$init->call($_REQUEST);