<?php

include_once(__DIR__ . '/../vendor/autoload.php');

if (!class_exists('Test\\ClassA')) {
    include(__DIR__ . '/ClassA.php');
}
if (!class_exists('ClassB')) {
    include(__DIR__ . '/ClassB.php');
}

