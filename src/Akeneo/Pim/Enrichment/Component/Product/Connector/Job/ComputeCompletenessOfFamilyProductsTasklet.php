<?php

declare(strict_types=1);

namespace Akeneo\Pim\Enrichment\Component\Product\Connector\Job;

use Akeneo\Pim\Enrichment\Bundle\Elasticsearch\IdentifierResult;
use Akeneo\Pim\Enrichment\Bundle\Storage\Sql\Product\SqlFindProductUuids;
use Akeneo\Pim\Enrichment\Component\Product\Completeness\CompletenessCalculator;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Pim\Enrichment\Component\Product\Query\Filter\Operators;
use Akeneo\Pim\Enrichment\Component\Product\Query\ProductQueryBuilderFactoryInterface;
use Akeneo\Pim\Enrichment\Component\Product\Query\SaveProductCompletenesses;
use Akeneo\Pim\Structure\Component\Model\FamilyInterface;
use Akeneo\Tool\Component\Batch\Item\InitializableInterface;
use Akeneo\Tool\Component\Batch\Item\ItemReaderInterface;
use Akeneo\Tool\Component\Batch\Item\TrackableTaskletInterface;
use Akeneo\Tool\Component\Batch\Job\JobRepositoryInterface;
use Akeneo\Tool\Component\Batch\Model\StepExecution;
use Akeneo\Tool\Component\Connector\Step\TaskletInterface;
use Akeneo\Tool\Component\StorageUtils\Cache\EntityManagerClearerInterface;
use Akeneo\Tool\Component\StorageUtils\Cursor\CursorInterface;
use Webmozart\Assert\Assert;

/**
 *  Computation of the completeness for all products belonging to a family that has been updated by mass action
 *
 * @copyright 2020 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * TODO refactor with Akeneo\Pim\Enrichment\Component\Product\Job\ComputeCompletenessOfProductsFamilyTasklet
 *            that work only for unitary update
 */
class ComputeCompletenessOfFamilyProductsTasklet implements TaskletInterface, TrackableTaskletInterface
{
    private const BATCH_SIZE = 100;

    private StepExecution $stepExecution;

    public function __construct(
        private ProductQueryBuilderFactoryInterface $productQueryBuilderFactory,
        private ItemReaderInterface $familyReader,
        private EntityManagerClearerInterface $cacheClearer,
        private JobRepositoryInterface $jobRepository,
        private CompletenessCalculator $completenessCalculator,
        private SaveProductCompletenesses $saveProductCompletenesses,
        private SqlFindProductUuids $sqlFindProductUuids
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    public function isTrackable(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        if ($this->familyReader instanceof InitializableInterface) {
            $this->familyReader->initialize();
        }

        $familyCodes = $this->extractFamilyCodes();
        if (empty($familyCodes)) {
            return;
        }

        $identifierResults = $this->getProductIdentifiersForFamilies($familyCodes);
        $this->stepExecution->setTotalItems($identifierResults->count());

        $productsToCompute = [];
        /** @var IdentifierResult $identifierResult */
        foreach ($identifierResults as $identifierResult) {
            Assert::same($identifierResult->getType(), ProductInterface::class);
            $productsToCompute[] = $identifierResult->getIdentifier();

            if (count($productsToCompute) >= self::BATCH_SIZE) {
                $this->computeCompleteness($productsToCompute);
                $this->cacheClearer->clear();
                $productsToCompute = [];
            }
        }

        if (count($productsToCompute) > 0) {
            $this->computeCompleteness($productsToCompute);
        }
    }

    private function computeCompleteness(array $productIdentifiers): void
    {
        $uuids = $this->sqlFindProductUuids->fromIdentifiers($productIdentifiers);
        $completenessCollections = $this->completenessCalculator->fromProductUuids(\array_values($uuids));
        $this->saveProductCompletenesses->saveAll($completenessCollections);

        $this->stepExecution->incrementProcessedItems(count($productIdentifiers));
        $this->stepExecution->incrementSummaryInfo('process', count($productIdentifiers));
        $this->jobRepository->updateStepExecution($this->stepExecution);
    }

    private function extractFamilyCodes()
    {
        $familyCodes = [];
        while (true) {
            $family = $this->familyReader->read();
            if (null === $family) {
                break;
            }

            Assert::isInstanceOf($family, FamilyInterface::class);

            $familyCodes[] = $family->getCode();
        }

        return $familyCodes;
    }

    private function getProductIdentifiersForFamilies(array $familyCodes): CursorInterface
    {
        $productQueryBuilder = $this->productQueryBuilderFactory->create();
        $productQueryBuilder->addFilter('family', Operators::IN_LIST, $familyCodes);

        return $productQueryBuilder->execute();
    }
}
