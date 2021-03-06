<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->exclude('var')
;

$rules = [
    '@DoctrineAnnotation' => true,
    '@PSR2' => true,
    '@Symfony' => true,
    'align_multiline_comment' => true,
    'array_indentation' => true,
    'array_syntax' => ['syntax' => 'short'],
    'cast_spaces' => false,
    'combine_consecutive_issets' => true,
    'combine_consecutive_unsets' => true,
    'compact_nullable_typehint' => true,
    'concat_space' => ['spacing' => 'one'],
    'dir_constant' => true,
    'ereg_to_preg' => true,
    'escape_implicit_backslashes' => true,
    'explicit_indirect_variable' => true,
    'explicit_string_variable' => true,
    'fopen_flag_order' => true,
    'fopen_flags' => true,
    'fully_qualified_strict_types' => true,
    'function_to_constant' => true,
    'general_phpdoc_annotation_remove' => ['annotations' => ['author']],
    // 'heredoc_indentation' => true,
    'heredoc_to_nowdoc' => true,
    'implode_call' => true,
    'is_null' => true,
    'linebreak_after_opening_tag' => true,
    'list_syntax' => ['syntax' => 'short'],
    'logical_operators' => true,
    'modernize_types_casting' => true,
    'native_function_invocation' => false, //['include' => ['@compiler_optimized'], 'scope' => 'namespaced', 'strict' => true],
    'no_alias_functions' => true,
    'no_binary_string' => true,
    'no_homoglyph_names' => true,
    'no_null_property_initialization' => true,
    'no_php4_constructor' => true,
    'no_superfluous_elseif' => true,
    'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
    'no_unset_cast' => true,
    // 'no_unset_on_property' => true,
    'no_useless_else' => true,
    'no_useless_return' => true,
    'php_unit_construct' => true,
    'php_unit_dedicate_assert' => true,
    'php_unit_dedicate_assert_internal_type' => true,
    'php_unit_expectation' => true,
    'php_unit_mock' => true,
    'php_unit_mock_short_will_return' => true,
    'php_unit_no_expectation_annotation' => true,
    // 'phpdoc_add_missing_param_annotation' => true,
    'phpdoc_align' => true,
    'phpdoc_summary' => false,
    'phpdoc_to_comment' => false,
    'phpdoc_trim_consecutive_blank_line_separation' => true,
    'phpdoc_var_without_name' => false, // это пришлось выключить чтобы сохранить $this аннотации в шаблонах
    'pow_to_exponentiation' => true,
    'psr_autoloading' => false,
    'random_api_migration' => true,
    'self_accessor' => true,
    'set_type_to_cast' => true,
    // 'simple_to_complex_string_variable' => true,
    'ternary_operator_spaces' => true,
    'ternary_to_null_coalescing' => true,
    'visibility_required' => true,
    'void_return' => true,
    'yoda_style' => false,
    'no_alternative_syntax' => false,
    'echo_tag_syntax' => false,
    'php_unit_method_casing' => false,
    'single_line_throw' => false,
    // не трогаем пробелы вокруг |, чтобы не корявить юнион-типы из-за бага https://github.com/FriendsOfPHP/PHP-CS-Fixer/issues/5495
    'binary_operator_spaces' => ['operators' => ['|' => null]],
];

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setUsingCache(true)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache')
    ->setFinder($finder);
