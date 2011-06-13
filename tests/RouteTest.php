<?php
namespace Aura\Router;

/**
 * Test class for Route.
 */
class RouteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RouteFactory
     */
    protected $factory;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->factory = new RouteFactory;
        $this->server = $_SERVER;
    }
    
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        parent::tearDown();
    }
    
    public function testIsMatchOnStaticPath()
    {
        $route = $this->factory->newInstance(array(
            'path' => '/foo/bar/baz',
            'values' => array(
                'controller' => 'zim',
                'action' => 'dib',
            ),
        ));
        
        // right path
        $actual = $route->isMatch('/foo/bar/baz', $this->server);
        $this->assertTrue($actual);
        $this->assertEquals('zim', $route->values['controller']);
        $this->assertEquals('dib', $route->values['action']);
        
        // wrong path
        $this->assertFalse($route->isMatch('/zim/dib/gir', $this->server));
    }
    
    public function testIsMatchOnDynamicPath()
    {
        $route = $this->factory->newInstance(array(
            'path' => '/{:controller}/{:action}/{:id}{:format}',
            'params' => array(
                'controller' => '([a-zA-Z][a-zA-Z0-9_-]+)',
                'action' => '([a-zA-Z][a-zA-Z0-9_-]+)',
                'id' => '([0-9]+)',
                'format' => '(\.[a-zA-Z0-9]$)?'
            ),
            'values' => array(
                'format' => '.html',
            ),
        ));
        
        $actual = $route->isMatch('/foo/bar/42', $this->server);
        $this->assertTrue($actual);
        $expect = array(
            'controller' => 'foo',
            'action' => 'bar',
            'id' => 42,
            'format' => '.html'
        );
        $this->assertEquals($expect, $route->values);
    }
    
    public function testIsMethodMatch()
    {
        $type = 'Aura\Router\Route';
    
        /**
         * try one method
         */
        $route = $this->factory->newInstance(array(
            'path' => '/foo/bar/baz',
            'method' => 'POST',
        ));
    
        // correct
        $this->assertTrue($route->isMatch('/foo/bar/baz', array(
            'REQUEST_METHOD' => 'POST',
        )));
    
        // wrong path
        $this->assertFalse($route->isMatch('/zim/dib/gir', array(
            'REQUEST_METHOD' => 'POST',
        )));
    
        // wrong method
        $this->assertFalse($route->isMatch('/foo/bar/baz', array(
            'REQUEST_METHOD' => 'GET',
        )));
        
        /**
         * try many methods
         */
        $route = $this->factory->newInstance(array(
            'path' => '/foo/bar/baz',
            'method' => array('GET', 'POST'),
        ));
    
        // correct
        $this->assertTrue($route->isMatch('/foo/bar/baz', array(
            'REQUEST_METHOD' => 'GET',
        )));
        
        $this->assertTrue($route->isMatch('/foo/bar/baz', array(
            'REQUEST_METHOD' => 'POST',
        )));
    
        // wrong path, right methods
        $this->assertFalse($route->isMatch('/zim/dib/gir', array(
            'REQUEST_METHOD' => 'GET',
        )));
        
        $this->assertFalse($route->isMatch('/zim/dib/gir', array(
            'REQUEST_METHOD' => 'POST',
        )));
        
        // right path, wrong method
        $this->assertFalse($route->isMatch('/foo/bar/baz', array(
            'REQUEST_METHOD' => 'PUT',
        )));
        
        // no request method
        $this->assertFalse($route->isMatch('/foo/bar/baz', array()));
    }
    
    public function testIsSecureMatch()
    {
        $type = 'Aura\Router\Route';
        
        /**
         * secure required
         */
        $route = $this->factory->newInstance(array(
            'path' => '/foo/bar/baz',
            'secure' => true,
        ));
        
        // correct
        $this->assertTrue($route->isMatch('/foo/bar/baz', array(
            'HTTPS' => 'on',
        )));
        
        // wrong path
        $this->assertFalse($route->isMatch('/zim/dib/gir', array(
            'HTTPS' => 'on',
        )));
        
        // not secure
        $this->assertFalse($route->isMatch('/foo/bar/baz', array(
            'HTTPS' => 'off',
        )));
        
        /**
         * not-secure required
         */
        $route = $this->factory->newInstance(array(
            'path' => '/foo/bar/baz',
            'secure' => false,
        ));
        
        // correct
        $this->assertTrue($route->isMatch('/foo/bar/baz', array(
            'HTTPS' => 'off',
        )));
        
        // secured when it should not be
        $this->assertFalse($route->isMatch('/foo/bar/baz', array(
            'HTTPS' => 'on',
        )));
        
        // wrong path
        $this->assertFalse($route->isMatch('/zim/dib/gir', array(
            'HTTPS' => 'off',
        )));
    }
    
    public function testIsCustomMatchWithClosure()
    {
        $type = 'Aura\Router\Route';
        
        $route = $this->factory->newInstance(array(
            'path' => '/foo/bar/baz',
            'is_match' => function($server, &$matches) {
                $matches['zim'] = 'gir';
                return true;
            },
        ));
        
        $actual = $route->isMatch('/foo/bar/baz', $this->server);
        $this->assertTrue($actual);
        $this->assertEquals('gir', $route->values['zim']);
        
        $route = $this->factory->newInstance(array(
            'path' => '/foo/bar/baz',
            'is_match' => function($server, $matches) {
                return false;
            },
        ));
        
        // even though path is correct, should fail because of the closure
        $this->assertFalse($route->isMatch('/foo/bar/baz', $this->server));
    }
    
    public function testIsCustomMatchWithCallback()
    {
        $type = 'Aura\Router\Route';
        
        $route = $this->factory->newInstance(array(
            'path' => '/foo/bar/baz',
            'is_match' => array($this, 'callbackForIsMatchTrue'),
        ));
        
        $actual = $route->isMatch('/foo/bar/baz', $this->server);
        $this->assertTrue($actual);
        $this->assertEquals('gir', $route->values['zim']);
        
        $route = $this->factory->newInstance(array(
            'path' => '/foo/bar/baz',
            'is_match' => array($this, 'callbackForIsMatchFalse'),
        ));
        
        // even though path is correct, should fail because of the closure
        $this->assertFalse($route->isMatch('/foo/bar/baz', $this->server));
    }
    
    public function callbackForIsMatchTrue(array $server, \ArrayObject $matches)
    {
        $matches['zim'] = 'gir';
        return true;
    }
    
    public function callbackForIsMatchFalse(array $server, \ArrayObject $matches)
    {
        return false;
    }
    
    /**
     * @expectedException \Aura\Router\Exception
     */
    public function testBadSubpattern()
    {
        $route = $this->factory->newInstance(array(
            'path' => '/{:controller}',
            'params' => array(
                // should open with a paren
                'controller' => '[a-zA-Z][a-zA-Z0-9_-]+',
            ),
        ));
    }
    
    public function testGenerate()
    {
        $route = $this->factory->newInstance(array(
          'path' => '/blog/{:id}/edit',
          'params' => array(
              'id' => '([0-9]+)',
          ),
        ));
        
        $uri = $route->generate(array('id' => 42, 'foo' => 'bar'));
        $this->assertEquals('/blog/42/edit', $uri);
    }
    
    public function testGenerateWithClosure()
    {
        $route = $this->factory->newInstance(array(
          'path' => '/blog/{:id}/edit',
          'params' => array(
              'id' => '([0-9]+)',
          ),
          'generate' => function($route, $data) {
              $data['id'] = 99;
              return $data;
          }
        ));
        
        $uri = $route->generate(array('id' => 42, 'foo' => 'bar'));
        $this->assertEquals('/blog/99/edit', $uri);
    }
    
    public function testGenerateWithCallback()
    {
        $route = $this->factory->newInstance(array(
          'path' => '/blog/{:id}/edit',
          'params' => array(
              'id' => '([0-9]+)',
          ),
          'generate' => array($this, 'callbackForGenerate'),
        ));
        
        $uri = $route->generate(array('id' => 42, 'foo' => 'bar'));
        $this->assertEquals('/blog/99/edit', $uri);
    }
    
    public function callbackForGenerate(\Aura\Router\Route $route, array $data)
    {
        $data['id'] = 99;
        return $data;
    }
    
    public function testIsMatchOnDefaultAndInlineSubpatterns()
    {
        $route = $this->factory->newInstance(array(
            'path' => '/{:controller}/{:action:(browse|read|edit|add|delete)}/{:id:(\d+)}{:format:(\..*)?}',
        ));
        
        $actual = $route->isMatch('/any-value/read/42', $this->server);
        $this->assertTrue($actual);
        $expect = array(
            'controller' => 'any-value',
            'action' => 'read',
            'id' => 42,
        );
        $this->assertEquals($expect, $route->values);
    }
    
    public function testIsNotRoutable()
    {
        $route = $this->factory->newInstance(array(
            'path' => '/foo/bar/baz',
            'values' => array(
                'controller' => 'zim',
                'action' => 'dib',
            ),
            'routable' => false,
        ));
        
        // right path
        $actual = $route->isMatch('/foo/bar/baz', $this->server);
        $this->assertFalse($actual);
        
        // wrong path
        $this->assertFalse($route->isMatch('/zim/dib/gir', $this->server));
    }
    
    public function testGenerateOnFullUri()
    {
        $route = $this->factory->newInstance(array(
            'name' => 'google-search',
            'path' => 'http://google.com/?q={:q}',
            'routable' => false,
            'path_prefix' => '/foo/bar', // SHOULD NOT show up
        ));
        
        $actual = $route->generate(array('q' => "what's up doc?"));
        $expect = "http://google.com/?q=what%27s+up+doc%3F";
        $this->assertSame($expect, $actual);
    }
}
