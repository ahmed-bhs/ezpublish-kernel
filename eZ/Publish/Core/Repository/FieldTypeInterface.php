<?php
/**
 * File containing the FieldType interface
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Core\Repository;
use ezp\Content\Field,
    eZ\Publish\Core\Repository\FieldType\Value,
    ezp\Base\Repository as BaseRepository,
    ezp\Content\Type\FieldDefinition,
    eZ\Publish\SPI\Persistence\Content\FieldValue;

/**
 * Interface for field types, the most basic storage unit of data inside eZ Publish.
 */
interface FieldTypeInterface
{
    /**
     * Return the field type identifier for this field type
     *
     * @return string
     */
    public function getFieldTypeIdentifier();

    /**
     * This method is called on occuring events. Implementations can perform corresponding actions
     *
     * @param string $event - prePublish, postPublish, preCreate, postCreate
     * @param Repository $repository
     * @param $fieldDef - the field definition of the field
     * @param $field - the field for which an action is performed
     */
    public function handleEvent( $event, BaseRepository $repository, FieldDefinition $fieldDef, Field $field );

    /**
     * Keys of settings which are available on this fieldtype.
     * @return array
     */
    public function allowedSettings();

    /**
     * Return an array of allowed validators to operate on this field type.
     *
     * @return array
     */
    public function allowedValidators();

    /**
     * Checks the type and structure of the $Value.
     *
     * @throws \ezp\Base\Exception\InvalidArgumentType if the parameter is not of the supported value sub type
     * @throws \ezp\Base\Exception\InvalidArgumentValue if the value does not match the expected structure
     *
     * @param \eZ\Publish\Core\Repository\FieldType\Value $inputValue
     *
     * @return \eZ\Publish\Core\Repository\FieldType\Value
     */
    public function acceptValue( Value $inputValue );

    /**
     * Returns the fallback default value of field type when no such default
     * value is provided in the field definition in content types.
     *
     * @return \eZ\Publish\Core\Repository\FieldType\Value
     */
    public function getDefaultDefaultValue();

    /**
     * Converts a $value to a persistence value
     *
     * @param \eZ\Publish\Core\Repository\FieldType\Value $value
     *
     * @return \eZ\Publish\SPI\Persistence\Content\FieldValue
     */
    public function toPersistenceValue( Value $value );

    /**
     * Converts a persistence $fieldValue to a Value
     *
     * @param \eZ\Publish\SPI\Persistence\Content\FieldValue $fieldValue
     *
     * @return \eZ\Publish\Core\Repository\FieldType\Value
     */
    public function fromPersistenceValue( FieldValue $fieldValue );

    /**
     * Returns whether the field type is searchable
     *
     * @return bool
     */
    public function isSearchable();
}
