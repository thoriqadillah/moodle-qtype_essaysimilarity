<?php

/**
 * Cosine Similarity Implementaion
 * Credit to @angeloskath, copied from https://github.com/angeloskath/php-nlp-tools/blob/master/src/NlpTools/Similarity/CosineSimilarity.php
 */
class cosine_similarity {

  private function product($v1, $v2) {
    $prod = 0.0;
    foreach ($v1 as $i => $xi) {
      $prod += $xi * $v2[$i];
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
  
  public function get_similarity($v1, $v2) {
    $dot = $this->product($v1, $v2);
    $magnitude = $this->magintude($v1) * $this->magintude($v2);

    return $magnitude == 0 ? 0 : $dot / $magnitude;
  }
}