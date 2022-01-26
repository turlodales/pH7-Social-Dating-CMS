<?php
/**
 * @author           Pierre-Henry Soria <hello@ph7cms.com>
 * @copyright        (c) 2022, Pierre-Henry Soria. All Rights Reserved.
 * @license          MIT License; See PH7.LICENSE.txt and PH7.COPYRIGHT.txt in the root directory.
 * @package          PH7 / App / Include / Class
 */

namespace PH7;

use stdClass;

interface UserModeratable
{
    public function approve(): void;

    public function disapprove(): void;

    public function approveAll(): void;

    public function disapproveAll(): void;

    public function ban(): void;

    public function unBan(): void;

    public function delete(): void;

    public function banAll(): void;

    public function unBanAll(): void;

    public function deleteAll(): void;

    public function sendRegistrationMail(string $sSubject, stdClass $oUser): void;
}