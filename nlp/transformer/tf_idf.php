<?php

/**
 * TF-IDF implementation with some modification
 * Credit to @jorgecasas from his PHP-ML library. Copied from https://github.com/jorgecasas/php-ml/blob/develop/src/FeatureExtraction/TfIdfTransformer.php
 */
class tf_idf {

  private $documents = [];
  private $idf = [];

  public function __construct($documents) {
    $this->documents = $documents;
    if (count($this->documents) > 0) $this->fit();
  }

  private function count_idf() {
    $this->idf = array_fill_keys(array_keys($this->documents[0]), 0);

    foreach ($this->documents as $sample) {
      foreach ($sample as $index => $count) {
        if ($count > 0) {
          ++$this->idf[$index];
        }
      }
    }
  }

  public function fit() {
    $this->count_idf();

    $n_docs = count($this->documents);
    foreach ($this->idf as &$value) {
      $value = 1 + log((float) (($n_docs + 1) / ($value + 1)), 10.0); // idf with smoothing to avoid division by zero
    }
  }

  public function transform() {
    foreach ($this->documents as &$document) {
      foreach ($document as $index => &$feature) {
        $feature *= $this->idf[$index];
      }
    }

    return $this->documents;
  }
}