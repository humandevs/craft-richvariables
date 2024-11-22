<?php
/**
 * Rich Variables plugin for Craft CMS 5.x
 *
 * Allows you to use entries from specified sections as variables in text and CKEditor fields.
 *
 * @link      https://nystudio107.com
 * @package   RichVariables
 */

namespace nystudio107\richvariables\models;

use craft\base\Model;

/**
 * Class Settings
 *
 * @package   RichVariables
 * @since     2.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * The section handles containing variable entries.
     *
     * @var array
     */
    public array $variablesSectionHandles = [];

    /**
     * Whether to use an icon for the menu.
     *
     * @var bool
     */
    public bool $useIconForMenu = true;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['variablesSectionHandles'], 'required'],
            [['variablesSectionHandles'], 'each', 'rule' => ['string']],
            [['useIconForMenu'], 'boolean'],
            [['useIconForMenu'], 'default', 'value' => true],
        ];
    }
}