<?php
/*
 *      functions.php
 */

$stopWords =array();
$file_handle = fopen("stopwordlist.csv", "r");
while (!feof($file_handle) ) {
	$line_of_text = fgetcsv($file_handle, 1024);
	array_push($stopWords, $line_of_text[0]);
}
fclose($file_handle);
//echo count($stopWords);


function get_keywords_from_pool($pool){
	global $stopWords;
	$pool = just_clean($pool);
	$word_array=explode(" ", $pool);
	$refined_word_array = array();
	foreach($word_array as $word){
		if(!in_array($word, $stopWords) && !empty($word) && !in_array(strtolower($word), $stopWords))
			array_push($refined_word_array, $word);
	}
	return $refined_word_array;
}

function just_clean($string){
    // Replace other special chars
    $specialCharacters = array(
        '#' => '',
        '$' => '',
        '%' => '',
        '&nbsp;' => '',
        '&amp;' => '',
        '&' => '',
        '@' => '',
        '.' => '',
        '€' => '',
        '+' => '',
        '=' => '',
        '§' => '',
        '\\' => '',
        '/' => '',
        '-' => ''
    );
    
    while (list($character, $replacement) = each($specialCharacters)) {
        $string = str_replace($character, '-' . $replacement . '-', $string);
    }
    
    $string = strtr($string,
        "ÀÁÂÃÄÅáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ",
        "AAAAAAaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn"
    );
    
    // Remove all remaining other unknown characters
    $string = preg_replace('/[^a-zA-Z0-9-]/', ' ', $string);
    $string = preg_replace('/^[-]+/', '', $string);
    $string = preg_replace('/[-]+$/', '', $string);
    $string = preg_replace('/[-]{2,}/', '', $string);
    
    return $string;
}

function extractCommonWords($string){
	global $stopWords;
	$string = preg_replace('/http:\/\/[a-zA-Z0-9-\_\+\=\?\/\.\&\*\%\$\#\!\@]+/', '', $string);
	$string = preg_replace('/@[a-zA-Z0-9]+/','',$string);
        $string = preg_replace('/sss+/i', '', $string);
        $string = trim($string); // trim the string
        $string = preg_replace('/[^a-zA-Z0-9 -]/', '', $string); // only take alphanumerical characters, but keep the spaces and dashes too…
        //$string = strtolower($string); // make it lowercase
  
        preg_match_all('/\b.*?\b/i', $string, $matchWords);
        $matchWords = $matchWords[0];
  
        foreach ( $matchWords as $key=>$item ) {
                    $item=trim($item);
            if ( $item == '' || in_array(strtolower($item), $stopWords) || strlen($item) < 3 ) {
                unset($matchWords[$key]);
            }
        }
        $wordCountArr = array();
        if ( is_array($matchWords) ) {
            foreach ( $matchWords as $key => $val ) {
                //$val = strtolower($val);
                if ( isset($wordCountArr[$val]) ) {
                    $wordCountArr[$val]++;
                } else {
                    $wordCountArr[$val] = 1;
                }
            }
        }
        arsort($wordCountArr);
        $wordCountArr = array_slice($wordCountArr, 0, 10);
        return $wordCountArr;
}

?>
