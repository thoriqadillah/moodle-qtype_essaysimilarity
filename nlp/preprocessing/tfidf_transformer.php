<?php

/**
 * TF-IDF transformer
 * Credit to @jorgecasas, copied from https://github.com/jorgecasas/php-ml/blob/develop/src/FeatureExtraction/TfIdfTransformer.php
 * The moodle actually has its own mlbackend, imported from https://github.com/jorgecasas/php-ml in the latest version. Last time I quick checked, it was >= 3.5
 * But, I don't know what version of the moodle people will use this plugin for, so I just copied it and call it a day
 */
class tfidf_transformer {

  /**
   * @var array
   */
  private $idf = [];

  public function __construct($samples = []) {
    if (count($samples) > 0) {
      $this->fit($samples);
    }
  }

  /**
   * Fit is the same as Inverse Document Frequency.
   * Inverse Document Frequency (IDF) is proportion of documents in the corpus that contain the term.
   * IDF = total corpus / total corpus that contains key we looking for
   * 
   * For example, assume we have 10 million documents (again, assume there is no data cleaning)
   * and the word cat appears in one thousand of these. So, the IDF will be
   * 
   * IDF = log (10000000 / 1000)
   * IDF = 4
   */
  public function fit($samples) {
    $this->count_frequency($samples);

    $count = count($samples);
    foreach ($this->idf as &$value) {
      $value = 1 + log((float) (($count + 1) / ($value + 1)), 10.0); // idf with smoothing to avoid division by zero
    }
  }

  public function transform(&$samples) {
    foreach ($samples as &$sample) {
      foreach ($sample as $index => &$feature) {
        $feature *= $this->idf[$index];
      }
    }
  }

  /**
   * Count Frequency is the same as Term Frequency. Term Frequency (TF) is the number of times the term appears in a document compared to the total number of words in the document
   * TF = the occurance of the key inside a document / total token
   * 
   * For example, a document has 100 words, (assume there is no data cleaning)
   * and then term or key cat appears 3 times, so the TF will be
   * 
   * TF = 3 / 100
   * TF = 0.03
   */
  protected function count_frequency($samples) {
    $this->idf = array_fill_keys(array_keys($samples[0]), 0);

    foreach ($samples as $sample) {
      foreach ($sample as $index => $count) {
        if ($count > 0) {
          ++$this->idf[$index];
        }
      }
    }
  }
}