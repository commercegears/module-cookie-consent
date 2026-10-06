<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Dynamic rows for additional cookie categories.
 */
class CustomCategories extends AbstractFieldArray
{
    /**
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn('code', ['label' => __('Code'), 'class' => 'required-entry', 'style' => 'width:110px']);
        $this->addColumn('label', ['label' => __('Name'), 'class' => 'required-entry', 'style' => 'width:140px']);
        $this->addColumn('description', ['label' => __('Description'), 'style' => 'width:260px']);
        $this->addColumn('consent_types', ['label' => __('Google Consent Types'), 'style' => 'width:170px']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Category');
    }
}
