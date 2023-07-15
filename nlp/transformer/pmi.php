<?php

global $CFG;
require_once($CFG->dirroot.'/question/type/essaysimilarity/nlp/cleaner/cleaner.php');

class pmi implements cleaner {

    private function bigram(array $vector): array {
        $bigrams = [];
        
        $keys = array_keys($vector);
        for ($i = 0; $i < count($keys) - 1; $i++) {
            $bigrams[] = [$vector[$keys[$i]], $vector[$keys[$i + 1]]];
        }

        return $bigrams;

    }

    private function coocurance_counts(array $bigrams, int $windows): array {
        $occurances = [];
        for ($i = 0; $i < count($bigrams); $i++) {
            $word1 = $bigrams[$i][0];
            $word2 = $bigrams[$i][1];
    
            for ($j = $i + 1; $j < min($i + $windows, count($bigrams)); $j++) {
                $nextWord1 = $bigrams[$j][0];
                $nextWord2 = $bigrams[$j][1];
    
                // Check if the words are within the window size
                if ($word1 === $nextWord1 || $word2 === $nextWord2) {
                    $occurances[$word1][$word2] = isset($occurances[$word1][$word2]) ? $occurances[$word1][$word2] + 1 : 1;
                }
            }
        }

        return $occurances;
    }

    public function clean(array $vector): array {
        $bigrams = $this->bigram($vector);
        $coocurance_counts = $this->coocurance_counts($bigrams, 16);

        
        $pmi_scores = [];
        $total_bigram = count($bigrams);
        foreach ($coocurance_counts as $word1 => $cooccurrence) {
            foreach ($cooccurrence as $word2 => $count) {
                $p_word1_word2 = $count / $total_bigram;
                $p_word1 = array_count_values($vector)[$word1] / count($vector);
                $p_word2 = array_count_values($vector)[$word2] / count($vector);
                
                $pmi = log($p_word1_word2 / ($p_word1 * $p_word2), 2);
                $pmi_scores[$word1][$word2] = $pmi;
            }
        }

        return $pmi_scores;
    }
}