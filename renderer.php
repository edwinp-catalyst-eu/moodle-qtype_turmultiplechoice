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
 * TUR Multiple choice question renderer classes.
 *
 * @package    qtype
 * @subpackage turmultiplechoice
 */

defined('MOODLE_INTERNAL') || die();

abstract class qtype_turmultiplechoice_renderer_base extends qtype_with_combined_feedback_renderer {

    protected abstract function get_input_type();

    protected abstract function get_input_name(question_attempt $qa, $value);

    protected abstract function get_input_value($value);

    protected abstract function get_input_id(question_attempt $qa, $value);

    protected function get_answersound(question_answer $ans, $contextid, $slot, $usageid) {

        $fs = get_file_storage();
        $files = $fs->get_area_files(1, 'question', 'answersound', $ans->id);
        if ($file = end($files)) {
            $filename = $file->get_filename();
            if ($filename != '.') {
                return moodle_url::make_file_url('/pluginfile.php',
                        "/1/question/answersound/$usageid/$slot/$ans->id/$filename");
            }
        }
    }

    protected function get_answerfeedbacksound(question_answer $ans, $contextid, $slot, $usageid) {

        $fs = get_file_storage();
        $files = $fs->get_area_files(1, 'question', 'feedbacksound', $ans->id);
        if ($file = end($files)) {
            $filename = $file->get_filename();
            if ($filename != '.') {
                return moodle_url::make_file_url('/pluginfile.php',
                        "/1/question/feedbacksound/$usageid/$slot/$ans->id/$filename");
            }
        }
    }

    protected function get_questionimage($questionid, $contextid, $slot, $usageid) {

        $fs = get_file_storage();
        $files = $fs->get_area_files(1, 'question', 'questionimage', $questionid);
        if ($file = end($files)) {
            $filename = $file->get_filename();
            if ($filename != '.') {
                return moodle_url::make_file_url('/pluginfile.php',
                        "/1/question/questionimage/$usageid/$slot/$questionid/$filename");
            }
        }
    }

    protected function get_questionsound($questionid, $contextid, $slot, $usageid) {

        $fs = get_file_storage();
        $files = $fs->get_area_files(1, 'question', 'questionsound', $questionid);
        if ($file = end($files)) {
            $filename = $file->get_filename();
            if ($filename != '.') {
                return moodle_url::make_file_url('/pluginfile.php',
                        "/1/question/questionsound/$usageid/$slot/$questionid/$filename");
            }
        }
    }

