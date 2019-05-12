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
 * Creates a clone of a object. Additinally to PHPs own clone assignment
 * it also clones object instances of in member variables. DeepClone
 * takes care about multiple usage of an instance in the source 
 * and also uses the same cloned instance in the copy. In other word
 * it retains object references.
 * 
 * In most cases DeepClone does not require any special configuration.
 * The most straight-forward way to use DeepClone is
 * 
 * <code>$clonedInstance = DeepClone::copy($sourceInstance);</code>
 * 
 * It performs a deep clone copy using default configurations. 
 * 
 * Deep Clone performs the following steps:
 * 
 * <ol>
 *   <li>Checks, if a object instance in the source tree needs special handling. By default all
 *   object instances in the source are deep-cloned. Special object handling can be 
 *   configured using {@see DeepClone::onObject()}</li>
 *   <li>Checks if a class needs special handling. By default all classes are deep cloned.
 *   Special handling can be configured with {@see DeepClone::onClass()}.</li>
 *   <li>Performs a deep clone of the object instance by default</li>
 *   <li>If the first attempt fails (which is usually deep cloing), it tries another way
 *   to clone. By default it tries again using php's clone. The error handling
 *   can be configured using {@see DeepClone::onError()}
 * </ol>
 * 
 * All configuration methods return the instance of <code>DeepClone</clone> and can be chained.
 * 
 * 
 * <b>Handling of special cases</b>
 * 
 * It's possible to define special handlers for specific object instances, classes, 
 * types of classes or events.
 * 
 * Handling handling of the following cases can be configured:
 * 
 * <ul>
 *   <li><b>{@see DeepClone::onClass()}</b>: Takes an associative array of class-names => handlers.
 *   All objects in the cloned objet tree which are an instance of the specified class are handled
 *   this way.
 *   </li>  
 *   <li><b>{@see DeepClone::onObject()}</b>: You can specify a instance and how to handle it.</li> 
 *   <li><b>{@see DeepClone::onError()}</b>: Specifies what to do if the standard attempt of cloning
 *   failes. By default a second attempt with php's own clone is made</li> 
 * </ul>
 * 
 * <b>Declaring what to do</b>
 * 
 * You can either use {@see @HandlerDefinition} to configure the handler or use one
 * of the magic shortcuts:
 * 
 * <ul>
 *    <li>
 *      <b><code>false</code></b>: Is a shortcut to <code>HandlerDefinition::does()->useOriginal();</code>. 
 *      In this case the original instance is kept.
 *    </li>
 *    <li>
 *       <b><code>true</code></b>: Is a shortcut to <code>HandlerDefinition::does()->useDeepClone();</code>
 *       Deep Cloning is used for the object. This is the default for most objects.
 *    </li>
 *    <li>
 *       <b><code>object<code>: If a object is passed, this object is used. 
 *       This is a shortcut to <code>HandlerDefinition::does()->useInstance();</code>
 *    </li>
 *    <li>
 *       <b><code>callable</code></b>: If a callable is given, the callable is called. 
 *       The first argument it has to take is the object to clone and the second 
 *       parameter an instance of {@see \ReflectionObject}. The function MUST
 *       return the instance to use in the cloned object.
 *       This is the shortcut to <code>HandlerDefinition::does()->useFunction();</code>
 *    </li>
 *    <li>
 *       If you want to use Php's own clone function you can pass the string
 *       'clone', which is a shortcut to <code>HandlerDefinition::does()->usePhpClone();</code>
 *    </li>
 * </ul>
 * 
 *
 * @author Andreas Prucha (Abexto - Helicon Software Development) <andreas.prucha@gmail.com>
 */
class DeepClone
{

    /**
     * Retains the original instance (no cloning at all)
     */
    const DO_KEEP = 0x01;

    /**
     * Deep Cloning is done on the object
     */
    const DO_DEEP_CLONE = 0x02;

    /**
     * Php Cloning is used on the object if possible. If it's not clonable
     * the original instance is used in the clone as well.
     */
    const DO_PHP_CLONE_OR_KEEP = 0x03;

    /**
     * Php Cloning is used on the object if possible. If it's not clonable
     * the the property is set to null in the destination.
     */
    const DO_PHP_CLONE_OR_SET_NULL = 0x04;

    /**
     * Use the specified instance
     */
    const DO_USE_INSTANCE = 0x05;

    /**
     * Use a callable for cloning
     */
    const DO_CALL = 0x06;

    /**
     * An exception is thrown
     */
    const DO_THOROW_EXCEPTION = 0x0F;

    protected $_onInternalObject = self::DO_PHP_CLONE_OR_KEEP;

