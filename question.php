<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Essay question type editing form.
 *
 * @copyright  2022 Atthoriq Adillah Wicaksana (thoriqadillah59@gmail.com)
 * @copyright  based on work by 2018 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined("MOODLE_INTERNAL") || die();

// require the parent class
require_once($CFG->dirroot.'/question/type/essay/question.php');

class qtype_essaycosine_question extends qtype_essay_question implements question_automatically_gradable {

  /** Processed response with additional information 
   * @var array 
  */
  public $response = [];

  public function make_behaviour(question_attempt $qa, $preferredbehaviour) {
    if ($this->enableautograde) return question_engine::make_archetypal_behaviour($preferredbehaviour, $qa);

    return question_engine::make_behaviour('manualgraded', $qa, $preferredbehaviour);
  }
  
  /**
   * In situations where is_gradable_response() returns false, this method
   * should generate a description of what the problem is.
   * @return string the message.
   */
  public function get_validation_error(array $response) {
    //TODO
    return '';
  }

  /**
   * Add additional detail to the response
   * @param array $response the response being passed
   * @return array $response
   */
  public function process_response($response) {
    global $CFG, $USER;

    $responsetext = '';
    $answerkeytext = '';
    
    // get the plain text of the response and answer key
    if (isset($response)) {
      $responsetext = $this->to_plaintext($response['answer'], $response['format']);
      $responsetext = core_text::strtolower($responsetext);
      
      $answerkeytext = $this->to_plaintext($this->answerkey, $this->answerkeyformat);
      $answerkeytext = core_text::strtolower($answerkeytext);
    }

    $stats = $this->get_stats($responsetext);
    
    $result = [
      'text' => $responsetext,
      'answerkey' => $answerkeytext,
      'stats' => $stats,
    ];
    
    // get the attachments if any
    if (isset($response['attachments'])) {
      $result['attachments'] = $response['attachments'];
    }

    // get the plagiarsm
    $plagiarism = [];
    $plagiarismparams = [];

    if ($CFG->enableplagiarism) {
      require_once($CFG->dirroot.'/lib/plagiarismlib.php');

      list($context, $course, $cm) = get_context_info_array($PAGE->context->id);
      $plagiarismparams = [
        'userid' => $USER->id,
        'text' => $responsetext
      ];

      if ($course) {
        $plagiarismparams['course'] = $course;
      }

      if ($cm) {
        $plagiarismparams['cmid'] = $cm->id;
        $plagiarismparams[$cm->modname] = $cm->instance;
      }

      $files = empty($response) || empty($response['attachments']) ? [] : $response['attachments']->get_files();
      $plagiarism[] = plagiarism_get_links($plagiarismparams);
      foreach ($files as $file) {
        $plagiarism[] = plagiarism_get_links($plagiarismparams + ['file' => $file]);
      }

      $result['plagiarism'] = $plagiarism;
    }

    return $result;    
  }

  /**
   * Grade a response to the question, returning a fraction between
   * get_min_fraction() and get_max_fraction(), and the corresponding {@link question_state}
   * right, partial or wrong.
   * @param array $response responses, as returned by
   *      {@link question_attempt_step::get_qt_data()}.
   * @return array (float, integer) the fraction, and the state.
   */
  public function grade_response(array $response) {
    $this->response = $this->process_response($response);

    $tok_response = $this->tokenize($this->response['text']);
    $tok_answerkey = $this->tokenize($this->response['answerkey']);
    $similarity = $this->cosine_similarity($tok_answerkey, $tok_response);
    $this->response['autograde'] = $similarity;

    return [$similarity, question_state::graded_state_for_fraction($$similarity)];
  }

  /**
   * Get one of the question hints. The question_attempt is passed in case
   * the question type wants to do something complex. For example, the
   * multiple choice with multiple responses question type will turn off most
   * of the hint options if the student has selected too many opitions.
   * @param int $hintnumber Which hint to display. Indexed starting from 0
   * @param question_attempt $qa The question_attempt.
   */
  public function get_hint($hintnumber, question_attempt $qa) {
    return null; // this plugin does not have hints for multiple tries
  }