    protected function get_questions_total($cmid) {
        global $DB;

        $sql = "SELECT COUNT(qs.slot)
                  FROM {course_modules} cm
                  JOIN {quiz_slots} qs ON qs.quizid = cm.instance
                 WHERE cm.id = ?";
        $params = array($cmid);

        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Whether a choice should be considered right, wrong or partially right.
     * @param question_answer $ans representing one of the choices.
     * @return fload 1.0, 0.0 or something in between, respectively.
     */
    protected abstract function is_right(question_answer $ans);

    protected abstract function prompt();

    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {
        global $CFG, $OUTPUT, $DB;

        $question = $qa->get_question();
        $questiontext = $question->format_questiontext($qa);
        $response = $question->get_response($qa);

        $inputname = $qa->get_qt_field_name('answer');
        $inputattributes = array(
            'type' => $this->get_input_type(),
            'name' => $inputname,
        );

        if ($options->readonly) {
            $inputattributes['disabled'] = 'disabled';
        }
		
		

        $radiobuttons = array();
        $feedbackimg = array();
        $feedback = array();
        $classes = array();
		
        foreach ($question->get_order($qa) as $value => $ansid) {
            $ans = $question->answers[$ansid];
			
            $inputattributes['name'] = $this->get_input_name($qa, $value);
            $inputattributes['value'] = $this->get_input_value($value);
            $inputattributes['id'] = $this->get_input_id($qa, $value);
            $isselected = $question->is_choice_selected($response, $value);
            if ($isselected) {
                $inputattributes['checked'] = 'checked';
            } else {
                unset($inputattributes['checked']);
            }

            $answersound = ($answersoundurl = $this->get_answersound($ans,
                    $question->contextid, $qa->get_slot(), $qa->get_usage_id())) ?
                        html_writer::div('', 'audioplay audiomargin', array('data-src' => $answersoundurl)) : '';

            $hidden = '';
            if (!$options->readonly && $this->get_input_type() == 'checkbox') {
                $hidden = html_writer::empty_tag('input', array(
                    'type' => 'hidden',
                    'name' => $inputattributes['name'],
                    'value' => 0,
                ));
            }
			$ordinalspan = html_writer::span($value + 1 . '. ', 'questionNumber');
            $radiobuttons[] = $answersound . $hidden . $ordinalspan .
                    html_writer::tag('label',
                        $question->make_html_inline(
                                        $question->format_text(
                                            $ans->answer,
                                            $ans->answerformat,
                                            $qa,
                                            'question',
                                            'answer',
                                            $ansid
                                        )
                                    ),
                    array('for' => $inputattributes['id'])) . html_writer::empty_tag('input', $inputattributes);

            // Param $options->suppresschoicefeedback is a hack specific to the
            // oumultiresponse question type. It would be good to refactor to
            // avoid refering to it here.
            if ($options->feedback && empty($options->suppresschoicefeedback) &&
                    $isselected && trim($ans->feedback)) {
                $feedbackaudiodiv = html_writer::div('', 'tm_feedbackaudio audioplay',
                    array('data-src' => $this->get_answerfeedbacksound($ans,
                            $question->contextid, $qa->get_slot(), $qa->get_usage_id())));
                $feedbacktextdiv = html_writer::div(
                        $question->make_html_inline(
                            $question->format_text(
                                $ans->feedback,
                                $ans->feedbackformat,
                                $qa, 'question',
                                'answerfeedback',
                                $ansid
                            )
                        ), 'tm_feedbacktext');
                $specificfeedbackdiv = html_writer::div(
                        $feedbackaudiodiv . $feedbacktextdiv,
                        'specificfeedback'
                    );
                $feedback[] = $specificfeedbackdiv;
            } else {
                $feedback[] = '';
            }
            $class = 'r' . ($value % 2);
            if ($options->correctness && $isselected) {
                $feedbackimg[] = $this->feedback_image($this->is_right($ans));
                $class .= ' ' . $this->feedback_class($this->is_right($ans));
            } else {
                $feedbackimg[] = '';
            }
            $classes[] = $class;
        }

        $result = '';
		$result .= html_writer::start_div('', array('id' => 'turmc_leftcolumn'));
		$questioninfo = new stdClass();
        $questioninfo->questionnumber = $qa->get_slot();
        $questioninfo->questionstotal = $this->get_questions_total($options->context->instanceid);
		$result .= html_writer::div(get_string('questionxofy', 'qtype_turprove', $questioninfo), 'turprove_leftcolumn_quiz_info');

        $questionsoundurl = $this->get_questionsound($question->id, $question->contextid, $qa->get_slot(), $qa->get_usage_id());
        $audiosource = html_writer::tag('source', '', array('type' => 'audio/mpeg', 'src' => $questionsoundurl));
        $audiosource .= 'Your browser does not support the audio tag.'; // TODO: Lang string
        $audioelement = html_writer::tag('audio', $audiosource, array('id' => 'audiodiv'));
        $result .= $audioelement;

        $result .= html_writer::div('', 'audioplay', array('data-src' => $questionsoundurl));
        $result .= html_writer::tag('div', $question->format_questiontext($qa), array('class' => 'qtext'));

        $result .= html_writer::start_tag('div', array('class' => 'ablock'));
        $result .= html_writer::tag('div', $this->prompt(), array('class' => 'prompt goRight'));

        $result .= html_writer::start_tag('div', array('class' => 'answer'));
        foreach ($radiobuttons as $key => $radio) {
            $result .= html_writer::tag('div', $radio . ' ' . $feedbackimg[$key] . $feedback[$key], array('class' => $classes[$key] . ' turmc_answer_wrapper')) . "\n";
        }
        $result .= html_writer::end_tag('div'); // Answer.
	    $result .= html_writer::end_div(); // #turmc_leftcolumn
		$result .= html_writer::end_div();
		 
		 
		$result .= html_writer::start_div('', array('id' => 'turmc_rightcolumn'));

        $turmultiplechoicequestionimagesrc = $this->get_questionimage($question->id,
                $question->contextid, $qa->get_slot(), $qa->get_usage_id());
        $turmultiplechoicequestionimage = html_writer::empty_tag('img',
                array('src' => $turmultiplechoicequestionimagesrc, 'class' => 'questionimage', 'style' => 'width:100%'));
        $turmultiplechoiceimagelink = html_writer::link($turmultiplechoicequestionimagesrc,
                $turmultiplechoicequestionimage, array(
                    'data-lightbox' => 'imagelink', 'data-title' => $questiontext));
        $turmultiplechoicequestionimagediv = html_writer::div(
                $turmultiplechoiceimagelink, 'qtype_turmultiplechoice_imagediv');
        $lightboxicon = html_writer::img(
                $CFG->wwwroot . '/question/type/turmultiplechoice/pix/lightboxicon.jpg',
                $questiontext, array('class' => 'tur_lightboxicon'));
        $lightboxlink = html_writer::link($turmultiplechoicequestionimagesrc, $lightboxicon,
                array('data-lightbox' => 'iconlink', 'data-title' => $questiontext));
        $lighboxdiv = html_writer::div($lightboxlink, 'qtype_turmultiplechoice_lightboxdiv');
        $result .= html_writer::div($turmultiplechoicequestionimagediv . $lighboxdiv, 'questionimagediv');
        $result .= html_writer::end_tag('div'); // Ablock.


        $attemptid = $DB->get_field('quiz_attempts', 'id', array('uniqueid' => $qa->get_usage_id()));
        $pageid = (int) $qa->get_slot() - 1;


        // Menu button
        if ($options->readonly) {
            $modulecontext = $options->context;
            $coursecontext = $modulecontext->get_course_context();
            $menuurl = $coursecontext->get_url();
        } else {
            $menuurl = new moodle_url($CFG->wwwroot . '/mod/quiz/summary.php', array('attempt' => $attemptid));
        }
        $link = html_writer::link($menuurl, get_string('menu', 'qtype_turmultiplechoice'),
                array('id' => 'tf_menubutton', 'class' => 'tf_button submit'));
        $result .= html_writer::div($link, 'singlebutton turforlag');

        $result .= html_writer::start_div('tf_prevnextquestion');

        // Previous button
        if ($pageid) {
            if ($options->readonly) {
                $previousurl = new moodle_url($CFG->wwwroot . '/mod/quiz/review.php',
                        array('attempt' => $attemptid, 'page' => $pageid - 1));
                $link = html_writer::link($previousurl, get_string('back', 'qtype_turmultiplechoice'),
                        array('id' => 'tf_previousbutton', 'class' => 'tf_button submit'));
                $result .= html_writer::div($link, 'singlebutton');
            } else {
                $previousurl = new moodle_url($CFG->wwwroot . '/mod/quiz/attempt.php',
                        array('attempt' => $attemptid, 'page' => $pageid - 1));
                $link = html_writer::link($previousurl, get_string('back', 'qtype_turmultiplechoice'),
                        array('id' => 'tf_previousbutton',  'class' => 'tf_button submit'));
                $result .= html_writer::div($link, 'singlebutton');
            }
        }

        // Next button
        if ($options->readonly) {
            if ($pageid + 1 != $this->get_questions_total($options->context->instanceid)) {
                $nexturl = new moodle_url($CFG->wwwroot . '/mod/quiz/review.php',
                        array('attempt' => $attemptid, 'page' => $pageid + 1));
                $link = html_writer::link($nexturl, get_string('forward', 'qtype_turmultiplechoice'),
                        array('id' => 'tf_nextbutton', 'class' => 'tf_button submit'));
                $result .= html_writer::div($link, 'singlebutton');
            }
        } else {
            $result .=  html_writer::empty_tag('input',
                    array('type' => 'submit', 'value' => get_string('forward', 'qtype_turmultiplechoice'), 'name' => 'next'));
        }
		$result .= html_writer::end_div(); // #turmc_rightcolumn
        $result .= html_writer::end_div(); // tf_prevnextquestion

        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                    $question->get_validation_error($qa->get_last_qt_data()),
                    array('class' => 'validationerror'));
        }

