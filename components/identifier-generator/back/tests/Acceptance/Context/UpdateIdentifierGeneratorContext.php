<?php

declare(strict_types=1);

namespace Akeneo\Test\Pim\Automation\IdentifierGenerator\Acceptance\Context;

use Akeneo\Pim\Automation\IdentifierGenerator\Application\Exception\ViolationsException;
use Akeneo\Pim\Automation\IdentifierGenerator\Application\Update\ReorderGeneratorsHandler;
use Akeneo\Pim\Automation\IdentifierGenerator\Application\Update\UpdateGeneratorCommand;
use Akeneo\Pim\Automation\IdentifierGenerator\Application\Update\UpdateGeneratorHandler;
use Akeneo\Pim\Automation\IdentifierGenerator\Domain\Repository\IdentifierGeneratorRepository;
use Behat\Behat\Context\Context;

/**
 * @copyright 2022 Akeneo SAS (https://www.akeneo.com)
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
final class UpdateIdentifierGeneratorContext implements Context
{
    public const DEFAULT_IDENTIFIER_GENERATOR_CODE = 'default';

    public function __construct(
        private readonly ViolationsContext $violationsContext,
        private readonly UpdateGeneratorHandler $updateGeneratorHandler,
        private readonly IdentifierGeneratorRepository $generatorRepository,
        private readonly ReorderGeneratorsHandler $reorderGeneratorsHandler,
    ) {
    }

    /**
     * @When I update the identifier generator
     */
    public function iUpdateTheIdentifierGenerator(): void
    {
        $this->tryToUpdateGenerator();
    }

    /**
     * @When I try to update an unknown identifier generator
     */
    public function iTryToUpdateAnUnknownIdentifierGenerator(): void
    {
        $this->tryToUpdateGenerator(code: 'unknown');
    }

    /**
     * @When I try to update an identifier generator with an unknown property
     */
    public function iTryToUpdateAnIdentifierGeneratorWithAnUnknownProperty(): void
    {
        $this->tryToUpdateGenerator(structure: [['type' => 'unknown', 'string' => 'abcdef']]);
    }

    /**
     * @When /^I try to update an identifier generator with target '(?P<target>[^']*)'$/
     */
    public function iTryToUpdateAnIdentifierGeneratorWithTarget(string $target): void
    {
        $this->tryToUpdateGenerator(target: $target);
    }

    /**
     * @When I try to update an identifier generator with blank structure
     */
    public function iTryToUpdateAnIdentifierGeneratorWithBlankStructure(): void
    {
        $this->tryToUpdateGenerator(structure: []);
    }

    /**
     * @When I update an identifier generator without label
     */
    public function iUpdateAnIdentifierGeneratorWithoutLabel(): void
    {
        $this->tryToUpdateGenerator(labels: []);
    }

    /**
     * @When /^I try to update an identifier generator with '(?P<locale>[^']*)' label '(?P<label>[^']*)'$/
     */
    public function iTryToUpdateAnIdentifierGeneratorWithLabel(string $locale, string $label): void
    {
        $this->tryToUpdateGenerator(labels: [$locale => $label]);
    }

    /**
     * @When I try to update an identifier generator with too many properties in structure
     */
    public function iTryToUpdateAnIdentifierGeneratorWithTooManyPropertiesInStructure(): void
    {
        $this->tryToUpdateGenerator(structure: [
            ['type' => 'free_text', 'string' => 'abcdef1'],
            ['type' => 'free_text', 'string' => 'abcdef2'],
            ['type' => 'free_text', 'string' => 'abcdef3'],
            ['type' => 'free_text', 'string' => 'abcdef4'],
            ['type' => 'free_text', 'string' => 'abcdef5'],
            ['type' => 'free_text', 'string' => 'abcdef6'],
            ['type' => 'free_text', 'string' => 'abcdef7'],
            ['type' => 'free_text', 'string' => 'abcdef8'],
            ['type' => 'free_text', 'string' => 'abcdef9'],
            ['type' => 'free_text', 'string' => 'abcdef10'],
            ['type' => 'free_text', 'string' => 'abcdef11'],
            ['type' => 'free_text', 'string' => 'abcdef12'],
            ['type' => 'free_text', 'string' => 'abcdef13'],
            ['type' => 'free_text', 'string' => 'abcdef14'],
            ['type' => 'free_text', 'string' => 'abcdef15'],
            ['type' => 'free_text', 'string' => 'abcdef16'],
            ['type' => 'free_text', 'string' => 'abcdef17'],
            ['type' => 'free_text', 'string' => 'abcdef18'],
            ['type' => 'free_text', 'string' => 'abcdef19'],
            ['type' => 'free_text', 'string' => 'abcdef20'],
            ['type' => 'free_text', 'string' => 'abcdef21'],
        ]);
    }

    /**
     * @When I try to update an identifier generator with multiple auto number in structure
     */
    public function iTryToUpdateAnIdentifierGeneratorWithMultipleAutoNumberInStructure(): void
    {
        $this->tryToUpdateGenerator(structure: [
            ['type' => 'auto_number', 'numberMin' => 2, 'digitsMin' => 3],
            ['type' => 'auto_number', 'numberMin' => 1, 'digitsMin' => 4],
        ]);
    }

    /**
     * @When I try to update an identifier generator with autoNumber number min negative
     */
    public function iTryToUpdateAnIdentifierGeneratorWithAutonumberNumberMinNegative(): void
    {
        $this->tryToUpdateGenerator(structure: [['type' => 'auto_number', 'numberMin' => -2, 'digitsMin' => 3]]);
    }

    /**
     * @When I try to update an identifier generator with autoNumber digits min negative
     */
    public function iTryToUpdateAnIdentifierGeneratorWithAutonumberDigitsMinNegative(): void
    {
        $this->tryToUpdateGenerator(structure: [['type' => 'auto_number', 'digitsMin' => -2, 'numberMin' => 4]]);
    }

    /**
     * @When I try to update an identifier generator with autoNumber digits min too big
     */
    public function iTryToUpdateAnIdentifierGeneratorWithAutonumberDigitsMinTooBig(): void
    {
        $this->tryToUpdateGenerator(structure: [['type' => 'auto_number', 'digitsMin' => 22, 'numberMin' => 4]]);
    }

    /**
     * @When /^I try to update an identifier generator with delimiter '(?P<delimiter>[^']*)'$/
     */
    public function iTryToUpdateAnIdentifierGeneratorWithDelimiter(string $delimiter): void
    {
        $this->tryToUpdateGenerator(delimiter: $delimiter);
    }

    /**
     * @When /^I (?:try to )?update an identifier generator with text transformation (?P<textTransformation>.+)$/
     */
    public function iTryToUpdateAnIdentifierGeneratorWithTextTransformation(string $textTransformation): void
    {
        $this->tryToUpdateGenerator(textTransformation: $textTransformation);
    }

    /**
     * @When I update the identifier generator with delimiter null
     */
    public function iUpdateTheIdentifierGeneratorWithDelimiterNull(): void
    {
        try {
            ($this->updateGeneratorHandler)(new UpdateGeneratorCommand(
                self::DEFAULT_IDENTIFIER_GENERATOR_CODE,
                [],
                [['type' => 'free_text', 'string' => 'abcdef']],
                ['fr_FR' => 'Générateur'],
                'sku',
                null,
                'no',
            ));
        } catch (ViolationsException $exception) {
            $this->violationsContext->setViolationsException($exception);
        }
    }

    /**
     * @When I try to update an identifier generator with unknown condition type
     */
    public function iTryToUpdateAnIdentifierGeneratorWithUnknownConditionType(): void
    {
        $this->tryToUpdateGenerator(conditions: [
            ['type' => 'unknown', 'value' => true],
        ]);
    }

    /**
     * @When /^I try to update an identifier generator with (\d+) conditions$/
     */
    public function iTryToUpdateAnIdentifierGeneratorWithConditions(string $count): void
    {
        $this->tryToUpdateGenerator(conditions: \array_fill(0, \intval($count), $this->getValidCondition('simple_select')));
    }

    private function tryToUpdateGenerator(
        ?string $code = null,
        ?array $structure = null,
        ?array $conditions = null,
        ?array $labels = null,
        ?string $target = null,
        ?string $delimiter = null,
        ?string $textTransformation = null,
    ): void {
        try {
            ($this->updateGeneratorHandler)(new UpdateGeneratorCommand(
                $code ?? self::DEFAULT_IDENTIFIER_GENERATOR_CODE,
                $conditions ?? [
                    $this->getValidCondition('enabled'),
                    $this->getValidCondition('family', 'EMPTY'),
                    $this->getValidCondition('simple_select'),
                    $this->getValidCondition('multi_select'),
                ],
                $structure ?? [['type' => 'free_text', 'string' => self::DEFAULT_IDENTIFIER_GENERATOR_CODE]],
                $labels ?? ['fr_FR' => 'Générateur'],
                $target ?? 'sku',
                $delimiter ?? 'updatedGenerator',
                $textTransformation ?? 'no',
            ));
        } catch (ViolationsException $violations) {
            $this->violationsContext->setViolationsException($violations);
        }
    }

    private function getValidCondition(string $type, ?string $operator = null): array
    {
        switch ($type) {
            case 'enabled':
                return [
                    'type' => 'enabled',
                    'value' => true,
                ];
            case 'family':
                $familyCondition = [
                    'type' => 'family',
                    'operator' => $operator ?? 'IN',
                ];
                if ('EMPTY' !== $operator) {
                    $familyCondition['value'] = ['tshirt'];
                }

                return $familyCondition;
            case 'simple_select':
                return [
                    'type' => 'simple_select',
                    'operator' => $operator ?? 'IN',
                    'attributeCode' => 'color',
                    'value' => ['green'],
                ];
            case 'multi_select':
                return [
                    'type' => 'multi_select',
                    'operator' => $operator ?? 'IN',
                    'attributeCode' => 'a_multi_select',
                    'value' => ['option_a', 'option_b'],
                ];
        }

        throw new \InvalidArgumentException('Unknown type ' . $type . ' for getValidCondition');
    }

    /**
     * @return string[]
     */
    private function splitList(string $codesList): array
    {
        $codesWithQuotes = \preg_split('/(, )|( and )/', $codesList);

        return \array_map(
            static fn (string $codeWithQuotes): string => \substr($codeWithQuotes, 1, -1),
            $codesWithQuotes
        );
    }
}
