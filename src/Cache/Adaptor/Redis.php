<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Cache\Adaptor;

use ILIAS\Cache\Config;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class Redis extends BaseAdaptor implements Adaptor
{
    private ?\Redis $server = null;

    public function __construct(Config $config)
    {
        parent::__construct($config);
        if (!class_exists(\Redis::class)) {
            return;
        }
        if ($config->getNodes() === []) {
            return;
        }
        $this->initServer($config);
    }


    public function isAvailable(): bool
    {
        return class_exists(\Redis::class) && $this->server !== null;
    }

    public function has(string $container, string $key): bool
    {
        return $this->server->exists($this->buildKey($container, $key)) === true;
    }

    public function get(string $container, string $key): ?string
    {
        return $this->server->get($this->buildKey($container, $key)) ?: null;
    }

    public function set(string $container, string $key, string $value, int $ttl): void
    {
        $this->server->set($this->buildKey($container, $key), $value, $ttl);
    }

    public function delete(string $container, string $key): void
    {
        $this->server->del($this->buildKey($container, $key));
    }

    public function flushContainer(string $container): void
    {
        $prefix = $this->buildContainerPrefix($container);
        $this->server->del($this->server->keys($prefix . "*"));
    }

    public function flush(): void
    {
        $this->server->flushAll();
    }

    protected function initServer(Config $config): void
    {
        $this->server = new \Redis();
        $nodes = $config->getNodes();
        $this->server->connect($nodes[0]->getHost(), $nodes[0]->getPort());
    }
}
