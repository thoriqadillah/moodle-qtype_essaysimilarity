<?php

include('stopword/stopword.php');
include('tfidf_transformer.php');

include("stemmer/stemmer.php");
include("stemmer/en/en.php");

class tokenizer {

  const PATTERN = '/[\pZ\pC]+/u';

  /**
   * Whitespace tokenizer
   * Credit to @angeloskath, copied from https://github.com/angeloskath/php-nlp-tools/blob/master/src/NlpTools/Tokenizers/WhitespaceTokenizer.php
   * @param string $str string to be tokenized
   * @return array $str tokenized string
   */
  public function tokenize($str, $lang = 'none') {
    
    $str = $this->normalize($str);
    $stopword = new stopword($lang);
    
    $token = preg_split(self::PATTERN, $str, -1, PREG_SPLIT_NO_EMPTY);
    
    if ($lang !== 'none') {
      require_once("stemmer/$lang/$lang.php");

      $classname = $lang . '_stemmer';
      if (class_exists($classname)) {
        $stemmer = new $classname();
      }
  
      $token = $stopword->remove_stopword($token, $stemmer);
    }

    return is_int(key($token)) ? array_count_values($token) : $token;
  }

  /**
   * Normalize the string from special characters and symbols
   */
  protected function normalize($str) {
    $str = preg_replace('/[^a-z0-9 -]/im', ' ', $str);
    $str = preg_replace('/( +)/im', ' ', $str);

    return trim($str);
  }
}