<?php

require_once('stopword/stopword.php');
require_once('stemmer/stemmer.php');

class tokenizer {

  private $lang = 'none';

  public function __construct($lang) {
    $this->lang = clean_param($lang, PARAM_ALPHA);
  }

  /**
   * Whitespace tokenizer
   * Credit to @angeloskath, copied from https://github.com/angeloskath/php-nlp-tools/blob/master/src/NlpTools/Tokenizers/WhitespaceTokenizer.php
   * @param string $str string to be tokenized
   * @return array $str tokenized string
   */
  public function tokenize($str) {
    $str = $this->normalize($str);
    $token = preg_split('/[\pZ\pC]+/u', $str, -1, PREG_SPLIT_NO_EMPTY);
    
    // we assume that stemmer implementation and stopword dictionary for certain language is present, otherwise errors will be thrown
    if ($this->lang !== 'none') {
      require_once("stemmer/$this->lang/$this->lang.php");

      $classname = $this->lang.'_stemmer';
      $stemmer = new $classname();

      $stopword = new stopword($this->lang);
      $token = $stopword->remove_stopword($token, $stemmer);
    }

    $raw = array_flip($token);
    $raw = array_map(function() {
      return 0;
    }, $raw);
    
    return [
      'counted' => array_count_values($token),
      'raw' => $raw
    ];
  }

  /**
   * Normalize the string from special characters and symbols
   */
  protected function normalize($str) {
    $str = preg_replace('/[^a-z -]/im', ' ', $str);
    $str = preg_replace('/( +)/im', ' ', $str);
    $str = str_replace('- ', '', $str);

    return trim($str);
  }
}