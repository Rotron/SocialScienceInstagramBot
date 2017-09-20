<?php

require __DIR__ . '\vendor\autoload.php';
require __DIR__ . 'file_util.php';


function getAllUserPosts($instagram_account, $_user_id)
{
    $feed = null;
    $medias = [];
    $max_media_next_id = null;
    do {
        print("...");
        if (is_null($feed)) {
            $feed = $instagram_account->timeline->getUserFeed($_user_id);
            $medias = $feed->getItems();
        } else {
            $feed = $instagram_account->timeline->getUserFeed($_user_id, $max_media_next_id);
            $medias = array_merge($medias, $feed->getItems());
        }
    } while (!is_null($max_media_next_id = $feed->getNextMaxId()));
    print("\n");
    return $medias;
}

function getTopLikedPosts($medias_list, $max_media_num = null)
{
    usort($medias_list, function ($a, $b) {
        return $a->getLikeCount() < $b->getLikeCount();
    });

    if (is_null($max_media_num))
        return $medias_list;
    else
        return array_slice($medias_list, 0, $max_media_num);
}

function getCustomComments($instagram_account, $media, $prcnt_of_cms)
{
    $cms = [];
    $mod_cms = [];
    $cm_helper = null;
    $next_max_id = null;
    $pg_num = 0;
    $total_cms = $media->getCommentCount();
    print($total_cms . "\n");
    $cm_per_page = 20;
    $max_cm_number = ceil(($prcnt_of_cms / 100) * $total_cms);
    print($max_cm_number . "\n");
    print("\n all pages : -> " . $total_cms / $cm_per_page);
    do {
        if (is_null($cm_helper)) {
            $cm_helper = $instagram_account->media->getComments($media->getId());
        } else {
            $cm_helper = $instagram_account->media->getComments($media->getId(), $next_max_id);
        }
        $cms = array_merge($cms, $cm_helper->getComments());
        print("\n ___" . $pg_num . "___" . "\n");
        print("..." . "\n");
        $pg_num++;
    } while (!is_null($next_max_id = $cm_helper->getNextMaxId()));
    $rand_keys = array_rand($cms, $max_cm_number);
    if (count($rand_keys) == 1)
        $rand_keys = [$rand_keys];
    for ($i = 0; $i < count($rand_keys); $i++) {
        print("..." . "\n");
        $mod_cms[$i] = $cms[$rand_keys[$i]];
    }

    return $mod_cms;
}




$ig = new \InstagramAPI\Instagram();
$account = getAccount("secret_file.txt");
$a = $ig->login($account[0], $account[1]);
$userId = $ig->people->getUserIdForName('_alizeyn');
//$medias = $ig->timeline->getUserFeed($userId)->getItems()[0]->getUser()->getUsername()
//$medias[0]->getMedia()->get
//$m = $ig->media-

try {

    $total_medias = getAllUserPosts($ig, $userId);
    $ordered_list = getTopLikedPosts($total_medias, 5);

    for ($i = 0; $i < count($ordered_list); $i++) {
        $caption = $ordered_list[$i]->getCaption();
        if (is_null($caption))
            $caption = "";
        else
            $caption = $caption->getText();

        print("-" . ($i + 1) . "-" . "\n"
            . " caption : " . $caption . "\n"
            . " likes : " . $ordered_list[$i]->getLikeCount() . "\n"
            . " link : " . $ordered_list[$i]->getItemUrl() . "\n\n");
        print(" Comments : " . "\n\n\n");
        $media_cms = getCustomComments($ig, $ordered_list[$i], 1);
        $media_cms_number = count($media_cms);
        if ($media_cms_number > 0) {
            for ($j = 0; $j < $media_cms_number; $j++) {
                print("\n_____________comment number" . $j . "_______________ \n");
                print("username : " . $media_cms[$j]->getUser()->getUsername() . "\n"
                    . "Comment   : " . $media_cms[$j]->getText() . "\n");
                print("____________________________\n");
            }
        }

    }
//    foreach ($cms as $cm)
//        print(' - ' . $cm->getText() . " \n");
} catch (Exception $e) {
    print($e->getMessage());
}


