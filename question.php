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
require_once('nlp/cosine_similarity.php');
require_once('nlp/preprocessing/tokenizer.php');
require_once('nlp/preprocessing/tfidf_transformer.php');

class qtype_essaysimilarity_question extends qtype_essay_question implements question_automatically_gradable {

  public function make_behaviour(question_attempt $qa, $preferredbehaviour) {
    return question_engine::make_archetypal_behaviour($preferredbehaviour, $qa);
  }
  
  /**
   * In situations where is_gradable_response() returns false, this method
   * should generate a description of what the problem is.
   * @param array $response
   * @return string the message.
   */
  public function get_validation_error($response) {
    // check if we have a text answer
    if (empty($response['answer']) && empty($response['attachments'])) { 
      return get_string('noresponse', 'quiz');
    }

    // ensure we have text response, if required
    $is_required = $this->responseformat == 'noinline' ? 0 : $this->responserequired; // Ensure we have text response, if it's required
    if ($is_required && empty($response['answer'])) {
      return get_string('pleaseinputtext', $this->plugin_name());
    }

    // check that the answer is not simply the unaltered response template/sample.
    if ($this->is_same_response($response, $this->responsetemplate)) {
      return get_string('responseisnotoriginal', $this->plugin_name());
    }

    // ensure we have attachments, if required
    $attachment_required = $this->attachments ? $this->attachmentsrequired : 0;
    if ($attachment_required && empty($response['attachments'])) {
      return get_string('pleaseattachfiles', $this->plugin_name());
    }

    // no validation error
    return '';
  }

  /**
   * Process text stats of the response and then save/update them in database
   * @param string $responsetext the response in plain text
   */
  public function get_and_save_textstats($responsetext, $nosave = false) {
    global $USER, $DB;

    // get all text stats and then save to DB according what user choose in form editing
    $textstats_table = 'question_answer_stats';
    $oldtextstats = $DB->get_record($textstats_table, ['questionid' => $this->id, 'userid' => $USER->id]);
    
    $stats = $this->get_stats($responsetext); 
    $textstatitems = explode(',', $this->textstatitems);
    $textstats = (object) [
      'questionid' => $this->id,
      'userid' => $USER->id
    ];
    foreach ($textstatitems as $item) {
      $textstats->$item = $stats->$item;
    }

    if ($nosave) return $textstats;

    if ($oldtextstats) {
      $textstats->id = $oldtextstats->id;
      $DB->update_record($textstats_table, $textstats);
    } else {
      $DB->insert_record($textstats_table, $textstats);
    }

    return $textstats;
  }

  public function get_textstats($responsetext) {
    return $this->get_and_save_textstats($responsetext, true);
  }

  private function preprocess($answerkeytext, $responsetext, $lang) {
    $tokenizer = new tokenizer($lang);
    
    list($counted_answerkey, $raw_answerkey) = $tokenizer->tokenize($answerkeytext);
    list($counted_response, $raw_response) = $tokenizer->tokenize($responsetext);

    $merged = array_merge($raw_answerkey, $raw_response);
    $tok_answerkey = array_replace($merged, $counted_answerkey);
    $tok_response = array_replace($merged, $counted_response);

    $sample = [$tok_answerkey, $tok_response];
    $transformer = new tfidf_transformer($sample);
    $transformer->transform($sample);

    return $sample;
  }
  
  /**
   * Grade a response to the question, returning a fraction between
   * get_min_fraction() and get_max_fraction(), and the corresponding {@link question_state}
   * right, partial or wrong.
   * @param array $response responses, as returned by
   *      {@link question_attempt_step::get_qt_data()}.
   * @return array (float, integer) the fraction, and the state.
   */
  public function grade_response($response) {
    $responsetext = $this->to_plaintext($response['answer'], $response['format']);
    $responsetext = core_text::strtolower($responsetext);

    $this->get_and_save_textstats($responsetext);
    
    $answerkeytext = $this->to_plaintext($this->answerkey, $this->answerkeyformat);
    $answerkeytext = core_text::strtolower($answerkeytext);

    list($tok_answerkey, $tok_response) = $this->preprocess($answerkeytext, $responsetext, $this->questionlanguage);

    $cossim = new cosine_similarity($tok_answerkey, $tok_response);
    $similarity = $cossim->get_similarity();

    $state = null;
    
    if ($similarity > $this->upper_correctness) {
      $state = question_state::$gradedright;
    } else if ($similarity < $this->lower_correctness) {
      $state = question_state::$gradedwrong;
    } else {
      $state = question_state::$gradedpartial;
    }

    return [$similarity, $state];
  }

  public function get_plagiarism($response) {
    global $CFG, $PAGE;
    require_once($CFG->dirroot.'/lib/plagiarismlib.php');

    $plagiarism = [];
    $plagiarismparams = [];

    if (!$CFG->enableplagiarism) return $plagiarism;

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

    return $plagiarism;
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
   * Parse text from certain format to string
   * @param string $text Text to be parsed
   * @param int $format Format of the text
   * @return string
   */
  public function to_plaintext($text, $format) {
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

  private function plugin_name() {
    return 'qtype_essaysimilarity';
  }

  /**
   * Get statistical count of the response
   * @param string $responsetext 
   */
  private function get_stats($responsetext) {
    $precision = 0;
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
      $stats->lexicaldensity = round(($stats->uniquewords / $stats->words) * 100, $precision);
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
    return count($items);
  }

  private function get_stats_paragraphs($responsetext) {
    $items = explode("\n", $responsetext);
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
    $vowels = ['a','e','i','o','u','y'];
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
    return [
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
    ];
  }
}