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
 * Defines how to handle deep cloning in this case
 * 
 * Use <code>DeepCloneHanlder::does()</code> to initiate the object and
 * use chaining to configure it:
 * 
 * <b>Examples</b>
 * <code>$handler = HandlerDefinition::does()->useDeepClone()</code>
 * Instructs the handler to use deep cloning in this case
 * <code>$handler = HandlerDefinition::does()->usePhpClone()</code>
 * Instructs the handler to use deep cloning in this case
 *
 * @author Andreas Prucha (Abexto - Helicon Software Development) <andreas.prucha@gmail.com>
 */
final class HandlerDefinition
{

    /**
     * What do do;
     */
    public $do = 0;

    /**
     * @var object|null|false Only valid with DO_USE
     */
    public $instance = false;

    /**
     * @var callable Callback to call. Only valid with DO_CALL 
     */
    public $func = null;

    /**
     * Constructor.
     * Not callable - use does() instead
     */
    protected function __construct($do = null)
    {
        if ($do !== null) {
            if ($do === 'clone') {
                $this->usePhpClone();
            } elseif ($do === false) {
                $this->useOriginal();
            } elseif ($do === true) {
                $this->useDeepClone();
            } elseif (is_callable($do)) {
                $this->useFunction($do);
            } elseif (is_object($do)) {
                $this->useInstance($do);
            } else {
                switch ($do) {
                    case \Amylian\DeepClone\DeepClone::DO_USE_INSTANCE:
                        throw new InvalidConfigurationException('Cannot configure this way. Pass instance to use or use \'HandlerDefinition::does()->useInstance($theInstnace)\'');
                        break;
                    case \Amylian\DeepClone\DeepClone::DO_CALL:
                        throw new InvalidConfigurationException('Cannot configure this way. Pass callable to use or use \'HandlerDefinition::does()->useFunction(function(){...})\'');
                        break;
                    case \Amylian\DeepClone\DeepClone::DO_CALL:
                        throw new InvalidConfigurationException('Cannot configure this way. Pass callable to use or use \'HandlerDefinition::does()->useFunction(function(){...})\'');
                        break;
                    default:
                        $this->do = $do;
                }
            }
        }
    }

    /**
     * Creates a new instance of the Handler.
     * 
     * The parameter $do can be used for quick configuration of the handler. 
     * Not all configurations are possible in this parameter. For more
     * complex configurations use chained calling. 
     * 
     * You can either use a shortcut (see {@see DeepClone}}, use use chained
     * calling (which is recommended:
     * 
     * <code>HandlerDefinition::does()->useOriginal()</code>: Keep the original instance.
     * <code>HandlerDefinition::does()->useDeepClone()</code>: Do deep cloning.
     * <code>HandlerDefinition::does()->usePhpClone()</code>: Use PHP clone op.
     * 
     * 
     * @param int|callable|true|false|objectstring A Shortcut
     * 
     */
    public static function does($do = null)
    {
        return new static($do);
    }

    public function useOriginal(): HandlerDefinition
    {
        $this->do = DeepClone::DO_KEEP;
    }

    /**
     * Instructs the handler to perform a PHP Clone
     */
    public function usePhpClone(): HandlerDefinition
    {
        $this->do = DeepClone::DO_PHP_CLONE_OR_KEEP;
        return $this;
    }

    public function useDeepClone(): HandlerDefinition
    {
        $this->do = DeepClone::DO_DEEP_CLONE;
        return $this;
    }

    /**
     * Instructs the handler to use the given instance
     * @param object|null $instanceToUse
     */
    public function useInstance($instanceToUse): HandlerDefinition
    {
        if ($instanceToUse !== null && !is_object($instanceToUse)) {
            throw new Exception\InvalidArgumentException(
                    'Method \'useInstance()\' expects an object as parameter \'\$instanceToUse\', but '.
                    gettype($instanceToUse).
                    ' given');
        }
        $this->do = DeepClone::DO_USE_INSTANCE;
        $this->instance = $instanceToUse;
        return $this;
    }

    /**
     * Instructs the handler to use a callable
     * 
     * The object to clone is passed in the first argument of the
     * callable. A instance of <code>ReflectionObject</code> in the 
     * second
     * 
     * The callable MUST return the value to use in the clone
     * 
     * @param callable $func
     */
    public function useFunction($func): HandlerDefinition
    {
        if (is_callable($func)) {
            throw new InvalidArgumentException('useFunction() expects a valid callable as parameter');
        }
        $this->do = DeepClone::DO_CALL;
        $this->func = $func;
        return $this;
    }

    /**
     * Instructs the handler to throw an exception
     * @return \Amylian\DeepClone\HandlerDefinition
     */
    public function throwException(): HandlerDefinition
    {
        $this->do = DeepClone::DO_THOROW_EXCEPTION;
        return $this;
    }

}
