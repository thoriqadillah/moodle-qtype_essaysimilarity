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
 * @subpackage essaysimilarity
 * @copyright  2022 Atthoriq Adillah Wicaksana (thoriqadillah59@gmail.com)
 * @copyright  based on work by 2022 Atthoriq Adillah Wicaksana (thoriqadillah59@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use PhpOffice\PhpSpreadsheet\Helper\Html;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/questionlib.php');
/**
 * The essaysimilarity question type renderer.
 *
 * @copyright  2022 Atthoriq Adillah Wicaksana 
 * @copyright  based on work by 2022 Atthoriq Adillah Wicaksana (thoriqadillah59@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essaysimilarity_renderer extends qtype_renderer {

  /** @var question_display_options */
  private $displayoptions = null;

  private $response = [];
  
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

    $this->displayoptions = $options;

    $question = $qa->get_question();
    $response = $qa->get_last_qt_data(); 
    
    // to process additional information about the response of the student 
    $this->response = $question->process_response($response);

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

    $answer_attr = ['class' => 'answer'];
    if ($options->readonly) {
      $answer_attr['style'] = 'background-color: #fff; 
                              padding: 0.1em;
                              margin: 10px 0px;';
    }

    $result .= html_writer::tag('div', $answer, $answer_attr);
    $result .= html_writer::tag('div', $files, ['class' => 'attachments']);

    $result .= html_writer::end_tag('div'); // div.ablock

    return $result;
  }

  /**
   * Generate the specific feedback. This is feedback that varies according to
   * the response the student gave.
   * @param question_attempt $qa the question attempt to display.
   * @return string HTML fragment.
   */
  protected function specific_feedback(question_attempt $qa) {
    $output = '';

    // Decide if we should show grade explanation.
    $step = $qa->get_last_step();
    if (!$step) return $output;

    $plugin_name = $this->plugin_name();
    $question = $qa->get_question();

    $showteacher = empty($this->displayoptions->context) ? false : has_capability('mod/quiz:grade', $this->displayoptions->context);
    $showstudent = $showteacher ? false : has_capability('mod/quiz:attempt', $this->displayoptions->context);

    // dropdown options in edit_essaysimilarity_form.php
    $show = [
      $this->get_constant('SHOW_NONE') => false,
      $this->get_constant('SHOW_STUDENTS_ONLY') => $showstudent,
      $this->get_constant('SHOW_TEACHERS_ONLY') => $showteacher, 
      $this->get_constant('SHOW_TEACHERS_AND_STUDENTS') => $showstudent || $showteacher
    ];

    $showanswerkey = $show[$question->showanswerkey];
    if ($showanswerkey) {
      $output .= html_writer::tag('h5', get_string('answerkey', $plugin_name));

      $step = $qa->get_last_step_with_behaviour_var('finish');
      if ($step->get_id()) {
        $output .= html_writer::tag('p', $question->answerkey);
      } 
    }

    // show text statistic if user choose so and stats item is selected at least one
    $showtextstats = $show[$question->showtextstats] && strlen(trim($question->textstatitems));
    if ($showtextstats) {
      $strmanager = get_string_manager();
      
      $table = new html_table();
      $table->attributes['class'] = 'generaltable essaysimilarity review stats';

      $statsitem = explode(',', $question->textstatitems);
      foreach ($statsitem as $item) {
        $label = get_string($item, $plugin_name);

        if ($strmanager->string_exists($item . '_help', $plugin_name)) {
          $label .= $this->help_icon($item, $plugin_name);
        }

        $value = isset($this->response['stats']->$item) ? $this->response['stats']->$item : 0.0;
        $value = number_format((float) $value);
        $value .= $item == 'lexicaldensity' ? '%' : ''; // add percent symbol if the item is lexical density

        $head = new html_table_cell($label);
        $data = new html_table_cell($value);
        $table->data[] = new html_table_row([$head, $data]);
      }

      $output .= html_writer::tag('h5', get_string('textstatistics', $plugin_name));
      $output .= html_writer::table($table);
    }

    // display plagiarism links if any
    if (isset($this->response['plagiarism'])) {
      $output .= html_writer::tag('h5', get_string('plagiarismcheck', $plugin_name));
  
      $plagiarism = [];
      foreach ($this->response['plagiarism'] as $link) {
        $plagiarism[] = html_writer::tag('a', $link, ['href' => $link]);
      }

      $plagiarism = implode(html_writer::empty_tag('br', $plagiarism));
      $output .= $plagiarism;
    }

    // show feedback if user choose so
    $showfeedback = $show[$question->showfeedback];
    $float_precision = isset($this->displayoptions->markdp) ? $this->displayoptions->markdp : 0;
    if ($showfeedback) {
      $output .= html_writer::tag('h5', get_string('feedbacksection', $plugin_name));

      $maxgrade = $qa->get_max_mark();
      
      $step = $qa->get_last_step_with_behaviour_var('finish');
      if ($step->get_id()) {
        $grade = format_float($this->response['autograde'] * $maxgrade, $float_precision);
      } else {
        $grade = $qa->format_mark($float_precision);
      }
      
      $output .= html_writer::tag('p', get_string('feedback', $plugin_name, $grade));
    }

    // add details of most recent manual override if any
    $step = $qa->get_last_step_with_behaviour_var('mark');
    if ($step->get_id()) {
      $gradeovr = (object) [
        'datetime' => userdate($step->get_timecreated(), get_string('explanationdatetime', $this->plugin_name())),
        'manualgrade' => format_float($step->get_behaviour_var('mark'), $float_precision)
      ];

      $output .= html_writer::tag('h5', get_string('grading', $this->plugin_name()));
      $output .= html_writer::tag('p', get_string('datetime', $this->plugin_name(), $gradeovr));
      $output .= html_writer::tag('p', get_string('explanationoverride', $this->plugin_name(), $gradeovr));
    }

    return $output;
  }

  /**
   * Display any extra question-type specific content that should be visible
   * when grading, if appropriate.
   *
   * @param question_attempt $qa a question attempt.
   * @param question_display_options $options controls what should and should not be displayed.
   * @return string HTML fragment.
   */
  public function manual_comment(question_attempt $qa, question_display_options $options) {
    $comment = '';
    if ($options->manualcomment != question_display_options::EDITABLE) return $comment;
    
    $plugin = $this->plugin_name();
    $question = $qa->get_question();

    $comment = $question->graderinfo;
    $comment = $question->format_text($comment, $comment, $qa, $plugin, 'graderinfo', $question->id);
    $comment = html_writer::nonempty_tag('div', $comment, ['class' => 'graderinfo']);

    return $comment;
  }


  /**
   * Displays any attached files when the question is in read-only mode.
   * @param question_attempt $qa the question attempt to display.
   * @param question_display_options $options controls what should and should
   *      not be displayed. Used to get the context.
   */
  public function files_read_only(question_attempt $qa, question_display_options $options) {
    $output = [];

    $files = $qa->get_last_qt_files('attachments', $options->context->id);
    foreach ($files as $file) {
      $url = $qa->get_response_file_url($file);
      $url = preg_replace('/(\?|\&|\&amp;)forcedownload=1/', '', $url);

      $mimetype = $file->get_mimetype();
      $mimetext = get_mimetype_description($file);
      $filetype = substr($mimetype, 0, strpos($mimetype, '/'));
      switch ($filetype) {
        case 'image':
          $attr = [
            'src' => $url,
            'alt' => $mimetext,
            'class' => 'img-responsive'
          ];
          $file = html_writer::empty_tag('img', $attr);
          break;

        case 'audio':
          $file = html_writer::empty_tag('source', ['src' => $url]);
          $attr = ['controls' => 'true'];
          $file = html_writer::tag('audio', $file.$url, $attr);
          break;

        case 'video':
          $file = html_writer::empty_tag('source', ['src' => $url]);
          $attr = [
            'controls' => 'true',
            'playsinline' => 'true'
          ];
          $file = html_writer::tag('video', $file.$url, $attr);
          break;

        default:
          $icon = file_file_icon($file);
          $icon = $this->output->pix_icon($icon, $mimetext, 'moodle', ['class' => 'icon']);
          $file = html_writer::link($qa->get_response_file_url($file), $icon.' '.s($file->get_filename()));
          break;
      }

      $attr = [
        'class'=> "read-only-file $filetype",
        'style' => 'width: 100%; max-width: 480px;'
      ];
      $output[] = html_writer::tag('p', $file, $attr);
    }

    return implode($output);
  }

  /**
   * Displays the input control for when the student should upload a single file.
   * @param question_attempt $qa the question attempt to display.
   * @param question_display_options $options controls what should and should
   *      not be displayed. Used to get the context.
   */
  public function files_input(question_attempt $qa, question_display_options $options) {
    global $CFG, $PAGE;
    require_once($CFG->dirroot.'/lib/form/filemanager.php');

    $question = $qa->get_question();
    $name = 'attachments';
    $itemid = $qa->prepare_response_files_draft_itemid($name, $options->context->id);
    $pickeroptions = (object) [
      'mainfile' => null,
      'maxfiles' => $question->attachments,
      'itemid'   => $itemid,
      'context'  => $options->context,
      'return_types' => FILE_INTERNAL
    ];

    if ($filetypes = $question->filetypeslist) {
      $pickeroptions->accepted_types = $filetypes;
    }

    $filemanager = new form_filemanager($pickeroptions);
    $filemanager->options->maxbytes = $qa->get_question()->maxbytes;
    $filesrenderer = $this->page->get_renderer('core', 'files');
    $attr = [
      'type'  => 'hidden',
      'value' => $itemid,
      'name'  => $qa->get_qt_field_name($name)
    ];
    $output = $filesrenderer->render($filemanager).html_writer::empty_tag('input', $attr);

    // Append warning about required number of attachments.
    $restrictions = [];
    if ($question->attachments) {
      if ($question->attachments == $question->attachmentsrequired) {
        $restrictions[] = get_string('requiredfilecount', $this->plugin_name(), $question->attachments);
      } else if ($question->attachmentsrequired > 0) {
        $restrictions[] = get_string('minimumfilecount', $this->plugin_name(), $question->attachmentsrequired);
      } else if ($question->attachments > 0) {
        $restrictions[] = get_string('maximumfilecount', $this->plugin_name(), $question->attachments);
      }
    }

    list($context, $course, $cm) = get_context_info_array($options->context->id);

    $maxbytes = $context ? $course->maxbytes : $PAGE->course->maxbytes;
    $maxbytes = get_user_max_upload_file_size($context, $CFG->maxbytes, $maxbytes);
    $maxbytes = number_format($maxbytes / 1048576) . ' MB'; // convert KB to MB
    if ($maxbytes != USER_CAN_IGNORE_FILE_SIZE_LIMITS) {
      $restrictions[] = get_string('maximumfilesize', $this->plugin_name(), $maxbytes);
    }

    // Append details of accepted file types.
    if ($filetypes && class_exists('\\core_form\\filetypes_util')) {
      // Moodle >= 3.4
      $util = new core_form\filetypes_util();
      $filetypes = $util->describe_file_types($filetypes);
      $filetypes = $this->render_from_template('core_form/filetypes-descriptions', $filetypes);
      $filetypes = get_string('acceptedfiletypes', 'qtype_essay').get_string('labelsep', 'langconfig').$filetypes;
      $restrictions[] = $filetypes;
    } else {
      // Moodle <= 3.3
      $filetypes = strtolower($filetypes);
      $filetypes = preg_split('/[\s,;:"\']+/', $filetypes, 0, PREG_SPLIT_NO_EMPTY);
      foreach($filetypes as $i => $filetype) {
        $filetype = str_replace('*.', '', $filetype);
        $filetypes[$i] = trim(ltrim($filetype, '.'));
      }
      $filetypes = implode(', ', $filetypes);
      $restrictions[] = get_string('acceptedfiletypes', 'qtype_essay').get_string('labelsep', 'langconfig').$filetypes;
    }

    if (count($restrictions)) {
      $output .= html_writer::alist($restrictions);
    }

    return $output;
  }

  /**
   * Fetch a constant attribute of qtype_essaysimilarity class inside "questiontype.php" file.
   */
  private function get_constant($name) {
    return constant("qtype_essaysimilarity::$name");
  }

  private function plugin_name() {
    return 'qtype_essaysimilarity';
  }

}

