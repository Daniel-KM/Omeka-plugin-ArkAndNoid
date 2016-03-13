<?php
/**
 * Helper to get or create ark.
 */
class Ark_View_Helper_Ark extends Zend_View_Helper_Abstract
{
    /**
     * Return the ark of a record.
     *
     * @param AbstractRecord|array $record Record object or array with record
     * type and record id.
     * @param string $type  Optional type: text (default), name, absolute, link,
     * or route.
     * @return string The ark of the record, if any.
     */
    public function ark($record, $type = 'text')
    {
        $ark = $this->_getArk($record, $type == 'route');
        if (empty($ark)) {
            return '';
        }

        switch ($type) {
            case 'link':
                return sprintf('<a href="%s">%s</a>', absolute_url($ark), $ark);
            case 'absolute':
                return absolute_url($ark);
            case 'name':
                $protocol = get_option('ark_protocol');
                $naan = get_option('ark_naan');
                return substr($ark, $naan ? strlen("$protocol/$naan/") : strlen("$protocol/"));
            case 'route':
            case 'text':
            default:
                return $ark;
        }
    }

    /**
     * Get the ark for the record.
     *
     * @param AbstractRecord|array $record Record object or array with record
     * type and record id.
     * @param boolean $asRoute Return as array or as string.
     * @return string|array|null The ark of the record, or null.
     */
    private function _getArk($record, $asArray = false)
    {
        $record = $this->_getRecord($record);
        if (empty($record)) {
            return;
        }

        $file = null;
        if (get_class($record) == 'File') {
            $file = $record;
            $record = $file->getItem();
        }

        // Unlike controller, the element texts are already loaded here, so this
        // avoids a direct query.
        $identifiers = $record->getElementTexts('Dublin Core', 'Identifier');
        $protocol = get_option('ark_protocol');
        $naan = get_option('ark_naan');
        $base = $naan ? "$protocol/$naan/" : "$protocol/";
        $ark = null;
        foreach ($identifiers as $identifier) {
            if (strpos($identifier->text, $base) === 0) {
                $ark = $identifier->text;
                break;
            }
        }

        if ($ark) {
            if ($asArray) {
                $ark = array(
                    'naan' => $naan,
                    'name' => substr($ark, strlen($base)),
                );
            }

            if ($file) {
                $qualifier = $this->_getQualifier($file);
                if ($asArray) {
                    $ark['qualifier'] = $qualifier;
                }
                else {
                    $ark .= '/' . $qualifier;
                }
            }
        }

        return $ark;
    }

    /**
     * Return the qualifier part of an ark.
     *
     * @param AbstractRecord $record
     * @return string The qualifier.
     */
    protected function _getQualifier($record)
    {
        $formats = apply_filters('ark_format_qualifiers', array());

        // Check the selected format (avoid issue for extra plugin class).
        $format = get_option('ark_format_qualifier');
        if (!isset($formats[$format])) {
            throw new Ark_ArkException(__('Ark format for qualifier "%s" is missing.', $format));
        }
        $class = $formats[$format]['class'];
        // TODO Check class (issue with Zend autoload).
        // if (!class_exists($format)) {
        //     throw new Ark_ArkException(__('Ark qualifier class "%s" is missing.', $class));
        // }

        $arkQualifier = new $class(array(
            'record' => $record,
            'format' => $format,
        ));
        return $arkQualifier->create($record);
    }

    /**
     * Helper to check and get a record. If no record, return empty record.
     *
     * This allows record to be an object or an array, in particular for
     * shortcodes.
     *
     * @return AbstractRecord|null.
     */
    private function _getRecord($record)
    {
        if (is_object($record)) {
            return $record;
        }

        if (!is_array($record)) {
            return;
        }

        if (isset($record['record_type']) && isset($record['record_id'])) {
            $recordType = $record['record_type'];
            $recordId = $record['record_id'];
        }
        elseif (count($record) == 1) {
            $recordId = reset($record);
            $recordType = key($record);
        }
        elseif (count($record) == 2) {
            $recordType = array_shift($record);
            $recordId = array_shift($record);
        }
        else {
            return;
        }

        return get_record_by_id($recordType, $recordId);
    }
}
