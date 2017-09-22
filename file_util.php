<?php
/**
 * Created by PhpStorm.
 * User: Ali Zeynali
 * Date: 9/20/2017
 * Time: 2:40 PM
 */

function getAccount($secret_file)
{
    $s_file = fopen($secret_file, "r");
    $account_string = trim(fgets($s_file));
    fclose($s_file);
    return explode(":", $account_string);
}

function wirte_header_to_file($base_data_dir, $media)
{
    $caption = $media->getCaption();
    if (is_null($caption))
        $caption = "";
    else
        $caption = $caption->getText();
    $header = "\"" . "Username" . "\"" . "," . "\"" . trim($media->getUser()->getUsername()) . "\"" . "\n"
        . "\"" . "Caption" . "\"" . "," . "\"" . trim($caption) . "\"" . "\n"
        . "\"" . "Likes" . "\"" . "," . "\"" . trim($media->getLikeCount()) . "\"" . "\n"
        . "\"" . "Link" . "\"" . "," . "\"" . trim($media->getItemUrl()) . "\"" . "\n\n\n\n";

    file_put_contents($base_data_dir . "/" . $media->getId() . ".csv", "\xEF\xBB\xBF" . $header);
    print($header);
}


function wirte_comment_to_the_file($base_data_dir, $media_cms, $media_id)
{
    if(count($media_cms) == 1 )
        $media_cms = [$media_cms];

    $media_file = fopen($base_data_dir . "/" . $media_id . ".csv", "a");
    for ($j = 0; $j < count($media_cms); $j++) {
        print("___");
        $cm_format = "\"" . "username" . "\"" . "," . "\"" . trim($media_cms[$j]->getUser()->getUsername()) . "\"" . "\n"
            . "\"" . "Comment" . "\"" . "," . "\"" . trim($media_cms[$j]->getText()) . "\"" . "\n";

        fwrite($media_file, $cm_format);
        print($cm_format);
    }
    print("\n");
    fclose($media_file);

}


function read_accounts_from_file($accounts_files_path)
{
    $acc_list = [];
    if ($acc_f = fopen($accounts_files_path, "r")) {
        print("in the if \n");
        for($i =0; !feof($acc_f); $i++)
            $acc_list[$i] = trim(fgets($acc_f));
    }
    return $acc_list;
}


