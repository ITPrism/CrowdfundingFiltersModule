<?php
/**
 * @package      Crowdfunding
 * @subpackage   Modules
 * @author       Todor Iliev
 * @copyright    Copyright (C) 2017 Todor Iliev <todor@itprism.com>. All rights reserved.
 * @license      GNU General Public License version 3 or later; see LICENSE.txt
 */

// no direct access
defined("_JEXEC") or die;

jimport('Prism.init');
jimport('Crowdfunding.init');

JLoader::register('CrowdfundingFiltersModuleHelper', JPATH_ROOT . '/modules/mod_crowdfundingfilters/helper.php');

$moduleclassSfx = htmlspecialchars($params->get('moduleclass_sfx'));

if ($params->get('enable_chosen', 0)) {
    JHtml::_('formbehavior.chosen', 'select.js-modfilters-filter');
}

$categoryId = 0;

$viewName     = $app->input->getCmd('view');
if (strcmp('category', $viewName) === 0) {
    $categoryId = $app->input->getInt('id');
    $url = JRoute::_(CrowdfundingHelperRoute::getCategoryRoute($categoryId));
} else {
    $url = JRoute::_(CrowdfundingHelperRoute::getDiscoverRoute());
}

$filterPhrase = $app->getUserStateFromRequest('mod_crowdfundingfilters.filter_phrase', 'filter_phrase', '');

// START DropDown filters

$elements = array();

// Prepare caching.
$cache = null;
if ($app->get('caching', 0)) {
    $cache = JFactory::getCache('com_crowdfunding', '');
    $cache->setLifeTime(Prism\Constants::TIME_SECONDS_24H);
}

if ($params->get('display_countries', 0)) {
    $countries = null;
    // Get the countries from the cache.
    if ($cache !== null) {
        $countries = $cache->get(Crowdfunding\Constants::CACHE_COUNTRIES_CODES);
        $countries = is_array($countries) ? $countries : null;
    }

    if ($countries === null) {
        $countries = new Crowdfunding\Countries(JFactory::getDbo());
        $countries->load();
        $countries = $countries->toOptions('code', 'name');
        $countries = is_array($countries) ? $countries : array();

        // Store the countries into the cache.
        if ($cache !== null and count($countries) > 0) {
            $cache->store($countries, Crowdfunding\Constants::CACHE_COUNTRIES_CODES);
        }
    }

    $filterCountry = $app->getUserStateFromRequest('mod_crowdfundingfilters.filter_country', 'filter_country');

    $option = JHtml::_('select.option', '', JText::_('MOD_CROWDFUNDINGFILTERS_SELECT_COUNTRY'), 'value', 'text');
    $option = array($option);

    $countries = array_merge($option, $countries);
    $elements[]  = 1;
}

if ($params->get('display_categories', 0)) {
    $categories = null;
    // Get the categories from the cache.
    if ($cache !== null) {
        $categories = $cache->get(Crowdfunding\Constants::CACHE_CATEGORIES);
        $categories = is_array($categories) ? $categories : null;
    }

    if ($categories === null) {
        $config     = array(
            'filter.published' => 1
        );
        $categories = JHtml::_('category.options', 'com_crowdfunding', $config);
        $categories = is_array($categories) ? $categories : array();

        // Store the categories into the cache.
        if ($cache !== null and count($categories) > 0) {
            $cache->store($categories, Crowdfunding\Constants::CACHE_CATEGORIES);
        }
    }

    $categoryId     = (strcmp($viewName, 'category') === 0) ? $app->input->getInt('id') : null;
    if ($categoryId !== null and (int)$categoryId > 0) {
        $app->input->set('filter_category', (int)$categoryId);
    }

    $filterCategory = $app->getUserStateFromRequest('mod_crowdfundingfilters.filter_category', 'filter_category');

    $option = JHtml::_('select.option', 0, JText::_('MOD_CROWDFUNDINGFILTERS_SELECT_CATEGORY'));
    $option = array($option);

    $categories = array_merge($option, $categories);
    $elements[]  = 1;
}

if ($params->get('display_funding_type', 0)) {
    $filterFundingType = $app->getUserStateFromRequest('mod_crowdfundingfilters.filter_fundingtype', 'filter_fundingtype');

    $fundingTypes = array(
        JHtml::_('select.option', '', JText::_('MOD_CROWDFUNDINGFILTERS_SELECT_FUNDING_TYPE')),
        JHtml::_('select.option', 'fixed', JText::_('MOD_CROWDFUNDINGFILTERS_FIXED')),
        JHtml::_('select.option', 'flexible', JText::_('MOD_CROWDFUNDINGFILTERS_FLEXIBLE'))
    );
    $elements[]  = 1;
}

