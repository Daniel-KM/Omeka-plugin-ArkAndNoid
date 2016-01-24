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
        $command = $this->_getParameter('command');
        $status = null;
        $output = null;
        $errors = null;

        $this->_executeCommand($command, $status, $output, $errors);

        if (!empty($errors)) {
            _log('[Ark] ' . __('Error output from ark command:\n%s', $errors), Zend_Log::WARN);
        }

        if ($status) {
            _log('[Ark] ' . __('Ark command failed with status code %s.', $status), Zend_Log::ERR);
            return;
        }

        return $output;
    }
}
