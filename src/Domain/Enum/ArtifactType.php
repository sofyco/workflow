<?php declare(strict_types=1);

namespace Sofyco\Workflow\Domain\Enum;

enum ArtifactType: string
{
    case File = 'file';
    case Image = 'image';
    case Audio = 'audio';
    case Video = 'video';
}
