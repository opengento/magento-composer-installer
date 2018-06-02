<?php

namespace MagentoHackathon\Composer\Magento\Patcher;

use MagentoHackathon\Composer\Magento\ProjectConfig;
use org\bovigo\vfs\vfsStream;

/**
 * @group patcher
 */
class BootstrapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider filesProvider
     */
    public function testMageFileIsChangedAfterPatching($mageFile, $functionsFile)
    {
        $structure = array('app' => array(
            'Mage.php' => file_get_contents($mageFile),
            'code/core/Mage/Core/functions.php' => file_get_contents($functionsFile),
        ));
        vfsStream::setup('root', null, $structure);

        $config = new ProjectConfig(
            array(
                ProjectConfig::EXTRA_WITH_BOOTSTRAP_PATCH_KEY => true,
                ProjectConfig::MAGENTO_ROOT_DIR_KEY => vfsStream::url('root'),
            ),
            array()
        );

        $mageClassFile = vfsStream::url('root/app/Mage.php');
        $patcher = Bootstrap::fromConfig($config);

        $this->assertTrue($patcher->canApplyPatch());
        $this->assertFileEquals($mageFile, $mageClassFile);

        $patcher->patch();

        $this->assertFalse($patcher->canApplyPatch());
        $this->assertFileNotEquals($mageFile, $mageClassFile);
    }

    /**
     * @dataProvider filesProvider
     */
    public function testFunctionsFileIsChangedAfterPatching($mageFile, $functionsFile)
    {
        $structure = array('app' => array(
            'Mage.php' => file_get_contents($functionsFile),
            'code/core/Mage/Core/functions.php' => file_get_contents($functionsFile),
        ));
        vfsStream::setup('root', null, $structure);

        $config = new ProjectConfig(
            array(
                ProjectConfig::EXTRA_WITH_BOOTSTRAP_PATCH_KEY => true,
                ProjectConfig::MAGENTO_ROOT_DIR_KEY => vfsStream::url('root'),
            ),
            array()
        );

        $origFile = vfsStream::url('root/app/code/core/Mage/Core/functions.php');
        $patcher = Bootstrap::fromConfig($config);

        $this->assertTrue($patcher->canApplyPatch());
        $this->assertFileEquals($functionsFile, $origFile);

        $patcher->patch();

        $this->assertFalse($patcher->canApplyPatch());
        $this->assertFileNotEquals($functionsFile, $origFile);
    }

    /**
     * @dataProvider filesProvider
     */
    public function testMageFileIsNotModifiedWhenThePatchingFeatureIsOff($mageFile, $functionsFile)
    {
        $structure = array('app' => array(
            'Mage.php' => file_get_contents($mageFile),
            'code/core/Mage/Core/functions.php' => file_get_contents($functionsFile),
        ));
        vfsStream::setup('root', null, $structure);

        $config = new ProjectConfig(
            array(
                ProjectConfig::EXTRA_WITH_BOOTSTRAP_PATCH_KEY => false,
                ProjectConfig::MAGENTO_ROOT_DIR_KEY => vfsStream::url('root'),
            ),
            array()
        );

        $mageClassFile = vfsStream::url('root/app/Mage.php');
        $patcher = Bootstrap::fromConfig($config);

        $this->assertFalse($patcher->canApplyPatch());
        $this->assertFileEquals($mageFile, $mageClassFile);

        $patcher->patch();

        $this->assertFileEquals($mageFile, $mageClassFile);
    }

    /**
     * @dataProvider filesProvider
     */
    public function testBootstrapPatchIsAppliedByDefault($mageFile, $functionsFile)
    {
        $structure = array('app' => array(
            'Mage.php' => file_get_contents($mageFile),
            'code/core/Mage/Core/functions.php' => file_get_contents($functionsFile),
        ));
        vfsStream::setup('root', null, $structure);

        $config = new ProjectConfig(
            // the patch flag is not declared on purpose
            array(ProjectConfig::MAGENTO_ROOT_DIR_KEY => vfsStream::url('root')),
            array()
        );

        $mageClassFile = vfsStream::url('root/app/Mage.php');
        $patcher = Bootstrap::fromConfig($config);

        $this->assertTrue($patcher->canApplyPatch());
        $this->assertFileEquals($mageFile, $mageClassFile);

        $patcher->patch();

        $this->assertFalse($patcher->canApplyPatch());
        $this->assertFileNotEquals($mageFile, $mageClassFile);
    }

    public function filesProvider()
    {
        $fixturesBasePath = __DIR__ . '/../../../../res/fixtures';
        $data = array(
            array($fixturesBasePath . '/php/Mage/Mage-v1.9.1.0.php', $fixturesBasePath . '/php/functions/functions-v1.9.3.8.php')
        );
        return $data;
    }

    public function testPatchingDoesNotThrowIfDisabledAndRunWithMissingMagePhpFile()
    {
        vfsStream::setup('root', null, array()); // empty FS

        $config = new ProjectConfig(
            array(
                ProjectConfig::EXTRA_WITH_BOOTSTRAP_PATCH_KEY => false,
                ProjectConfig::MAGENTO_ROOT_DIR_KEY => vfsStream::url('root'),
            ),
            array()
        );

        $patcher = Bootstrap::fromConfig($config);

        $this->assertFalse($patcher->canApplyPatch());
        $this->assertFalse($patcher->patch());
    }

    /**
     * @expectedException DomainException
     */
    public function testPatchingThrowsIfEnabledAndRunWithMissingMagePhpFile()
    {
        vfsStream::setup('root', null, array()); // empty FS

        $config = new ProjectConfig(
            array(
                ProjectConfig::EXTRA_WITH_BOOTSTRAP_PATCH_KEY => true,
                ProjectConfig::MAGENTO_ROOT_DIR_KEY => vfsStream::url('root'),
            ),
            array()
        );

        Bootstrap::fromConfig($config)->patch();
    }
}
