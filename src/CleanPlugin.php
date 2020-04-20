<?php

namespace TGHP\WpComposerClean;

use TGHP\WpComposerClean\Merge\ExtraPackage;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\EventDispatcher\Event as BaseEvent;
use Composer\Plugin\PluginEvents;
use Composer\Script\ScriptEvents;
use Composer\Script\Event as ScriptEvent;
use Composer\Json\JsonFile;

class CleanPlugin implements PluginInterface, EventSubscriberInterface
{

    /**
     * @var Composer $composer
     */
    protected $composer;
    
    /**
     * Priority that plugin uses to register callbacks.
     */
    const CALLBACK_PRIORITY = 50000;
    
    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
    }
    
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::PRE_UPDATE_CMD => ['onPreUpdate', self::CALLBACK_PRIORITY],
            PluginEvents::INIT => ['onInit', self::CALLBACK_PRIORITY],
        ];
    }

    /**
     * @param ScriptEvent $event
     */
    public function onInit(BaseEvent $event)
    {
        $this->merge();
    }

    /**
     * @param ScriptEvent $event
     */
    public function onPreUpdate(ScriptEvent $event)
    {
        $this->merge();
    }

    /**
     * Add repositories and packages for discovered sources
     */
    protected function merge()
    {
        $root = $this->composer->getPackage();
        
        foreach(['plugins', 'themes'] as $srcType) {
            $srcComposerFiles = glob("./src/{$srcType}/*/composer.json");

            foreach($srcComposerFiles as $composerFile) {
                $package = new ExtraPackage($composerFile, $this->composer);
                $package->mergeInto($root);
            }
        }
    }
}