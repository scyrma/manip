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
 * manip question renderer class.
 *
 * @package    qtype
 * @subpackage manip
 * @copyright  2012 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Generates the output for manip questions
 *
 * @copyright  2012 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_manip_renderer extends qtype_renderer {

    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {

        $question = $qa->get_question();
        
        $files = '';
        if (empty($options->readonly)) {
            $files = $this->files_input($qa, $question->attachment, $options);
        } else {
            $files = $this->files_read_only($qa, $options);
        }

        $result = '';
        $result .= html_writer::tag('div', $question->format_questiontext($qa),
                array('class' => 'qtext'));

        $result .= html_writer::start_tag('div', array('class' => 'ablock'));
        $result .= html_writer::tag('div', $files, array('class' => 'attachment'));
        $result .= html_writer::end_tag('div');

        return $result;
    }

    /**
     * Displays any attached files when the question is in read-only mode.
     * @param question_attempt $qa the question attempt to display.
     * @param question_display_options $options controls what should and should
     *      not be displayed. Used to get the context.
     */
    public function files_read_only(question_attempt $qa, question_display_options $options) {
        $files = $qa->get_last_qt_files('attachment', $options->context->id);
        $output = array();
        
        foreach ($files as $file) {
            $mimetype = $file->get_mimetype();
            $output[] = html_writer::tag('p', html_writer::link($qa->get_response_file_url($file),
                    $this->output->pix_icon(file_mimetype_icon($mimetype), $mimetype,
                    'moodle', array('class' => 'icon')) . ' ' . s($file->get_filename())));
        }
        return implode($output);
    }

    /**
     * Displays the input control for when the student should upload a single file.
     * @param question_attempt $qa the question attempt to display.
     * @param int $numallowed the maximum number of attachments allowed. -1 = unlimited.
     * @param question_display_options $options controls what should and should
     *      not be displayed. Used to get the context.
     */
    public function files_input(question_attempt $qa, $numallowed,
            question_display_options $options) {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . '/lib/form/filemanager.php');

        // TODO: voir si on peut limiter la taille limite du fichier.
        $pickeroptions = new stdClass();
        $pickeroptions->accepted_types = array('.docx');
        $pickeroptions->mainfile = null;
        $pickeroptions->maxfiles = 1;
        $pickeroptions->itemid = $qa->prepare_response_files_draft_itemid('attachment', $options->context->id);
        $pickeroptions->context = $options->context;

        // TODO: même ligne que deux lignes plus haut, mais c'est comme ça dans "essay". Pourquoi???
        //$pickeroptions->itemid = $qa->prepare_response_files_draft_itemid('attachment', $options->context->id);

        $PAGE->requires->js_init_call('M.qtype_manip.initUpload', array($qa->get_usage_id(), $qa->get_slot()), true, array(
            'name'     => 'qtype_manip',
            'fullpath' => '/question/type/manip/module.js',
            'requires' => array('base', 'dom', 'node', 'node-base', 'event', 'widget-base', 'selector-css3', 'event-valuechange'),
        ));
        
        return form_filemanager_render($pickeroptions) . html_writer::empty_tag(
                'input', array('type' => 'hidden', 'name' => $qa->get_qt_field_name('attachment'),
                'value' => $pickeroptions->itemid));
    }

}
