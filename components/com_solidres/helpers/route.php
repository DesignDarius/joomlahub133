<?php
/**
 ------------------------------------------------------------------------
 SOLIDRES - Accommodation booking extension for Joomla
 ------------------------------------------------------------------------
 * @author    Solidres Team <contact@solidres.com>
 * @website   https://www.solidres.com
 * @copyright Copyright (C) 2013 Solidres. All Rights Reserved.
 * @license   GNU General Public License version 3, or later
 ------------------------------------------------------------------------
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Menu\MenuItem;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

abstract class SolidresHelperRoute
{
    protected static $lookup = [];

    public static function getReservationAssetRoute($slug)
    {
        $slug = (string) $slug;
        $view = 'reservationasset';

        if (false !== strpos($slug, ':')) {
            [$id, $alias] = explode(':', $slug, 2);

            if (str_ends_with($id, '-apartment')) {
                $view    = 'apartment';
                $assetId = (int) str_replace('-apartment', '', $id);
                $slug    = $assetId . ':' . $alias;
                static $apartmentRoomTypeIds = [];

                if (!isset($apartmentRoomTypeIds[$assetId])) {
                    /** @var DatabaseDriver $db */
                    $db                             = Factory::getContainer()->get(DatabaseInterface::class);
                    $query                          = $db->getQuery(true)
                        ->select($db->quoteName('id'))
                        ->from($db->quoteName('#__sr_room_types'))
                        ->where($db->quoteName('state') . ' = 1')
                        ->where($db->quoteName('reservation_asset_id') . ' = :assetId')
                        ->bind(':assetId', $assetId, ParameterType::INTEGER);
                    $apartmentRoomTypeIds[$assetId] = (int)($db->setQuery($query)->loadResult() ?? 0);
                }

                if ($apartmentRoomTypeIds[$assetId] && ($menuItem = self::findMenuItem(
                        'apartment',
                        ['id' => $apartmentRoomTypeIds[$assetId]]
                    ))) {
                    $menuItemId = $menuItem->id;
                    $slug       = $apartmentRoomTypeIds[$assetId] . ':' . $alias;
                } elseif ($menuItem = self::findMenuItem('reservationasset', ['id' => $assetId])) {
                    $menuItemId = $menuItem->id;
                } else {
                }
            } elseif ($menuItem = self::findMenuItem('reservationasset', ['id' => $id])) {
                $menuItemId = $menuItem->id;
            }
        }

        $link = 'index.php?option=com_solidres&view=' . $view . '&id=' . $slug;

        // Default menu ID is search menu
        if (!isset($menuItemId)) {
            $activeMenu = Factory::getApplication()->getMenu()->getActive();

            if ($activeMenu && ($activeMenu->query['view'] ?? '') === 'search') {
                $menuItemId = $activeMenu->id;
            } elseif ($searchMenu = static::findMenuItem('search')) {
                $menuItemId = $searchMenu->id;
            }
        }

        if (isset($menuItemId)) {
            $link .= '&Itemid=' . $menuItemId;
        }

        return $link;
    }

    public static function findMenuItem(string $view, array $keyValues = []): ?MenuItem
    {
        ksort($keyValues);
        $app       = Factory::getApplication();
        $key       = serialize($keyValues);
        $lookupKey = $view . ':' . $key;
        static $menuItems = [];
        static $lookupKeys = [];

        $language   = Multilanguage::isEnabled() ? $app->getLanguage()->getTag() : '*';
        $attributes = ['component_id', 'language'];
        $values     = [ComponentHelper::getComponent('com_solidres')->id, [$language, '*']];

        if (!isset($menuItems[$language])) {
            $menuItems[$language] = $app->getMenu()->getItems($attributes, $values);
        }

        if (!array_key_exists($lookupKey, $lookupKeys)) {
            $lookupKeys[$lookupKey] = null;

            foreach ($menuItems[$language] as $item) {
                $menuView = $item->query['view'] ?? '';

                if ($view === $menuView) {
                    if ($keyValues) {
                        foreach ($keyValues as $key => $id) {
                            if (isset($item->query[$key]) && $item->query[$key] == $id) {
                                $lookupKeys[$lookupKey] = $item;

                                return $lookupKeys[$lookupKey];
                            }
                        }
                    } else {
                        $lookupKeys[$lookupKey] = $item;
                    }
                }
            }
        }

        return $lookupKeys[$lookupKey];
    }

    public static function getRoomTypeRoute($slug)
    {
        return 'index.php?option=com_solidres&view=roomtype&id=' . $slug;
    }

    public static function findMenuItems($needles = [])
    {
        $component  = ComponentHelper::getComponent('com_solidres');
        $attributes = ['component_id'];
        $values     = [$component->id];

        if (isset($needles['language'])) {
            if ($needles['language'] !== '*') {
                $attributes[] = 'language';
                $values[]     = [$needles['language'], '*'];
            }

            unset($needles['language']);
        }

        foreach ($needles as $k => $v) {
            $attributes[] = $k;
            $values[]     = $v;
        }

        return Factory::getApplication()->getMenu('site')->getItems($attributes, $values);
    }

    public static function getPartnerRoute($partnerId = null, $layout = null)
    {
        if (null === $partnerId) {
            $partnerId = SRUtilities::getPartnerId();
        }

        $partnerId = (int) $partnerId;
        $link      = 'index.php?option=com_solidres&view=partner&id=' . $partnerId;

        if ($layout && $layout !== 'default') {
            $link .= '&layout=' . $layout;
        }

        return $link;
    }
}
