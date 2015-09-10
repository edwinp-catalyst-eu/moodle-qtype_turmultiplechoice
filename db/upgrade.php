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
 * Multiple choice question type upgrade code.
 *
 * @package    qtype
 * @subpackage turmultiplechoice
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Upgrade code for the multiple choice question type.
 * @param int $oldversion the version we are upgrading from.
 */
function xmldb_qtype_turmultiplechoice_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Moodle v2.3.0 release upgrade line
    // Put any upgrade step following this.

    // Moodle v2.4.0 release upgrade line
    // Put any upgrade step following this.

    // Moodle v2.5.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2013092300) {

        // Find duplicate rows before they break the 2013092304 step below.
        $sql = "SELECT question, MIN(id) AS recordidtokeep
                  FROM {question_turmultiplechoice}
              GROUP BY question
                HAVING COUNT(1) > 1";
        $problemids = $DB->get_recordset_sql($sql);

        foreach ($problemids as $problem) {
            $DB->delete_records_select(
                    'question_turmultiplechoice',
                    'question = ? AND id > ?',
                    array(
                        $problem->question,
                        $problem->recordidtokeep
                    )
                );
        }
        $problemids->close();

        // turmultiplechoice savepoint reached
        upgrade_plugin_savepoint(true, 2013092300, 'qtype', 'turmultiplechoice');
    }

    if ($oldversion < 2013092301) {

        // Define table question_turmultiplechoice to be renamed to qtype_turmultichoice_options.
        $table = new xmldb_table('question_turmultiplechoice');

        // Launch rename table for question_turmultiplechoice.
        $dbman->rename_table($table, 'qtype_turmultichoice_options');

        // turmultiplechoice savepoint reached
        upgrade_plugin_savepoint(true, 2013092301, 'qtype', 'turmultiplechoice');
    }

    if ($oldversion < 2013092302) {

        // Define key question (foreign) to be dropped form qtype_turmultichoice_options
        $table = new xmldb_table('qtype_turmultichoice_options');
        $key = new xmldb_key('question', XMLDB_KEY_FOREIGN, array('question'), 'question', array('id'));

        // Launch drop key question.
        $dbman->drop_key($table, $key);

        // Record that qtype_match savepoint was reached.
        upgrade_plugin_savepoint(true, 2013092302, 'qtype', 'turmultiplechoice');
    }

    if ($oldversion < 2013092303) {

        // Rename field question on table qtype_turmultichoice_options to questionid.
        $table = new xmldb_table('qtype_turmultichoice_options');
        $field = new xmldb_field('question', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'id');

        // Launch rename field question.
        $dbman->rename_field($table, $field, 'questionid');

        // Record that qtype_match savepoint was reached.
        upgrade_plugin_savepoint(true, 2013092303, 'qtype', 'turmultiplechoice');
    }

    if ($oldversion < 2013092304) {

        // Define key questionid (foreign-unique) to be added to qtype_multichoice_options.
        $table = new xmldb_table('qtype_turmultichoice_options');
        $key = new xmldb_key('questionid', XMLDB_KEY_FOREIGN_UNIQUE, array('questionid'), 'question', array('id'));

        // Launch add key questionid.
        $dbman->add_key($table, $key);

        // Record that qtype_match savepoint was reached.
        upgrade_plugin_savepoint(true, 2013092304, 'qtype', 'turmultiplechoice');
    }

    if ($oldversion < 2013092305) {

        // Define field answers to be dropped from qtype_multichoice_options.
        $table = new xmldb_table('qtype_turmultichoice_options');
        $field = new xmldb_field('answers');

        // Conditionally launch drop field answers.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Record that qtype_match savepoint was reached.
        upgrade_plugin_savepoint(true, 2013092305, 'qtype', 'turmultiplechoice');
    }

    // Moodle v2.6.0 release upgrade line.
    // Put any upgrade step following this.

    // Moodle v2.7.0 release upgrade line.
    // Put any upgrade step following this.

    return true;
}
