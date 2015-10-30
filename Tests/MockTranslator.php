<?php
namespace Recognize\FilemanagerBundle\Tests;

use Recognize\FilemanagerBundle\Entity\FileReference;
use Symfony\Component\Translation\TranslatorInterface;

class MockTranslator implements TranslatorInterface {

    public function generateThumbnailForFile( Filereference $ref = null ){
        return "";
    }


    /**
     * Translates the given message.
     *
     * @param string $id The message id (may also be an object that can be cast to string)
     * @param array $parameters An array of parameters for the message
     * @param string|null $domain The domain for the message or null to use the default
     * @param string|null $locale The locale or null to use the default
     *
     * @throws \InvalidArgumentException If the locale contains invalid characters
     *
     * @return string The translated string
     *
     * @api
     */
    public function trans($id, array $parameters = array(), $domain = null, $locale = null)
    {
        return $id;
    }

    /**
     * Translates the given choice message by choosing a translation according to a number.
     *
     * @param string $id The message id (may also be an object that can be cast to string)
     * @param int $number The number to use to find the indice of the message
     * @param array $parameters An array of parameters for the message
     * @param string|null $domain The domain for the message or null to use the default
     * @param string|null $locale The locale or null to use the default
     *
     * @throws \InvalidArgumentException If the locale contains invalid characters
     *
     * @return string The translated string
     *
     * @api
     */
    public function transChoice($id, $number, array $parameters = array(), $domain = null, $locale = null)
    {
        // TODO: Implement transChoice() method.
    }

    /**
     * Sets the current locale.
     *
     * @param string $locale The locale
     *
     * @throws \InvalidArgumentException If the locale contains invalid characters
     *
     * @api
     */
    public function setLocale($locale)
    {
        // TODO: Implement setLocale() method.
    }

    /**
     * Returns the current locale.
     *
     * @return string The locale
     *
     * @api
     */
    public function getLocale()
    {
        // TODO: Implement getLocale() method.
    }
}