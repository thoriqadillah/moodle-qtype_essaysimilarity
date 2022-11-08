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
 * Question type class for the essay question type.
 *
 * @package    qtype
 * @subpackage essaycosine
 * @copyright  2022 Atthoriq Adillah Wicaksana (thoriqadillah59@gmail.com)
 * @copyright  based on work by 2022 Atthoriq Adillah Wicaksana (thoriqadillah59@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use PhpOffice\PhpSpreadsheet\Helper\Html;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/questionlib.php');
/**
 * The essaycosine question type renderer.
 *
 * @copyright  2022 Atthoriq Adillah Wicaksana 
 * @copyright  based on work by 2022 Atthoriq Adillah Wicaksana (thoriqadillah59@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essaycosine_renderer extends qtype_renderer {

  /** @var question_display_options */
  public $diplayoptions = null;
  
  /**
   * Generate the display of the formulation part of the question. This is the
   * area that contains the quetsion text, and the controls for students to
   * input their answers. Some question types also embed bits of feedback, for
   * example ticks and crosses, in this area.
   *
   * @param question_attempt $qa the question attempt to display.
   * @param question_display_options $options controls what should and should not be displayed.
   * @return string HTML fragment.
   */
  public function formulation_and_controls(question_attempt $qa, question_display_options $options) {
    global $PAGE;

    $question = $qa->get_question();
    $response = $qa->get_last_qt_data(); 
    //TODO: process the necessarry response details
    // $response = $question->process_response($response);

    // format question text
    $qtext = $question->format_questiontext($qa);

    // answer textarea field.
    $step = $qa->get_last_step_with_qt_var('answer');
    
    if (!$step->has_qt_var('answer') && empty($options->readonly)) {
      $step = new question_attempt_step(['answer' => $question->responsetemplate]);
    }

    $renderer = $question->get_format_renderer($this->page);
    $linecount = $question->responsefieldlines;

    $answer = $renderer->response_area_input('answer', $qa, $step, $linecount, $options->context);
    if ($options->readonly) {
      $answer = $renderer->response_area_read_only('answer', $qa, $step, $linecount, $options->context);
    }

    $files = '';
    if ($question->attachments) {
      $files = $options->readonly ? $this->files_read_only($qa, $options) : $this->files_input($qa, $options);
    }

    // render answer textarea
    $result = '';
    $result .= html_writer::tag('div', $qtext, ['class' => 'qtext']);
    $result .= html_writer::start_tag('div', ['class' => 'ablock']);

    $result .= html_writer::tag('div', $answer, ['class' => 'answer']);
    $result .= html_writer::tag('div', $files, array('class' => 'attachments'));

    $result .= html_writer::end_tag('div'); // div.ablock

    return $result;
  }


  /**
   * Displays any attached files when the question is in read-only mode.
   * @param question_attempt $qa the question attempt to display.
   * @param question_display_options $options controls what should and should
   *      not be displayed. Used to get the context.
   */
  public function files_read_only(question_attempt $qa, question_display_options $options) {
    //TODO
  }

  /**
   * Displays the input control for when the student should upload a single file.
   * @param question_attempt $qa the question attempt to display.
   * @param question_display_options $options controls what should and should
   *      not be displayed. Used to get the context.
   */
  public function files_input(question_attempt $qa, question_display_options $options) {
    //TODO
  }

}

require_once($CFG->dirroot.'/question/type/essay/renderer.php');
/**
 * An essaycosine format renderer for essaycosines where the student should not enter
 * any inline response.
 *
 * @copyright  2022 Atthoriq Adillah Wicaksana (thoriqadillah59@gmail.com)
 * @copyright  based on work by 2013 Binghamton University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essaycosine_format_noinline_renderer extends qtype_essay_format_noinline_renderer {
  protected function class_name() {
    return 'qtype_essaycosine_noinline';
  }
}

/**
* An essaycosine format renderer for essaycosines where the student should use the HTML
* editor without the file picker.
*
* @copyright  2022 Atthoriq Adillah Wicaksana (thoriqadillah59@gmail.com)
* @copyright  based on work by 2011 The Open University
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class qtype_essaycosine_format_editor_renderer extends qtype_essay_format_editor_renderer {
  protected function class_name() {
      return 'qtype_essaycosine_editor';
  }
}

/**
* An essaycosine format renderer for essaycosines where the student should use the HTML
* editor with the file picker.
*
* @copyright  2022 Atthoriq Adillah Wicaksana (thoriqadillah59@gmail.com)
* @copyright  based on work by 2011 The Open University
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class qtype_essaycosine_format_editorfilepicker_renderer extends qtype_essay_format_editorfilepicker_renderer {
  protected function class_name() {
      return 'qtype_essaycosine_editorfilepicker';
  }
}

/**
* An essaycosine format renderer for essaycosines where the student should use a plain
* input box, but with a normal, proportional font.
*
* @copyright  2022 Atthoriq Adillah Wicaksana (thoriqadillah59@gmail.com)
* @copyright  based on work by 2011 The Open University
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class qtype_essaycosine_format_plain_renderer extends qtype_essay_format_plain_renderer {
  protected function class_name() {
      return 'qtype_essaycosine_plain';
  }
}

/**
* An essaycosine format renderer for essaycosines where the student should use a plain
* input box with a monospaced font. You might use this, for example, for a
* question where the students should type computer code.
*
* @copyright  2022 Atthoriq Adillah Wicaksana (thoriqadillah59@gmail.com)
* @copyright  based on work by 2011 The Open University
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class qtype_essaycosine_format_monospaced_renderer extends qtype_essay_format_plain_renderer {
  protected function class_name() {
      return 'qtype_essaycosine_monospaced';
  }
}


