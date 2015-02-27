<?php
namespace AssetCompress;

use AssetCompress\AssetConfig;
use AssetCompress\Factory;
use Cake\TestSuite\TestCase;

class FactoryTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        AssetConfig::clearAllCachedKeys();
        $testConfig = APP . 'config' . DS . 'config.ini';
        $this->config = AssetConfig::buildFromIniFile($testConfig);

        $this->integrationFile = APP . 'config' . DS . 'integration.ini';
    }

    public function testFilterRegistry()
    {
        $factory = new Factory($this->config);
        $registry = $factory->filterRegistry();
        $this->assertTrue($registry->contains('Sprockets'));
        $this->assertTrue($registry->contains('YuiJs'));
        $this->assertTrue($registry->contains('CssMinFilter'));

        $filter = $registry->get('UglifyJs');
        $this->assertEquals('/path/to/uglify-js', $filter->settings()['path']);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Cannot load filter "Derp"
     */
    public function testFilterRegistryMissingFilter()
    {
        $this->config->filterConfig('Derp', ['path' => '/test']);
        $factory = new Factory($this->config);
        $factory->filterRegistry();
    }

    public function testAssetCollection()
    {
        $config = AssetConfig::buildFromIniFile($this->integrationFile, [
            'TEST_FILES/' => APP,
            'WEBROOT/' => TMP
        ]);
        $factory = new Factory($config);
        $collection = $factory->assetCollection();

        $this->assertCount(3, $collection);
        $this->assertTrue($collection->contains('libs.js'));
        $this->assertTrue($collection->contains('foo.bar.js'));
        $this->assertTrue($collection->contains('all.css'));

        $asset = $collection->get('libs.js');
        $this->assertCount(2, $asset->files(), 'Not enough files');
        $paths = [
            APP . 'js',
            APP . 'js/**'
        ];
        $this->assertEquals($paths, $asset->paths(), 'Paths are incorrect');
        $this->assertEquals(['Sprockets'], $asset->filterNames(), 'Filters are incorrect');
        $this->assertFalse($asset->isThemed(), 'Themed is wrong');
        $this->assertEquals('libs.js', $asset->name(), 'Asset name is wrong');
        $this->assertEquals('js', $asset->ext(), 'Asset ext is wrong');
        $this->assertEquals(TMP . 'cache_js', $asset->outputDir(), 'Asset path is wrong');
        $this->assertEquals(TMP . 'cache_js/libs.js', $asset->path(), 'Asset path is wrong');
    }

    public function testWriter()
    {
        $config = AssetConfig::buildFromIniFile($this->integrationFile, [
            'TEST_FILES/' => APP,
            'WEBROOT/' => TMP
        ]);
        $config->theme('Red');
        $config->set('js.timestamp', true);
        $factory = new Factory($config);
        $writer = $factory->writer();

        $expected = [
            'timestamp' => [
                'js' => true,
                'css' => false
            ],
            'path' => TMP,
            'theme' => 'Red'
        ];
        $this->assertEquals($expected, $writer->config());
    }
}
