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
 * Defines form for editing On-site/Off-site block instances.
 *
 * @package   block_lanonly
 * @copyright 2010 Taunton's College
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Form for editing On-site/Off-site block instances.
 *
 * Based on the {@see block_html_edit_form} but modified to allow 2 versions of the content and
 * title to be defined
 *
 * @copyright 2010 Taunton's College
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_lanonly_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        // Fields for editing HTML block title and contents.
        $mform->addElement('header', 'configheaderonsite', get_string('insidelan', 'block_lanonly'));

        $mform->addElement('text', 'config_title_onsite', get_string('title', 'block_lanonly'));
        $mform->setType('config_title_onsite', PARAM_MULTILANG);

        $editoroptions = array(
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'noclean' => true,
            'context' => $this->block->context
        );
        $strcontent = get_string('content', 'block_lanonly');
        $strleaveblank = get_string('leaveblanktohide', 'block_lanonly');
        $mform->addElement('editor', 'config_text_onsite', $strcontent, null, $editoroptions);
        $mform->setType('config_text_onsite', PARAM_RAW); // XSS is prevented when printing the block contents and serving files
        $mform->addElement('static', 'leaveblank_onsite', '', $strleaveblank);

        $mform->addElement('header', 'configheaderoffsite', get_string('outsidelan', 'block_lanonly'));

        $mform->addElement('text', 'config_title_offsite', get_string('title', 'block_lanonly'));
        $mform->setType('config_title_offsite', PARAM_MULTILANG);

        $mform->addElement('editor', 'config_text_offsite', $strcontent, null, $editoroptions);
        $mform->setType('config_text_offsite', PARAM_RAW); // XSS is prevented when printing the block contents and serving files
        $mform->addElement('static', 'leaveblank_offsite', '', $strleaveblank);
    }

    public function set_data($defaults) {
        if (!empty($this->block->config) && is_object($this->block->config)) {
            $text_onsite = $this->block->config->text_onsite;
            $draftid_editor = file_get_submitted_draft_itemid('config_text_onsite');
            if (empty($text_onsite)) {
                $currenttext_onsite = '';
            } else {
                $currenttext_onsite = $text_onsite;
            }

            if (empty($this->block->config->title_onsite)) {
                $defaults->config_title_onsite = '';
            } else {
                $defaults->config_title_onsite = $this->block->config->title_onsite;
            }

            $defaults->config_text_onsite['text'] = file_prepare_draft_area($draftid_editor, 
                                                                            $this->block->context->id,
                                                                            'block_lanonly',
                                                                            'content',
                                                                            0,
                                                                            array('subdirs'=>true),
                                                                            $currenttext_onsite);
            $defaults->config_text_onsite['itemid'] = $draftid_editor;
            $defaults->config_text_onsite['format'] = $this->block->config->format_onsite;

            $text_offsite = $this->block->config->text_offsite;
            $draftid_editor = file_get_submitted_draft_itemid('config_text_offsite');
            if (empty($text_offsite)) {
                $currenttext_offsite = '';
            } else {
                $currenttext_offsite = $text_offsite;
            }

            if (empty($this->block->config->title_offsite)) {
                $defaults->config_title_offsite = '';
            } else {
                $defaults->config_title_offsite = $this->block->config->title_offsite;
            }

            $defaults->config_text_offsite['text'] = file_prepare_draft_area($draftid_editor,
                                                                             $this->block->context->id,
                                                                             'block_lanonly',
                                                                             'content',
                                                                             0,
                                                                             array('subdirs'=>true),
                                                                             $currenttext_offsite);
            $defaults->config_text_offsite['itemid'] = $draftid_editor;
            $defaults->config_text_offsite['format'] = $this->block->config->format_offsite;

        } else {
            $text_onsite = '';
            $text_offsite = '';
        }
        // have to delete text here, otherwise parent::set_data will empty content
        // of editor
        unset($this->block->config->text_onsite);
        unset($this->block->config->text_offsite);
        parent::set_data($defaults);
        // restore $text
        $this->block->config->text_onsite = $text_onsite;
        $this->block->config->text_offsite = $text_offsite;
    }
}
