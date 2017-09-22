<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/file_util.php';

$POST_LIMIT = 5;
$COMMENT_PERCENT = 20;


function getAllUserPosts($instagram_account, $_user_id)
{
    global $POST_LIMIT;
    $feed = null;
    $res_array = [];
    $max_media_next_id = null;
    do {
        print("...");

        if (is_null($feed))
            $feed = $instagram_account->timeline->getUserFeed($_user_id);
        else
            $feed = $instagram_account->timeline->getUserFeed($_user_id, $max_media_next_id);

        $alt_array = getTopLikedPosts($feed->getItems(), $POST_LIMIT);
        $res_array = getTopLikedPosts(array_merge($res_array, $alt_array), $POST_LIMIT);
    } while (!is_null($max_media_next_id = $feed->getNextMaxId()));

    print("\n");
    return $res_array;
}

function getTopLikedPosts($medias_list, $max_media_num = null)
{
    $media_list_count = count($medias_list);
    usort($medias_list, function ($a, $b) {
        return $a->getLikeCount() < $b->getLikeCount();
    });

    if (is_null($max_media_num) || $media_list_count < $max_media_num)
        return $medias_list;
    else
        return array_slice($medias_list, 0, $max_media_num);
}


function getCustomComments($base_data_dir, $instagram_account, $media, $prcnt_of_cms)
{
    $writed_comments = 0;
    $mod_cms = [];
    $cm_helper = null;
    $next_max_id = null;
    $pg_num = 0;
    $fetched_cms_count = 0;
    $media_handler = $instagram_account->media;
    $total_cms_count = $media->getCommentCount();
    $max_cms_count = ceil($total_cms_count * ($prcnt_of_cms / 100));
    $partion_size = ceil($total_cms_count / $max_cms_count);
    $media_id = $media->getId();
    $rand_in_partion_area = 0;
    $loading = false;

    print("total amount of comments : " . $total_cms_count . "\n");

    do {
        if (is_null($cm_helper)) {
            $cm_helper = $media_handler->getComments($media_id);
        } else {
            $cm_helper = $media_handler->getComments($media_id, $next_max_id);
        }
        print(".\n");
        $cms = $cm_helper->getComments();
        $cms_count_in_page = count($cms);
        $fetched_cms_count += $cms_count_in_page;

        print("raw cms size per page : " . count($cms) . "\n");
        print("writted comments till now : " . $writed_comments . " \n");
        if ($partion_size <= $cms_count_in_page && !$loading) {
            print("partion_size <= cms_count_in_page" . "\n");
            print("partion size : " . $partion_size . "   comments count in the page : " . $cms_count_in_page . "\n");
            print("feteched cms : " . $fetched_cms_count . "\n");
            print("max should get comments : " . $max_cms_count . "\n");

            for ($i = 0; $i < ceil($cms_count_in_page / $partion_size); $i++) {
                print ("i : " . $i . " devide : " . ceil($cms_count_in_page / $partion_size) . "\n");
                $rand_index = mt_rand(0, $partion_size - 1) + ($i * $partion_size);
                if ($rand_index > $cms_count_in_page) {
                    $rand_index = $cms_count_in_page;
                }
                print("rand index is : " . $rand_index . "\n");
                if ($rand_index < $cms_count_in_page) {
                    $cm = $cms[$rand_index];
                    $writed_comments++;
                    wirte_comment_to_the_file($base_data_dir, $cm, $media_id);
                }

            }
        }
        if ($partion_size > $cms_count_in_page || $loading) {
            if(!$loading) {
                $rand_in_partion_area = mt_rand(0, $partion_size - 1) + ($fetched_cms_count - $cms_count_in_page);
            }

            $loading = true;
            print("partion_size > cms_count_in_page" . "\n");
            print("partion size : " . $partion_size . "   comments count in the page : " . $cms_count_in_page . "\n");
            print("rand_in_partion_area : " . $rand_in_partion_area . "   fetched_cms_count : " . $fetched_cms_count . "\n");
            print("max should get comments : " . $max_cms_count . "\n");
            if ($rand_in_partion_area < $fetched_cms_count) {
                $cm = $cms[$rand_in_partion_area - ($fetched_cms_count - $cms_count_in_page)];
                wirte_comment_to_the_file($base_data_dir, $cm, $media_id);
                $writed_comments++;
                $loading = false;
            }
        }
        $pg_num++;

    } while (($fetched_cms_count < $total_cms_count) && !is_null($next_max_id = $cm_helper->getNextMaxId()));


    $cms_count = count($cms);
    $rand_keys = array_rand($cms, ceil($cms_count * ($prcnt_of_cms / 100)));
    $rand_keys_count = count($rand_keys);
    if ($rand_keys_count == 1)
        $rand_keys = [$rand_keys];
    for ($i = 0; $i < $rand_keys_count; $i++) {
        $mod_cms[$i] = $cms[$rand_keys[$i]];
    }
    return $mod_cms;
}

$ig = new \InstagramAPI\Instagram();
$account = getAccount("secret_file.txt");
$a = $ig->login($account[0], $account[1]);

$usernames = read_accounts_from_file(__DIR__ . '/accounts.txt');
var_dump($usernames);

foreach ($usernames as $username) {
    print($username);
    try {
        $userId = $ig->people->getUserIdForName($username);
    } catch (Exception $err) {
        print($err->getMessage());
        continue;
    }
    $base_data_dir = "./Data/" . $username . "/";

    if (!file_exists($base_data_dir))
        mkdir($base_data_dir, 0777, true);

    try {
        $total_medias = getAllUserPosts($ig, $userId);
        $ordered_list = getTopLikedPosts($total_medias, $POST_LIMIT);
        for ($i = 0; $i < count($ordered_list); $i++) {
            print(".\n");
            wirte_header_to_file($base_data_dir, $ordered_list[$i]);
            $media_cms = getCustomComments($base_data_dir, $ig, $ordered_list[$i], $COMMENT_PERCENT);
        }
    } catch (Exception $e) {
        print($e->getMessage());
    }
}
print("@@@@@@@@@@@@@@@@@@****ALL DONE*****@@@@@@@@@@@@@@@@@@");



