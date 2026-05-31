<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Id;

interface IdGeneratorInterface
{
    public function generate(): string;
}
