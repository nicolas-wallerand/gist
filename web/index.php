<?php

$app = require __DIR__.'/../app/bootstrap.php';

$app['env'] = 'prod';

$app->run();
