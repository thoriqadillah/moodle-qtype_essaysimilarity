<?php

include('../stemmer/stemmer.php');

class stopword {
  protected $stopwords = [];

  public function __construct($lang = 'en') {
    $this->stopwords = require('lang/'. $lang . '.php');
  }

  /**
   * Remove stop word from token and then stem the token
   * @param array $token
   * @param stemmer $stemmer stemmer interface
   * @return array cleaned token
   */
  public function remove_stopword($token, $stemmer) {
    $token = array_udiff($token, $this->stopwords, 'strcasecmp');
    
    $processed = [];
    foreach ($token as $tok) {
      $processed[] = $stemmer->stem($tok);
    }

    return $processed;
  }
}