if ($params->get('display_project_type', 0)) {
    $projectTypes = null;
    // Get the types from the cache.
    if ($cache !== null) {
        $projectTypes = $cache->get(Crowdfunding\Constants::CACHE_PROJECT_TYPES);
        $projectTypes = is_array($projectTypes) ? $projectTypes : null;
    }

    if ($projectTypes === null) {
        $types        = new Crowdfunding\Types(JFactory::getDbo());
        $types->load();

        $projectTypes = $types->toOptions();
        $projectTypes = is_array($projectTypes) ? $projectTypes : array();

        // Store the types into the cache.
        if ($cache !== null and count($projectTypes) > 0) {
            $cache->store($projectTypes, Crowdfunding\Constants::CACHE_PROJECT_TYPES);
        }
    }

    $filterProjectType = $app->getUserStateFromRequest('mod_crowdfundingfilters.filter_projecttype', 'filter_projecttype');

    $optionSelect = array(
        0 => array(
            'value' => 0,
            'text'  => JText::_('MOD_CROWDFUNDINGFILTERS_SELECT_PROJECT_TYPE')
        )
    );
    $projectTypes = array_merge($optionSelect, $projectTypes);
    $elements[]  = 1;
}

// Prepare the span size of the drop down elements.
$elements = count($elements) ?: 1;
$spanDropDowns = 12 / $elements;

// END DropDown filters

// START Predefined filters

$elements = array();

// Prepare statistic options.
$statistics = new Crowdfunding\Statistics\Basic(JFactory::getDbo());

$statisticValues = array();
if ($cache !== null) {
    $statisticValues = $cache->get(Crowdfunding\Constants::CACHE_STATISTIC_VALUES);
    if ($statisticValues === false) {
        $statisticValues = array();
    }
}

if ($params->get('display_started_soon', 0)) {
    $options = array(
        'state'    => Prism\Constants::PUBLISHED,
        'approved' => Prism\Constants::APPROVED,
        'interval' => 7
    );

    if (!array_key_exists('started_soon', $statisticValues)) {
        $statisticValues['started_soon'] = $statistics->getStartedSoonProjects($options);
    }
    $startedSoon = $statisticValues['started_soon'];
    $elements[]  = 1;
}

// Ending soon projects.
if ($params->get('display_ending_soon', 0)) {
    $options = array(
        'state'    => Prism\Constants::PUBLISHED,
        'approved' => Prism\Constants::APPROVED,
        'interval' => 7
    );

    if (!array_key_exists('ending_soon', $statisticValues)) {
        $statisticValues['ending_soon'] = $statistics->getEndingSoonProjects($options);
    }

    $endingSoon = $statistics->getEndingSoonProjects($options);
    $elements[]  = 1;
}

// Featured projects.
if ($params->get('display_featured', 0)) {
    $options = array(
        'state'    => Prism\Constants::PUBLISHED,
        'approved' => Prism\Constants::APPROVED
    );

    if (!array_key_exists('featured', $statisticValues)) {
        $statisticValues['featured'] = $statistics->getFeaturedProjects($options);
    }

    $featured = $statistics->getFeaturedProjects($options);
    $elements[]  = 1;
}

// Successfully completed projects.
if ($params->get('display_successfully_completed', 0)) {
    $options = array(
        'state'    => Prism\Constants::PUBLISHED,
        'approved' => Prism\Constants::APPROVED
    );

    if (!array_key_exists('completed', $statisticValues)) {
        $statisticValues['completed'] = $statistics->getSuccessfullyCompletedProjects($options);
    }

    $successfullyCompleted = $statistics->getSuccessfullyCompletedProjects($options);
    $elements[]  = 1;
}

// Store statistic values in the cache.
if ($cache !== null and count($statisticValues) > 0) {
    $cache->store($statisticValues, Crowdfunding\Constants::CACHE_STATISTIC_VALUES);
}

// Prepare the span size.
$elements = count($elements) ?: 1;
$span = 12 / $elements;

// END Predefined filters


// START Sorting filters

$allowedDirections = array('ASC', 'DESC');
$orderDir = $app->input->get('filter_order_Dir', 'DESC');
if (!in_array($orderDir, $allowedDirections, true)) {
    $orderDir = 'DESC';
}

// Get current ordering.
$orderBy = $app->input->get('filter_order');

$orderDirOptions = array(
    'order_by'    => $orderBy,
    'order_dir'    => $orderDir,
    'opposite_dir' => (strcmp('ASC', $orderDir) === 0) ? 'DESC' : 'ASC'
);

// END Sorting filters

require JModuleHelper::getLayoutPath('mod_crowdfundingfilters', $params->get('layout', 'default'));