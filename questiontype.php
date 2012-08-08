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
 * Question type class for the true-false question type.
 *
 * @package    qtype
 * @subpackage manip
 * @copyright  2012 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * The manip question type class.
 *
 * @copyright  2012 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_manip extends question_type {

    public function extra_question_fields() {
        return array('question_manip', 'regex', 'correct', 'incorrect');
    }
    
    public function save_question_options($question) {
        global $DB;
        $result = new stdClass();
        $context = $question->context;
        
        //error_log(print_r($question, true));
        //file_put_contents('/tmp/form.txt', print_r($question, true));

        // Fetch old answer ids so that we can reuse them
        $oldanswers = $DB->get_records('question_answers',
                array('question' => $question->id), 'id ASC');

        // Save the correct answer - update an existing answer if possible.
        $answer = array_shift($oldanswers);
        if (!$answer) {
            $answer = new stdClass();
            $answer->question = $question->id;
            $answer->answer = '';
            $answer->feedback = '';
            $answer->id = $DB->insert_record('question_answers', $answer);
        }
        
        //debugging('ICI.1 :: ($question) :: '. print_r($question, true));
        //debugging('ICI.2 :: ($question) :: '. var_export(get_object_vars($question), true));
        //error_log('ICI.3 :: ($question) :: '. var_export(get_object_vars($question), true));

        $answer->answer   = 'correct'; //get_string('true', 'qtype_manip');
        $answer->fraction = 1.0; // $question->correctanswer;
        $answer->feedback = $this->import_or_save_files($question->feedbackcorrect,
                $context, 'question', 'answerfeedback', $answer->id);
        $answer->feedbackformat = $question->feedbackcorrect['format'];
        $DB->update_record('question_answers', $answer);
        $correctid = $answer->id;

        // Save the incorrect answer - update an existing answer if possible.
        $answer = array_shift($oldanswers);
        if (!$answer) {
            $answer = new stdClass();
            $answer->question = $question->id;
            $answer->answer = '';
            $answer->feedback = '';
            $answer->id = $DB->insert_record('question_answers', $answer);
        }

        $answer->answer   = 'incorrect'; //get_string('false', 'qtype_manip');
        $answer->fraction = 0.0; // 1 - (int)$question->correctanswer;
        $answer->feedback = $this->import_or_save_files($question->feedbackincorrect,
                $context, 'question', 'answerfeedback', $answer->id);
        $answer->feedbackformat = $question->feedbackincorrect['format'];
        $DB->update_record('question_answers', $answer);
        $incorrectid = $answer->id;

        // Delete any left over old answer records.
        $fs = get_file_storage();
        foreach ($oldanswers as $oldanswer) {
            $fs->delete_area_files($context->id, 'question', 'answerfeedback', $oldanswer->id);
            $DB->delete_records('question_answers', array('id' => $oldanswer->id));
        }

        //debugging('$question :: '. print_r($question, true));

        if ($question->regex == 'other') {
            $question->regex = $question->regexother;
        }

        // Save question options in question_manip table
        if ($options = $DB->get_record('question_manip', array('question' => $question->id))) {
            $options->regex = $question->regex;
            $options->correct = $correctid;
            $options->incorrect = $incorrectid;
            $DB->update_record('question_manip', $options);
        } else {
            $options = new stdClass();
            $options->question    = $question->id;
            $options->regex = $question->regex;
            $options->correct = $correctid;
            $options->incorrect = $incorrectid;
            $DB->insert_record('question_manip', $options);
        }

        // $this->save_hints($question); // TODO: à confirmer - pas de hints a priori...

        return true;
    }

    /**
     * Loads the question type specific options for the question.
     */
    public function get_question_options($question) {
        global $DB, $OUTPUT;
        // Get additional information from database
        // and attach it to the question object
        if (!$question->options = $DB->get_record('question_manip',
                array('question' => $question->id))) {
            echo $OUTPUT->notification('Error: Missing question options!');
            return false;
        }
        // Load the answers
        if (!$question->options->answers = $DB->get_records('question_answers',
                array('question' =>  $question->id), 'id ASC')) {
            echo $OUTPUT->notification('Error: Missing question answers for manip question ' .
                    $question->id . '!');
            return false;
        }

        return true;
    }

    public function get_regex() {
        return array(
            // TODO: set all strings to be translatable?
            'other' => get_string('otherregex', 'qtype_manip'),
            '<w:pStyle w:val="En-tte"' => 'En-tête',
            '<pic:cNvPr id="0" name="nom_image.jpg"/>' => 'Image insérée avec nom du fichier',
            '<wp:cNvGraphicFramePr>' => 'Images insérées',
            '<w:spacing w:line="480"' => 'Interligne double',
            '<w:vAlign w:val="both"' => 'Justification verticale',
            '<w:lang w:val="en-CA"' => 'Langue canadien anglais',
            '<w:pStyle w:val="Notedebasdepage"' => 'Notes de bas de page',
            '<o:OLEObject Type="Link"' => 'Objet Olé lié',
            'w:orient="landscape"' => 'Orientation paysage',
            '<w:docPartGallery w:val="Page Numbers (Bottom of Page)"' => 'Pagination dans le pied de page',
            '<w:jc w:val="center"' => 'Paragraphes centrés horizontalement',
            '<w:jc w:val="both"' => 'Paragraphes justifiés horizontalement',
            '<w:numPr>' => 'Puces ou numéro',
            '<w:br/>' => 'Saut de ligne',
            '</w:sectPr>' => 'Saut section',
            '<w:br w:type="page"' => 'Sauts de page',
            '<w:pStyle w:val="TM1"' => 'Style table des matières niveau 1',
            '<w:pStyle w:val="TM2"' => 'Style table des matières niveau 2',
            '<w:pStyle w:val="TM3"' => 'Style table des matières niveau 3',
            '<w:instrText xml:space="preserve"> TOC \o "1-2"' => 'Table des matères deux premiers niveaux',
            '<w:instrText xml:space="preserve"> TOC \o "1-3"' => 'Table des matères trois premiers niveaux',
            '<w:tblGrid>' => 'Tableaux',
            '<w:tblHeader/>' => 'Tableaux avec ligne en en-tête répétée',
            '<w:spacing w:line="240" w:lineRule="auto"' => 'Interligne simple',
            '<w:ind w:left="1134" w:right="1134"' => 'Retrait de 2 cm',
            '<w:trHeight w:val="567"' => 'Tableau - Hauteur de ligne - 1cm',
            '<w:trHeight w:val="1134"' => 'Tableau - Hauteur de ligne - 2cm',
            '<w:jc w:val="center"/>' => 'Nouvelle balise RG',
            '<w:trHeight w:val="1701"' => 'Tableau - Hauteur de ligne - 3cm',
            '<w:trHeight w:val="2268"' => 'Tableau - Hauteur de ligne - 4cm',
            '<w:trHeight w:val="2835"' => 'Tableau - Hauteur de ligne - 5cm',
            '<w:gridCol w:w="567"' => 'Tableau - Largeur de colonne - 1cm',
            '<w:gridCol w:w="1134"' => 'Tableau - Largeur de colonne - 2cm',
            '<w:gridCol w:w="1701"' => 'Tableau - Largeur de colonne - 3cm',
            '<w:gridCol w:w="2268"' => 'Tableau - Largeur de colonne - 4cm',
            '<w:gridCol w:w="2835"' => 'Tableau - Largeur de colonne - 5cm',
            'w:fill=' => 'Tableau - Trame de fond',
            '<w:spacing w:line="240"' => 'Interligne simple',
            '<w:r>' => 'Saut de paragraphe',
            '<w:pgNumType w:fmt="lowerRoman"' => 'Pagination chiffres romains minuscules',
            '<w:numFmt w:val="decimal"' => 'Pagination chiffres arabes',
            '<w:start ="1"' => 'Numérotation débutant à 1',
            '<w:ind w:firstLine="1134"' => 'Retrait première ligne 2 cm',
            '<w:jc w:val="center"' => 'Texte centré horizontalement',
            '<w:jc w:val="right"' => 'Texte aligné à droite',
            '<w:vAlign w:val="center"' => 'Texte centré verticalement',
            '<w:vAlign w:val="bottom"' => 'Texte aligné au bas',
            '<w:jc w:val="both"' => 'Paragraphe justifié',
            '<w:ind w:left="567"' => 'Retrait à gauche 1 cm',
            '<w:ind w:left="1134"' => 'Retrait à gauche 2 cm',
            '<w:ind w:right="567"' => 'Retrait à droite 1 cm',
            '<w:ind w:right="1134"' => 'Retrait à droite 2 cm',
        );
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        error_log('initialise_question_instance ($question, $questiondata) :: '. print_r($question, true) .' :: '. print_r($questiondata, true));
        $answers = $questiondata->options->answers;

        $question->feedbackcorrect = $answers[$questiondata->options->correct]->feedback;
        $question->feedbackincorrect = $answers[$questiondata->options->incorrect]->feedback;
        $question->feedbackcorrectformat =
                $answers[$questiondata->options->correct]->feedbackformat;
        $question->feedbackincorrectformat =
                $answers[$questiondata->options->incorrect]->feedbackformat;
        $question->correctanswerid =  $questiondata->options->correct;
        $question->incorrectanswerid = $questiondata->options->incorrect;
    }

    public function response_file_areas() {
        return array('attachment');
    }

    public function delete_question($questionid, $contextid) {
        global $DB;
        $DB->delete_records('question_manip', array('question' => $questionid));

        parent::delete_question($questionid, $contextid);
    }

    public function move_files($questionid, $oldcontextid, $newcontextid) {
        // error_log('move_files!');
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $fs = get_file_storage();
        // TODO: confirmer "graderinfo"
        $fs->move_area_files_to_new_context($oldcontextid, $newcontextid, 'qtype_manip', 'graderinfo', $questionid);
        //$this->move_files_in_answers($questionid, $oldcontextid, $newcontextid);
    }

    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_answers($questionid, $contextid);
    }

    public function is_usable_by_random() {
        return false;
    }

    public function get_possible_responses($questiondata) {
        /* TODO: soit utiliser des constantes pour les fractions (1.0 et 0.0),
         *       soit permettre au prof de spécifier les fractions. */
        return array(
            $questiondata->id => array(
                0 => new question_possible_response('correct' /* get_string('correctanswer', 'qtype_manip') */,
                        1.0 /* $questiondata->options->answers[$questiondata->options->correctanswer]->fraction*/
                        ),
                1 => new question_possible_response('incorrect' /* get_string('incorrectanswer', 'qtype_manip') */,
                        0.0 /* $questiondata->options->answers[$questiondata->options->incorrectanswer]->fraction */
                        ),
                null => question_possible_response::no_response()
            )
        );
    }
    
//    public function import_from_xml($data, $question, $format, $extra = null) {
//        parent::import_from_xml($data, $question, $format, $extra);
//        debugging(print_r($data, true));
//    }
}
