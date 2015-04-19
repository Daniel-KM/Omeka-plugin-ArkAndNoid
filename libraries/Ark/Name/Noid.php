<?php
/**
 * Noid format for Ark name.
 *
 * @package Ark
 */
class Ark_Name_Noid extends Ark_Name_Abstract
{
    protected $_isFullArk = true;

    protected function _create($record)
    {
        // Record is not used for noid. Extends the class if needed.
        return $this->_noid();
    }

    protected function _noid()
    {
        $command = $this->_getParameter('ark_options');

        $this->_executeCommand($cmd, $status, $output, $errors);

        if (!empty($errors)) {
            _log(__('Error output from ark command:\n%s', $errors), Zend_Log::WARN);
        }

        if ($status) {
            _log(__('Ark command failed with status code %s.', $status), Zend_Log::ERR);
            return;
        }

        return $output;
    }
}
