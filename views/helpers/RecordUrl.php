<?php
/**
 * Helper to return an ark url instead of standard "records/show/id" ones.
 *
 * @see Omeka\View\Helper\RecordUrl.php
 * @package Omeka\Plugins\Ark\views\helpers
 */
class Ark_View_Helper_RecordUrl extends Omeka_View_Helper_RecordUrl
{
    /**
     * Return a URL to a record.
     *
     * @uses Omeka_Record_AbstractRecord::getCurrentRecord()
     * @uses Omeka_Record_AbstractRecord::getRecordUrl()
     * @uses Omeka_View_Helper_Url::url()
     * @throws Omeka_View_Exception
     * @param Omeka_Record_AbstractRecord|string $record
     * @param string|null $action
     * @param bool $getAbsoluteUrl
     * @param array $queryParams
     * @return string
     */
    public function recordUrl($record, $action = null, $getAbsoluteUrl = false, $queryParams = array())
    {
        // Get the current record from the view if passed as a string.
        if (is_string($record)) {
            $record = $this->view->getCurrentRecord($record);
        }
        if (!($record instanceof Omeka_Record_AbstractRecord)) {
            throw new Omeka_View_Exception(__('Invalid record passed while getting record URL.'));
        }

        // Because this action is not a default one, it means that the webmaster
        // want it in all cases.
        if (get_class($record) == 'File' && $action
                && in_array($action, explode(' ', get_option('ark_file_variants')))
            ) {
            return $this->_getUrlForFileVariant($record, $action, $getAbsoluteUrl, $queryParams);
        }

        if (is_admin_theme()) {
            if (!get_option('ark_use_admin')) {
                return $this->_getCleanUrlOrDefault($record, $action, $getAbsoluteUrl, $queryParams);
            }
        }
        // Public interface.
        elseif (!get_option('ark_use_public')) {
            return $this->_getCleanUrlOrDefault($record, $action, $getAbsoluteUrl, $queryParams);
        }

        // Get the ark url if any.
        $ark = $this->_getArk($record, $action);
        if ($ark) {
            if (!isset($ark['qualifier'])) {
                $ark['qualifier'] = '';
            }
            $route = 'ark_id';
            $urlString = $this->view->url($ark, $route, $queryParams);
            if ($getAbsoluteUrl) {
                $urlString = $this->view->serverUrl() . $urlString;
            }
            return $urlString;
        }

        return $this->_getCleanUrlOrDefault($record, $action, $getAbsoluteUrl, $queryParams);
    }

    /**
     * Get the ark of a record.
     *
     * @param AbstractRecord $record
     * @param string|null $action
     * @return array|null Identifier of the record, if any, else null.
     */
    protected function _getArk($record, $action = null)
    {
        if ($action == 'show' || is_null($action)) {
            if (in_array(get_class($record), array(
                    'Collection',
                    'Item',
                    'File',
                ))) {
                return $this->view->ark($record, 'route');
            }
        }
    }

    /**
     * Get the ark url for a derivative of a file.
     *
     * @param File $file
     * @param string|null $format
     * @param bool $getAbsoluteUrl
     * @param array $queryParams
     * @return string Ark url.
     */
    protected function _getUrlForFileVariant($file, $format = 'original', $getAbsoluteUrl = false, $queryParams = array())
    {
        $ark = $this->_getArk($file);
        if ($ark) {
            $ark['variant'] = $format;
            $route = 'ark_file_variant';
            $urlString = $this->view->url($ark, $route, $queryParams);
        }
        // If no path have been found, return the standard path.
        else {
            $urlString = $file->getWebPath($format);
        }
        if ($getAbsoluteUrl) {
            $urlString = $this->view->serverUrl() . $urlString;
        }
        return $urlString;
    }

    /**
     * Return the clean url if CleanUrl is enabled, else return default.
     *
     * @param Omeka_Record_AbstractRecord|string $record
     * @param string|null $action
     * @param bool $getAbsoluteUrl
     * @param array $queryParams
     * @return string
     */
    private function _getCleanUrlOrDefault($record, $action, $getAbsoluteUrl, $queryParams)
    {
        return plugin_is_active('CleanUrl')
            ? $this->_getCleanUrl($record, $action, $getAbsoluteUrl, $queryParams)
            : parent::recordUrl($record, $action, $getAbsoluteUrl, $queryParams);
    }

    /**
     * Return the clean url if CleanUrl is enabled, else return default.
     *
     * The plugin should be checked before via _getCleanUrlOrDefault().
     *
     * @param Omeka_Record_AbstractRecord|string $record
     * @param string|null $action
     * @param bool $getAbsoluteUrl
     * @param array $queryParams
     * @return string
     */
    private function _getCleanUrl($record, $action, $getAbsoluteUrl, $queryParams)
    {
        // This helper is not loaded before, because the Ark one is used.
        require_once PLUGIN_DIR . '/CleanUrl/views/helpers/RecordUrl.php';
        $helper = new CleanUrl_View_Helper_RecordUrl();
        return $helper
            ->setView($this->view)
            ->recordUrl($record, $action, $getAbsoluteUrl, $queryParams);
    }
}