  /**
   * Generate a brief, plain-text, summary of the correct answer to this question.
   * This is used by various reports, and can also be useful when testing.
   * This method will return null if such a summary is not possible, or
   * inappropriate.
   * @return string|null a plain text summary of the right answer to this question.
   */
  public function get_right_answer_summary() {
    return null; // this plugin does not show the right answer summary
  }

  /**
   * Whitespace tokenizer
   * @param string $str string to be tokenized
   * @return array $str tokenized string
   */
  private function tokenize($str) {
    $PATTERN = '/[\pZ\pC]+/u';

    return preg_split($PATTERN, $str, -1, PREG_SPLIT_NO_EMPTY);
  }

  /**
   * Get the similarity between two string
   * @param array @a first string that has been tokenized
   * @param array @b secoond string that has been tokenized
   * @return float percentage of the similarity
   */
  private function cosine_similarity($a, $b) {
    if (!is_array($a) || !is_array($b)) {
      return 0.00;
    }

    $v1 = is_int(key($a)) ? array_count_values($a) : $a;
    $v2 = is_int(key($b)) ? array_count_values($b) : $b;
    
    $prod = 0.0;
    $v1_norm = 0.0;
    foreach ($v1 as $i => $xi) {
      if (isset($v2[$i])) {
        $prod += $xi * $v2[$i];
      }

      $v1_norm += $xi * $xi;
    }

    $v1_norm = sqrt($v1_norm);
    if ($v1_norm === 0) return $v1_norm;
    
    $v2_norm = 0.0;
    foreach ($v2 as $i => $xi) {
      $v2_norm += $xi * $xi;
    }

    $v2_norm = sqrt($v2_norm);
    if ($v2_norm === 0) return $v2_norm;

    return $prod / ($v1_norm * $v2_norm);
  }

  /**
   * Parse text from certain format to string
   * @param string $text Text to be parsed
   * @param int $format Format of the text
   * @return string
   */
  private function to_plaintext($text, $format) {
    if (empty($text)) return '';

    $plaintext = question_utils::to_plain_text($text, $format, ['para' => false]);
    $plaintext = $this->standardize_white_space($plaintext);

    return $plaintext;
  }

  /**
   * Standardize white space in $text. Html-entity for non-breaking space, $nbsp; 
   * is converted to a unicode character, "\xc2\xa0", that can be simulated by two ascii chars (194,160)
   * @param string $text
   * @return string
   */
  private function standardize_white_space($text) {
    $text = str_replace(chr(194).chr(160), ' ', $text);
    $text = preg_replace('/[ \t]+/', ' ', trim($text));
    $text = preg_replace('/( *[\x0A-\x0D]+ *)+/s', "\n", $text);

    return $text;
  }

  /**
   * Get statistical count of the response
   * @param string $responsetext 
   */
  private function get_stats($responsetext) {
    $precision = 1;
    $stats = (object) [
      'chars' => $this->get_stats_chars($responsetext),
      'words' => $this->get_stats_words($responsetext),
      'sentences' => $this->get_stats_sentences($responsetext),
      'paragraphs' => $this->get_stats_paragraphs($responsetext),
      'longwords' => $this->get_stats_longwords($responsetext),
      'uniquewords' => $this->get_stats_uniquewords($responsetext),
      'fogindex' => 0,
      'lexicaldensity' => 0,
      'charspersentence' => 0,
      'wordspersentence' => 0,
      'longwordspersentence' => 0,
      'sentencesperparagraph' => 0
    ];

    if ($stats->words) {
      $stats->lexicaldensity = round(($stats->uniquewords / $stats->words) * 100, 0).'%';
    }

    if ($stats->sentences) {
      $stats->charspersentence = round($stats->chars / $stats->sentences, $precision);
      $stats->wordspersentence = round($stats->words / $stats->sentences, $precision);
      $stats->longwordspersentence = round($stats->longwords / $stats->sentences, $precision);
    }

    if ($stats->wordspersentence) {
      $stats->fogindex = ($stats->wordspersentence + $stats->longwordspersentence);
      $stats->fogindex = round($stats->fogindex * 0.4, $precision);
    }

    if ($stats->paragraphs) {
      $stats->sentencesperparagraph = round($stats->sentences / $stats->paragraphs, $precision);
    }

    return $stats;
  }