    /**
     * @var HandlerDefinition What to do in case of error (Default: Retry with PHP-Clone 
     */
    protected $_onError = null;

    /**
     * @var array ClassName => Handler: What to do with instances of specified classes 
     */
    protected $_onClass = [];

    /**
     * @var array Object-ID => Handler: What to do with these instances. Also used to
     * rember already known instances
     */
    protected $_onObject = [];
    protected $source = null;

    /**
     * Defines how to handle specific object instances
     * @var type 
     */
    protected $onObject = [];
    
    /**
     * @var type Use spl_object_id instead of spl_object_hash
     */
    protected $useSplObjectId = true;

    public function __construct($source)
    {
        $this->source = $source;

        // Set default configuration
        
        $this->useSplObjectId = function_exists('\spl_object_id');

        $this->onInternalObject(static::DO_PHP_CLONE_OR_KEEP);
        $this->onError(static::DO_PHP_CLONE_OR_KEEP);
    }

    protected function preparHandler($do): HandlerDefinition
    {
        if ($do instanceof HandlerDefinition) {
            if ($do->do) {
                return $do;
            } else {
                throw new InvalidConfigurationException('DeepClone Handler is not configured');
            }
        } else {
            return HandlerDefinition::does($do);
        }
    }
    
    /**
     * Returns the object ID (or hash in older versions of PHP)
     * @param object $obj
     * @return mixed
     */
    private function getObjectId($obj)
    {
        return $this->useSplObjectId ? spl_object_id($obj) : spl_object_hash($obj);
    }

    /**
     * Adds a special handler for onre or more classes
     * 
     * @param array $do ClassName => Handler
     * @return \Amylian\Utils\DeepClone
     */
    public function onClass(array $do = []): DeepClone
    {
        foreach ($do as $className => $handling) {
            $this->_onClass[$className] = $this->preparHandler($handling);
        }
        return $this;
    }

    /**
     * Defines a special handler for a concrete instance
     * 
     * @param object $obj The object to handle
     * @param HandlerDefinition|object|int {@see DeepCopy}
     */
    public function onObject($obj, $do): DeepClone
    {
        $oid = $this->getObjectId($obj);
        if ($do !== null) {
            $this->_onObject[$oid] = $this->preparHandler($do);
        } else{
            unset($this->_onObject[$oid]);
        }
        return $this;
    }

    /**
     * Defines how to handle internal PHP classes.
     * 
     * @param HandlerDefinition|int|true|false|callable|object $do How to handle the object
     * @return \Amylian\DeepClone\DeepClone
     */
    public function onInternalObject($do): DeepClone
    {
        $this->_onInternalObject = $this->preparHandler($do);
        return $this;
    }

    /**
     * Defines how to handle problems when cloning an object
     * 
     * @param DeepCloneHadler|callable|object|int
     * @return DeepClone This object
     */
    public function onError($do): DeepClone
    {
        $this->_onRetry = $this->preparHandler($do);
        return $this;
    }

    protected function cloneArray(array $sourceArray): array
    {
        $result = [];
        foreach ($sourceArray as $k => $v) {
            $result[$k] = $this->cloneAny($v);
        }
        return $result;
    }

    private function doDeepClone($sourceObject, ?\ReflectionObject $sourceObjectReflection)
    {
        $result = $sourceObjectReflection->newInstanceWithoutConstructor();

        try {
            // We need to register the object here already to 
            // keep cross references in the object tree and prevent 
            // infinite recursion. The reference is just removed in case
            // of an exception
            $oldObjectHandler = $this->getOnObjectHandler($sourceObject);
            $this->onObject($sourceObject, HandlerDefinition::does()->useInstance($result));

            $resultObjectReflection = new \ReflectionObject($result);

            $sourcePropertyReflections = $sourceObjectReflection->getProperties();
            foreach ($sourcePropertyReflections as $sourcePropertyReflection) {
                /* @var $sourcePropertyReflection \ReflectionProperty */
                $sourcePropertyReflection->setAccessible(true);

                try {
                    $resultPropertyReflection = $sourceObjectReflection->getProperty($sourcePropertyReflection->getName());
                } catch (\ReflectionException $e) {
                    $resultPropertyReflection = null;
                }

                if ($resultPropertyReflection !== null) {
                    $resultPropertyReflection->setAccessible(true);
                    $resultPropertyReflection->setValue($result, $this->cloneAny($sourcePropertyReflection->getValue($sourceObject)));
                } else {
                    $result->{$sourcePropertyReflection->getName()} = $this->cloneAny($sourcePropertyReflection->getValue($sourceObject));
                }
            }
        } catch (\Exception $e) {
            // obviously something went terribly wrong - we cannot
            // continue using this cloned instance
            $this->onObject($sourceObject, $oldObjectHandler);
            // Throw the catched exception again
            throw $e;
        }
        return $result;
    }
    
