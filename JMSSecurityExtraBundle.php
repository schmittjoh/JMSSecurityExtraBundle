<?php

/*
 * Copyright 2010 Johannes M. Schmitt <schmittjoh@gmail.com>
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

namespace JMS\SecurityExtraBundle;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use JMS\SecurityExtraBundle\DependencyInjection\Compiler\AddAfterInvocationProvidersPass;
use JMS\SecurityExtraBundle\DependencyInjection\Compiler\CollectSecuredServicesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;


/**
 * Registers our custom compiler pass
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class JMSSecurityExtraBundle extends Bundle
{
    const VERSION = '1.1.0-DEV';

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $passConfig = $container->getCompilerPassConfig();
        $passConfig->addPass(new AddAfterInvocationProvidersPass());
        $passConfig->addPass(new CollectSecuredServicesPass());
    }
}