  private function get_stats_chars($responsetext) {
    return core_text::strlen($responsetext);
  }

  private function get_stats_words($responsetext) {
    return str_word_count($responsetext, 0);
  }

  private function get_stats_sentences($responsetext) {
    $items = preg_split('/[!?.]+(?![0-9])/', $responsetext);
    // $items = array_filter($items);
    return count($items);
  }

  private function get_stats_paragraphs($responsetext) {
    $items = explode("\n", $responsetext);
    // $items = array_filter($items);
    return count($items);
  }

  private function get_stats_uniquewords($text) {
    $items = core_text::strtolower($text);
    $items = str_word_count($items, 1);
    $items = array_unique($items);
    return count($items);
  }

  private function get_stats_longwords($text) {
    $count = 0;
    $items = core_text::strtolower($text);
    $items = str_word_count($items, 1);
    $items = array_unique($items);
    foreach ($items as $item) {
        if ($this->count_syllables($item) > 2) {
            $count++;
        }
    }
    return $count;
  }

  private function count_syllables($word) {
    // https://github.com/vanderlee/phpSyllable (multilang)
    // https://github.com/DaveChild/Text-Statistics (English only)
    // https://pear.php.net/manual/en/package.text.text-statistics.intro.php
    // https://pear.php.net/package/Text_Statistics/docs/latest/__filesource/fsource_Text_Statistics__Text_Statistics-1.0.1TextWord.php.html

    static $syllable_counts = null;
    if ($syllable_counts === null) {
        // initialize with some well-known problematic words
        $syllable_counts = self::get_syllable_counts();
    }

    $str = strtolower($word);

    // very short word (1 or 2 chars)
    if (strlen($str) < 2) {
        return 1;
    }

    // If we already know the syllable count, use that.
    if (array_key_exists($str, $syllable_counts)) {
        return $syllable_counts[$str];
    }

    $count = 0;

    // Detect common endings with extra syllable.
    if (preg_match('/(ia|io|ius|ium)^/', $str)) {
        $count++;
    }

    // Detect syllables for double-vowels.
    $vowelcount = 0;
    $vowels = [ 'aa','ae','ai','ao','au','ay',
                'ea','ee','ei','eo','eu','ey',
                'ia','ie','ii','io','iu','iy',
                'oa','oe','oi','oo','ou','oy',
                'ua','ue','ui','uo','uu','uy',
                'ya','ye','yi','yo','yu','yy' ];
    $str = str_replace($vowels, '', $str, $vowelcount);
    $count += $vowelcount;

    // If the last letter is "E", it is often silent.
    $silentvowel = (substr($str, -1) == 'e');

    if ($silentvowel) {
        $final3chars = substr($str, -3);
        if (preg_match('/[bcdfgkpstxyz]le/', $final3chars)) {
            // able, cycle, idle, rifle, angle, ankle, apple, hassle, little, axle, puzzle
            $silentvowel = false;
        } else if ($final3chars == 'phe') {
            // apostrophe, catastrophe
            $silentvowel = false;
        }
    }

    // Detect syllables for single-vowels.
    $vowelcount = 0;
    $vowels = array('a','e','i','o','u','y');
    $str = str_replace($vowels, '', $str, $vowelcount);
    $count += $vowelcount;

    // Adjust the count for words that end in "e"
    // and have at least one other vowel.
    if ($count > 1 && $silentvowel) {
        $count--;
    }

    $syllable_counts[$str] = $count;
    return $count;
  }