        $this->page->requires->js_init_call(
                    'M.qtype_turmultiplechoice.init',
                    array(
                        '#q' . $qa->get_slot(),
                        $options->readonly,
                        ($options->feedback == 1) ? false : $question->autoplay
                    ),
                    false,
                    array(
                        'name'     => 'qtype_turmultiplechoice',
                        'fullpath' => '/question/type/turmultiplechoice/module.js',
                        'requires' => array('base', 'node', 'event', 'overlay'),
                    )
                );

        return $result;
    }

    public function specific_feedback(question_attempt $qa) {
        return $this->combined_feedback($qa);
    }
}

/**
 * Subclass for generating the bits of output specific to TUR multiple choice
 * single questions.
 *
 */
class qtype_turmultiplechoice_single_renderer extends qtype_turmultiplechoice_renderer_base {

    public function head_code(question_attempt $qa) {
        global $CFG;

        $js = new moodle_url($CFG->wwwroot . '/question/type/turmultiplechoice/lightbox/lightbox-plus-jquery.min.js');
        $this->page->requires->js($js);
        $stylesheet = new moodle_url($CFG->wwwroot . '/question/type/turmultiplechoice/lightbox/lightbox.css');
        $this->page->requires->css($stylesheet);
    }

    protected function get_input_type() {
        return 'radio';
    }

