<?php

/*
 * BSD 3-Clause License
 * 
 * Copyright (c) 2019, Abexto - Helicon Software Development / Amylian Project
 *  
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 
 * * Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 * 
 * * Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 * 
 * * Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 */

namespace Amylian\DeepClone\Testing\Unit;

/**
 * Description of DeepCloneTest
 *
 * @author Andreas Prucha (Abexto - Helicon Software Development) <andreas.prucha@gmail.com>
 */
class DeepCloneTest extends \PHPUnit\Framework\TestCase
{

    protected function getPropertyValue($object, $propertyName)
    {
        $objectReflection = new \ReflectionClass($object);
        $propertyReflection = $objectReflection->getProperty($propertyName);
        $propertyReflection->setAccessible(true);
        return $propertyReflection->getValue($object);
    }

    public function testWithStdClass()
    {
        $x = new \ReflectionObject($this);
        $barCopy = \Amylian\DeepClone\DeepClone::of($x)
                ->onError(\Amylian\DeepClone\DeepClone::DO_THOROW_EXCEPTION)
                ->create();
        $o = new \stdClass();
        $o->aProperty = new \StdClass();
        $o->aProperty->aValue = 'value';
        $this->assertSame('value', $o->aProperty->aValue);
        $c = \Amylian\DeepClone\DeepClone::copy($o);
        $this->assertNotSame($o, $c);
        $this->assertNotSame(spl_object_hash($o), spl_object_hash($c));
        $this->assertSame('value', $c->aProperty->aValue);
        $this->assertEquals($o, $c);
    }

    public function testPropertiesAreCloned()
    {
        $foo = new \Amylian\DeepClone\Testing\Misc\Foo();
        $bar = new \Amylian\DeepClone\Testing\Misc\Bar($foo);
        $foo->setBar($bar);
        $this->assertSame($foo, $bar->getFoo());

        $barCopy = \Amylian\DeepClone\DeepClone::of($bar)
                ->onError(\Amylian\DeepClone\DeepClone::DO_THOROW_EXCEPTION)
                ->create();
        $this->assertNotSame($bar, $barCopy);
        $this->assertEquals($bar, $barCopy);
        $this->assertInstanceOf(\Amylian\DeepClone\Testing\Misc\Bar::class, $barCopy);
        $this->assertNotSame($foo, $barCopy->getFoo());
        $this->assertInstanceOf(\Amylian\DeepClone\Testing\Misc\Foo::class, $barCopy->getFoo());
    }

    public function testCrossReferencesGetSameInstance()
    {
        $foo = new \Amylian\DeepClone\Testing\Misc\Foo();
        $bar = new \Amylian\DeepClone\Testing\Misc\Bar($foo);
        $foo->setBar($bar);
        $this->assertSame($foo, $bar->getFoo());

        $barCopy = \Amylian\DeepClone\DeepClone::of($bar)->create();

        $this->assertSame($barCopy, $barCopy->getFoo()->getBar());
        $this->assertEquals($bar, $barCopy);
    }

    public function testCloneFinalClassWithPrivateConstructorAndUndefinedProperty()
    {
        $o = \Amylian\DeepClone\Testing\Misc\FinalClassWithPrivateConstructor::create('yes');
        $cp = \Amylian\DeepClone\DeepClone::of($o)->create();
        $this->assertEquals($o, $cp);
    }

    public function testCloningArrayWithMixedValues()
    {
        $foo1 = new \Amylian\DeepClone\Testing\Misc\Foo();
        $bar1 = new \Amylian\DeepClone\Testing\Misc\Bar($foo1);
        $foo1->setBar($bar1);
        $foo2 = new \Amylian\DeepClone\Testing\Misc\Foo();
        $bar2 = new \Amylian\DeepClone\Testing\Misc\Bar($foo2);
        $foo2->setBar($bar2);
        $originalArray = [
            'foo1' => $foo1,
            'bar1' => $bar1,
            'foo2' => $foo2,
            'bar2' => $bar2,
            'aNull' => null,
            'aInt' => 666,
            'aString' => 'teststring',
            'secondFoo1' => $foo1,
            'secondFoo2' => $foo2,
            'aClosure' => function($s) {
                return $s . 'Result';
            },
            'aReflection' => new \ReflectionClass($this),
        ];

        $originalArray['aFinalClassWithPrivateConstructor'] = \Amylian\DeepClone\Testing\Misc\FinalClassWithPrivateConstructor::create('yes');

        $this->assertSame($originalArray['foo1'], $foo1);
        $this->assertSame($originalArray['foo1'], $originalArray['secondFoo1']);
        $this->assertSame('closureResult', $originalArray['aClosure']('closure'));
        $this->assertTrue(isset($originalArray['aFinalClassWithPrivateConstructor']->value));
        $this->assertSame('yes', $originalArray['aFinalClassWithPrivateConstructor']->getValue());
        $arrayCopy = \Amylian\DeepClone\DeepClone::of($originalArray)->create();

        $this->assertNotSame($arrayCopy, $originalArray);
        $this->assertEquals($arrayCopy, $originalArray);

        $this->assertNotSame($arrayCopy['foo1'], $foo1);
        $this->assertSame($arrayCopy['foo1'], $arrayCopy['secondFoo1']);

        $this->assertSame(666, $arrayCopy['aInt']);
        $this->assertSame('teststring', $arrayCopy['aString']);
        $this->assertSame('closureResult', $arrayCopy['aClosure']('closure'));
        $this->assertTrue(isset($arrayCopy['aFinalClassWithPrivateConstructor']->value));
        $this->assertSame('yes', $arrayCopy['aFinalClassWithPrivateConstructor']->getValue());
    }

    public function testCopyStaticShortcut()
    {
        $foo = new \Amylian\DeepClone\Testing\Misc\Foo();
        $bar = new \Amylian\DeepClone\Testing\Misc\Bar($foo);
        $foo->setBar($bar);
        $this->assertSame($foo, $bar->getFoo());

        $barCopy = \Amylian\DeepClone\DeepClone::copy($bar);
        $this->assertNotSame($bar, $barCopy);
        $this->assertEquals($bar, $barCopy);
        $this->assertInstanceOf(\Amylian\DeepClone\Testing\Misc\Bar::class, $barCopy);
        $this->assertNotSame($foo, $barCopy->getFoo());
        $this->assertInstanceOf(\Amylian\DeepClone\Testing\Misc\Foo::class, $barCopy->getFoo());
    }

    public function testExceptionOnInternalObject()
    {
        $this->expectException(\ReflectionException::class);
        $o = new \StdClass();
        $o->func = function() {
            return true;
        };
        $c = \Amylian\DeepClone\DeepClone::of($o)
                ->onInternalObject(\Amylian\DeepClone\DeepClone::DO_DEEP_CLONE)
                ->onError(\Amylian\DeepClone\DeepClone::DO_THOROW_EXCEPTION)
                ->create();
        $this->assertNotSame($c, $o);
    }
    
    public function testSameInstanceOfClosure()
    {
        $o = new \StdClass();
        $o->func = function() {
            return true;
        };
        $c = \Amylian\DeepClone\DeepClone::of($o)
                ->onInternalObject(\Amylian\DeepClone\DeepClone::DO_PHP_CLONE_OR_KEEP)
                ->onError(\Amylian\DeepClone\DeepClone::DO_THOROW_EXCEPTION)
                ->create();
        $this->assertNotSame($c, $o);
        $this->assertSame($c->func, $o->func);
    }
    

}
