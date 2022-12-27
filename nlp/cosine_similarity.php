<?php

class cosine_similarity {

  protected $v1;
  protected $v2;

  public function __construct($tok_answerkey, $tok_response) {
    $this->v1 = $tok_answerkey;
    $this->v2 = $tok_response;
  }

  private function dot() {
    $prod = 0.0;
    foreach ($this->v1 as $i => $xi) {
      $prod += $xi * $this->v2[$i];
    }

    return $prod;
  }

  private function magintude($vect): float {
    $magnitude = 0.0;
    foreach ($vect as $v) {
      $magnitude += $v * $v;
    }

    return sqrt($magnitude);
  }
  
  /**
   * Get the similarity between two string
   * Credit to @angeloskath, copied from https://github.com/angeloskath/php-nlp-tools/blob/master/src/NlpTools/Similarity/CosineSimilarity.php
   * @return float percentage of the similarity
   */
  public function get_similarity() {
    $prod = $this->dot();
    $v1_norm = $this->magintude($this->v1);
    $v2_norm = $this->magintude($this->v2);

    $magnitude = ($v1_norm * $v2_norm);
    return ($magnitude == 0) ? 0 : $prod / $magnitude;
  }
}