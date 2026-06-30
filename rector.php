<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\AnnotationToAttributeRector;
use Rector\Php80\ValueObject\AnnotationToAttribute;

return RectorConfig::configure()
    ->withPaths([
        //__DIR__ . '/assets',
        //__DIR__ . '/config',
        //__DIR__ . '/public',
        __DIR__ . '/src',
    ])
    // uncomment to reach your current PHP version
    ->withPhpSets()
    ->withSets([
        \Rector\Doctrine\Set\DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
        \Rector\Symfony\Set\SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        \Rector\Symfony\Set\SensiolabsSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ])
    ->withRules([
        \Rector\Php74\Rector\FuncCall\MoneyFormatToNumberFormatRector::class
    ])
    ->withConfiguredRule(AnnotationToAttributeRector::class,
    [
        new AnnotationToAttribute(\Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity::class),
        new AnnotationToAttribute(\Ibericode\Vat\Bundle\Validator\Constraints\VatNumber::class),
    ])
    ->withTypeCoverageLevel(0);
