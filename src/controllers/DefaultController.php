<?php
/**
 * Rich Variables plugin for Craft CMS 5.x
 *
 * Allows you to use entries from specified sections as variables in text, CKEditor, and Redactor fields.
 *
 * @link      https://nystudio107.com
 * @package   RichVariables
 */

namespace nystudio107\richvariables\controllers;

use aelvan\preparsefield\fields\PreparseFieldType as PreparseField;
use Craft;
use craft\base\Field;
use craft\ckeditor\Field as CKEditorField;
use craft\elements\Entry;
use craft\fields\Date;
use craft\fields\Dropdown;
use craft\fields\Number;
use craft\fields\PlainText;
use craft\helpers\Json;
use craft\redactor\Field as RedactorField;
use craft\web\Controller;
use nystudio107\richvariables\assetbundles\richvariables\RichVariablesAsset;
use nystudio107\richvariables\RichVariables;
use yii\base\InvalidConfigException;

/**
 * Class DefaultController
 *
 * @author    
 * @package   RichVariables
 * @since     1.0.0
 */
class DefaultController extends Controller
{
    // Constants
    // =========================================================================

    const VALID_FIELD_CLASSES = [
        PlainText::class,
        Number::class,
        Date::class,
        Dropdown::class,
        CKEditorField::class,
        RedactorField::class,
        PreparseField::class,
        // Add other field types if necessary
    ];

    // Public Methods
    // =========================================================================

    /**
     * Returns the variables list as JSON for use in the plugin's JavaScript.
     *
     * @return \yii\web\Response
     * @throws InvalidConfigException
     */
    public function actionIndex(): \yii\web\Response
    {
        $variablesList = [];

        // Get the section handles from settings
        $settings = RichVariables::$plugin->getSettings();
        $variablesSectionHandles = $settings->variablesSectionHandles;

        // Ensure $variablesSectionHandles is an array
        if (!is_array($variablesSectionHandles)) {
            $variablesSectionHandles = [$variablesSectionHandles];
        }

        // Fetch entries from the specified sections
        foreach ($variablesSectionHandles as $sectionHandle) {
            $entries = Entry::find()
                ->section($sectionHandle)
                ->all();

            foreach ($entries as $entry) {
                $layout = $entry->getFieldLayout();
                if ($layout) {
                    $fields = $layout->getCustomFields();
                    /** @var Field $field */
                    foreach ($fields as $field) {
                        foreach (self::VALID_FIELD_CLASSES as $fieldClass) {
                            if ($field instanceof $fieldClass) {
                                // Build the variable title and Reference Tag
                                $thisVar = [
                                    'title' => $sectionHandle . ' - ' . $entry->title . ' - ' . $field->name,
                                    'text' => '{entry:' . $entry->id . ':' . $field->handle . '}',
                                ];
                                $variablesList[] = $thisVar;
                            }
                        }
                    }
                }
            }
        }

        // Get the URL to our menu icon from our asset bundle
        try {
            Craft::$app->getView()->registerAssetBundle(RichVariablesAsset::class);
        } catch (InvalidConfigException $e) {
            Craft::error($e->getMessage(), __METHOD__);
        }

        $menuIconUrl = Craft::$app->assetManager->getPublishedUrl(
            '@nystudio107/richvariables/assetbundles/richvariables/dist',
            true
        ) . '/img/RichVariables-menu-icon.svg';

        // Prepare the result array
        $result = [
            'variablesList' => $variablesList,
            'menuIconUrl' => $menuIconUrl,
            'useIconForMenu' => $settings->useIconForMenu,
        ];

        // Return everything to our JavaScript encoded as JSON
        return $this->asJson($result);
    }
}