<?php
namespace Craft;

/**
 * Digital Products Helper
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2016, Pixel & Tonic, Inc.
 */
class DigitalProductsHelper
{

    // Methods
    // =========================================================================

    /**
     * Generate a license key.
     *
     * @return string
     */
    public static function generateLicenseKey($codeAlphabet, $keyLength)
    {
        $licenseKey = '';

        for ($i = 0; $i < $keyLength; $i++) {
            $licenseKey .= $codeAlphabet[mt_rand(0, strlen($codeAlphabet) - 1)];
        }

        return $licenseKey;
    }
}
