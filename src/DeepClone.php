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

namespace Amylian\DeepClone;

/**
 * Creates a DeepClone of a object
 * 
 * The 
 *
 * @author Andreas Prucha, Abexto - Helicon Software Development <andreas.prucha@gmail.com>
 */
class DeepClone
{

    /**
     * Applies to the class or interface including subclasses
     */
    const IS_INSTANCEOF = 0x00;

    /**
     * Applies to of objects of exactly this class
     */
    const IS_SAME = 0x01;

    /**
     * Retains the original instance (no cloning at all)
     */
    const DO_KEEP = 0x00;

    /**
     * Deep Cloning is done on the object
     */
    const DO_DEEP_CLONE = 0x10;

    /**
     * Php Cloning is used on the object (clone $source)
     */
    const DO_PHP_CLONE = 0x20;

    /**
     * Use the specified instance
     */
    const DO_USE_INSTANCE = 0x30;
    
    /**
     * Use a callable for cloning
     */
    const DO_CALL = 0x40;

    /**
     * An exception is thrown
     */
    const DO_THOROW_EXCEPTION = 0xF0;
    
    protected const DO_MASK = 0xF0;
    protected const IS_MASK = 0x0F;

    protected $_onInternalObject = self::DO_PHP_CLONE;
    protected $_onRetry = self::DO_PHP_CLONE;
    protected $_onError = self::DO_THOROW_EXCEPTION;
    protected $_onClass = [];
    protected $_onObject = [];

    protected $source = null;

    /**
     * Defines how to handle specific object instances
     * @var type 
     */
    protected $onObject = [];

    public function __construct($source)
    {
        $this->source = $source;
    }

    protected function validateHandler($handler, $callingMethod = '', $usedArgument = '')
    {
        if (!is_int($do) && !is_callable($do)) {
            $message = '';
            if ($callingMethod) {
                $message = 'Invalid Argument passed to ' . $callingMethod . ' ';
                if ($usedArgument) {
                    $message .= " (Argument '$usedArgument') ";
                }
            }
            $message .= '- Must be a valid DeepCopy::Xxx value or a valid callable.';
            throw new InvalidArgumentException($message);
        }
    }

    /**
     * Adds a special handler for a class
     * 
     * @param string|object $className Class name of objects to handle
     * @param int|DeepCloneHandler $do Handler. See Class Description of {@see DeepClone}
     * @param int $is Matching mode {@see DeepClone::IS_INSTANCEOF} or {@see DeepClone::IS_SAME}
     * @return \Amylian\Utils\DeepClone
     */
    public function onClass(string $className, $do, $is = self::IS_INSTANCEOF): DeepClone
    {
        $this->validateHandler($do, __METHOD__, '$do');
        $this->_onClass[$className] = ['do' => $do, 'is' => $is];
    }

    /**
     * Defines a special handler for a concrete instance
     * 
     * @param object $object The object to handle
     * @param type $do What to do {@see DeepCopy}
     * @param false|object|null $instance If set to <code>false</code>, 
     */
    public function onObject(object $object, $do = self::DO_KEEP, $instance = false)
    {
        $this->validateHandler($do, __METHOD__, '$do');
        $oid = spl_object_id($object);
        $this->_onObject[$oid] = ['do' => $do, 'instance' => ($instance !== false) ? $instance : $object];
    }

    /**
     * Defines how internal objects shall be handled by default
     * 
     * Internal objects are instances of classes defined by PHP itself. 
     * Initiation by refelection (which DeepClone does) fails on
     * some of these classes. Thus it's recommended to use PHP-Clone 
     * for such internal objects
     * 
     * If callable is given it's called.
     * 
     * @param int|callable $handler A DO_Xxx - constant or callable. {@see DeepClone}
     */
    public function onInternalObject($handler = self::DO_PHP_CLONE)
    {
        $this->_onInternalObject = $handler;
    }

    /**
     * Defines how to handle problems when cloning an object
     * 
     * @param int|callable $handler A DO_Xxx - constant or callable. {@see DeepClone}
     */
    public function onRetry($handler = self::DO_PHP_CLONE)
    {
        $this->_onRetry = $handler;
    }

    /**
     * Defines what to do when the prefered cloning method and the fallback fails
     * 
     * @param int|callable $handler
     */
    public function onError($handler = self::DO_THOROW_EXCEPTION)
    {
        $this->_onError = $handler;
    }

    protected function cloneArray(array $sourceArray): array
    {
        $result = [];
        foreach ($sourceArray as $k => $v) {
            $result[$k] = $this->cloneAny($v);
        }
        return $result;
    }

    protected function doDeepClone($sourceObject, ?\ReflectionObject $sourceObjectReflection)
    {
        
    }