require_once($CFG->dirroot.'/question/type/essay/renderer.php');
/**
 * An essaysimilarity format renderer for essaysimilaritys where the student should not enter
 * any inline response.
 *
 * @copyright  2022 Atthoriq Adillah Wicaksana (thoriqadillah59@gmail.com)
 * @copyright  based on work by 2013 Binghamton University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essaysimilarity_format_noinline_renderer extends qtype_essay_format_noinline_renderer {
  protected function class_name() {
    return 'qtype_essaysimilarity_noinline';
  }
}

/**
* An essaysimilarity format renderer for essaysimilaritys where the student should use the HTML
* editor without the file picker.
*
* @copyright  2022 Atthoriq Adillah Wicaksana (thoriqadillah59@gmail.com)
* @copyright  based on work by 2011 The Open University
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class qtype_essaysimilarity_format_editor_renderer extends qtype_essay_format_editor_renderer {
  protected function class_name() {
      return 'qtype_essaysimilarity_editor';
  }
}

/**
* An essaysimilarity format renderer for essaysimilaritys where the student should use the HTML
* editor with the file picker.
*
* @copyright  2022 Atthoriq Adillah Wicaksana (thoriqadillah59@gmail.com)
* @copyright  based on work by 2011 The Open University
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class qtype_essaysimilarity_format_editorfilepicker_renderer extends qtype_essay_format_editorfilepicker_renderer {
  protected function class_name() {
      return 'qtype_essaysimilarity_editorfilepicker';
  }
}

/**
* An essaysimilarity format renderer for essaysimilaritys where the student should use a plain
* input box, but with a normal, proportional font.
*
* @copyright  2022 Atthoriq Adillah Wicaksana (thoriqadillah59@gmail.com)
* @copyright  based on work by 2011 The Open University
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class qtype_essaysimilarity_format_plain_renderer extends qtype_essay_format_plain_renderer {
  protected function class_name() {
      return 'qtype_essaysimilarity_plain';
  }
}

/**
* An essaysimilarity format renderer for essaysimilaritys where the student should use a plain
* input box with a monospaced font. You might use this, for example, for a
* question where the students should type computer code.
*
* @copyright  2022 Atthoriq Adillah Wicaksana (thoriqadillah59@gmail.com)
* @copyright  based on work by 2011 The Open University
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class qtype_essaysimilarity_format_monospaced_renderer extends qtype_essay_format_plain_renderer {
  protected function class_name() {
      return 'qtype_essaysimilarity_monospaced';
  }
}


