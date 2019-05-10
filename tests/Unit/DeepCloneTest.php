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
 * @author Andreas Prucha, Abexto - Helicon Software Development <andreas.prucha@gmail.com>
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
    
    public function testPropertiesAreCloned()
    {
        $foo = new \Amylian\DeepClone\Testing\Misc\Foo();
        $bar = new \Amylian\DeepClone\Testing\Misc\Bar($foo);
        $foo->setBar($bar);
        $this->assertSame($foo, $bar->getFoo());
        
        $barCopy = \Amylian\Utils\DeepClone::of($bar);
        $this->assertNotSame($bar, $barCopy);
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
        
        $barCopy = \Amylian\Utils\DeepClone::of($bar);
        
        $this->assertSame($barCopy, $barCopy->getFoo()->getBar());
    }
    
    public function testCloneFinalClassWithPrivateConstructorAndUndefinedProperty()
    {
        $o = \Amylian\DeepClone\Testing\Misc\FinalClassWithPrivateConstructor::create('yes');
        $cp = \Amylian\Utils\DeepClone::of($o);
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
                return $s.'Result';
            },
            'aReflection' => new \ReflectionClass($this),
        ];
            
        $originalArray['aFinalClassWithPrivateConstructor'] = \Amylian\DeepClone\Testing\Misc\FinalClassWithPrivateConstructor::create('yes');
        
        $this->assertSame($originalArray['foo1'], $foo1);
        $this->assertSame($originalArray['foo1'], $originalArray['secondFoo1']);
        $this->assertSame('closureResult', $originalArray['aClosure']('closure'));
        $this->assertTrue(isset($originalArray['aFinalClassWithPrivateConstructor']->value));
        $this->assertSame('yes', $originalArray['aFinalClassWithPrivateConstructor']->getValue());
        $arrayCopy = \Amylian\Utils\DeepClone::of($originalArray);
        
        $this->assertNotSame($arrayCopy['foo1'], $foo1);
        $this->assertSame($arrayCopy['foo1'], $arrayCopy['secondFoo1']);
        
        $this->assertSame(666, $arrayCopy['aInt']);
        $this->assertSame('teststring', $arrayCopy['aString']);
        $this->assertSame('closureResult', $arrayCopy['aClosure']('closure'));
        $this->assertTrue(isset($arrayCopy['aFinalClassWithPrivateConstructor']->value));
        $this->assertSame('yes', $arrayCopy['aFinalClassWithPrivateConstructor']->getValue());
        
    }
    
}
