<?php
/**
 * Created by PhpStorm.
 * User: isuvorov
 * Date: 9/12/14
 * Time: 11:14 AM
 */

require_once __DIR__ . '/gdocs.php';

$doc = new GDocs($url);
var_dump($doc->getTable()->getAssoc());
var_dump($doc->getTable()->getRaw());
//
//$result = GDocs::getFromUrl($url);