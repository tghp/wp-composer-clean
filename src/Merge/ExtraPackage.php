<?php

namespace TGHP\WpComposerClean\Merge;

use Composer\Composer;
use Composer\Json\JsonFile;
use Composer\Package\CompletePackage;
use Composer\Package\Link;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\RootAliasPackage;
use Composer\Package\RootPackageInterface;
use Composer\Package\Version\VersionParser;
use Composer\Semver\Constraint\MatchAllConstraint;

class ExtraPackage
{

    /**
     * @var Composer $composer
     */
    protected $composer;

    /**
     * @var string $path
     */
    protected $path;

    /**
     * @var array $json
     */
    protected $json;

    /**
     * @var CompletePackage $package
     */
    protected $package;

    public function __construct($path, Composer $composer)
    {
        $this->path = $path;
        $this->composer = $composer;
        $this->json = $this->readPackage($path);
        $this->package = $this->loadPackage($this->json);
    }

    /**
     * Load composer.json contents of the package and provide reasonable defaults
     * as Composer would also do for the root package
     *
     * @param string $path
     * @return array
     */
    protected function readPackage($path)
    {
        $file = new JsonFile($path);
        $json = $file->read();
        
        if (!isset($json['name'])) {
            $json['name'] = 'wpcomposerclean/' . str_replace(DIRECTORY_SEPARATOR, '-', $path);
        }

        if (!isset($json['version'])) {
            $json['version'] = '1.0.0';
        }

        return $json;
    }

    /**
     * @param array $json
     * @return CompletePackage
     */
    protected function loadPackage(array $json)
    {
        $loader = new ArrayLoader();
        $package = $loader->load($json);

        return $package;
    }

    /**
     * @param RootPackageInterface $rootPackage
     */
    public function mergeInto(RootPackageInterface $rootPackage)
    {
        $this->mergeRepository();
        $this->mergeRequires('require', $rootPackage);
    }

    /**
     * @param RootPackageInterface $rootPackage
     */
    protected function mergeRepository()
    {
        $repoManager = $this->composer->getRepositoryManager();
        $newRepos = array();

        $repo = $repoManager->createRepository(
            'path',
            ['url' => dirname($this->path)]
        );
        $repoManager->prependRepository($repo);

    }

    /**
     * Merge require or require-dev into a RootPackageInterface
     *
     * @param string $type 
     * @param RootPackageInterface $rootPackage
     */
    protected function mergeRequires($type, RootPackageInterface $rootPackage)
    {
        $requires = $rootPackage->getRequires();

        // Gather package names used for require
        $rootPackageName = $this->composer->getPackage()->getName();
        $packageName = $this->package->getName();

        // Create a constraint used in the requier link
        $devConstraint = new MatchAllConstraint();
        $devConstraint->setPrettyString('@dev');

        // Create a link
        $link = new Link(
            $rootPackageName,
            $packageName,
            $devConstraint,
            'requires',
            $devConstraint->getPrettyString()
        );
        
        // Add to the copy of requires
        $requires[$packageName] = $link;

        // Put the modified requires back into the root package
        $rootPackage->setRequires($requires);
    }
    
}
