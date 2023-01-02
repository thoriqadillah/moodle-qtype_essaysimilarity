<?php

require_once('transformer.php');
require_once('matrix.php');
require_once('svd.php');

class lsa implements transformer {

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
    $transformed = (new svd($this->matrix))->truncate()->transform();
    $original = $this->matrix->original();

    foreach ($original as $i => $_) {
      $original[$i] = array_combine(
        array_keys($original[0]), array_values($transformed[$i])
      );
    }

    return $original;
  }
}