<?php declare(strict_types=1);

namespace Sofyco\Workflow\Domain\Model;

use DateTimeImmutable;
use Sofyco\Workflow\Domain\Enum\WorkflowStatus;

final class Workflow
{
    public function __construct(
        private string            $id,
        private string            $name,
        private WorkflowStatus    $status,
        private int               $latestVersion,
        private string            $userId,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getStatus(): WorkflowStatus
    {
        return $this->status;
    }

    public function setStatus(WorkflowStatus $status): void
    {
        $this->status = $status;
    }

    public function getLatestVersion(): int
    {
        return $this->latestVersion;
    }

    public function setLatestVersion(int $latestVersion): void
    {
        $this->latestVersion = $latestVersion;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
