<?php
class cosine_similarity {

  protected $v1;
  protected $v2;

  public function __construct($tok_answerkey, $tok_response) {
    $this->v1 = $tok_answerkey;
    $this->v2 = $tok_response;
  }
  
  /**
   * Get the similarity between two string
   * Credit to @angeloskath, copied from https://github.com/angeloskath/php-nlp-tools/blob/master/src/NlpTools/Similarity/CosineSimilarity.php
   * @return float percentage of the similarity
   */
  public function get_similarity() {
    $prod = 0.0;
    $v1_norm = 0.0;
    foreach ($this->v1 as $i => $xi) {
      if (isset($this->v2[$i])) {
        $prod += $xi * $this->v2[$i];
      }

      $v1_norm += $xi * $xi;
    }

    $v1_norm = sqrt($v1_norm);
    if ($v1_norm === 0) return 0.00;
    
    $v2_norm = 0.0;
    foreach ($this->v2 as $i => $xi) {
      $v2_norm += $xi * $xi;
    }

    $v2_norm = sqrt($v2_norm);
    if ($v2_norm === 0) return 0.00;

    return $prod / ($v1_norm * $v2_norm);
  }
}