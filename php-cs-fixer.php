<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PhpCsFixer\Config;
use PhpCsFixerCustomFixers\Fixer\NoUselessCommentFixer;
use PhpCsFixerCustomFixers\Fixer\PhpdocParamTypeFixer;
use PhpCsFixerCustomFixers\Fixer\SingleSpaceAfterStatementFixer;
use PhpCsFixerCustomFixers\Fixer\SingleSpaceBeforeStatementFixer;
use PhpCsFixerCustomFixers\Fixers;

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__);

$header = <<<EOF
(c) shopware AG <info@shopware.com>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

return (new Config())
    ->registerCustomFixers(new Fixers())
    ->setRiskyAllowed(true)
    ->setRules(array(
        '@PSR2' => true,
        '@Symfony' => true,
        'header_comment' => array('header' => $header, 'separate' => 'bottom', 'comment_type' => 'PHPDoc'),
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_class_elements' => true,
        'ordered_imports' => true,
        'phpdoc_order' => true,
        'phpdoc_summary' => false,
        'blank_line_after_opening_tag' => false,
        'concat_space' => array('spacing' => 'one'),
        'array_syntax' => array('syntax' => 'long'),
        'yoda_style' => array('equal' => false, 'identical' => false, 'less_and_greater' => false),
        'general_phpdoc_annotation_remove' => array(
            'annotations' => array('copyright', 'category'),
        ),
        'phpdoc_var_annotation_correct_order' => true,
        'doctrine_annotation_indentation' => true,
        'doctrine_annotation_spaces' => true,
        'no_superfluous_phpdoc_tags' => true,
        'native_constant_invocation' => false,
        'php_unit_test_case_static_method_calls' => true,
        'operator_linebreak' => array('only_booleans' => true),
        'visibility_required' => false,
        NoUselessCommentFixer::name() => true,
        SingleSpaceAfterStatementFixer::name() => true,
        SingleSpaceBeforeStatementFixer::name() => true,
        PhpdocParamTypeFixer::name() => true,
    ))
    ->setFinder($finder)
;
