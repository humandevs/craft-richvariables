<?php
/**
 * Rich Variables plugin for Craft CMS 5.x
 *
 * Allows you to easily use entries from specified sections as variables in text and CKEditor fields.
 *
 * @link      https://nystudio107.com
 * @package   RichVariables
 */

namespace nystudio107\richvariables;

use Craft;
use craft\base\Plugin;
use craft\events\PluginEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use Exception;
use nystudio107\richvariables\assetbundles\richvariables\RichVariablesAsset;
use nystudio107\richvariables\models\Settings;
use nystudio107\richvariables\variables\RichVariablesVariable;
use Twig\Error\LoaderError;
use yii\base\Event;
use yii\base\InvalidConfigException;

class RichVariables extends Plugin
{
    /**
     * @var RichVariables
     */
    public static $plugin;

    /**
     * @var string
     */
    public string $schemaVersion = '1.0.0';

    /**
     * @var bool
     */
    public bool $hasCpSection = false;

    /**
     * @var bool
     */
    public bool $hasCpSettings = true;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Add in our event listeners that are needed for every request
        $this->installEventListeners();

        // We're loaded!
        Craft::info(
            Craft::t(
                'rich-variables',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    /**
     * Install our event listeners
     */
    protected function installEventListeners()
    {
        // Register our variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('richVariables', [
                    'class' => RichVariablesVariable::class,
                ]);
            }
        );

        // Handler: Plugins::EVENT_AFTER_INSTALL_PLUGIN
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    $request = $this->request;
                    if ($request->isCpRequest) {
                        Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('settings/plugins/rich-variables'))->send();
                    }
                }
            }
        );
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): ?craft\base\Model
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        // Get all of the sections
        $sectionsService = Craft::$app->getSections();
        $allSections = $sectionsService->getAllSections();
        $sections = [];

        foreach ($allSections as $section) {
            $sections[] = [
                'label' => $section->name,
                'value' => $section->handle,
            ];
        }

        // Render our settings template
        try {
            return Craft::$app->view->renderTemplate(
                'rich-variables/settings',
                [
                    'settings' => $this->getSettings(),
                    'sections' => $sections,
                ]
            );
        } catch (LoaderError $e) {
            Craft::error($e->getMessage(), __METHOD__);
        } catch (Exception $e) {
            Craft::error($e->getMessage(), __METHOD__);
        }

        return '';
    }
}