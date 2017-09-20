<?php

require __DIR__ . '\vendor\autoload.php';
require __DIR__ . '\file_util.php';

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


    do {
        print("in the comments page : " . $pg_num . "\n");
        if (is_null($cm_helper)) {
            $cm_helper = $instagram_account->media->getComments($media->getId());
        } else {
            $cm_helper = $instagram_account->media->getComments($media->getId(), $next_max_id);
        }
        $cms = array_merge($cms, $cm_helper->getComments());
        $pg_num++;
    } while (!is_null($next_max_id = $cm_helper->getNextMaxId()));
    print("number of all the comments are : " . count($cms) . "\n");
    $rand_keys = array_rand($cms, ceil(count($cms) * ($prcnt_of_cms / 100)));
    print("number of rand keys are : " . count($rand_keys) . "\n");
    if (count($rand_keys) == 1)
        $rand_keys = [$rand_keys];
    for ($i = 0; $i < count($rand_keys); $i++) {
        $mod_cms[$i] = $cms[$rand_keys[$i]];
    }
    print("number of modifed keys : " . count($mod_cms) . "\n");
    return $mod_cms;
}


$ig = new \InstagramAPI\Instagram();
$account = getAccount("secret_file.txt");
$a = $ig->login($account[0], $account[1]);
$username = "_alizeyn";
$userId = $ig->people->getUserIdForName($username);

$base_data_dir = "./Data/" . $username . "/";
if (!file_exists($base_data_dir))
    mkdir($base_data_dir, 0777, true);

try {
    $total_medias = getAllUserPosts($ig, $userId);
    $ordered_list = getTopLikedPosts($total_medias, 5);

    for ($i = 0; $i < count($ordered_list); $i++) {
        wirte_header_to_file($base_data_dir, $ordered_list[$i]);
        print(" Comments : " . "\n\n\n");
        $media_cms = getCustomComments($ig, $ordered_list[$i], 100);
        $media_cms_number = count($media_cms);
        if ($media_cms_number > 0) {
            wirte_comment_to_the_file($base_data_dir, $media_cms, $ordered_list[$i]->getId());
        }

    }

} catch (Exception $e) {
    print($e->getMessage());
}

//$ig->timeline->getUserFeed($userId)->getItems()[0]->getMediaId()