    protected function get_input_name(question_attempt $qa, $value) {
        return $qa->get_qt_field_name('answer');
    }

    protected function get_input_value($value) {
        return $value;
    }

    protected function get_input_id(question_attempt $qa, $value) {
        return $qa->get_qt_field_name('answer' . $value);
    }

    protected function is_right(question_answer $ans) {
        return $ans->fraction;
    }

    protected function prompt() {
        return get_string('selectone', 'qtype_turmultiplechoice');
    }

    public function correct_response(question_attempt $qa) {
        $question = $qa->get_question();

        foreach ($question->answers as $ansid => $ans) {
            if (question_state::graded_state_for_fraction($ans->fraction) ==
                    question_state::$gradedright) {
                return get_string('correctansweris', 'qtype_turmultiplechoice',
                        $question->make_html_inline($question->format_text($ans->answer, $ans->answerformat,
                                $qa, 'question', 'answer', $ansid)));
            }
        }

        return '';
    }
}

/**
 * Subclass for generating the bits of output specific to TUR multiple choice
 * multi=select questions.
 *
 */
class qtype_turmultiplechoice_multi_renderer extends qtype_turmultiplechoice_renderer_base {

    public function head_code(question_attempt $qa) {
        global $CFG;

        $js = new moodle_url($CFG->wwwroot . '/question/type/turmultiplechoice/lightbox/lightbox-plus-jquery.min.js');
        $this->page->requires->js($js);
        $stylesheet = new moodle_url($CFG->wwwroot . '/question/type/turmultiplechoice/lightbox/lightbox.css');
        $this->page->requires->css($stylesheet);
    }

    protected function get_input_type() {
        return 'checkbox';
    }

    protected function get_input_name(question_attempt $qa, $value) {
        return $qa->get_qt_field_name('choice' . $value);
    }

    protected function get_input_value($value) {
        return 1;
    }

    protected function get_input_id(question_attempt $qa, $value) {
        return $this->get_input_name($qa, $value);
    }

    protected function is_right(question_answer $ans) {
        if ($ans->fraction > 0) {
            return 1;
        } else {
            return 0;
        }
    }

    protected function prompt() {
        return get_string('selectmultiple', 'qtype_turmultiplechoice');
    }

    public function correct_response(question_attempt $qa) {
        $question = $qa->get_question();

        $right = array();
        foreach ($question->answers as $ansid => $ans) {
            if ($ans->fraction > 0) {
                $right[] = $question->make_html_inline($question->format_text($ans->answer, $ans->answerformat,
                        $qa, 'question', 'answer', $ansid));
            }
        }

        if (!empty($right)) {
                return get_string('correctansweris', 'qtype_turmultiplechoice',
                        implode(', ', $right));
        }
        return '';
    }

    protected function num_parts_correct(question_attempt $qa) {
        if ($qa->get_question()->get_num_selected_choices($qa->get_last_qt_data()) >
                $qa->get_question()->get_num_correct_choices()) {
            return get_string('toomanyselected', 'qtype_turmultiplechoice');
        }

        return parent::num_parts_correct($qa);
    }
}