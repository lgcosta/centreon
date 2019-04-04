<?php
/**
 * Copyright 2019 Centreon
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

namespace Centreon\Tests\Resource\Traits;

use Pimple\Container;
use Centreon\ServiceProvider;

/**
 * Container provider for Centreon\Infrastructure\Service\CentreonDBManagerService
 *
 * @author Centreon
 * @version 1.0.0
 * @package centreon
 * @subpackage test
 */
trait CentreonDbManagerTrait
{

    /**
     * Set up DB manager service in container
     *
     * <code>
     * public function setUp()
     * {
     *     $container = new \Pimple\Container;
     *     $this->setUpCentreonDbManager($container);
     * }
     * </code>
     *
     * @param \Pimple\Container $container
     */
    public function setUpCentreonDbManager(Container $container)
    {

        $container[ServiceProvider::CENTREON_DB_MANAGER] = new class {
            
        };
    }
}
