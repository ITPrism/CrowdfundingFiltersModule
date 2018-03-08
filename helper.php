<?php
/**
 * @package      Crowdfunding
 * @subpackage   Modules
 * @author       Todor Iliev
 * @copyright    Copyright (C) 2017 Todor Iliev <todor@itprism.com>. All rights reserved.
 * @license      GNU General Public License version 3 or later; see LICENSE.txt
 */

use Joomla\Utilities\ArrayHelper;
defined('_JEXEC') or die;

class CrowdfundingFiltersModuleHelper
{
    /**
     * Generates a link that will be used for sorting results.
     *
     * @param string $label
     * @param string $orderBy
     * @param array $options
     * @param string $class
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public static function orderByLink($label, $orderBy, $options, $class = '')
    {
        $html = array();

        $orderedBy    = ArrayHelper::getValue($options, 'order_by', 0, 'int');
        $orderedDir   = ArrayHelper::getValue($options, 'order_dir', 'ASC');
        $oppositeDir  = ArrayHelper::getValue($options, 'opposite_dir', 'DESC');

        $elementClass = (strlen($class) > 0) ? ' class="'.$class.'"' : '';
        $iconClass    = (strcmp('ASC', $orderedDir) === 0) ? 'fa fa-caret-up': 'fa fa-caret-down';

        $parameters = array(
            'filter_order'     => $orderBy,
            'filter_order_Dir' => ($orderedBy === $orderBy) ? $oppositeDir : $orderedDir
        );

        $html[] = '<div '.$elementClass.'>';
        $html[] = '<a href="'.JRoute::_(CrowdfundingHelperRoute::getDiscoverRoute($parameters)).'">'.$label.'</a>';

        if ($orderedBy === $orderBy) {
            $html[] = '<span class="' . $iconClass . '"></span>';
        }
        $html[] = '</div>';

        return implode("\n", $html);
    }
}
