<?php
/**
 * Change the format for Ark qualifier.
 *
 * @package Ark
 */
class Ark_Qualifier_Internal extends Ark_Qualifier_Abstract
{
    protected function _create($record)
    {
        $format = $this->_getParameter('format');
        switch (get_class($record)) {
            case 'File':
                switch ($format) {
                    case 'order':
                        $order = $record->order;
                        if (empty($record->order)) {
                            // This is not optimized, but the field "order" may
                            // be used.
                            $files = $record->getItem()->getFiles();
                            foreach ($files as $i => $f) {
                                if ($f->id == $record->id) {
                                    $order = $i + 1;
                                    break;
                                }
                            }
                        }
                        return $order;

                    case 'filename':
                        return $record->filename;

                    case 'filename_without_extension':
                        return pathinfo($record->filename, PATHINFO_FILENAME);

                    case 'original_filename':
                        return pathinfo($record->original_filename, PATHINFO_BASENAME);

                    case 'original_filename_without_extension':
                        return pathinfo($record->original_filename, PATHINFO_FILENAME);

                    case 'omeka_id':
                    default:
                        return $record->id;
                }
                break;

            default:
                return $record->id;
        }
    }

    protected function _getRecordFromQualifier($record, $qualifier)
    {
        switch (get_class($record)) {
            case 'Collection':
                return;

            case 'Item':
                switch ($this->_getParameter('format')) {
                    case 'omeka_id':
                        $qualifier = (integer) $qualifier;
                        if (empty($qualifier)) {
                            return;
                        }
                        $qualifierRecord = get_record_by_id('File', $qualifier);
                        break;

                    case 'order':
                        $qualifier = (integer) $qualifier;
                        if (empty($qualifier)) {
                            return;
                        }
                        $qualifierRecord = get_db()->getTable('File')
                            // The human order starts at one.
                            ->findOneByItem($record->id, $qualifier - 1, 'order');
                        return $qualifierRecord;

                    case 'filename':
                        $qualifierRecord = get_record('File', array(
                            'item_id' => $record->id,
                            'filename' => $qualifier,
                        ));
                        return $qualifierRecord;

                    case 'filename_without_extension':
                        $qualifierRecord  = get_db()->getTable('File')->findBySql(
                            'item_id = ? AND filename LIKE ?',
                            array($record->id, $qualifier . '.%'),
                            true);
                        return $qualifierRecord;

                    case 'original_filename':
                        $qualifierRecord  = get_db()->getTable('File')->findBySql(
                            'item_id = ? AND original_filename LIKE ?',
                            array($record->id, '%' . $qualifier),
                            true);
                        return $qualifierRecord;

                    case 'filename_without_extension':
                        $qualifierRecord  = get_db()->getTable('File')->findBySql(
                            'item_id = ? AND original_filename LIKE ?',
                            array($record->id, $qualifier . '.%'),
                            true);
                        return $qualifierRecord;

                    default;
                        return;
                }
                return empty($qualifierRecord) || $qualifierRecord->item_id != $record->id
                    ? null
                    : $qualifierRecord;
        }
    }
}
