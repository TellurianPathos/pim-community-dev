<?php

declare(strict_types=1);

namespace Akeneo\Tool\Component\BatchQueue\Queue;

use Ramsey\Uuid\UuidInterface;

/**
 * @author    Nicolas Marniesse <nicolas.marniesse@akeneo.com>
 * @copyright 2021 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
interface JobExecutionMessageInterface
{
    public static function createJobExecutionMessage(
        int $jobExecutionId,
        array $options,
        ?string $tenantId = null
    ): JobExecutionMessageInterface;

    public static function createJobExecutionMessageFromNormalized(array $normalized): JobExecutionMessageInterface;

    public function getId(): UuidInterface;

    public function getJobExecutionId(): ?int;

    public function getCreateTime(): \DateTime;

    public function getUpdatedTime(): ?\DateTime;

    public function getOptions(): array;

    public function tenantId(): ?string;
}
