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
 * <code>$handler = DeepCloneHandler::does()->useDeepClone()</code>
 * Instructs the handler to use deep cloning in this case
 * <code>$handler = DeepCloneHandler::does()->usePhpClone()</code>
 * Instructs the handler to use deep cloning in this case
 * 
 *
 * @author Andreas Prucha, Abexto - Helicon Software Development <andreas.prucha@gmail.com>
 */
final class DeepCloneHandler
{

    /**
     * Constructor.
     * Not callable - use does() instead
     */
    protected function __construct($do = null)
    {
        if (is_int($do)) {
            switch ($do) {
                case \Amylian\DeepClone\DeepClone::DO_DEEP_CLONE: $this->useDeepClone();
                    break;
                case \Amylian\DeepClone\DeepClone::DO_PHP_CLONE: $this->usePhpClone();
                    break;
                case \Amylian\DeepClone\DeepClone::DO_KEEP: $this->useOriginal();
                    break;
                case \Amylian\DeepClone\DeepClone::DO_KEEP: $this->useOriginal();
                    break;
                default:
                    throw new InvalidArgumentException('Preconfiguration with this type is not possible. Use configuration methods to configure');
            }
        } else {
            if (is_callable($do)) {
                $this->useCallback($do);
            } else {
               throw new InvalidArgumentException('Preconfiguration of DeepCloneHandler failed. Valid Handling Id or Callable expected.');
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
     * Valid values of <code>$do</code> are:
     * <code>null</code>: No quick configuration are done
     * <code>{@see DeepClone::DO_DEEP_CLONE}</code>: Use deep cloning (default). {@see DeepCloneHandler::useDeepClone()}
     * <code>{@see DeepClone::DO_PHP_CLONE}</code>: Use PHPs clone. {@see DeepCloneHandler::usePhpClone()}
     * <code>{@see DeepClone::DO_KEEP}</code>: Do not clone - keep the original. {@see DeepCloneHandler::useOriginal()}
     * <code>{@see DeepClone::DO_THROW_EXCEPTION}</code>: Throws an exception. {@see DeepCloneHandler::throwException()}. 
     * <code>{@see callable}</code>: A valid callable. {@see DeepCloneHandler::useFunction()}. 
     * 
     * @param int|callable
     * 
     */
    public static function does($do = null)
    {
        return new static($do);
    }

    /**
     * What do do (Deep-Cloning is done by default)
     */
    public $do = DeepClone::DO_DEEP_CLONE;

    /**
     * @var object|null|false Only valid with DO_USE
     */
    public $instance = false;

    /**
     * @var callable Callback to call. Only valid with DO_CALL 
     */
    public $func = null;

    public function useOriginal(): DeepCloneHandler
    {
        $this->do = DeepClone::DO_KEEP;
    }

    /**
     * Instructs the handler to perform a PHP Clone
     */
    public function usePhpClone(): DeepCloneHandler
    {
        $this->do = DeepClone::DO_PHP_CLONE;
        return $this;
    }

    public function useDeepClone(): DeepCloneHandler
    {
        $this->do = DeepClone::DO_DEEP_CLONE;
        return $this;
    }

    /**
     * Instructs the handler to use the given instance
     * @param object|null $instanceToUse
     */
    public function useInstance(?object $instanceToUse): DeepCloneHandler
    {
        $this->do = DeepClone::DO_USE_INSTANCE;
        $this->instance = object;
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
    public function useFunction($func): DeepCloneHandler
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
     * @return \Amylian\DeepClone\DeepCloneHandler
     */
    public function throwException(): DeepCloneHandler
    {
        $this->do = DeepClone::DO_THOROW_EXCEPTION;
        return $this;
    }

}
