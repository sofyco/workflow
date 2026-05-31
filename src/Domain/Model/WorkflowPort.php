<?php declare(strict_types=1);

namespace Sofyco\Workflow\Domain\Model;

use Sofyco\Workflow\Domain\Enum\ArtifactType;

final readonly class WorkflowPort
{
    /**
     * @param list<string> $allowedMimeTypes
     * @param array<string, mixed> $constraints
     */
    public function __construct(
        public string       $name,
        public ArtifactType $type,
        public bool         $required = true,
        public bool         $multiple = false,
        public array        $allowedMimeTypes = [],
        public array        $constraints = [],
    )
    {
    }
}