    /**
     * Returns the Handler of a object instance
     * 
     * @param object $objtInstance
     * @return null|\Amylian\DeepClone\HandlerDefinition A handler if set (or null)
     */
    protected function getOnObjectHandler($objtInstance): ?HandlerDefinition
    {
        return $this->_onObject[$this->getObjectId($objtInstance)] ?? null;
    }

    /**
     * Redirects to the correct handler 
     * 
     * @param object $sourceObject
     * @param \Amylian\DeepClone\HandlerDefinition $handler
     * @param \ReflectionObject $sourceObjectReflection
     * @param type $handledException
     * @return object
     * @throws \Amylian\DeepClone\Exception\InvalidConfigurationException
     */
    protected function doHandlerDepending($sourceObject, HandlerDefinition $handler, \ReflectionObject $sourceObjectReflection, $handledException = null)
    {
        $sourceObjectReflection = $sourceObjectReflection ?? new \ReflectionObject($sourceObject);
        switch ($handler->do) {
            case static::DO_CALL: {
                    return call_user_func($handler->func, $sourceObject, $sourceObjectReflection);
                }
            case static::DO_DEEP_CLONE: {
                    return $this->doDeepClone($sourceObject, $sourceObjectReflection);
                }
            case static::DO_PHP_CLONE_OR_KEEP: {
                    if ($sourceObjectReflection->isCloneable())
                        return clone $sourceObject;
                    else
                        return $sourceObject;
                }
            case static::DO_PHP_CLONE_OR_SET_NULL: {
                    if ($sourceObjectReflection->isCloneable())
                        return clone $sourceObject;
                    else
                        throw new Exception\DeepCloningFailedException('Deep cloning failed');
                }
            case static::DO_KEEP: {
                    return $sourceObject;
                }
            case static::DO_USE_INSTANCE: {
                    return $handler->instance;
                }
            case static::DO_THOROW_EXCEPTION: {
                    if (isset($handledException))
                        throw $handledException;
                    else
                        throw \Exception('Could not clone');
                }
            default: {
                    throw new \Amylian\DeepClone\Exception\InvalidConfigurationException('Invalid handler type: ' . $handler->do);
                }
        }
    }

    protected function cloneObject($sourceObject)
    {
        $sourceObjectReflection = new \ReflectionObject($sourceObject);

        // Check if we have a special handler for the object

        $knownObjectHandler = $this->getOnObjectHandler($sourceObject);
        if ($knownObjectHandler !== null) {
            return $this->doHandlerDepending($sourceObject, $knownObjectHandler, $sourceObjectReflection);
        }

        try {

            // Check if we have a special handler for the class

            foreach ($this->_onClass as $className => $classHandlingDefinition) {
                if ($sourceObject instanceof $className) {
                    return $this->doHandlerDepending($sourceObject, $classHandlingDefinition, $sourceObjectReflection);
                }
            }

            // Cloning of internal php objects
            if ($sourceObjectReflection->isInternal()) {
                return $this->doHandlerDepending($sourceObject, $this->_onInternalObject, $sourceObjectReflection);
            }


            // No special case - do Deep Cloning

            return $this->doDeepClone($sourceObject, $sourceObjectReflection);
        } catch (\Exception $e) {

            // We had any kind of error - retry

            return $this->doHandlerDepending($sourceObject, $this->_onRetry, $sourceObjectReflection, $e);
        }
    }

    protected function cloneAny($thing)
    {
        if ($thing === null) {
            return null;
        } elseif (is_object($thing)) {
            $result = $this->cloneObject($thing);
            $this->onObject($thing, HandlerDefinition::does()->useInstance($result));
            return $result;
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
     * <code>$cloned = DeepClone::of($original)->create();</code>
     * 
     * See class description of {@see DeepClone} for more examples
     * 
     * @param mixed $source The object to clone
     * @param array $exclude Array of instances of objects or classes to exclude
     * @see DeepClone
     */
    public static function of($source): DeepClone
    {
        return new static($source);
    }

    /**
     * Deep clones the source
     * 
     * Performs a deep cloning with default options, 
     * 
     * It is a shortcut of <code>DeepClone::of($source)->create();</code>.
     * 
     * @param mixed $source
     * @return mixed
     */
    public static function copy($source)
    {
        return static::of($source)->onError(DeepClone::DO_PHP_CLONE_OR_KEEP)->create();
    }

}
