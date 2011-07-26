<?php

/**
 * @author Bibhas
 * @copyright 2010
 */
set_time_limit(0);
require_once 'simple_html_dom.php';

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 3.2//EN">

<html>
<head>
    <title>URL and Email harvester</title>
    <style type="text/css" media="all">
    body{
        background-color: #efefef;
    }
    a{
        text-decoration: none;
        color: #fb0;
    }
    #container{
        width: 800px;
        margin: 0 auto;
        text-align: center;
        font-family: monospace;
    }
    table#url, table#email{
        margin: 0 auto;
    }
    td{
        padding: 0 20px;
    }
    tr{
        height: 20px;
    }
    </style>
</head>

<body>
    <div id="container">
        <div id="header">
            <h1>URL and Email Harvester</h1>
        </div>
        <form action="index.php" method="get">
            <label for="url">Enter url</label>
            <input type="text" name="url" id="url" />
            <input type="submit" name="submit" />
        </form>
        <p>
        <?php
        if(isset($_GET['url']) && !empty($_GET['url'])){
            $url=urldecode(trim($_GET['url']));
            $emails=array();
            $urls=array();
            $url_list=find_urls($url, $urls);
            if(count($url_list)>0){
            //print_r($url_list);
            ?>
            <h2>URLs</h2>
            <table id="url">
                    <?php
                    foreach($url_list as $url){
                        $t_url=$url;
                        if(strlen($url)>50)
                            $t_url=substr($url, 0, 50) . "..";
                        echo "
                            <tr>
                                <td>{$t_url}</td>
                                <td>
                                    <a target='_blank' href='{$url}'>Visit</a>
                                </td>
                            </tr>";
                    }
                    ?>
            </table>
            <?php
            }else{ echo "<p>No URL Found</p>"; }
            $email_list=find_email($url, $emails);
            if(count($email_list)>0){
            //print_r($email_list);
            ?>
            <h2>Emails</h2>
            <table id="email">
                    <?php
                    foreach($email_list as $email)
                        echo "
                            <tr>
                                <td>{$email}</td>
                                <td>
                                    <a href='mailto:{$email}'>Mail</a>
                                </td>
                            </tr>";
                    ?>
            </table>
            <?php
            }else{ echo "<p>No Email address Found</p>"; }
            
        }
        ?>
        </p>
</div>
</body>
</html>
<?php
function find_urls($url, $urls){
    $url_regex="/http:\/\/[a-zA-Z0-9\.\?\/\=\!\@\#\$\%\^\&\*\_\-\+]+\.[a-zA-Z0-9\.\?\/\=\!\@\#\$\%\^\&\*\_\-\+]+/";
    $html = new simple_html_dom();
    $html->load_file($url);
    $matches=array();
    foreach($html->find('text') as $e) {
        $text=$e->innertext;
        if(preg_match_all($url_regex, $text, $matches)>0){
            if(!in_array($matches[0][0], $urls))
                array_push($urls, $matches[0][0]);
        }
    }
    
    foreach($html->find('a') as $a) {
        $url=$a->href;
        if(preg_match_all($url_regex, $url, $matches)>0){
            if(!in_array($matches[0][0], $urls))
                array_push($urls, $matches[0][0]);
        }
    }
    $html->clear();
    unset($html);
    return($urls);
}
function find_email($url, $emails){
    $html1 = new simple_html_dom();
    $html1->load_file($url);
    $e_matches=array();
    foreach($html1->find('text') as $e) {
        $text=$e->innertext;
        if(preg_match_all("/[a-zA-Z0-9\_\.]+@[a-zA-Z0-9\.]+/", $text, $e_matches)>0){
            if(!in_array($e_matches[0][0], $emails))
                array_push($emails, $e_matches[0][0]);
        }
    }
    foreach($html1->find('a') as $a) {
        $url=$a->href;
        if(preg_match_all("/[a-zA-Z0-9\_\.]+@[a-zA-Z0-9\.]+/", $url, $e_matches)>0){
            if(!in_array($e_matches[0][0], $emails))
                array_push($emails, $e_matches[0][0]);
        }
    }
    $html1->clear();
    unset($html1);
    return($emails);
}
?>