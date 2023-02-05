<?php

require_once('transformer.php');
require_once('matrix.php');
require_once('svd.php');

class lsa {

  /**
   * @var matrix 
   */
  private $matrix;

  /**
   * @param array $documents 
   */
  public function __construct($documents) {
    $this->matrix = new matrix($documents);
  }

  /**
   * Perform latent semantic analysis to get the most important topic of the word with dimensional reduction
   */
  public function transform() {

    $svd = new svd($this->matrix);
    $S = $svd->S();

    // Truncate the matrix with low-rank approximation
    for ($i = $svd->K(); $i < count($S); $i++) { 
      $S[$i][$i] = 0;
    }

    // Perform LSA
    $lsa = $this->matrix->multiply($this->matrix->multiply($svd->U(), $S), $svd->VT());
    $transformed = [];

    foreach ($this->matrix->original() as $i => $_) {
      $transformed[$i] = array_combine(
        array_keys($this->matrix->original()[0]), array_values($lsa[$i])
      );
    }

    return $transformed;
  }
}