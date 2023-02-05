<?php

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
    $M = $this->matrix->get();
    $V = $svd->V();

    // Truncate the matrix with low-rank approximation
    for ($i = 0; $i < count($V); $i++) { 
      for ($j = $svd->K(); $j < count($V[0]); $j++) { 
        $V[$i][$j] = 0;
      }
    }

    $lsa = $this->matrix->multiply($M, $V); // Perform LSA
    
    $documents = $this->matrix->original();
    $transformed = [];

    foreach ($documents as $i => $_) {
      $transformed[$i] = array_combine(
        array_keys($documents[0]), array_values($lsa[$i])
      );
    }

    return $transformed;
  }
}