<?php
/**
 * Created by PhpStorm.
 * User: Ali Zeynali
 * Date: 9/20/2017
 * Time: 2:40 PM
 */

function getAccount($secret_file) {
    $s_file =  fopen($secret_file, "r");
    $account_string = trim(fgets($s_file));
    return explode(":", $account_string);
}

