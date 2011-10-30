<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\SecurityExtraBundle\Annotation;

use JMS\SecurityExtraBundle\Exception\InvalidArgumentException;

/**
 * Annotation for expression-based access control.
 *
 * @Annotation
 * @Target("METHOD")
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
final class PreAuthorize
{
    /**
     * @Required
     * @var string
     */
    public $expr;

    public function __construct()
    {
        if (0 === func_num_args()) {
            return;
        }
        $values = func_get_arg(0);

        if (isset($values['value'])) {
            $values['expr'] = $values['value'];
        }
        if (!isset($values['expr'])) {
            throw new InvalidArgumentException('The "expr" attribute must be set for annotation @PreAuthorize.');
        }

        if (!is_string($values['expr'])) {
            throw new InvalidArgumentException(sprintf('The "expr" attribute of annotation @PreAuthorize must be a string, but got "%s".', gettype($values['expr'])));
        }

        $this->expr = $values['expr'];
    }
}