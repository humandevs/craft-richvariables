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
use craft\ckeditor\Field as CKEditorField;
use craft\ckeditor\events\RegisterPluginFileEvent;
use craft\events\PluginEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use nystudio107\pluginvite\services\VitePluginService;
use nystudio107\richvariables\assetbundles\richvariables\RichVariablesAsset;
use nystudio107\richvariables\models\Settings;
use nystudio107\richvariables\variables\RichVariablesVariable;
use yii\base\Event;
use yii\base\InvalidConfigException;

/**
 * Class RichVariables
 *
 * @property VitePluginService $vite
 */
class RichVariables extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var RichVariables
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * @var bool
     */
    public $hasCpSection = false;

    /**
     * @var bool
     */
    public $hasCpSettings = true;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct($id, $parent = null, array $config = [])
    {
        $config['components'] = [
            // Register the Vite service
            'vite' => [
                'class' => VitePluginService::class,
                'assetClass' => RichVariablesAsset::class,
                'useDevServer' => true,
                'devServerPublic' => 'http://localhost:3001',
                'serverPublic' => 'http://localhost:8000',
                'errorEntry' => 'src/js/app.ts',
                'devServerInternal' => 'http://craft-richvariables-buildchain:3001',
                'checkDevServer' => true,
            ],
        ];

        parent::__construct($id, $parent, $config);
    }

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

    // Protected Methods
    // =========================================================================

    /**
     * Install our event listeners
     */
    protected function installEventListeners()
    {
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('richVariables', [
                    'class' => RichVariablesVariable::class,
                    'viteService' => $this->vite,
                ]);
            }
        );

        // Handler: Plugins::EVENT_AFTER_INSTALL_PLUGIN
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    $request = Craft::$app->getRequest();
                    if ($request->getIsCpRequest()) {
                        Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('settings/plugins/rich-variables'))->send();
                    }
                }
            }
        );

        $request = Craft::$app->getRequest();
        // Install only for non-console site requests
        if ($request->getIsSiteRequest() && !$request->getIsConsoleRequest()) {
            $this->installSiteEventListeners();
        }
        // Install only for non-console Control Panel requests
        if ($request->getIsCpRequest() && !$request->getIsConsoleRequest()) {
            $this->installCpEventListeners();
        }
    }

    /**
     * Install site event listeners for site requests only
     */
    protected function installSiteEventListeners()
    {
        // Handler: UrlManager::EVENT_REGISTER_SITE_URL_RULES
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                Craft::debug(
                    'UrlManager::EVENT_REGISTER_SITE_URL_RULES',
                    __METHOD__
                );
                // Register our Control Panel routes
                $event->rules = array_merge(
                    $event->rules,
                    $this->customFrontendRoutes()
                );
            }
        );
    }

    /**
     * Return the custom frontend routes
     *
     * @return array
     */
    protected function customFrontendRoutes(): array
    {
        return [
            // Define your custom frontend routes here
        ];
    }

    /**
     * Install site event listeners for Control Panel requests only
     */
    protected function installCpEventListeners()
    {
        // Handler: Plugins::EVENT_AFTER_LOAD_PLUGINS
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_LOAD_PLUGINS,
            function () {
                $this->installCKEditorPlugin();
            }
        );
    }

    /**
     * Install our CKEditor plugin
     */
    protected function installCKEditorPlugin()
    {
        // Event handler: CKEditorField::EVENT_REGISTER_PLUGIN_FILES
        Event::on(
            CKEditorField::class,
            CKEditorField::EVENT_REGISTER_PLUGIN_FILES,
            function (RegisterPluginFileEvent $event) {
                // Use Vite to get the correct asset URL
                $pluginName = 'richvariables';
                $pluginUrl = self::$plugin->vite->register('src/js/richvariables.js');

                // Ensure that the plugin URL is a string
                if (is_string($pluginUrl)) {
                    $event->plugins[$pluginName] = $pluginUrl;
                }
            }
        );

        // Register our asset bundle
        try {
            Craft::$app->getView()->registerAssetBundle(RichVariablesAsset::class);
        } catch (InvalidConfigException $e) {
            Craft::error($e->getMessage(), __METHOD__);
        }
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
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
        } catch (\Throwable $e) {
            Craft::error('Error rendering settings template: ' . $e->getMessage(), __METHOD__);
        }

        return '';
    }
}