<?php
    $s = microtime(true);

    require __DIR__ . '/../vendor/autoload.php';

    $app = new honwei189\Flayer;
    honwei189\Config::load();

    $app->bind("honwei189\\Fw\\Fw");
    $app->fw()->bootstrap();
    $e = microtime(true);

    $sec     = $e - $s;
    $ms      = round((double) $sec * 1000, 2);
    $secPer  = round((double) (1 / $sec), 2);
    $sec     = round($sec, 4);
    $memPeak = round(memory_get_peak_usage() / 1024 / 1024, 4);
    $mem     = round(memory_get_usage() / 1024 / 1024, 4);

    if (php_sapi_name() == "cli") {
        echo PHP_EOL . "Generated Time : $ms ms , $sec sec " . PHP_EOL . "Memory Usage   : {$mem} mb (current), {$memPeak} mb (peak)" . PHP_EOL . PHP_EOL;
}
