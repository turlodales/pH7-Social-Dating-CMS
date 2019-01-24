<?php
/**
 * @title            DbConfig Class
 * @desc             Database Config Class.
 *
 * @author           Pierre-Henry Soria <hello@ph7cms.com>
 * @copyright        (c) 2012-2018, Pierre-Henry Soria. All Rights Reserved.
 * @license          GNU General Public License; See PH7.LICENSE.txt and PH7.COPYRIGHT.txt in the root directory.
 * @package          PH7 / Framework / Mvc / Model
 * @version          1.1
 */

namespace PH7\Framework\Mvc\Model;

defined('PH7') or exit('Restricted access');

use PH7\DbTableName;
use PH7\Framework\Cache\Cache;
use PH7\Framework\Seo\Data\MetaData;

final class DbConfig
{
    const CACHE_GROUP = 'db/config';
    const CACHE_TIME = 999000;
    const ENABLED_SITE = 'enable';
    const MAINTENANCE_SITE = 'maintenance';

    /**
     * Private constructor to prevent instantiation of class, because it's a static class.
     */
    private function __construct()
    {
    }

    /**
     * @param string|null $sSetting You can specify a specific parameter.
     *
     * @return string|int|\stdClass A string or an integer if you specify a specific parameter, otherwise returns an object.
     */
    public static function getSetting($sSetting = null)
    {
        $oCache = (new Cache)->start(self::CACHE_GROUP, 'setting' . $sSetting, self::CACHE_TIME);

        // @return value of config the database
        if (!empty($sSetting)) {
            if (!$sData = $oCache->get()) {
                $rStmt = Engine\Db::getInstance()->prepare('SELECT settingValue FROM' . Engine\Db::prefix(DbTableName::SETTING) . 'WHERE settingName = :setting');
                $rStmt->bindParam(':setting', $sSetting, \PDO::PARAM_STR);
                $rStmt->execute();
                $sData = $rStmt->fetchColumn();
                Engine\Db::free($rStmt);
                $oCache->put($sData);
            }
            $mData = $sData;
        } else {
            if (!$oData = $oCache->get()) {
                $rStmt = Engine\Db::getInstance()->prepare('SELECT * FROM' . Engine\Db::prefix(DbTableName::SETTING));
                $rStmt->execute();
                $oData = $rStmt->fetch(\PDO::FETCH_OBJ);
                Engine\Db::free($rStmt);
                $oCache->put($oData);
            }
            $mData = $oData;
        }

        unset($oCache);

        return empty($mData) ? 0 : $mData;
    }

    /**
     * @param string $sValue Value to set.
     * @param string $sName Name of the DB ph7_settings column.
     *
     * @return int 1 on success.
     */
    public static function setSetting($sValue, $sName)
    {
        return Engine\Record::getInstance()->update(
            DbTableName::SETTING,
            'settingValue',
            $sValue,
            'settingName',
            $sName
        );
    }

    /**
     * @param string $sLangId
     *
     * @return \stdClass
     */
    public static function getMetaMain($sLangId)
    {
        $oCache = (new Cache)->start(self::CACHE_GROUP, 'metaMain' . $sLangId, self::CACHE_TIME);

        // @return value of meta tags the database
        if (!$oMetaData = $oCache->get()) {
            $sSql = 'SELECT * FROM' . Engine\Db::prefix(DbTableName::META_MAIN) . 'WHERE langId = :langId';

            // Get meta data with the current language if it exists in the "MetaMain" table ...
            $rStmt = Engine\Db::getInstance()->prepare($sSql);
            $rStmt->bindParam(':langId', $sLangId, \PDO::PARAM_STR);
            $rStmt->execute();
            $oMetaData = $rStmt->fetch(\PDO::FETCH_OBJ);

            // If the current language doesn't exist in the "MetaMain" table, we create a new table for the new language with default value
            if (empty($oMetaData)) {
                $aData = MetaData::getDefaultMeta();

                // Create the new meta data language
                Engine\Record::getInstance()->insert(DbTableName::META_MAIN, $aData);
                $oMetaData = (object)$aData;
                unset($aData);
            }
            Engine\Db::free($rStmt);
            $oCache->put($oMetaData);
        }
        unset($oCache);

        return $oMetaData;
    }

    /**
     * Sets the Meta Main Data.
     *
     * @param string $sSection
     * @param string $sValue
     * @param string $sLangId
     *
     * @return void
     */
    public static function setMetaMain($sSection, $sValue, $sLangId)
    {
        Engine\Record::getInstance()->update(
            DbTableName::META_MAIN,
            $sSection,
            $sValue,
            'langId',
            $sLangId
        );
    }

    /**
     * @param string $sStatus '0' = Disable | '1' = Enable. (need to be string because in DB it is an "enum").
     * @param string $sFieldName
     *
     * @return void
     */
    public static function setSocialWidgets($sStatus, $sFieldName = 'socialMediaWidgets')
    {
        $sStatus = (string)$sStatus; // Cast into string to be sure it will work as in DB it's an "enum" type

        self::setSetting($sStatus, $sFieldName);

        // addthis JS file's staticID is '1'
        $rStmt = Engine\Db::getInstance()->prepare('UPDATE' . Engine\Db::prefix(DbTableName::STATIC_FILE) . 'SET active = :status WHERE staticId = 1 AND fileType = \'js\' LIMIT 1');
        $rStmt->execute(['status' => $sStatus]);

        // Clear "db/design/static" cache. '1' matches with TRUE in Design::files(); (note, don't need to clear DbConfig as it'll always be called in SettingFormProcess class which clears the cache anyway)
        (new Cache)->start(Design::CACHE_STATIC_GROUP, 'filesjs1', null)->clear();
    }

    /**
     * @param string $sStatus The constant 'DbConfig::ENABLED_SITE' or 'DbConfig::MAINTENANCE_SITE'
     * @param string $sFieldName
     *
     * @return void
     */
    public static function setSiteMode($sStatus, $sFieldName = 'siteStatus')
    {
        if ($sStatus !== self::MAINTENANCE_SITE && $sStatus !== self::ENABLED_SITE) {
            exit('Wrong maintenance mode type!');
        }

        self::setSetting($sStatus, $sFieldName);

        /* Clear DbConfig Cache (this method is not always called in SettingFormProcess class, so clear the cache to be sure) */
        self::clearCache();
    }

    /**
     * Clean the entire DbConfig group Cache.
     *
     * @return void
     */
    public static function clearCache()
    {
        (new Cache)->start(
            self::CACHE_GROUP,
            null,
            null
        )->clear();
    }
}
