<?php

namespace APP\plugins\themes\encounters;

use DOMDocument;
use DOMXPath;

/** Turn the journal's checklist into required OJS form fields. */
class SubmissionChecklist
{
    public static function configureEditor(array &$config, callable $translate): void
    {
        if (($config['id'] ?? '') !== 'submissionGuidanceSettings') {
            return;
        }
        foreach ($config['fields'] as &$field) {
            if ($field['name'] !== 'submissionChecklist') {
                continue;
            }
            $field['component'] = 'encounters-checklist-editor';
            $field['description'] = $translate('description');
            foreach (['introduction', 'requirement', 'add', 'remove', 'up', 'down', 'empty', 'blank', 'undo'] as $key) {
                $field['editorLabels'][$key] = $translate($key);
            }
            return;
        }
    }

    public static function configure(array &$config, callable $requirementLabel): void
    {
        if (($config['id'] ?? '') !== 'startSubmission') {
            return;
        }
        foreach ($config['fields'] as $index => $field) {
            if ($field['name'] !== 'submissionRequirements') {
                continue;
            }
            $document = new DOMDocument();
            $previous = libxml_use_internal_errors(true);
            try {
                $document->loadHTML('<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . ($field['description'] ?? '') . '</body></html>', LIBXML_NONET);
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
            }
            $xpath = new DOMXPath($document);
            $lists = $xpath->query('//body//*[self::ul or self::ol][not(ancestor::li)]');
            $items = [];
            foreach ($lists as $list) {
                foreach ($xpath->query('./li', $list) as $item) {
                    if (trim($item->textContent) !== '') {
                        $items[] = self::innerHtml($document, $item);
                    }
                }
            }
            // Keep OJS's original confirmation for prose-only checklists.
            if (!$items) {
                return;
            }
            foreach (iterator_to_array($lists) as $list) {
                $list->parentNode->removeChild($list);
            }
            $fields = [[
                'name' => 'encountersChecklistIntroduction',
                'component' => 'field-html',
                'label' => $field['label'],
                'description' => self::innerHtml($document, $document->getElementsByTagName('body')->item(0)),
                'groupId' => $field['groupId'],
                'isInert' => true,
                'class' => 'encounters-checklist-introduction',
            ]];
            foreach ($items as $number => $html) {
                $fields[] = [
                    'name' => 'encountersRequirement' . ($number + 1),
                    'component' => 'field-options',
                    'label' => $requirementLabel($number + 1),
                    'groupId' => $field['groupId'],
                    'type' => 'checkbox',
                    'options' => [['value' => true, 'label' => $html]],
                    'value' => false,
                    'isRequired' => true,
                    // Start-form confirmations are not stored compliance records.
                    'isInert' => true,
                    'class' => 'encounters-submission-requirement',
                ];
            }
            array_splice($config['fields'], $index, 1, $fields);
            return;
        }
    }

    private static function innerHtml(DOMDocument $document, \DOMNode $node): string
    {
        $html = '';
        foreach ($node->childNodes as $child) {
            $html .= $document->saveHTML($child);
        }
        return $html;
    }
}
