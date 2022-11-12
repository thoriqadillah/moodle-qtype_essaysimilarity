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
  public function tokenize($str, $lang = 'en') {
    require_once("stemmer/$lang/$lang.php");

    $str = $this->clean($str);
    $stopword = new stopword($lang);

    $token = preg_split(self::PATTERN, $str, -1, PREG_SPLIT_NO_EMPTY);

    $stemmer = new en_stemmer(); //default stemmer
    $classname = $lang . '_stemmer';
    if (class_exists($classname)) {
      $stemmer = new $classname();
    }

    $token = $stopword->remove_stopword($token, $stemmer);
    $token = array_filter($token);

    //TODO: transform the token with tf-idf
    
    return is_int(key($token)) ? array_count_values($token) : $token;
  }

  /**
   * Clean the string from special characters
   */
  protected function clean($str) {
    return preg_replace('/[^A-Za-z0-9. -]/', '', $str);
  }
}