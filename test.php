<?php
set_time_limit(0);
require_once 'simple_html_dom.php';
require_once 'functions.php';
require_once 'db/DbObj.php';

$url = "http://bibhas.in/";
if($url[strlen($url)-1] != "/")
    $url .= "/";

echo "<pre>";

$html = new simple_html_dom();
$result = get($url, $html);
$url_id = save_url($result);
save_keywords($url_id, $result);
save_url_array($result['anchors'], $html);

echo "</pre>";

function save_url_array($anchors, $html){
    
    foreach($anchors as $url){
        echo $url . "\n";
        $result = get($url, $html);
        //print_r($result);
        $url_id = save_url($result);
        save_keywords($url_id, $result);
    }
    
}
function get($url, $html){
    $result = array('url' => $url);
    $headers = @get_headers($url, TRUE);
    if(empty($headers))
        return array();
    else{
        list($protocol, $status, $desc) = explode(" ", $headers[0], 3);
        if($status == 200){
            $html->load_file($url);
            
            $title = $html->find('title', 0);
            if(!empty($title))
                $result['title'] = $title->innertext;
            else
                $result['title'] = '';
            
            $anchors = $html->find('a');
            if(count($anchors)>0)
                $anchors_array = clean_anchors($anchors, $url);
            else
                $anchors_array = array();
            $result['anchors'] = $anchors_array;
            
            $body = $html->find('body', 0);
            $body_text = $body->plaintext;
            $keyword_list = get_keywords_from_pool($body_text);
            $keyword_list = array_unique($keyword_list, SORT_STRING);
            $result['keywords'] = $keyword_list;
            
            $html->clear();
            
            return $result;
        }else{
            return array();
        }
    }
}

function clean_anchors($anchors, $url){
    $url_pattern = "/(http|https):\/\/[a-zA-Z0-9]+\.[a-zA-Z0-9\.\/\?\=\&\%\_\*\-\!\@\#\$\^\(\)\+\|\~\`]+/";
    $anchors_array = array();
    $temp = array();
    foreach($anchors as $a){
        $href=$a->href;
        if($href[0]=="/")
            $href = substr($href, 1);
        if(preg_match($url_pattern, $href))
            array_push($anchors_array, $href);
        else{
            $href = $url . $href;
            array_push($anchors_array, $href);
        }
    }
    $anchors_array = array_unique($anchors_array);
    return $anchors_array;
}
function save_url($url){
    if(!isset($url['url']))
        return;
    $db = new DbObj();
    echo $url['url'] . "\n";
    if(is_tld($url['url'])){
        echo "tld \n";
        $res_one = $db->get_data("urls", "href='{$url['url']}'");
        if(count($res_one)==0){
            $db->insert_data(
                                "urls",
                                array(
                                    "href" => $url['url'],
                                    "title" => trim($url['title']),
                                    "parent_id" => "NULL",
                                    "is_crawled" => 1
                                )
                            );
            $res_two = $db->get_data("urls", "href='{$url['url']}'");
            return $res_two[0]['id'];
        }else{
            return -1;
        }
    }else{
        echo "not tld \n";
        $tld = get_tld($url['url']);
        $res_three = $db->get_data("urls", "href='{$tld}'");
        if(count($res_three)==0){
            $html = new simple_html_dom();
            $url_temp = get($tld, $html);
            //check if tld exists
            $db->insert_data(
                                "urls",
                                array(
                                    "href" => $tld,
                                    "title" => trim($url_temp['title']),
                                    "parent_id" => "NULL",
                                    "is_crawled" => 0
                                )
                            );
            $res_five = $db->get_data("urls", "href='{$tld}'");
            $parent_id=$res_five[0]['id'];
        }else{
            $parent_id=$res_three[0]['id'];
        }
        $res_four = $db->get_data("urls", "href='{$url['url']}'");
        if(count($res_four)==0){
            $db->insert_data(
                            "urls",
                            array(
                                "href" => $url['url'],
                                "title" => $url['title'],
                                "parent_id" => $parent_id,
                                "is_crawled" => 1
                            )
                        );
            $res_six = $db->get_data("urls", "href='{$url['url']}'");
            $url_id=$res_six[0]['id'];
        }else{
            $url_id=$res_four[0]['id'];
        }
        return $url_id;
    }
}

function save_keywords($url_id, $result){
    if($url_id >= 0){
        if(count($result['keywords'])>0){
            $db = new DbObj();
            foreach($result['keywords'] as $kw){
                if(strlen($kw)<=50){
                    $res_one = $db->get_data("keywords", "keyword = '{$kw}'");
                    //echo $kw . " " . count($res_one) . "\n"; print_r($res_one);
                    if(count($res_one)==0){
                        //echo "152\n";
                        $db->insert_data(
                                            "keywords",
                                            array(
                                                "keyword" => $kw
                                            )
                                        );
                        $res_two = $db->get_data("keywords", "keyword = '{$kw}'");
                        //echo "152: " . $kw . " \n"; print_r($res_two);
                        $kw_id=$res_two[0]['id'];
                        $res_three = $db->get_data("url_keyword_link", "url_id = '{$url_id}' AND keyword_id = '{$kw_id}'");
                        if(count($res_three)==0)
                            $db->insert_data("url_keyword_link", array("url_id" => $url_id, "keyword_id" => $kw_id));
                    }else{
                        //echo "166\n";
                        $kw_id=$res_one[0]['id'];
                        $res_four = $db->get_data("url_keyword_link", "url_id = '{$url_id}' AND keyword_id = '{$kw_id}'");
                        //echo count($res_four) . "\n";
                        if(count($res_four)==0){
                            $db->insert_data("url_keyword_link", array("url_id" => $url_id, "keyword_id" => $kw_id));
                        }
                    }
                }
            }
        }
    }
}

function get_tld($url){
    $info = parse_url($url);
    $tld = $info['scheme'] . "://" . $info['host'] . "/";
    return $tld;
}
function is_tld($url){
    $info = parse_url($url);
    if((empty($info['path']) || $info['path']=="/") && empty($info['query']))
        return TRUE;
    else
        return FALSE;
}
?>
<pre>
<?php //print_r($result); ?>
</pre>