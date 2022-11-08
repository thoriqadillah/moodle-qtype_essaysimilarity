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
 * Defines the editing form for the essaycosine question type.
 *
 * @package    qtype
 * @subpackage essaycosine
 * @copyright  2022 Atthoriq Adillah Wicaksana (thoriqadillah59@gmail.com)
 * @copyright  based on work by 2018 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use function PHPUnit\Framework\returnSelf;

defined('MOODLE_INTERNAL') || die();

//get parent class
require_once($CFG->dirroot.'/question/type/essay/edit_essay_form.php');

/**
 * Essay question type editing form.
 *
 * @copyright  2022 Atthoriq Adillah Wicaksana (thoriqadillah59@gmail.com)
 * @copyright  based on work by 2018 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essaycosine_edit_form extends qtype_essay_edit_form {

  /** Number of rows in TEXTAREA elements */
  const TEXTAREA_ROWS = 5;

  public function definition_inner($mform) {
    global $PAGE;

    parent::definition_inner($mform);

    $plugin = $this->plugin_name();
    $dropdown_options = $this->dropdown_options();

    // add Javascript to expand/contract text input fields
    $PAGE->requires->js_call_amd("$plugin/form", 'init', []);

    /////////////////////////////////////////////////
    // add plugin main form elements               //
    /////////////////////////////////////////////////
    $name = 'autograding';
    $label = get_string($name, $plugin);
    $mform->addElement('header', $name, $label);
    $mform->setExpanded($name, true);

    $name = 'enableautograde';
    $label = get_string($name, $plugin);
    $mform->addElement('selectyesno', $name, $label);
    $mform->addHelpButton($name, $name, $plugin);
    $mform->setType($name, PARAM_INT);
    $mform->setDefault($name, $this->default($name, 1));

    $name = 'answerkey';
    $label = get_string($name, $plugin);
    $mform->addElement('editor', $name, $label, [], $this->editoroptions);
    $mform->addHelpButton($name, $name, $plugin);

    $name = 'showfeedback';
    $label = get_string($name, $plugin);
    $mform->addElement('select', $name, $label, $dropdown_options);
    $mform->addHelpButton($name, $name, $plugin);
    $mform->setType($name, PARAM_INT);
    $mform->setDefault($name, $this->default($name, $this->constant('SHOW_TEACHERS_AND_STUDENTS')));
    $mform->disabledIf($name, 'enableautograde', 'eq', 0);

    $name = 'showtextstats';
    $label = get_string($name, $plugin);
    $mform->addElement('select', $name, $label, $dropdown_options);
    $mform->addHelpButton($name, $name, $plugin);
    $mform->setType($name, PARAM_INT);
    $mform->setDefault($name, $this->default($name, $this->constant('SHOW_TEACHERS_ONLY')));
    $mform->disabledIf($name, 'enableautograde', 'eq', 0);

    $name = 'textstatitems';
    $label = get_string($name, $plugin);
    $elements = [];
    foreach($this->textstatitems_options() as $value => $text) {
      $elements[] = $mform->createElement('checkbox', $name."[$value]",  '', $text);
    }
    $mform->addGroup($elements, $name, $label, html_writer::empty_tag('br'), false);
    $mform->addHelpButton($name, $name, $plugin);
    $mform->disabledIf($name, 'enableautograde', 'eq', 0);
    $mform->disabledIf($name, 'showtextstats', 'eq', $this->constant('SHOW_NONE'));

    foreach($this->textstatitems_options() as $value => $text) {
      $mform->setType($name."[$value]", PARAM_INT);
    }

    // collapse certain form sections
    $sections = [
      'responseoptions',
      'responsetemplateheader',
      'graderinfoheader',
    ];
    foreach ($sections as $section) {
      if ($mform->elementExists($section)) {
        $mform->setExpanded($section, false);
      }
    }

    // reduce vertical height of textareas
    $names = [
      'questiontext',
      'generalfeedback',
      'responsetemplate',
      'answerkey',
      'graderinfo'
    ];
    foreach ($names as $name) {
      if ($mform->elementExists($name)) {
        $element = $mform->getElement($name);
        $attributes = $element->getAttributes();
        $attributes['rows'] = self::TEXTAREA_ROWS;
        $element->setAttributes($attributes);
      }
    }

    // insert plugin main form before response options
    $prevsection = 'responseoptions';
    $names = [
      'autograding',
      'enableautograde',
      'answerkey',
      'showfeedback',
      'showtextstats',
      'textstatitems',
    ];
    foreach($names as $name) {
      if ($mform->elementExists($name)) {
        $mform->insertElementBefore($mform->removeElement($name, false), $prevsection);
      }
    }
  }

  protected function data_processing($question) {
    $question = parent::data_preprocessing($question);

    if (empty($question)) return $question;

    /////////////////////////////////////////////////////////////////////////
    // initialize initial value for plugin form when making new question   //
    /////////////////////////////////////////////////////////////////////////

    // initialize for numeric value
    $question->enableautograde = $question->options->enableautograde;
    $question->showfeedback = $question->options->showfeedback;
    $question->showtextstats = $question->options->showtextstats;

    // initialize HTML text field
    if (!isset($question->options->answerkey) || !isset($question->options->answerkeyformat)) {
      $question->options->answerkey = '';
      $question->options->answerkeyformat = FORMAT_HTML;
    }

    $question->answerkey = [
      'text' => $question->options->answerkey,
      'format' => $question->options->answerkeyformat
    ];

    // initialize statistical item (a comma delimited list)
    if (! isset($question->options->textstatitems)) {
      $question->options->textstatitems = '';
    }

    $question->textstatitems = $question->options->textstatitems;
    $question->textstatitems = explode(',', $question->textstatitems);
    $question->textstatitems = array_flip($question->textstatitems);
    foreach ($this->textstatitems_options() as $value) {
      $question->textstatitems[$value] = array_key_exists($value, $question->textstatitems);
    }
  }

  /**
   * Get array of show/hide options
   *
   * @return array [type => description]
   */
  public function dropdown_options() {
    $plugin = $this->plugin_name();

    return [
      $this->constant('SHOW_NONE')                  => get_string('no'),
      $this->constant('SHOW_STUDENTS_ONLY')         => get_string('showtostudentsonly', $plugin),
      $this->constant('SHOW_TEACHERS_ONLY')         => get_string('showtoteachersonly', $plugin),
      $this->constant('SHOW_TEACHERS_AND_STUDENTS') => get_string('showtoteachersandstudents', $plugin)
    ];
  } 

  /**
   * Get array of countable statistical item
   *
   * @return array(type => description)
   */
  function textstatitems_options() {
    $plugin = $this->plugin_name();

    $options = [
      'chars'                  => get_string('chars' , $plugin),
      'words'                  => get_string('words' , $plugin),
      'sentences'              => get_string('sentences' , $plugin),
      'paragraphs'             => get_string('paragraphs' , $plugin),
      'uniquewords'            => get_string('uniquewords' , $plugin),
      'longwords'              => get_string('longwords' , $plugin),
      'charspersentence'       => get_string('charspersentence' , $plugin),
      'wordspersentence'       => get_string('wordspersentence' , $plugin),
      'longwordspersentence'   => get_string('longwordspersentence' , $plugin),
      'sentencesperparagraph'  => get_string('sentencesperparagraph' , $plugin),
      'lexicaldensity'         => get_string('lexicaldensity' , $plugin),
      'fogindex'               => get_string('fogindex' , $plugin),
    ];
    
    return $options;
  }

  /**
   * Returns default value for item
   *
   * @param string $name Item name
   * @param string|mixed|null $value Default value (optional, default = null)
   * @return string|mixed|null Default value for field with this $name
   */
  public function default($name, $value) {
    if (method_exists($this, 'get_default_value')) {
      return $this->get_default_value($name, $value); // Moodle >= 3.10
    } else {
      return get_user_preferences($this->plugin_name().'_'.$name, $value); // Moodle <= 3.9
    }
  }

  /**
   * Fetch a constant from the plugin class in "questiontype.php".
   * @param string $name name of the constant
   * @return int value of the constant
   */
  public function constant($name) {
    return constant('qtype_essaycosine::'.$name);
  }

  public function plugin_name() {
    return 'qtype_essaycosine';
  }

  public function qtype() {
    return 'essaycosine';
  }

}