  static protected function get_syllable_counts() {
    return array(
      // final "e" as separate syllable
      'aborigine' => 5,
      'adobe' => 3,
      'anemone' => 4,
      'cafe' => 2,
      'chile' => 2,
      'coyote' => 3,
      'epitome' => 4,
      'guacamole' => 4,
      'hyperbole' => 4,
      'karate' => 3,
      'machete' => 3,
      'maybe' => 2,
      'recipe' => 3,
      'sesame' => 3,
      'simile' => 3,
      'yosemite' => 4,

      // internal silent-e
      'jukebox' => 2,
      'shoreline' => 2,

      // double vowel as 2-syllables
      'cooperation' => 5,
      'react' => 2,

      // internal "ia" as 2-syllables
      'piano' => 3,
      'giant' => 2,
      // social, racial, spatial

      // final "ia" as 2-syllables
      'Australia' => 4,
      'California' => 5,

      // final "io" as 2-syllables
      'radio' => 3,
      'Ohio' => 3,

      // final "ion" as 2-syllables
      'ion' => 2,
      'lion' => 2,
      'union' => 3,

      // final "ius" as 2-syllables
      'genius' => 3,
      'celsius' => 3,
      'radius' => 3,

      // final "ium" as 2-syllables
      'aquarium' => 3,
      'calcium' => 3,
      'stadium' => 3,
      // Belgium

      // final "eum|oem" as 2-syllables
      'museum' => 2,
      'poem' => 2,

      // final "che" as 1-syllable
      'apache' => 3,
      'psyche' => 2,

      // final "ble" as 1-syllable
      'able' => 2,
      'adaptable' => 4,
      'incredible' => 4,
      'syllable' => 3, 
      'table' => 2,

      // final "cle" as 1-syllable
      'cycle' => 2,
      'bicycle' => 3,
      'vehicle' => 3,

      // final "dle" as 1-syllable
      'handle' => 2,
      'idle' => 2,
      'saddle' => 2,

      // final "fle" as 1-syllable
      'rifle' => 2,
      'shuffle' => 2,

      // final "gle" as 1-syllable
      'angle' => 2,
      'struggle' => 2,
      'triangle' => 3,

      // final "kle" as 1-syllable
      'ankle' => 2,
      'tackle' => 2,
      'buckle' => 2,

      // final "ple" as 1-syllable
      'apple' => 2,
      'example' => 3,
      'people' => 2,

      // final "sle" as 1-syllable
      'aisle' => 1,
      'isle' => 1,
      'hassle' => 2,

      // final "tle" as 1-syllable
      'little' => 2,
      'subtle' => 2,
      'title' => 2,

      // final "xle" as 1-syllable
      'axle' => 2,

      // final "yle" as 1-syllable
      'style' => 1,
      'styles' => 1,

      // final "zle" as 1-syllable
      'puzzle' => 2,
      'drizzle' => 2,

      // final "phe" as 1-syllable
      'apostrophe' => 4,
      'catastrophe' => 4,

      // female names
      'aphrodite' => 4,
      'ariadne' => 4,
      'chloe' => 2,
      'jesse' => 2,
      'daphne' => 2,
      'hermione' => 4,
      'penelope' => 4,
      'persephone' => 4,
      'phoebe' => 2,
      'zoe' => 2,

      // unusual words
      'abalone' => 4,
      'abare' => 3,
      'abed' => 2,
      'abruzzese' => 4,
      'abbruzzese' => 4,
      'acreage' => 3,
      'adame' => 3,
      'adieu' => 2,
      'calliope' => 4,
      'circe' => 2,
      'gethsemane' => 4,
      'syncope' => 3,
      'tamale' => 3,
      'eurydice' => 4,
      'euterpe' => 3,
    );
  }
}