    protected function doHandlerDepending($sourceObject, array $handling = [])
    {
        $handler = $handling['do'] ?? static::DO_DEEP_CLONE;
        $sourceObjectReflection = $handling['sourceObjectReflection'] ?? new \ReflectionObject($sourceObject);
        if (is_callable($handler)) {
            return call_user_func($handler, $sourceObject, $sourceObjectReflection);
        } else {
            switch ($handler & static::DO_MASK) {
                case static::DO_DEEP_CLONE: {
                        return $this->doDeepClone($sourceObject, $sourceObjectReflection);
                    }
                case static::DO_PHP_CLONE: {
                        return clone $sourceObject;
                    }
                case static::DO_KEEP: {
                        return $sourceObject;
                    }
                case static::DO_USE_INSTANCE: {
                        return $handling['instance'];
                    }
                case static::DO_THOROW_EXCEPTION: {
                        if (isset($exceptionObject))
                            throw $exceptionObject;
                    }
                default: return $sourceObject;
            }
        }
    }

    protected function cloneObject(object $sourceObject)
    {
        $sourceOid = spl_object_id($sourceObject);

        // Check if we already met this object instance - if yes, reuse the result
        $knownObjectHandler = $this->_onObject[$sourceOid] ?? null;

        $sourceObjectReflection = new ReflectionObject($sourceObject);

        try {
            $objectHandlingDefinition = $this->_onObject[$sourceOid] ?? null;
            if ($objectHandlingDefinition !== null) {
                return $this->doHandlerDepending($objectHandlingDefinition['do'], $sourceObject, $reflectonObject);
            }

            foreach ($this->_onClass as $className => $classHandlingDefinition) {
                switch ($classHandlingDefinition['is'] ?? static::IS_INSTANCEOF) {
                    case self::IS_SAME: {
                            $matches = (get_class($sourceObject) == $className);
                            break;
                        }
                    default: {
                            $matches = ($sourceObject instanceof $className);
                        }
                }
                if ($matches) {
                return $this->doHandlerDepending($sourceObject, $classHandlingDefinition);
                }
            }
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }





        $onObjectEntry = $this->onObject[$sourceOid] ?? null;
        if (is_array($onObjectEntry)) {
            $done = $onObjectEntry['done'] ?? false;
            if ($done) {
                return $sourceObject;
            }
        }

        if (isset($this->knownObjects[$sourceOid])) {
            return $this->knownObjects[$sourceOid]; // Alrady cloned ==> RETURN known clone
        };

        foreach ($this->excludedClasses as $excludedClass => $mode) {
            if ($mode & self::IS_SAME === self::IS_SAME) {
                $matches = get_class($sourceObject) == $excludedClass;
            } else {
                $matches = $sourceObject instanceof $excludedClass;
            };
            if ($matches) {
                if ($mode & self::DO_PHP_CLONE === self::DO_PHP_CLONE) {
                    return clone $sourceObject;
                } else {
                    return $sourceObject;
                }
            }
        }

        $sourceOid = spl_object_id($sourceObject);
        $objectReflection = new \ReflectionObject($sourceObject);
        $result = $objectReflection->newInstanceWithoutConstructor();
        $this->knownObjects[$sourceOid] = $result;

        $sourcePropertyReflections = $objectReflection->getProperties();
        foreach ($sourcePropertyReflections as $propertyReflection) {
            /* @var $propertyReflection \ReflectionProperty */
            $propertyReflection->setAccessible(true);
            $propertyReflection->setValue($result, $this->cloneAny($propertyReflection->getValue($sourceObject)));
        }
        return $result;
    }

    protected function cloneAny($thing)
    {
        if (is_object($thing)) {
            return $this->cloneObject($thing);
        } elseif (is_array($thing)) {
            return $this->cloneArray($thing);
        } else {
            return $thing;
        }
    }

    /**
     * Creates a deep clone of the source
     * @return mixed The clone
     */
    public function create()
    {
        return $this->cloneAny($this->source);
    }

    /**
     * Creates instance of the deep clone with source set.
     * 
     * This method does <b>node</b> create a clone by itself.
     * You need to call {@see DeepClone::create()} to finally create 
     * the clone. 
     * 
     * Example:
     * 
     * <code>$cloned = DeepClone::of($original)->create();
     * 
     * See class description of {@see DeepClone} for more examples
     * 
     * @param mixed $source The object to clone
     * @param array $exclude Array of instances of objects or classes to exclude
     * @see DeepClone
     */
    public static function of($source): DeepClone
    {
        $instance = new static($source);
        $instance->exclude($exclude);
        return $instance->create();
    }

}
