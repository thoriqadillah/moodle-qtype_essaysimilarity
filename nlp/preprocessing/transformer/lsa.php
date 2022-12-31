<?php

require_once('matrix.php');
require_once('svd.php');

class lsa {

  /**
   * Perform latent semantic analysis to get the most important topic of the word with dimensional reduction
   * @param matrix $matrix
   * @param int $features total feature that want to be extracted
   * 
   */
  public function transform($matrix) {
    return (new svd($matrix))->truncate()->transform();
    // $transformed = (new svd($matrix))->truncate()->transform();
    // return $matrix->replace_original($transformed);
  }
}