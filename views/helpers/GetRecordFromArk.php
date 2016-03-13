<?php
/**
 * Ark Get Record From Identifier
 *
 * @package Omeka\Plugins\Ark\views\helpers
 */
class Ark_View_Helper_GetRecordFromArk extends Zend_View_Helper_Abstract
{
    /**
     * Get record from ark. This doesn't manage qualifier, except for files.
     *
     * @param string|array $ark The ark of the record to find.
     * @param string $return Format of the returned value. can be: 'record',
     * 'type and id' or 'id' (to use only when the type is known).
     * @return Omeka_Record_AbstractRecord|array|integer|null The record.
     */
    public function getRecordFromArk(
        $ark,
        $return = 'record'
    ) {
        if (empty($ark)) {
            return null;
        }

        $protocol = get_option('ark_protocol');
        $naan = get_option('ark_naan');
        $base = $naan ? "$protocol/$naan/" : "$protocol/";

        if (is_string($ark)) {
            // Quick check of format.
            if (strpos($ark, $base) !== 0) {
                return null;
            }

            // This is the ark of the naan.
            if ($ark == $base) {
                return null;
            }

            $fullName = substr($ark, strlen($base));
            if ($fullName == '?' || $fullName == '??') {
                return null;
            }

            // Get the identifier and the qualifier parts.
            $pos = strpos($fullName, '/');
            if ($pos === false) {
                $name = $fullName;
                $qualifier = '';
            }
            else {
                $name = substr($fullName, 0, $pos);
                $qualifier = substr($fullName, $pos + 1);
            }
        }
        elseif (is_array($ark)) {
             if ($ark['naan'] !== get_option('ark_naan')
                    || empty($ark['name']) || $ark['name'] == '?' || $ark['name'] == '??'
                ) {
                return null;
            }
            $name = $ark['name'];
            $qualifier = empty($ark['qualifier']) ? null : $ark['qualifier'];
        }
        else {
            return null;
        }

        $db = get_db();
        $element = $db->getTable('Element')
            ->findByElementSetNameAndElementName('Dublin Core', 'Identifier');
        if (empty($element)) {
            return null;
        }

        $table = $db->getTable('ElementText');
        $alias = $table->getTableAlias();
        $select = $table->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->from(array(), array($alias . '.record_type', $alias . '.record_id'))
            ->where("`$alias`.`element_id` = ?", $element->id)
            ->where("`$alias`.`text` = ?", $base . $name)
            // If more than one arks have the same id, always use the first one.
            ->order("$alias.id ASC")
            ->limit(1);
        $result = $table->fetchRow($select);

        if (!$result) {
            return null;
        }

        $recordType = $result['record_type'];
        $recordId = $result['record_id'];

        if ($qualifier) {
            $record = get_record_by_id($recordType, $recordId);
            $qualifierRecord = $this->_getRecordFromQualifier($record, $qualifier);
            if ($qualifierRecord) {
                $recordType = get_class($qualifierRecord);
                $recordId = $qualifierRecord->id;
            }
        }

        switch ($return) {
            case 'record':
            default:
                return get_record_by_id($recordType, $recordId);

            case 'type and id':
                return array('record_type' => $recordType, 'record_id' => $recordId);

            case 'id':
                return $recordId;
        }
    }

    /**
     * Return the record from the qualifier part of an ark.
     *
     * @param AbstractRecord $record Main record (item).
     * @param string $qualifier The qualifier part of the ark.
     * @return AbstractRecord|null The record, if any.
     */
    protected function _getRecordFromQualifier($record, $qualifier)
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
        return $arkQualifier->getRecordFromQualifier($record, $qualifier);
    }
}
